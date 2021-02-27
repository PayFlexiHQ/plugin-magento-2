define(
        [
            "jquery",
            'mage/url',
            "Magento_Checkout/js/view/payment/default",
            "Magento_Checkout/js/action/place-order",
            "Magento_Checkout/js/model/payment/additional-validators",
            "Magento_Checkout/js/model/quote",
            "Magento_Checkout/js/model/full-screen-loader",
            "Magento_Checkout/js/action/redirect-on-success",
        ],
        function (
                $,
                mageUrl,
                Component,
                placeOrderAction,
                additionalValidators,
                quote,
                fullScreenLoader,
                redirectOnSuccessAction
                ) {
            'use strict';

            return Component.extend({
                defaults: {
                    template: 'Payflexi_Checkout/payment/payflexi_checkout'
                },

                redirectAfterPlaceOrder: false,

                isActive: function () {
                    return true;
                },
                /**
                 * Provide redirect to page
                 */
                redirectToCustomAction: function (url) {
                    fullScreenLoader.startLoader();
                    window.location.replace(mageUrl.build(url));
                },

                /**
                 * @override
                 */
                afterPlaceOrder: function () {

                    var checkoutConfig = window.checkoutConfig;
                    var paymentData = quote.billingAddress();
                    var payflexiConfiguration = checkoutConfig.payment.payflexi_checkout;
                    var visibleItems = checkoutConfig.quoteItemData;
                    var products = '';
                    visibleItems.forEach((item, index) => {
                        var orderedItem = item.name + ", Qty:" + item.qty + ", Sku:" + item.product.sku;

                        if (item.options && item.options.length >= 1) {
                            item.options.forEach((option, i) => {
                                orderedItem = orderedItem  + ", " +  option.label + ":" + option.value;
                            })
                        }

                        if (index < visibleItems.length - 1) {
                            orderedItem =  orderedItem + " | ";
                        }                            
                        products += orderedItem;
                    });

                    if (payflexiConfiguration.integration_type == 'standard') {
                        this.redirectToCustomAction(payflexiConfiguration.integration_type_standard_url);
                    } else {
                        if (checkoutConfig.isCustomerLoggedIn) {
                            var customerData = checkoutConfig.customerData;
                            paymentData.email = customerData.email;
                        } else {
                            paymentData.email = quote.guestEmail;
                        }

                        var quoteId = checkoutConfig.quoteItemData[0].quote_id;

                        var _this = this;
                        _this.isPlaceOrderActionAllowed(false);
                        var handler = PayFlexi.checkout({
                            key: payflexiConfiguration.public_key,
                            name: paymentData.firstname + ' ' + paymentData.lastname,
                            email: paymentData.email,
                            amount: Math.ceil(quote.totals().grand_total), // get order total from quote for an accurate... quote
                            currency: checkoutConfig.totalsData.quote_currency_code,
                            meta: {
                                title: products,
                                quoteId: quoteId,
                                billing_address: paymentData.street[0] + ", " + paymentData.street[1],
                                phone: paymentData.telephone,
                                city: paymentData.city + ", " + paymentData.countryId
                            },
                            onSuccess: function (response) {
                                fullScreenLoader.startLoader();
                                $.ajax({
                                    method: "GET",
                                    url: payflexiConfiguration.api_url + "V1/payflexi/verify/" + response.reference + "_-~-_" + quoteId
                                }).success(function (data) {
                                    data = JSON.parse(data);

                                    if (data.status) {
                                        if (data.status === "approved") {
                                            // redirect to success page after
                                            redirectOnSuccessAction.execute();
                                            return;
                                        }
                                    }

                                    fullScreenLoader.stopLoader();

                                    _this.isPlaceOrderActionAllowed(true);
                                    _this.messageContainer.addErrorMessage({
                                        message: "Error, please try again"
                                    });
                                });
                            },
                            onDecline: function(response){
                                fullScreenLoader.startLoader();
                                $.ajax({
                                    method: "GET",
                                    url: payflexiConfiguration.api_url + "V1/payflexi/verify/" + response.reference + "_-~-_" + quoteId
                                }).success(function (data) {
                                    data = JSON.parse(data);
                                   _this.redirectToCustomAction(payflexiConfiguration.recreate_quote_url);
                                });
                            },
                            onExit: function(){
                                _this.redirectToCustomAction(payflexiConfiguration.recreate_quote_url);
                            }
                        });
                        handler.renderCheckout();
                    }
                },

            });
        }
);
