<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Commercial\CustomPricing\Domain\CustomPriceCollector;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Commercial\CustomPricing\Subscriber\ProductSubscriber;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Commercial\CustomPricing\Domain\CustomPriceUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class CustomPriceCollectorDecorator extends CustomPriceCollector
{
    public function __construct(
        private CustomPriceCollector $decorated, 
        private readonly CustomPriceProvider $customPriceProvider,
        private readonly Connection $connection,
        private readonly EntityRepository $customPriceRepository
    )
    {
        
    }

    public function collect(array $customer, array $products): ?array
    {
        //only get prices where cache not expired
        $customerId = $customer[0];
        $unexpired = array_column($this->getUnexpiredProducts($customerId, $products), "productId");  

        $prices =  $this->decorated->collect($customer, $unexpired);

        $expired = array_diff($products, $unexpired); 

        $customPrices = count($expired) > 0 ? $this->customPriceProvider->getCustomPrices($customer, $expired) : [];

        if(!empty($customPrices))
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
            ->setParameter('updatedAt', (new \DateTime())->sub(new \DateInterval('PT5M'))->format("Y-m-d H:i:s"))
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
