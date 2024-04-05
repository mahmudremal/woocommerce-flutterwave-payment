/**
 * Frontend Script.
 * 
 * @package WooFlutter
 */


import { decodeEntities } from '@wordpress/html-entities';
// import { useCheckoutContext } from '@woocommerce/base-contexts';
import { useEffect } from '@wordpress/element';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting( 'flutterwave_data', {} )

const label = decodeEntities( settings.title )

// const Content = () => {
// 	return decodeEntities( settings.description || '' )
// }
// const Label = ( props ) => {
// 	const { PaymentMethodLabel } = props.components
// 	return <PaymentMethodLabel text={ label } />
// }
// const Content = () => {
// 	return (
// 		<div class="wooflutter__method">
// 			<div class="wooflutter__wrap">
// 				<div class="wooflutter__head"></div>
// 				<div class="wooflutter__body">
// 					<div class="wooflutter__desc">
// 						{decodeEntities(settings.description || '')}
// 					</div>
// 				</div>
// 				<div class="wooflutter__foot"></div>
// 			</div>
// 		</div>
// 	)
// }
/*
const Content = (props) => {
    const { eventRegistration, emitResponse } = props;
    // const { onCheckoutAfterProcessingWithError, onCheckoutAfterProcessingWithSuccess, onPaymentProcessing } = eventRegistration;
    const { onCheckoutValidation, onCheckoutSuccess, onCheckoutFail, onPaymentSetup, onShippingRateSuccess, onShippingRateFail, onShippingRateSelectSuccess, onShippingRateSelectFail } = eventRegistration;

    useEffect(() => {
        const unsubscribeError = onCheckoutFail((response) => {
            console.error('Checkout processing error:', response);
        });
        const unsubscribeSuccess = onCheckoutSuccess(async (response) => {
            console.log('Checkout processing success:', response);

            // Assuming 'redirect_url' is the property containing the redirect URL
            const redirectUrl = response.payment_result?.redirect_url;

            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                console.error('Redirect URL not found in response');
            }
        });
        const unsubscribePaymentProcessing = onPaymentSetup((response) => {
            console.log('Payment processing:', [eventRegistration, response]);
        });
		// 
        return () => {
            unsubscribeError();
            unsubscribeSuccess();
            unsubscribePaymentProcessing();
        };
    }, [onCheckoutValidation, onCheckoutSuccess, onCheckoutFail, onPaymentSetup, onShippingRateSuccess, onShippingRateFail, onShippingRateSelectSuccess, onShippingRateSelectFail]);

    return decodeEntities(settings.description || '');
};
*/
const Content = ( props ) => {
	const { eventRegistration, emitResponse } = props;
    const { onCheckoutAfterProcessingWithError, onCheckoutAfterProcessingWithSuccess, onPaymentProcessing } = eventRegistration;
    const { onCheckoutValidation, onCheckoutSuccess, onCheckoutFail, onPaymentSetup, onShippingRateSuccess, onShippingRateFail, onShippingRateSelectSuccess, onShippingRateSelectFail } = eventRegistration;
	useEffect(() => {
        const unsubscribeSuccess = onCheckoutSuccess(async (response) => {
            console.log('Checkout processing success:', response);
            // Your custom logic for handling successful checkout here
            return {
                type: emitResponse.responseTypes.SUCCESS,
                message: 'Checkout was successful',
            };
        });

        const unsubscribeError = onCheckoutFail((response) => {
            console.error('Checkout processing error:', response);
            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'There was an error during checkout',
            };
        });

        const unsubscribePaymentProcessing = onPaymentSetup((response) => {
            console.log('Payment processing:', response);
            // Your custom logic for handling payment processing
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
	return decodeEntities( settings.description || '' );
};
const Icon = () => {
	return settings.icon 
		? <img src={settings.icon} style={{ float: 'right', marginRight: '20px' }} /> 
		: ''
}

const Label = () => {
	return (
        <span style={{ width: '100%' }}>
            {label}
            <Icon />
        </span>
    )
}

registerPaymentMethod( {
	name: "flutterwave",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	}
})

// export default Content;
// window.addEventListener('checkoutConfirmed', function(event) {
// 	console.log('checkoutConfirmed', event);
// 	const { response } = event.detail;
// 	// Check if the response contains a redirect URL
// 	if (response && response.redirect_url) {
// 		// Redirect the user to the specified URL
// 		// window.location.href = response.redirect_url;
// 	}
// });
// (function ($) {
// 	class WooFlutterCheckout {
// 		constructor() {
// 			this.setup_hooks();
// 		}
// 		setup_hooks() {
// 			this.setup_events();
// 		}
// 		setup_events() {
			
// 		}
// 	}
// 	new WooFlutterCheckout();
// })(jQuery);
