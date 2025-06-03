<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Entity\TorqCustomPriceCustomData;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<TorqCustomPriceCustomDataEntity>
 */
class TorqCustomPriceCustomDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TorqCustomPriceCustomDataEntity::class;
    }
}