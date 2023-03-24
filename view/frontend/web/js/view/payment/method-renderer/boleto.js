/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
], function (
    Component,
    $
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Pagcommerce_Payment/payment/boleto'
        },
        getCode: function() {
            return 'pagcommerce_payment_boleto';
        },
        maskCpfCnpj: function() {
            var cpf = jQuery('#'+this.getCode()+'_cpf');
            var v = cpf.val();
            if (v.length <= 14) {
                v=v.replace(/\D/g,"");
                v=v.replace(/(\d{3})(\d)/,"$1.$2");
                v=v.replace(/(\d{3})(\d)/,"$1.$2");
                v=v.replace(/(\d{3})(\d{1,2})$/,"$1-$2");
            }else{
                v=v.replace(/\D/g,"")
                v=v.replace(/^(\d{2})(\d)/,"$1.$2");
                v=v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3");
                v=v.replace(/\.(\d{3})(\d)/,".$1/$2");
                v=v.replace(/(\d{4})(\d)/,"$1-$2");
            }

            cpf.val(v);
        },
        getInstructionsPix: function(){
            return "";
        },
        validate: function() {
            var $form = $('#' + this.getCode() + '-form');
            return $form.validation() && $form.validation('isValid');
        }
    });
});
