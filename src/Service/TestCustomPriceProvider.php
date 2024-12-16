<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

class TestCustomPriceProvider implements CustomPriceProvider
{
    public function getCustomPrice(string $customerId, string $customerGroupId, array $productIds): ?array
    {
        $prices = [];

        foreach($productIds as $product){
            $prices[$product] = 999.99;
        }

        return $prices;
    }
}
