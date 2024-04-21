<?php
/**
 * WooCommerce Payment Widget Register
 *
 * @link https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md
 * 
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WeDevs\Dokan\Vendor\SettingsApi\Abstracts\Gateways;

class Dokan_Gateway_Flutterwave extends Gateways {

    /**
     * Render the settings page for flutterwave.
     *
     * @since 3.7.10
     *
     * @param array $settings Settings to render.
     *
     * @return array
     */
    public function render_settings(array $settings ): array {
        $settings[] = [
            'id'        => 'flutterwave_card',
            'title'     => __('Flutterwave', 'wooflutter'),
            'desc'      => __('Flutterwave settings.', 'wooflutter'),
            'info'      => [],
            'icon'      => '',
            'type'      => 'card',
            'parent_id' => 'payment',
            'tab'       => 'general',
            'editable'  => true,
        ];
        $settings[] = [
            'id'        => 'bank',
            'title'     => __('Bank', 'wooflutter'),
            'desc'      => __('Bank settings', 'wooflutter'),
            'icon'      => '',
            'type'      => 'section',
            'parent_id' => 'payment',
            'tab'       => 'general',
            'editable'  => true,
            'card'      => 'bank_card',
            'fields'    => [
                [
                    'id'        => 'ac_name',
                    'title'     => __('Account Name', 'wooflutter'),
                    'desc'      => __('Enter your bank account name.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'text',
                    'parent_id' => 'bank',
                ],
                [
                    'id'        => 'ac_number',
                    'title'     => __('Account Number', 'wooflutter'),
                    'desc'      => __('Enter your bank account number.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'text',
                    'parent_id' => 'bank',
                ],
                [
                    'id'        => 'bank_name',
                    'title'     => __('Bank Name', 'wooflutter'),
                    'desc'      => __('Enter your bank name.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'text',
                    'parent_id' => 'bank',
                ],
                [
                    'id'        => 'bank_addr',
                    'title'     => __('Bank Address', 'wooflutter'),
                    'desc'      => __('Enter your bank address.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'textarea',
                    'parent_id' => 'bank',
                ],
                [
                    'id'        => 'routing_number',
                    'title'     => __('Routing Number', 'wooflutter'),
                    'desc'      => __('Enter your bank routing number.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'text',
                    'parent_id' => 'bank',
                ],
                [
                    'id'        => 'iban',
                    'title'     => __('IBAN', 'wooflutter'),
                    'desc'      => __('Enter your IBAN number.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'text',
                    'parent_id' => 'bank',
                ],
                [
                    'id'        => 'swift',
                    'title'     => __('Swift Code', 'wooflutter'),
                    'desc'      => __('Enter your banks Swift Code.', 'wooflutter'),
                    'icon'      => '',
                    'type'      => 'text',
                    'parent_id' => 'bank',
                ],
            ],
        ];

        return $settings;
    }
    /**
     * FUnction to process on payment through flutterwave platform.
     *
     * @since 3.7.10
     *
     * @param array $settings Settings to render.
     *
     * @return array
     */
	public function process_payment($order_id, $order_data) {
		// print_r('Hi there');wp_die();
		$settings = get_option('dokan_payment_gateway_' . $this->id . '_settings', []);
	
		if ( empty( $settings['public_key'] ) || empty( $settings['secret_key'] ) ) {
			throw new \Exception( 'Flutterwave Public and Secret Keys are not configured.' );
		}
	
		$order = wc_get_order( $order_id ); // Assuming WooCommerce integration
	
		$amount = $order->get_total();
		$currency = get_woocommerce_currency();
		$customer_email = $order->get_billing_email();
		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
	
		$data = array(
			'customer_email' => $customer_email,
			'amount' => $amount,
			'currency' => $currency,
			'payment_options' => 'card, banktransfer', // Specify allowed payment methods (optional)
			'customer_name' => $customer_name,
			'tx_ref' => uniqid( 'dokan_' ), // Generate a unique transaction reference
			'orderID' => $order_id // Pass Dokan order ID for reference
		);
	
		$url = 'https://api.flutterwave.com/v3/payments';
		$authorization = 'Bearer ' . $settings['secret_key'];
	
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: ' . $authorization
		));
	
		$response = curl_exec($ch);
		curl_close($ch);
	
		$response_data = json_decode($response, true);
	
		if (isset($response_data['status']) && $response_data['status'] === 'success') {
			$transaction_id = $response_data['data']['id'];
	
			// Update order status in Dokan based on response and transaction ID
			$order->update_status( 'processing' ); // Replace with appropriate Dokan order status
	
			// Add order notes with transaction ID
			$order->add_order_note( 'Flutterwave Transaction ID: ' . $transaction_id );
	
			return true; // Payment successful
		} else {
			throw new \Exception( 'Payment failed: ' . (isset($response_data['message']) ? $response_data['message'] : 'Unknown error') );
		}
	}
	
}
