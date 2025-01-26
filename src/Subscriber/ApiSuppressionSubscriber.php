<?php

namespace Torq\Shopware\CustomPricing\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents as KernelEvents;
use Torq\Shopware\CustomPricing\Constants\ConfigConstants;
use Torq\Shopware\CustomPricing\Service\CustomPriceCollectorDecorator;

class ApiSuppressionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SystemConfigService $systemConfigService){}

    public static function getSubscribedEvents() 
    { 
        return [
            KernelEvents::CONTROLLER => 'handleControllerEvent',
            ProductListingCriteriaEvent::class => "handleProductListingCriteria"
        ];
    }

    public function handleControllerEvent(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        match($route) {
            'frontend.detail.page' => $this->suppressionCheck(ConfigConstants::PRODUCT_DETAIL_PAGE_ASYNC),
            'frontend.search.suggest' => $this->suppressionCheck(ConfigConstants::SEARCH_SUPPRESSION),
            default => null
        };
    }

    public function handleProductListingCriteria(ProductListingCriteriaEvent $event): void
    {
        if($this->configValue(ConfigConstants::PRODUCT_LISTING_SUPPRESSION) === true)
        {
            CustomPriceCollectorDecorator::setSupressApiCall(true);
        }
    }

    public function suppressionCheck(string $configControl): void
    {
        if($this->configValue($configControl) === true)
        {
            CustomPriceCollectorDecorator::setSupressApiCall(true);
        }
    }

    private function configValue(string $key): bool
    {
        return !!$this->systemConfigService->get($key);
    }

}