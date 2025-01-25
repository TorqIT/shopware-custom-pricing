<?php

namespace Torq\Shopware\CustomPricing\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents as KernelEvents;
use Torq\Shopware\CustomPricing\Constants\ConfigConstants;
use Torq\Shopware\CustomPricing\Service\CustomPriceCollectorDecorator;

/** @package Torq\Shopware\CustomPricing\Subscriber */
class CacheControlSubscriber implements EventSubscriberInterface
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
            'frontend.cart.offcanvas' => $this->offCanvasCacheControl($event),
            default => null
        };
    }

    public function offCanvasCacheControl(ControllerEvent $event): void
    {
        if($this->systemConfigService->get(ConfigConstants::FORCE_OFF_CANVAS_RECALCULATE) === true)
        {
            CustomPriceCollectorDecorator::setForceApiCall(true);
        }
    }

}