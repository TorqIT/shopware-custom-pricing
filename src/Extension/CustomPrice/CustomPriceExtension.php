<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Extension\CustomPrice;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceDefinition;
use Torq\Shopware\CustomPricing\Entity\TorqCustomPriceCustomData\TorqCustomPriceCustomDataDefinition;

class CustomPriceExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'torqCustomData', // property name on CustomPriceEntity
                'id',               // storageName / local field in CustomPriceDefinition (source)
                'custom_price_id',  // referenceStorageName / remote field in TorqCustomPriceCustomDataDefinition (reference)
                TorqCustomPriceCustomDataDefinition::class, // Reference definition
                true                // autoLoad
            ))->addFlags(new ApiAware())
        );
    }

    public function getDefinitionClass(): string
    {
        return CustomPriceDefinition::class;
    }
}