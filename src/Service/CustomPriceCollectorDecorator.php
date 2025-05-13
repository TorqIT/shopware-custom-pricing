<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Commercial\CustomPricing\Domain\CustomPriceCollector;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Torq\Shopware\CustomPricing\Constants\ConfigConstants;

class CustomPriceCollectorDecorator extends CustomPriceCollector
{
    private CustomPriceProvider $customPriceProvider;

    public const PARAMETER_SEPERATOR = "~~~";

    public function __construct(
        private CustomPriceCollector $decorated, 
        private readonly Connection $connection,
        private readonly EntityRepository $customPriceRepository,
        private readonly SystemConfigService $systemConfigService,
        iterable $customPriceProviders
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
        $unexpired = ($cacheDuration === 'PT0M' || CustomPriceApiDirector::getForceApiCall()) ? [] : array_column($this->getUnexpiredProducts($customerId, $products), "productId");  

        //use base decorated for unexpired prices
        $prices = ($cacheDuration === 'PT0M' || CustomPriceApiDirector::getForceApiCall()) ? [] : $this->decorated->collect([$customerId], $unexpired);

        //for expired/non-existing prices, get custom prices
        $expired = array_diff($products, $unexpired); 

        $customPrices = 
            count($expired) > 0 && ($cacheDuration === 'PT0M' || !CustomPriceApiDirector::getSupressApiCall())
            ? 
            $this->customPriceProvider->getCustomPrices($customerId, $expired, $parameters) 
            : 
            [];

        //save custom prices for use later
        if($cacheDuration !== 'PT0M' && !empty($customPrices))
        {
            $this->saveCustomPrices($customerId, $customPrices);
        }

        $merged = array_merge($prices ?? [], $customPrices ?? []);
        return $merged;
    }

    private function getUnexpiredProducts(string $customerId, array $products): ?array
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
        return $this->connection->createQueryBuilder()
            ->select('LOWER(HEX(product_id)) AS productId')
            ->from(CustomPriceDefinition::ENTITY_NAME)
            ->where('product_id IN (:productIds)')
            ->andWhere('(customer_id = :customerId OR customer_group_id = :groupId)')
            ->andWhere('((created_at > :updatedAt AND updated_at IS NULL) OR (updated_at > :updatedAt))')
            ->setParameter('productIds', Uuid::fromHexToBytesList($products), ArrayParameterType::BINARY)
            ->setParameter('customerId', $customerId)
            ->setParameter('groupId', $customerGroupId)
            ->setParameter('updatedAt', (new \DateTime())->sub(new \DateInterval($cacheDuration))->format("Y-m-d H:i:s"))
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function saveCustomPrices($customer, $customPrices){
        $payload = [];

        foreach($customPrices as $productId => $customPrice){
            
            $customPriceEntry = [
                'id' => md5($customer . $productId),
                'productId' => $productId,
                'customerId' => $customer,
                'price' => []
            ];

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
            }

            $payload[] =  $customPriceEntry;
            
        }

        $b = $this->customPriceRepository->upsert($payload, \Shopware\Core\Framework\Context::createDefaultContext());

        return $b;
    }
}
