<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Model;

class CustomPrice
{
    private float $price;

    public function __construct(float $price)
    {
        $this->price = $price;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
