import AsyncProductDetailPagePricingPlugin from './async-product-detail-page-pricing/async-product-detail-page-pricing-plugin';

const PluginManager = window.PluginManager;
PluginManager.register('AsyncProductDetailPagePricingPlugin', AsyncProductDetailPagePricingPlugin, '[data-async-product-detail-page-pricing-plugin]');