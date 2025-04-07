/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'jquery',
        'inputmask'
    ],
    function (
        Component,
        rendererList,
        $
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pagcommerce_payment_cc',
                component: 'Pagcommerce_Payment/js/view/payment/method-renderer/cc'
            }
        );

        return Component.extend({
            cpfCnpjMask: function () {
            },

            initialize: function () {
                this._super();

                const checkCpfCnpjLength = setInterval(() => {
                    if ($('input[name="payment[cc_cpf]"]').length) {
                        Inputmask(['999.999.999-99', '99.999.999/9999-99']).mask('input[name="payment[cc_cpf]"]');

                        clearInterval(checkCpfCnpjLength);
                    }
                }, 100);
            }
        });
    }
);
