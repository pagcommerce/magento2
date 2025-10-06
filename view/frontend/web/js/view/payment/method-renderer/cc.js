define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'mage/translate',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'ko',
        'mage/storage',
        'mage/url'
    ],
    function (
        Component,
        $,
        creditCardData,
        cardNumberValidator,
        $t,
        selectPaymentMethodAction,
        checkoutData,
        quote,
        ko,
        storage,
        urlBuilder
    ) {
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
                creditCardInstallments: '',
                taxvat: '',
                creditCardOwner: ''
            },

            initialize: function () {
                this._super();
                this.taxvat(this.getTaxVat());

                // installments como observableArray
                this.installments = ko.observableArray(this.getInstallmentsInitial());

                // observa mudanças no grand_total
                quote.totals.subscribe(function (totals) {
                    if (totals && totals['grand_total']) {
                        this.updateInstallments(totals['grand_total']);
                    }
                }.bind(this));

                return this;
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardNumber',
                        'creditCardVerificationNumber',
                        'creditCardInstallments',
                        'taxvat',
                        'creditCardOwner'
                    ]);
                return this;
            },

            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            getCode: function() {
                return 'pagcommerce_payment_cc';
            },

            getData: function() {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'customertaxvat': this.taxvat(),
                        'cc_number': this.creditCardNumber(),
                        'cc_expiration_year': this.creditCardExpYear(),
                        'cc_expiration_month': this.creditCardExpMonth(),
                        'cc_cvv': this.creditCardVerificationNumber(),
                        'cc_owner_name': this.creditCardOwner(),
                        'installment': this.creditCardInstallments()
                    }
                };
            },

            /** =================== PARCELAS =================== */
            getInstallments: function(){
                return window.checkoutConfig.payment.pagcommerce_payment_cc.installments;
            },

            getInstallmentsInitial: function () {
                var installments = this.getInstallments();
                return _.map(installments, function (value, key) {
                    return {
                        'value': value.parcel,
                        'parcel': value.label
                    };
                });
            },

            updateInstallments: function(total) {
                var serviceUrl = urlBuilder.build('rest/V1/pagcommerce/installments/' + total);

                storage.get(serviceUrl).done(function (response) {
                    this.installments(_.map(response, function (value, key) {
                        return {
                            'value': value.value,
                            'parcel': value.label
                        };
                    }));
                }.bind(this));
            },

            /** =================== OUTROS MÉTODOS =================== */
            getCcMonths: function() {
                return window.checkoutConfig.payment.pagcommerce_payment_cc.months;
            },
            getTaxVat: function() {
                return window.checkoutConfig.payment.pagcommerce_payment_cc.taxvat;
            },
            getCcYears: function() {
                return window.checkoutConfig.payment.pagcommerce_payment_cc.years;
            },
            getCcMonthsValues: function() {
                return _.map(this.getCcMonths(), function(value, key) {
                    return {
                        'value': key,
                        'month': value
                    };
                });
            },
            getCcYearsValues: function() {
                return _.map(this.getCcYears(), function(value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
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
