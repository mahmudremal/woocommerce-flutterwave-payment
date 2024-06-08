<?php
/**
 * Internationalize bundle managment
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;
class Woocommerce {
	use Singleton;
	private $settings = false;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
        add_filter('wooflutter/widgets/list', [$this, 'wooflutter_widgets_list'], 10, 1);
		/**
         * Turncat processing next if Gravityform is not enabled.
         */
        if (!in_array('woo', WOOFLUTTER_WIDGETS)) {return;}

		add_filter('wooflutter/wc/get/settings', [$this, 'get_settings'], 0, 2);
		add_action('wooflutter/payment/flutterwave/status', [$this, 'wooflutter_payment_flutterwave_status'], 10, 4);
		add_action('wooflutter/payment/flutterwave/status/back2text', [$this, 'wooflutter_payment_flutterwave_status_back2text'], 10, 5);
		add_action('wooflutter/payment/flutterwave/status/back2link', [$this, 'wooflutter_payment_flutterwave_status_back2link'], 10, 5);
		add_action('wooflutter/payment/flutterwave/status/retry', [$this, 'wooflutter_payment_flutterwave_status_retry'], 10, 5);

		
        // Add Flutterwave gateway to available payment gateways in WooCommerce
        add_action('before_woocommerce_init', [$this, 'before_woocommerce_init'], 10, 0);
        add_action('plugins_loaded', [ $this, 'load_flutterwave_gateway' ], 1, 0);
        add_filter('woocommerce_payment_gateways', [$this, 'add_flutterwave_gateway']);
        // Step 2: Display Flutterwave Payment Option on Checkout Page
        // add_filter('woocommerce_available_payment_gateways', [$this, 'add_flutterwave_gateway']);
        // Step 3: WooCommerce Settings Page Integration
        // Add a new section to the WooCommerce settings page
        // add_filter('woocommerce_settings_tabs_array', [$this, 'add_flutterwave_settings_tab'], 50);
        // Add settings fields to the Flutterwave settings tab
        add_action('woocommerce_settings_tabs_flutterwave', [$this, 'output_flutterwave_settings']);

        // add_filter('woocommerce_blocks_checkout_payment_methods', [$this, 'register_payment_block_template']);
		add_action('woocommerce_blocks_loaded', [$this, 'woocommerce_blocks_loaded'], 10, 0);

		
		add_action('wp_enqueue_scripts', [$this, 'register_styles']);
		add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 10, 1);
	}
    /**
     * Added this WooCommerce integration widget to the widget list.
     * 
     * @param array $widgets list of all available widgets.
     * 
     * @return array widget list
     */
    public function wooflutter_widgets_list($widgets) {
        $widgets['woo'] = [
            'title' => __('WooCommerce', 'wooflutter'),
            'description' => __('WooCommerce multi-vendor woocommerce plugin integration for vendor withdrawals and much more.', 'wooflutter'),
            'image' => WOOFLUTTER_BUILD_URI . '/icons/woo.png',
            // 'callback' => [$this, 'wooflutter_widgets_list_callback'],
            'priority' => 10,
            'active' => in_array('woo', WOOFLUTTER_WIDGETS),
        ];
        return $widgets;
    }
	/**
	 * Get settings object form wooocommrers payment settings including api keys and sensitive informations.
	 * 
	 * @mahmudremal
	 * @param null $settings an array or settings by default pushed.
	 * @param string|object $key is an specific key to get specific settings.
	 */
	public function get_settings($settings, $key = false) {
		if (!$this->settings) {
			// $this->settings = get_option('', []);
			$gateways = WC()->payment_gateways->payment_gateways();
			$this->settings = ($gateways && isset($gateways['flutterwave']))?$gateways['flutterwave']->settings:[];
			$this->settings['public_key'] = ($this->settings['testmode'] == 'yes')?$this->settings['test_public_key']:$this->settings['live_public_key'];
			$this->settings['secret_key'] = ($this->settings['testmode'] == 'yes')?$this->settings['test_secret_key']:$this->settings['live_secret_key'];
		}
		if ($key !== false) {
			return isset($this->settings[$key])?$this->settings[$key]:$settings;
		}
		return $this->settings;
		return [$settings, ...$this->settings];
	}
	public function wooflutter_payment_flutterwave_status($type, $transaction_id, $payment_status, $tx_ref) {
		if ($type !== 'wc') {return;}
		global $WooFlutter_Flutterwave;global $trxStatus;
		$settings = WOOFLUTTER_OPTIONS;global $pageTitle;
		$order_id = wc_get_order_id_by_order_key(substr($tx_ref, 3));
		$order = wc_get_order($order_id);

		$payment_gateway    = WC()->payment_gateways->payment_gateways();
		$settings = isset($payment_gateway['flutterwave'])?$payment_gateway['flutterwave']->settings:false;
		if ($settings) {
			$settings = (object) $settings;
			$settings->testmode = $settings->testmode == 'yes';
			$settings->public_key = $settings->testmode ? $settings->test_public_key : $settings->live_public_key;
			$settings->secret_key = $settings->testmode ? $settings->test_secret_key : $settings->live_secret_key;
			$WooFlutter_Flutterwave->set_api_key($settings);
		}
		$trxInfo = apply_filters('wooflutter/project/payment/flutterwave/info', $transaction_id);
		$trxStatus = (isset($trxInfo['data']) && isset($trxInfo['data']['status']))?$trxInfo['data']['status']:false;
		$isVerified = $trxStatus == $payment_status;
		$isSuccessful = $payment_status == 'successful';
		if ($isSuccessful) {
			$order->update_status('processing');
			$order->add_order_note(
				sprintf(
					__('Payment marked as Successful on %s Using %s, Transection ID #%s. Order status chaged to %s.', 'wooflutter'),
					wp_date('M d, Y H:i'),
					__('Flutterwave', 'wooflutter'),
					$transaction_id,
					'processing'
				)
			);
		}

		$order->update_meta_data('_flutterwave_trx_info', $trxInfo);
		$order->set_transaction_id($transaction_id);
		$order->save();
		switch ($payment_status) {
			case 'successful':
				$_messageIcon = 'fa-check-circle';
				$_messageClass = '_success';
				break;
			default:
				$_messageIcon = 'fa-times-circle';
				$_messageClass = '_failed';
				break;
		}
		/*
		add_filter( 'wp_title', function($title) {
			global $trxStatus;global $pageTitle;
			$pageTitle = $title;
			switch ($trxStatus) {
				case 'successful':
					$pageTitle = $title = __('Payment successful', 'domain');
					break;
				default:
					$pageTitle = $title = __('Payment failed', 'domain');
					break;
			}
			return $title;
		}, 10, 1);
		*/
		if ($isVerified || true) {
		} else {
			wp_die(__('Something went wrong!', 'wooflutter'));
		}
	}
	public function wooflutter_payment_flutterwave_status_back2text($text, $type, $transaction_id, $payment_status, $tx_ref) {
		if ($type !== 'wc') {return $text;}
		return false;
	}
	public function wooflutter_payment_flutterwave_status_back2link($link, $type, $transaction_id, $payment_status, $tx_ref) {
		if ($type !== 'wc') {return $link;}
		return false;
	}
	public function wooflutter_payment_flutterwave_status_retry($retry, $type, $transaction_id, $payment_status, $tx_ref) {
		if ($type !== 'wc') {return $retry;}
		return false;
	}

	

    /**
     * Function to run before woocommerce init
     */
    public function before_woocommerce_init() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'cart_checkout_blocks',
                    __FILE__,
                    false // true (compatible, default) or false (not compatible)
                );
        }
    }
    /**
     * Load Payment gateway scripts after plugins loaded.
     */
    public function load_flutterwave_gateway() {
        include_once(WOOFLUTTER_DIR_PATH . '/inc/widgets/class-wc-gateway-flutter.php');
    }
	/**
	 * Block support PHP class
	 */
	public function woocommerce_blocks_loaded() {
		if(!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {return;}
		// here we're including our "gateway block support class"
		require_once WOOFLUTTER_DIR_PATH . '/inc/blocks/class-wc-flutterwave-gateway-blocks-support.php';
		// registering the PHP class we have just included
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new Flutterwave_Gateway_Blocks_Support );

				// $payment_method_registry->register(
                //     \Automattic\WooCommerce\Blocks\Package::container()->get(
                //         Flutterwave_Gateway_Blocks_Support::class
                //     )
                // );
			}
		);
	}
    /**
     * Add Flutterwave Payment gateway
     */
    public function add_flutterwave_gateway($gateways) {
        $gateways[] = 'WOOFLUTTER\Inc\WC_Gateway_Flutter';
        // print_r($gateways);wp_die();
        return $gateways;
    }
    public function add_flutterwave_settings_tab($settings_tabs) {
        $settings_tabs['flutterwave'] = 'Flutterwave';
        return $settings_tabs;
    }
    public function output_flutterwave_settings() {
        // Output the settings fields for the Flutterwave payment gateway
        // Include fields for pausing, unpausing, and setting up the gateway options
    }
    public static function register_payment_block_template($templates) {
        // Register your custom payment method block template file
        $templates['FlutterWave'] = WOOFLUTTER_DIR_PATH . '/inc/blocks/FlutterWave.php';
        // print_r($templates);wp_die();
        return $templates;
    }
    
	
	/**
	 * Enqueue frontend Styles.
	 * @return null
	 */
	public function register_styles() {
		global $WooFlutter_Assets;
		wp_enqueue_style('woo-public', WOOFLUTTER_BUILD_CSS_URI . '/woo_public.css', [], $WooFlutter_Assets->filemtime(WOOFLUTTER_BUILD_CSS_DIR_PATH . '/woo_public.css'), 'all');
	}
	/**
	 * Enqueue frontend Scripts.
	 * @return null
	 */
	public function register_scripts() {
		global $WooFlutter_Assets;
		wp_enqueue_script('woo-public', WOOFLUTTER_BUILD_JS_URI . '/woo_public.js', [], $WooFlutter_Assets->filemtime(WOOFLUTTER_BUILD_JS_DIR_PATH . '/woo_public.js'), true);
	}
	/**
	 * Enqueue backend Scripts and stylesheet.
	 * @return null
	 */
	public function admin_enqueue_scripts($curr_page) {
	}

}
