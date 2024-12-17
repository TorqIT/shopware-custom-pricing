<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

class ExternalPriceApiService implements CustomPriceProvider
{
    public function __construct(
        private readonly CascadeApiWrapper $apiWrapper
    ) {}

    public function getCustomPrice(string $customerId, string $customerGroupId, array $productIds): ?array
    {
        return $this->apiWrapper->getPrices(
            'defaultCompanyId',
            $customerId,
            $productIds,
            'defaultSourceLocation',
            'defaultSalesLocation'
        );
    }
}
