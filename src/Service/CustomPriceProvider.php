<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Torq\Shopware\CustomPricing\Model\CustomPrice;

class CustomPriceProvider
{
    private AbstractCustomPriceApiService $apiService;

    public function __construct(AbstractCustomPriceApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function getCustomPrice(string $productId): ?CustomPrice
    {
        return $this->apiService->fetchPrice($productId);
    }
}
