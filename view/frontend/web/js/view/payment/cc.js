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
        'jquery'
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
            initialize: function () {
                this._super();
                const checkCpfCnpjLength = setInterval(() => {
                    let field = $('input[name="payment[cc_cpf]"]');

                    if (field.length) {
                        field.on('keyup', function () {
                            let v = $(this).val();

                            v = v.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

                            if (v.length <= 11) {
                                v = v.replace(/([a-zA-Z0-9]{3})([a-zA-Z0-9])/, '$1.$2');
                                v = v.replace(/([a-zA-Z0-9]{3})([a-zA-Z0-9])/, '$1.$2');
                                v = v.replace(/([a-zA-Z0-9]{3})([a-zA-Z0-9]{1,2})$/, '$1-$2');
                            } else {
                                v = v.replace(/^([a-zA-Z0-9]{2})([a-zA-Z0-9])/, '$1.$2');
                                v = v.replace(/^([a-zA-Z0-9]{2})\.([a-zA-Z0-9]{3})([a-zA-Z0-9])/, '$1.$2.$3');
                                v = v.replace(/\.([a-zA-Z0-9]{3})([a-zA-Z0-9])/, '.$1/$2');
                                v = v.replace(/([a-zA-Z0-9]{4})([a-zA-Z0-9])/, '$1-$2');
                            }

                            $(this).val(v);
                        });

                        clearInterval(checkCpfCnpjLength);
                    }
                }, 100);
            }
        });
    }
);
