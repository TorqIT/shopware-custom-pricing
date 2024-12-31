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

        $customPrices = $this->customPriceProvider->getCustomPrices($customer, $products);

        return $customPrices;
    }

}
