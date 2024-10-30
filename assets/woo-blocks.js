if (window.wc
    && window.wc.wcBlocksRegistry
    && window.React
) {
    /**
     * External dependencies
     */
    const {registerPaymentMethod} = wc.wcBlocksRegistry;
    const {createElement, useEffect} = React;
    const {__} = wp.i18n;
    const {getSetting} = wc.wcSettings;
    const {decodeEntities} = wp.htmlEntities;
    const {PAYMENT_STORE_KEY} = wc.wcBlocksData;
    const {useSelect, useDispatch} = wp.data;

    /**
     * Internal dependencies
     */
    const PAYMENT_METHOD_NAME = 'helio'
    const settings = getSetting('helio_data', {});

    const defaultLabel = __(
        'Helio Payment',
        'helio'
    );
    const label = decodeEntities(settings.title) || defaultLabel;


    window.helioSetPaymentProcessing = () => {};

    /**
     * Content component
     */
    const Content = createElement((props) => {
        const {eventRegistration, emitResponse} = props;

        const {
            onPaymentProcessing,
            onCheckoutAfterProcessingWithSuccess,
            onCheckoutAfterProcessingWithError
        } = eventRegistration;

        useEffect(() => {
            const unsubscribe1 = onPaymentProcessing(async (result) => {
                console.log('onPaymentProcessing', result);
            });

            const unsubscribe2 = onCheckoutAfterProcessingWithSuccess(async (result) => {
                console.log('onCheckoutAfterProcessingWithSuccess', result);
                const {processingResponse} = result;
                const {paymentDetails} = processingResponse;

                window.initHelioCheckout(paymentDetails);

                jQuery('[data-block-name="woocommerce/checkout"]').addClass("processing");
            });

            const unsubscribe3 = onCheckoutAfterProcessingWithError(async (result) => {
                console.log('onCheckoutAfterProcessingWithSuccess', result);
            });

            // Unsubscribes when this component is unmounted.
            return () => {
                unsubscribe1();
                unsubscribe2();
                unsubscribe3();
            };
        }, [
            emitResponse.responseTypes.SUCCESS,
            emitResponse.responseTypes.ERROR,
            emitResponse.responseTypes.FAIL,
        ]);

        if (settings.tokens) {
            return decodeEntities(settings.description || "");
        }
    }, null);

    /**
     * Label component
     *
     * @param {*} props Props from payment API.
     */
    const Label = createElement(props => {
        const {PaymentMethodLabel} = props.components;
        return createElement(PaymentMethodLabel, {text: label})
    }, null)


    /**
     * Payment method config object.
     */
    const bankTransferPaymentMethod = {
        name: PAYMENT_METHOD_NAME,
        label: Label,
        content: Content,
        edit: Content,
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings?.supports ?? [],
        },
    };

    registerPaymentMethod(bankTransferPaymentMethod);
}
