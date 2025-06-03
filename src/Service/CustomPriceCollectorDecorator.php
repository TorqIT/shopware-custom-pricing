<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Torq\Shopware\CustomPricing\Constants\ConfigConstants;
use Shopware\Commercial\CustomPricing\Domain\CustomPriceCollector;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Commercial\CustomPricing\Subscriber\ProductSubscriber;
use Shopware\Commercial\CustomPricing\Entity\Field\CustomPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\Price\CustomPriceCollection;
use Shopware\Commercial\CustomPricing\Entity\FieldSerializer\CustomPriceFieldSerializer;
use Torq\Shopware\CustomPricing\Entity\TorqCustomPriceCustomData\TorqCustomPriceCustomDataDefinition;

class CustomPriceCollectorDecorator extends CustomPriceCollector
{
    private CustomPriceProvider $customPriceProvider;

    public const PARAMETER_SEPERATOR = "~~~";

    public function __construct(
        private CustomPriceCollector $decorated, 
        private readonly Connection $connection,
        private readonly EntityRepository $customPriceRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly CustomPriceFieldSerializer $priceFieldSerializer,
        iterable $customPriceProviders,
    )
    {
        $i = $customPriceProviders->getIterator();

        $this->customPriceProvider = $i->current();
    }

    public function collect(array $customer, array $products ): ?array
    {
        $customerIdStr = $customer[0];

        $parameters = [];
        //A listener on CustomPriceResolveCustomersEvent can be used to add extra params that will be added to the parameters array for use by the external price api
        // c0308b8c0927424ca4f3c4040b8affe0~~~special_param_name=thevalue~~~anotherparam=anothervalue
        $params = explode(self::PARAMETER_SEPERATOR, $customerIdStr);
        $customerId = $params[0];
        if(count($params) > 1){    
            for($i=1; $i < count($params); $i++){
                $param = explode("=",$params[$i]);
                if(count($param) > 1){    
                    $parameters[$param[0]] = $param[1];
                }
            }
        }

        $cacheDuration = $this->systemConfigService->get(ConfigConstants::CACHE_DURATION) ?? 'PT5M';

        //only get prices where cache not expired
        $unexpired = ($cacheDuration === 'PT0M' || CustomPriceApiDirector::getForceApiCall()) ? [] : array_column($this->getUnexpiredProducts($customerId, $products, $parameters), "productId");  

        //use base decorated for unexpired prices
        $prices = ($cacheDuration === 'PT0M' || CustomPriceApiDirector::getForceApiCall()) ? [] : $this->collectCustomPrices([$customerId], $products, $parameters);

        //for expired/non-existing prices, get custom prices
        $expired = array_diff($products, $unexpired); 

        $callApi = CustomPriceApiDirector::getSupressApiCall() ? false:($cacheDuration === 'PT0M' || !CustomPriceApiDirector::getSupressApiCall());
        $customPrices = 
            count($expired) > 0 && $callApi
            ? 
            $this->customPriceProvider->getCustomPrices($customerId, $expired, $parameters) 
            : 
            [];

        //save custom prices for use later
        if($cacheDuration !== 'PT0M' && !empty($customPrices))
        {
            $this->saveCustomPrices($customerId, $customPrices, $parameters);
        }

        //let the custom price provider decide if it wants to process stock as well
        $this->customPriceProvider->processStock($customerId, $products, $parameters);

        $merged = array_merge($prices ?? [], $customPrices ?? []);
        return $merged;
    }

    private function getUnexpiredProducts(string $customerId, array $products, $parameters): ?array
    {        
        $customer = $this->connection->fetchAssociative(
            'SELECT LOWER(HEX(id)) as id, LOWER(HEX(customer_group_id)) as customer_group_id FROM customer WHERE id = :customerId',
            ['customerId' => Uuid::fromHexToBytes($customerId)]
        );

        if (!$customer || !\is_string($customer['id'])) {
            return [];
        }

        $customerId = Uuid::fromHexToBytes($customer['id']);
        
        $customerGroupId = \is_string($customer['customer_group_id']) && $customer['customer_group_id']
            ? Uuid::fromHexToBytes($customer['customer_group_id'])
            : null;

        $cacheDuration = $this->systemConfigService->get(ConfigConstants::CACHE_DURATION) ?? 'PT5M';

        /** @var array<int, array{productId: string, customerId: ?string, customerGroupId: ?string, price: ?string}> $result */
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('LOWER(HEX(product_id)) AS productId')
            ->from(CustomPriceDefinition::ENTITY_NAME, 'customPrice')
            ->where('product_id IN (:productIds)')
            ->andWhere('(customer_id = :customerId OR customer_group_id = :groupId)')
            ->andWhere('((customPrice.created_at > :updatedAt AND customPrice.updated_at IS NULL) OR (customPrice.updated_at > :updatedAt))')
            ->setParameter('productIds', Uuid::fromHexToBytesList($products), ArrayParameterType::BINARY)
            ->setParameter('customerId', $customerId)
            ->setParameter('groupId', $customerGroupId)
            ->setParameter('updatedAt', (new \DateTime())->sub(new \DateInterval($cacheDuration))->format("Y-m-d H:i:s"));

        //any parameters are used to query the custom_fields data
        if(!empty($parameters)){
            $queryBuilder->leftJoin('customPrice', TorqCustomPriceCustomDataDefinition::ENTITY_NAME,'customPriceData','customPrice.id = customPriceData.custom_price_id');
            foreach($parameters as $key => $value){
                $queryBuilder->andWhere("json_value(customPriceData.custom_fields, '$." . $key . "')  = :" . $key )
                    ->setParameter($key, $value);
            }
        }

        return  $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    private function collectCustomPrices(array $customer, array $products, $parameters): ?array
    {        
        if(empty($parameters)){
            $this->decorated->collect($customer, $products);
        }

        $customPrices = [];
        foreach ($customer as $customerId) {
            $customPrices = $this->getCustomPrices($products, $customerId, $parameters);

            if (\count($customPrices) > 0) {
                break;
            }
        }

        if (\count($customPrices) === 0) {
            return null;
        }

        $prices = [];
        foreach ($customPrices as $productId => $customPrice) {
            $prices[$productId] = $this->createPriceCollections($productId, $customPrice);
        }

        return $prices;
    }

    private function saveCustomPrices($customer, $customPrices, $parameters){
        $payload = [];

        $values = implode("|",$parameters);
        foreach($customPrices as $productId => $customPrice){
            
            $customPriceEntry = [
                'id' => md5($customer . $productId),
                'productId' => $productId,
                'customerId' => $customer,
                'price' => []
            ];

            if(!empty($parameters)){
                 $customPriceEntry['torqCustomData'] = [
                    'id' => md5($customer . $productId . $values),
                    'customFields' => [
                        ...$parameters  //save all the extra params as custom fields
                    ],
                ];
            }

            foreach($customPrice['prices'] as $price){

                $outerPrice = [];
                $outerPrice['quantityStart'] = $price->getQuantityStart();
                $outerPrice['quantityEnd'] = $price->getQuantityEnd();
                $outerPrice['price'] = [];

                foreach($price->getPrice() as $p){
                    $innerPrice = [
                        'currencyId' => $p->getCurrencyId(),
                        'gross' => $p->getGross(),
                        'net' => $p->getNet(),
                        'linked' => $p->getLinked()
                    ];

                    $outerPrice['price'][] = $innerPrice;
                }
                $customPriceEntry['price'][] = $outerPrice;

                if(!empty($parameters)){
                    $customPriceEntry['torqCustomData']['price'][] = $outerPrice;
                }
            }

            $payload[] =  $customPriceEntry;
            
        }

        $b = $this->customPriceRepository->upsert($payload, \Shopware\Core\Framework\Context::createDefaultContext());

        return $b;
    }



        /**
     * @param array<CustomPriceCollection>|null $customPrice
     *
     * @return array{price: PriceCollection|null, prices: ProductPriceCollection, cheapestPrice: null}
     */
    private function createPriceCollections(string $productId, ?array $customPrice): ?array
    {
        if ($customPrice === null) {
            return null;
        }

        $productPriceCollection = new ProductPriceCollection();

        foreach ($customPrice as $price) {
            if ($price->first() === null) {
                continue;
            }

            $start = $price->first()->getQuantityStart();
            $end = $price->first()->getQuantityEnd();

            $productPrice = new ProductPriceEntity();
            $productPrice->setId(Uuid::randomHex());
            $productPrice->setRuleId(ProductSubscriber::CUSTOMER_PRICE_RULE);
            $productPrice->setPrice($price);
            $productPrice->setProductId($productId);
            $productPrice->setQuantityStart($start);
            $productPrice->setQuantityEnd($end);

            $productPriceCollection->add($productPrice);
        }
        $productPriceCollection->sortByQuantity();

        return [
            'price' => $productPriceCollection->first() !== null ? $productPriceCollection->first()->getPrice() : null,
            'prices' => $productPriceCollection,
            'cheapestPrice' => null,
        ];
    }

    /**
     * @param array<string> $products
     *
     * @return array<string, array<CustomPriceCollection>|null>
     */
    private function getCustomPrices(array $products, string $customerId, $parameters): array
    {
        $customer = $this->connection->fetchAssociative(
            'SELECT LOWER(HEX(id)) as id, LOWER(HEX(customer_group_id)) as customer_group_id FROM customer WHERE id = :customerId',
            ['customerId' => Uuid::fromHexToBytes($customerId)]
        );

        if (!$customer || !\is_string($customer['id'])) {
            return [];
        }

        $tmpField = new CustomPriceField('price', 'price');

        $customerId = Uuid::fromHexToBytes($customer['id']);
        $customerGroupId = \is_string($customer['customer_group_id']) && $customer['customer_group_id']
            ? Uuid::fromHexToBytes($customer['customer_group_id'])
            : null;

        /** @var array<int, array{productId: string, customerId: ?string, customerGroupId: ?string, price: ?string}> $result */
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('LOWER(HEX(product_id)) AS productId', 'LOWER(HEX(customer_id)) AS customerId', 'LOWER(HEX(customer_group_id)) AS customerGroupId', (!empty($parameters) ? 'customPriceData.price':'customPrice.price'))
            ->from(CustomPriceDefinition::ENTITY_NAME, 'customPrice')
            ->where('product_id IN (:productIds)')
            ->andWhere('(customer_id = :customerId OR customer_group_id = :groupId)')
            ->setParameter('productIds', Uuid::fromHexToBytesList($products), ArrayParameterType::BINARY)
            ->setParameter('customerId', $customerId)
            ->setParameter('groupId', $customerGroupId);

            //any parameters are used to query the custom_fields data
        if(!empty($parameters)){
            $queryBuilder->leftJoin('customPrice', TorqCustomPriceCustomDataDefinition::ENTITY_NAME,'customPriceData','customPrice.id = customPriceData.custom_price_id');
            foreach($parameters as $key => $value){
                $queryBuilder->andWhere("json_value(customPriceData.custom_fields, '$." . $key . "')  = :" . $key )
                    ->setParameter($key, $value);
            }
        }

        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        \usort($result, static fn (array $a, array $b) => $a['customerGroupId'] === null ? 1 : -1);

        $result = FetchModeHelper::groupUnique($result);
        /** @var array<string, array{customerId: string, customerGroupId: string, price: string}> $result */

        return array_map(
            fn (array $price) => $this->priceFieldSerializer->decode($tmpField, $price['price']),
            $result
        );
    }
}
