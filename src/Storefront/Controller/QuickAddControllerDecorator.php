<?php

namespace Torq\Shopware\CustomPricing\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Torq\Shopware\Common\Storefront\Controller\QuickAddController;
use Torq\Shopware\CustomPricing\Service\CustomPriceApiDirector;

class QuickAddControllerDecorator extends QuickAddController
{
    public function __construct(private QuickAddController $decorated)
    {
    }

    public function getResults(Request $request, SalesChannelContext $context): Response
    {
        CustomPriceApiDirector::setSupressApiCall(true);

        return $this->decorated->getResults($request, $context);
    }
}
