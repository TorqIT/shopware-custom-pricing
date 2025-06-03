<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Entity\TorqCustomPriceCustomData;

use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;

#[Package('core')] // Adjust package as needed
class TorqCustomPriceCustomDataEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $customPriceId = null;
    protected ?CustomPriceEntity $customPrice = null;
    protected ?array $customFields = null; // Stored as JSON, accessed as array

    /**
     * @var array<CustomPriceCollection>
     */
    protected array $price = [];

    public function getCustomPriceId(): ?string
    {
        return $this->customPriceId;
    }

    public function setCustomPriceId(?string $customPriceId): void
    {
        $this->customPriceId = $customPriceId;
    }

    public function getCustomPrice(): ?CustomPriceEntity
    {
        return $this->customPrice;
    }

    public function setCustomPrice(?CustomPriceEntity $customPrice): void
    {
        $this->customPrice = $customPrice;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

     /**
     * @return array<CustomPriceCollection>
     */
    public function getPrice(): array
    {
        return $this->price;
    }

    /**
     * @param array<CustomPriceCollection> $price
     */
    public function setPrice(array $price): void
    {
        $this->price = $price;
    }
}