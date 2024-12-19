<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Torq\Shopware\CustomPricing\Service\CustomPriceProvider;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CustomPriceTestController
{
    private CustomPriceProvider $priceProvider;

    public function __construct(CustomPriceProvider $priceProvider)
    {
        $this->priceProvider = $priceProvider;
    }

    /**
     * @Route("/test-price/{productId}", name="test.custom.price", methods={"GET"})
     */
    public function testPrice(string $productId): JsonResponse
    {
        $price = $this->priceProvider->getCustomPrice($productId);

        return new JsonResponse([
            'productId' => $productId,
            'price' => $price ? $price->getPrice() : null,
        ]);
    }
}
