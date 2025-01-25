<?php

namespace Torq\Shopware\CustomPricing\Storefront\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
        $productId = $request->get('productId');
        $criteria = new Criteria([$productId]);
        
        /** @var SalesChannelProductEntity $parent */
        $product = $this->salesChannelProductRepository->search($criteria, $context)->first();

        $response = [];
        $response['price'] = $this->renderView("@TorqCustomPricing/async-pricing/basic-price.html.twig",[
            'product' => $product
        ]);

        return $this->json($response);
    }
}
