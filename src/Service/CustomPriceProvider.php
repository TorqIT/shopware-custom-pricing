<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Shopware\Commercial\CustomPricing\Entity\CustomPrice\Price\CustomPriceCollection;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\Price\CustomPrice;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Commercial\CustomPricing\Subscriber\ProductSubscriber;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Uuid\Uuid;

abstract class CustomPriceProvider
{
    public function __construct(private ExternalIdMapper $externalIdMapper)
    {

    }

    public function getCustomPrices(string $customerId, array $productIds)
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
     * @param CustomPriceCollection|null $customPriceCollection
     *
     * @return array{price: PriceCollection|null, prices: ProductPriceCollection, cheapestPrice: null}
     */
    private function createPriceCollections(string $productId, CustomPriceCollection $customPriceCollection): ?array
    {
        if ($customPriceCollection === null || $customPriceCollection->first() === null) {
            return null;
        }

        //this will need refactored if we ever support tiered pricing
        $productPriceCollection = new ProductPriceCollection();
        $productPrice = new ProductPriceEntity();
        $productPrice->setId(Uuid::randomHex());
        $productPrice->setRuleId(ProductSubscriber::CUSTOMER_PRICE_RULE);
        $productPrice->setQuantityStart(1);
        $productPrice->setQuantityEnd(null);
        $productPrice->setProductId($productId);

        $priceCollection = new PriceCollection();

        /** @var CustomPrice $price */
        foreach ($customPriceCollection as $price) {
            
            $newPrice = new Price(
                $price->getCurrencyId(),
                $price->getNet(),
                $price->getGross(),
                $price->getLinked()
            );
            $priceCollection->add($newPrice);
        }

        $productPrice->setPrice($priceCollection);
        $productPriceCollection->add($productPrice);

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
