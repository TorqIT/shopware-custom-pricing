<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Torq\Shopware\CustomPricing\Service\CustomPriceProvider;

class ExternalPriceApiService implements CustomPriceProvider 
{
    protected string $apiUrl;
    protected string $apiUsername;
    protected string $apiPassword;

    public function __construct(string $apiUrl = '', string $apiUsername = '', string $apiPassword = '')
    {
        $this->apiUrl = $apiUrl;
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
    }

    public function getCustomPrice(string $customerId, string $customerGroupId, array $productIds): ?array
    {
        // Implement generic API calling logic here
        return null;
    }
}