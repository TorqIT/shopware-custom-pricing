<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Shopware\Commercial\CustomPricing\Entity\CustomPrice\Price\CustomPriceCollection;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\Price\CustomPrice;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Commercial\CustomPricing\Subscriber\ProductSubscriber;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Uuid\Uuid;

abstract class CustomPriceProvider
{
    public function __construct(private ExternalIdMapper $externalIdMapper)
    {

    }

    public function getCustomPrices(array $customerId, array $productIds)
    {
        $mappedProductIds = $this->externalIdMapper->mapProductIds($productIds);
        $mappedCustomerId = $this->externalIdMapper->mapCustomerId($customerId);

        $externalPrices = $this->getExternalPrices($mappedCustomerId, $mappedProductIds);

        $prices = [];
        foreach ($externalPrices as $productId => $customPrice) {
            $prices[$productId] = $this->createPriceCollections($productId, $customPrice);
        }

        return $prices;
    }

    /**
     * @param CustomPriceCollection|null $customPrice
     *
     * @return array{price: PriceCollection|null, prices: ProductPriceCollection, cheapestPrice: null}
     */
    private function createPriceCollections(string $productId, CustomPriceCollection $customPrice): ?array
    {
        if ($customPrice === null) {
            return null;
        }

        $productPriceCollection = new ProductPriceCollection();

        /** @var CustomPrice $price */
        foreach ($customPrice as $price) {
            if ($price === null) {
                continue;
            }

            $start = $price->getQuantityStart();
            $end = $price->getQuantityEnd();

            $productPrice = new ProductPriceEntity();
            $productPrice->setId(Uuid::randomHex());
            $productPrice->setRuleId(ProductSubscriber::CUSTOMER_PRICE_RULE);
            $productPrice->setPrice($customPrice);
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
     * @return array<string, CustomPriceCollection|null>
     */
    protected abstract function getExternalPrices(array $customerIdMapped, array $productIdsMapped): array;
}
