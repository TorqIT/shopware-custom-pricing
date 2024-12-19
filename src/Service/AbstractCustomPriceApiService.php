<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Torq\Shopware\CustomPricing\Model\CustomPrice;

interface AbstractCustomPriceApiService
{
    public function fetchPrice(string $productId): ?CustomPrice;
}
