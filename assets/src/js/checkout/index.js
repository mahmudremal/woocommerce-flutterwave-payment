/**
 * Frontend Checkout Custom peyment method support Script.
 * 
 * @package WooFlutter
 */


import { decodeEntities } from '@wordpress/html-entities';
// import { useCheckoutContext } from '@woocommerce/base-contexts';
import { useEffect } from '@wordpress/element';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting( 'flutterwave_data', {} )


const Icon = () => {
	return settings.build_dir 
		? <img src={settings.build_dir + '/icons/flutterwave.svg'} style={{ float: 'right', marginRight: '20px' }} /> 
		: ''
}
const label = decodeEntities( settings.title );
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return (
        <span style={{ width: '100%' }}>
			<PaymentMethodLabel text={ label } />
            <Icon />
        </span>
    )
}
const Content_HTML = () => {
	return (
		<div class="wooflutter__method">
			<div class="wooflutter__wrap">
				<div class="wooflutter__head"></div>
				<div class="wooflutter__body">
					<div class="wooflutter__desc">
						{decodeEntities(settings.description || '')}
					</div>
				</div>
				<div class="wooflutter__foot"></div>
			</div>
		</div>
	)
}
let paymentIntend = false;let intervalPaymentIntend = false;
const removePaymentIntend = () => {
	if (paymentIntend) {paymentIntend.remove();}
	if (intervalPaymentIntend) {clearInterval(intervalPaymentIntend);}
};
const Content = ( props ) => {
	const { eventRegistration, emitResponse } = props;
    const { onCheckoutAfterProcessingWithError, onCheckoutAfterProcessingWithSuccess, onPaymentProcessing } = eventRegistration;
    const { onCheckoutValidation, onCheckoutSuccess, onCheckoutFail, onPaymentSetup, onShippingRateSuccess, onShippingRateFail, onShippingRateSelectSuccess, onShippingRateSelectFail } = eventRegistration;
	useEffect(() => {
        const unsubscribeSuccess = onCheckoutSuccess(async (response) => {
			removePaymentIntend(); // Remove payment intend overaly if exists.
            console.log('Checkout processing success:', response);
            // Your custom logic for handling successful checkout here
            return {
                type: emitResponse.responseTypes.SUCCESS,
                message: 'Checkout was successful',
            };
        });

        const unsubscribeError = onCheckoutFail((response) => {
			removePaymentIntend(); // Remove payment intend overaly if exists.
			if (response && response?.redirectUrl && response.redirectUrl.trim() != '') {
				location.href = response.redirectUrl;
				return {
					type: emitResponse.responseTypes.ERROR,
                	message: 'You\re redirecting to a secured payment screen. Make your payment to mark this order successful.',
				};
			} else {
				console.error('Checkout processing error:', response);
				return {
					type: emitResponse.responseTypes.ERROR,
					message: 'There was an error during checkout',
				};
			}
        });

        const unsubscribePaymentProcessing = onPaymentSetup((response) => {
            // console.log('Payment processing:', response);
            // Your custom logic for handling payment processing
			removePaymentIntend(); // Remove payment intend overaly if exists.
			paymentIntend = document.createElement('div');
			paymentIntend.classList.add('flutterwave__checkout');
			paymentIntend.classList.add('flutterwave__loading');
			paymentIntend.style.backgroundImage = `url("${settings.build_dir}/icons/flutterwave-full.svg")`;
			// console.log(paymentIntend)
			document.body.appendChild(paymentIntend);
			intervalPaymentIntend = setInterval(() => {
				removePaymentIntend(); // Remove payment intend overaly if exists.
			}, 10000);
            return true;
        });
        window.props = props;

        // Clean up the event handlers when the component is unmounted
        return () => {
            unsubscribeSuccess();
            unsubscribeError();
            unsubscribePaymentProcessing();
        };
    }, [eventRegistration, emitResponse]);
	return Content_HTML();
};
// console.log('Initialize checkout method addon');
registerPaymentMethod( {
	paymentMethodId: 'flutterwave',
	name: "flutterwave",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'mygateway' ),
	supports: {
		features: settings.supports,
	}
})