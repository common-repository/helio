const HELIO_CHECKOUT_EMBED_SOURCE = 'https://embed.hel.io/assets/index-v1.js';

jQuery(document).ready(function ($) {
    window.initHelioCheckout = initHelioCheckout;

    $('form.checkout').on('checkout_place_order_success', function shortcodesCheckout (e, result) {
        if ($('input#payment_method_helio').is(':checked') && $('form.checkout input[name="transactionId"]').length == 0) {
            initHelioCheckout(result);
        }

        if ($('form.checkout input[name="transactionId"]').length > 0) {
            $('input[name="transactionId"]').remove();
        }

        return true;
    })

    function initHelioCheckout(result) {
        const paylinkId = result.paylink_id
        const checkoutProcessNonce = result.nonce || '';
        const checkoutOrderId = result.order_id || 0;
        const isDarkmode = !!result.is_darkmode

        if(!checkoutProcessNonce || !checkoutOrderId) {
            throw new Error(`Unable to show Helio modal - missing IDs: ${JSON.stringify({
                checkoutProcessNonce,
                checkoutOrderId
            })}`);
        }

        $('#helio-modal').remove();
        $('body').append(result.modal);

        setTimeout(() => {

            const createScriptTag = () => {
                const script = document.createElement('script');
                script.setAttribute('type', 'module');
                script.setAttribute('id', 'HELIO_CHECKOUT_V1');
                script.setAttribute('src', HELIO_CHECKOUT_EMBED_SOURCE);
                document.body.appendChild(script);

                return script;
            };

            const runHelioCheckout = () => {
                window.helioCheckout(
                    document.getElementById("helioCheckoutContainer"),
                    {
                        paylinkId,
                        network: !!result.is_devnet ? 'test' : 'main',
                        amount: result.total,
                        display: "modal",
                        requiredCurrencies: ['USDC'],
                        theme: {
                            themeMode: isDarkmode ? 'dark' : 'light',
                        },
                        source: `WooCommerce v${result.plugin_version}`,
                        sourceIntegration: "WOO_COMMERCE",
                        sourceIntegrationVersion: result.plugin_version,
                        additionalJSON: {
                            helioWooPluginVersion: result.plugin_version,
                            orderID: checkoutOrderId,
                            checkoutNonce: checkoutProcessNonce
                        },

                        onSuccess: (event) => {
                            const blockchainTransactionId = event.transaction
                            const blockchainSymbol = event.blockchainSymbol;

                            const checkoutFieldData = {
                                transactionId: blockchainTransactionId,
                                blockchainSymbol,
                                order_id: checkoutOrderId,
                                _wpnonce: checkoutProcessNonce
                            }

                            if (window.wc && window.wc.wcSettings && window.wc.wcSettings.getSetting('helio_data')) { // woo checkout block support
                                $.ajax({
                                    method: 'POST',
                                    contentType: 'application/json',
                                    url: '/?wc-ajax=helio_checkout',
                                    data: JSON.stringify(checkoutFieldData),
                                    success: function (res) {
                                        if (res.success || res.redirect) {
                                            window.location.href = res.redirect;
                                        } else {
                                            window.location.reload()
                                        }
                                    },
                                    error: function (request, status, error) {
                                        console.error(request, status, error);
                                    },
                                })
                            } else {
                                // process non-blocks
                                var form = $('form.checkout');

                                Object.entries(checkoutFieldData).forEach(([name, value]) => {
                                    form.append(`<input type="hidden" name="${name}" value="${value}">`);
                                })

                                form.removeClass("processing");
                                form.trigger('submit');
                            }
                        }
                    }
                );
            }

            if (window.helioCheckout) {
                runHelioCheckout()
            } else {
                const script = document.querySelector(`script[src="${HELIO_CHECKOUT_EMBED_SOURCE}"]`) ?? createScriptTag()

                script.addEventListener("load", () => {
                    runHelioCheckout()
                });
            }
        }, 0)
    }
})
