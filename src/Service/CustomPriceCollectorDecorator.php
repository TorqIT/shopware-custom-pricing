<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Shopware\Commercial\CustomPricing\Domain\CustomPriceCollector;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Commercial\CustomPricing\Subscriber\ProductSubscriber;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;

class CustomPriceCollectorDecorator extends CustomPriceCollector
{
    public function __construct(private CustomPriceCollector $decorated, private CustomPriceProvider $customPriceProvider)
    {
        
    }

    public function collect(array $customer, array $products): ?array
    {
        
        $prices =  $this->decorated->collect($customer, $products);

        $newPrices = [];
        foreach($products as $product){
            $newPrices[$product] = $this->getCustomPrice($product);
        }

        return $newPrices;
    }

    private function getCustomPrice(string $productId){
        

        $productPriceCollection = new ProductPriceCollection();
        $priceCollection = new PriceCollection();

        $priceValue = $this->customPriceProvider->getCustomPrice("customerId", "customerGroupId", [$productId]);

        $priceCollection->add(new Price("b7d2554b0ce847cd82f3ac9bd1c0dfca", $priceValue[$productId], $priceValue[$productId], false, null, null));

        $start = 1;
        $end = 100;

        $productPrice = new ProductPriceEntity();
        $productPrice->setId(Uuid::randomHex());
        $productPrice->setRuleId(ProductSubscriber::CUSTOMER_PRICE_RULE);
        $productPrice->setPrice($priceCollection);
        $productPrice->setProductId($productId);
        $productPrice->setQuantityStart($start);
        $productPrice->setQuantityEnd($end);

        $productPriceCollection->add($productPrice);
        
        $productPriceCollection->sortByQuantity();

        return [
            'price' => $productPriceCollection->first() !== null ? $productPriceCollection->first()->getPrice() : null,
            'prices' => $productPriceCollection,
            'cheapestPrice' => null,
        ];
    }
    
}
