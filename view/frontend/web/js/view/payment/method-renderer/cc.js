/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Pagcommerce_Payment/payment/cc',
                creditCardType: '',
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardNumber: '',
                creditCardSsStartMonth: '',
                creditCardSsStartYear: '',
                creditCardVerificationNumber: '',
                creditCardInstallments: ''
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'creditCardType',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardNumber',
                        'creditCardVerificationNumber',
                        'creditCardSsStartMonth',
                        'creditCardSsStartYear',
                        'creditCardInstallments'
                    ]);
                return this;
            },

            getCode: function() {
                return 'pagcommerce_payment_cc';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {

                    }
                };
            },

            getTransactionResults: function() {
                return _.map(window.checkoutConfig.payment.sample_gateway.transactionResults, function(value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            },


            getCcMonths: function() {
                return window.checkoutConfig.payment.pagcommerce_payment_cc.months;
            },
            getCcYears: function() {
                return window.checkoutConfig.payment.pagcommerce_payment_cc.years;
            },
            getInstallments: function(){
                return window.checkoutConfig.payment.pagcommerce_payment_cc.installments;
            },
            getInstallmentsValues: function() {
                return _.map(this.getInstallments(), function(value, key) {
                    return {
                        'value': key,
                        'parcel': value.label
                    }
                });
            },
            getCcMonthsValues: function() {
                return _.map(this.getCcMonths(), function(value, key) {
                    return {
                        'value': key,
                        'month': value
                    }
                });
            },
            getCcYearsValues: function() {
                return _.map(this.getCcYears(), function(value, key) {
                    return {
                        'value': key,
                        'year': value
                    }
                });
            },
            isActive: function () {
                return true;
            },
            hasVerification: function() {
                return window.checkoutConfig.payment.pagcommerce_payment_cc.hasVerification;
            },
            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }

        });
    }
);
