<?php

namespace Torq\Shopware\CustomPricing\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents as KernelEvents;
use Torq\Shopware\CustomPricing\Constants\ConfigConstants;
use Torq\Shopware\CustomPricing\Service\CustomPriceApiDirector;

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
            'frontend.cart.offcanvas' => $this->cacheControlCheck(ConfigConstants::FORCE_OFF_CANVAS_RECALCULATE),
            'frontend.checkout.cart.page' => $this->cacheControlCheck(ConfigConstants::FORCE_CART_PREVIEW_RECALCULATE),
            'frontend.checkout.confirm.page' => $this->cacheControlCheck(ConfigConstants::FORCE_CHECKOUT_CONFIRM_RECALCULATE),
            default => null
        };
    }

    public function cacheControlCheck(string $configControl): void
    {
        if($this->systemConfigService->get($configControl) === true)
        {
            CustomPriceApiDirector::setForceApiCall(true);
        }
    }

}