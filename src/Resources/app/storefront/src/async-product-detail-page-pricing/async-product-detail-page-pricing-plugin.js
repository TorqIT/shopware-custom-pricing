const { PluginBaseClass } = window;
import DomAccess from 'src/helper/dom-access.helper';


export default class AsyncProductDetailPagePricingPlugin extends PluginBaseClass {
    
    static options = {
        productId: ''
    };

    init() {
        this._fetchProductPricing();
    }

    _registerEvents() {
    }

    async _fetchProductPricing() {
        const response = await fetch('/torq/custom-pricing/get-price?productId=' + this.options.productId);
        const data = await response.json();
        DomAccess.querySelector(this.el, '.torq-async-product-detail-page-pricing-loader').remove();
        DomAccess.querySelector(this.el, '.product-detail-price').innerHTML = data.price;
        this.el.classList.remove("torq-async-product-detail-page-pricing-loading");
    }
}