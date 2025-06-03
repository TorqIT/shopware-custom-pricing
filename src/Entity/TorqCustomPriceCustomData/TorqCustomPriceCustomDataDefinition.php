<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Entity\TorqCustomPriceCustomData;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Commercial\CustomPricing\Entity\Field\CustomPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;

#[Package('core')] // Adjust package as needed
class TorqCustomPriceCustomDataDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'torq_custom_price_custom_data';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TorqCustomPriceCustomDataEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TorqCustomPriceCustomDataCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('custom_price_id', 'customPriceId', CustomPriceDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new JsonField('custom_fields', 'customFields'))->addFlags(new ApiAware()),
            (new CustomPriceField('price', 'price'))->addFlags(new Required(), new ApiAware()),

            (new OneToOneAssociationField('customPrice', 'custom_price_id', 'id', CustomPriceDefinition::class, false))
                ->addFlags(new ApiAware()),
        ]);
    }
}