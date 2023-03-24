define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pagcommerce_payment_boleto',
                component: 'Pagcommerce_Payment/js/view/payment/method-renderer/boleto'
            }
        );
        return Component.extend({});
    }
);
