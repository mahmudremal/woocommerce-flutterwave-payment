<?php
/**
 * Ajax request handler
 *
 * @link https://rudrastyh.com/woocommerce/checkout-block-payment-method-integration.html
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
// use WOOFLUTTER\inc\Traits\Singleton;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Flutterwave (Flutterwave) payment method integration
 *
 * @since 3.0.0
 */
final class Flutterwave_Gateway_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = 'flutterwave';

	/**
	 * An instance of the Asset Api
	 *
	 * @var Api
	 */
	private $asset_api;

	/**
	 * Constructor
	 *
	 * @param Api $asset_api An instance of Api.
	 */
	// Api 
	public function __construct($asset_api = false) {
		$this->asset_api = $asset_api;
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option('woocommerce_flutterwave_settings', []);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var($this->get_setting('enabled', false), FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		// $this->asset_api->register_script(
		// 	'wc-payment-method-flutterwave',
		// 	WOOFLUTTER_DIR_URI . '/assets/build/js/wc-payment-method-flutterwave.js'
		//);
		// return ['wc-payment-method-flutterwave'];
		// 
		wp_register_script(
			'wc-flutterwave-blocks-integration',
			WOOFLUTTER_BUILD_URI . '/js/woo_public.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities'
			],
			apply_filters('wooflutter/function/filemtime', apply_filters('wooflutter/path/fix/slashes', WOOFLUTTER_BUILD_PATH . '/js/woo_public.js')),
			true
		);
		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-flutterwave-blocks-integration');
		}
		return ['wc-flutterwave-blocks-integration'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'			=> $this->get_setting('title'),
			'supports'		=> $this->get_supported_features(),
			'description'	=> $this->get_setting('description'),
			'build_dir'		=> WOOFLUTTER_BUILD_URI,
		];
	}
}
