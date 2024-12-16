<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

interface CustomPriceProvider
{
    public function getCustomPrice(string $customerId, string $customerGroupId, array $productIds): ?array;
    
}
