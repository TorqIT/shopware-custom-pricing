<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Cart processor that forces product recalculation when CustomPriceApiDirector::getForceApiCall() is true.
 * 
 * This runs before ProductCartProcessor (priority 6000 vs 5000) and marks product line items as modified
 * to force the ProductCartProcessor to refetch products from the gateway, which in turn triggers the
 * custom pricing events.
 */
class ProductRecalculationProcessor implements CartDataCollectorInterface
{
    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        // Only act when force recalculation is enabled by CacheControlSubscriber
        if (!CustomPriceApiDirector::getForceApiCall()) {
            return;
        }

        // Get all product line items
        $productLineItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        
        foreach ($productLineItems as $lineItem) {
            // Mark as modified to force ProductCartProcessor to refetch this product
            // This will cause ProductCartProcessor::getNotCompleted() to include this product ID,
            // which will trigger ProductGateway::get() and fire the sales_channel.product.loaded event
            $lineItem->markModified();
        }
    }
}