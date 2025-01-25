<?php

namespace Torq\Shopware\CustomPricing\Subscriber;

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
            KernelEvents::CONTROLLER => 'handleControllerEvent'
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

    public function suppressionCheck(string $configControl): void
    {
        if($this->systemConfigService->get($configControl) === true)
        {
            CustomPriceCollectorDecorator::setSupressApiCall(true);
        }
    }

}