<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Storefront\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class AsyncPricingController extends StorefrontController
{
    public function __construct(private SalesChannelRepository $salesChannelProductRepository)
    {
    }

    #[Route(
        path: '/torq/custom-pricing/get-price',
        name: 'torq.custom-pricing.get-price',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function getPrice(Request $request, SalesChannelContext $context): Response
    {
        $productId = $request->query->getString('productId');
        if (!Uuid::isValid($productId)) {
            return $this->emptyPrice(Response::HTTP_BAD_REQUEST);
        }

        $product = $this->salesChannelProductRepository->search(new Criteria([$productId]), $context)->first();

        // A deleted or hidden product is still requested by cached pages and bookmarks.
        if ($product === null) {
            return $this->emptyPrice(Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'price' => $this->renderView('@TorqCustomPricing/async-pricing/basic-price.html.twig', [
                'product' => $product,
            ]),
        ]);
    }

    /**
     * The storefront plugin parses every response as JSON and writes `price`
     * into the page, so failures still answer with an empty price.
     */
    private function emptyPrice(int $status): Response
    {
        return $this->json(['price' => ''], $status);
    }
}
