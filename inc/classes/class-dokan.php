<?php
/**
 * Flutterwave Payment Request Handler
 * 
 * @link https://github.com/woocommerce/woocommerce-gateway-stripe/pull/1467/files
 * @link https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#client-side-integration
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class Dokan {
	use Singleton;
    private $id = 'flutterwave';
	protected function __construct() {
        $this->setup_hooks();
	}
	public function setup_hooks() {
        add_action('plugins_loaded', [$this, 'load_dokan_custom_payment_method'], 10, 0);
        add_filter('dokan_payment_gateways', [$this, 'dokan_payment_gateways'], 10, 1);
        add_filter('dokan_withdraw_methods', [$this, 'dokan_withdraw_methods'], 10, 1);
        add_filter('dokan_withdraw_method_icon', [$this, 'dokan_withdraw_method_icon'], 10, 2);
        add_filter('dokan_withdraw_method_additional_info', [$this, 'dokan_withdraw_method_additional_info'], 10, 2);
        add_filter('dokan_get_active_withdraw_methods', [$this, 'dokan_get_active_withdraw_methods'], 10, 1);
        add_filter('dokan_get_seller_active_withdraw_methods', [$this, 'dokan_get_seller_active_withdraw_methods'], 10, 2);
        add_filter('dokan_get_withdraw_method_title', [$this, 'dokan_get_withdraw_method_title'], 10, 3);
        add_filter('dokan_withdraw_method_settings_title', [$this, 'dokan_withdraw_method_settings_title'], 10, 2);
        add_filter('dokan_withdraw_withdrawable_payment_methods', [$this, 'dokan_withdraw_withdrawable_payment_methods'], 10, 1);
        add_filter('dokan_store_profile_settings_args', [$this, 'dokan_store_profile_settings_args'], 10, 2);
        add_filter('dokan_is_seller_connected_to_payment_method', [$this, 'dokan_is_seller_connected_to_payment_method'], 10, 3);
        add_filter('dokan_payment_settings_required_fields', [$this, 'dokan_payment_settings_required_fields'], 10, 3);
        add_filter('dokan_withdraw_request_details_data', [$this, 'dokan_withdraw_request_details_data'], 10, 2);
        add_filter('dokan_withdraw_is_valid_request', [$this, 'dokan_withdraw_is_valid_request'], 10, 2);

        add_action('dokan_store_profile_saved', [$this, 'dokan_store_profile_saved'], 10, 2);
        // add_action('dokan_withdraw_request_approved', [$this, 'dokan_withdraw_request_approved'], 10, 1);
    }
    /**
     * Loaded dokan custom payment method after loaded all plugins.
     * 
     * @since 3.7.10
     *
     * @param array $gateways Gateways since added.
     *
     * @return array
     */
    public function load_dokan_custom_payment_method() {
        require_once(WOOFLUTTER_DIR_PATH . '/inc/widgets/class-dokan-gateway-flutter.php');
    }
    /**
     * Added custom payment gateways with initating and returned an array pushing it in.
     *
     * @since 3.7.10
     *
     * @param array $gateways Gateways since added.
     *
     * @return array
     */
    public function dokan_payment_gateways($gateways = []) {
        $gateways[] = new Dokan_Gateway_Flutterwave();
        return $gateways;
    }
    /**
     * Added Flutterwave withdrow payment method.
     *
     * @since 3.7.10
     *
     * @param array $methods Methods where to push.
     *
     * @return array
     */
    public function dokan_withdraw_methods($methods = []) {
        $methods[$this->id] = [
            'title'        => __('Flutterwave', 'wooflutter'),
            'callback'     => [$this, 'dokan_withdraw_method_flutterwave'],
            'apply_charge' => true,
        ];
        return $methods;
    }
    /**
     * Callback for Flutterwave in store settings.
     *
     * @since 3.7.10
     *
     * @param array $store_settings Store settings object.
     *
     * @return string|void
     */
    public function dokan_withdraw_method_flutterwave($store_settings) {
        $email = isset($store_settings['payment'][$this->id]['email']) ? esc_attr($store_settings['payment'][$this->id]['email']) : '';
        include apply_filters('dokan_withdraw_method_flutterwave_template', WOOFLUTTER_DIR_PATH . '/templates/payments/vendor-settings.php', $store_settings);
    }
    /**
     * Get Flutterwave method icon.
     *
     * @since 3.7.10
     *
     * @param array $method_icon Method icon to be returned with.
     * @param array $method_key Method ID to be sorted with.
     *
     * @return string method icon url
     */
    public function dokan_withdraw_method_icon($method_icon, $method_key) {
        if ($method_key == $this->id) {
            $method_icon = WOOFLUTTER_BUILD_URI . '/icons/flutterwave.svg';
        }
        return $method_icon;
    }
    /**
     * Get Flutterwave method icon.
     *
     * @since 3.7.10
     *
     * @param array $method_info Method icon to be returned with.
     * @param array $method_key Method ID to be sorted with.
     *
     * @return string method info string
     */
    public function dokan_withdraw_method_additional_info($method_info, $method_key) {
        if ($method_key == $this->id) {
            $method_info = sprintf(
                __('%s Gateway', 'wooflutter'),
                'Flutterwave'
           );
        }
        return $method_info;
    }
    /**
     * Here the ability to get ride on active withdraw methods.
     * 
     * @since 3.7.10
     *
     * @param array $methods Methods array list.
     *
     * @return array Active methods
     */
    public function dokan_get_active_withdraw_methods($methods) {
        // print_r($methods);wp_die();
        return $methods;
    }
    /**
     * Here the ability to get ride on seller/vendor active withdrawal methods.
     * 
     * @since 3.7.10
     *
     * @param array $methods Methods array list.
     *
     * @return array Active methods
     */
    public function dokan_get_seller_active_withdraw_methods($active_payment_methods, $vendor_id) {
        $vendor_id          = ($vendor_id)?$vendor_id:dokan_get_current_user_id();
        $payment_methods    = get_user_meta($vendor_id, 'dokan_profile_settings', true);
        $required_fields    = (array) apply_filters('dokan_payment_settings_required_fields', [], $this->id, $vendor_id);
        $is_active          = true;
        foreach ($required_fields as $field_key) {
            if (
                !isset($payment_methods['payment'][$this->id]) || !isset($payment_methods['payment'][$this->id][$field_key]) || empty($payment_methods['payment'][$this->id][$field_key])
            ) {
                $is_active = false;
            }
        }
        if ($is_active) {
            $active_payment_methods[] = $this->id;
            // array_push($active_payment_methods, $this->id);
        }
        
        return $active_payment_methods;
    }
    /**
     * Here the ability to get ride on seller/vendor active withdrawal methods.
     * 
     * @since 3.7.10
     *
     * @param array $methods Methods array list.
     *
     * @return array Active methods
     */
    public function dokan_get_withdraw_method_title($title, $method_key, $request) {
        return $title;
    }
    /**
     * Here the ability to sefine flutterwave payment method settings title.
     * 
     * @since 3.4.3
     *
     * @param string $heading Heading text on settings screen.
     * @param string $slug Slug text as the id of payment method.
     *
     * @return string Flutterwave heading, or default heading.
     */
    public function dokan_withdraw_method_settings_title($heading, $slug) {
        if ($slug == $this->id) {
            $heading = __('Flutterwave Payment Settings', 'wooflutter');
        }
        return $heading;
    }
    /**
     * Here the ability to get ride on seller/vendor active withdrawable payment methods ID as array.
     * 
     * @since 3.7.10
     *
     * @param array $methods Withdrawable Methods array list.
     *
     * @return array Active & withdrawable methods
     */
    public function dokan_withdraw_withdrawable_payment_methods($methods) {
        if (!isset($methods[$this->id])) {
            $methods[] = $this->id;
        }
        return $methods;
    }
    /**
     * Here the ability to get ride on seller/vendor active withdrawable payment methods ID as array.
     * 
     * @since 3.7.10
     *
     * @param array $methods Withdrawable Methods array list.
     *
     * @return array Active & withdrawable methods
     */
    public function dokan_store_profile_settings_args($dokan_settings, $store_id) {
        if (isset($_POST['settings'][$this->id]) && !empty($_POST['settings'][$this->id])) {
            $dokan_settings['payment'] = isset($dokan_settings['payment'])?(array) $dokan_settings['payment']:[];
            // Sanitized this array recrusively
            $bank = wc_clean(wp_unslash($_POST['settings'][$this->id]));
            
            $dokan_settings['payment'][$this->id] = [
                'test_mode'             => $bank['test_mode']??false,
                'live_public_key'       => $bank['live_public_key'],
                'live_secret_key'       => $bank['live_secret_key'],
                'live_encript_key'      => $bank['live_encript_key'],
                'test_public_key'       => $bank['test_public_key'],
                'test_secret_key'       => $bank['test_secret_key'],
                'account_bank'          => $bank['account_bank'],
                'account_number'        => $bank['account_number'],
                'split_accounts'        => $bank['split_accounts']??[],
                'declaration'           => $bank['declaration']??'',
            ];
        }
        return $dokan_settings;
    }
    /**
     * Here the ability to give the required fields array to verify. if required fields empty or undefuned, then it will maked as disconnected.
     * 
     * @since 3.7.10
     *
     * @param array $required_fields Fields required.
     * @param string $payment_method_id Payment method ID.
     * @param int $seller_id Seller account ID.
     *
     * @return array all required fields as array.
     */
    public function dokan_payment_settings_required_fields($required_fields, $payment_method_id, $seller_id) {
        if ($payment_method_id == $this->id) {
            $required_fields[] = 'live_secret_key';
            $required_fields[] = 'test_secret_key';
        }
        return (array) $required_fields;
    }
    /**
     * Get if user with id $seller_id is connected to the payment method having $payment_method_id
     * 
     * @since 3.7.10
     *
     * @param bool $is_connected Whether if it is connected or not.
     * @param string $payment_method_id Paymen tmethod ID.
     * @param int $seller_id Seller account ID.
     *
     * @return bool returned bool conditionally
     */
    public function dokan_is_seller_connected_to_payment_method($is_connected, $payment_method_id, $seller_id) {
        // if ($payment_method_id == $this->id) {
        //     $is_connected = true;
        // }
        return $is_connected;
    }
    /**
     * To pass withdraw request custom payment gateway data if necessery. Probably not necessery.
     * 
     * @since 3.7.10
     *
     * @param array $details is an array of al details of withdrow request.
     * @param object|Withdraw $withdraw Object of an instance of WeDevs\Dokan\Withdraw class
     *
     * @return array returned array of details
     */
    public function dokan_withdraw_request_details_data($details, $withdraw) {
        foreach ($details as $key => $value) {
            if (in_array($key, ['live_public_key', 'live_secret_key', 'live_encript_key', 'test_public_key', 'test_secret_key'])) {
                unset($details[$key]);
            }
        }
        return $details;
    }
    /**
     * Function to execute payment withdraw completion
     * 
     * @since 3.7.10
     *
     * @param object|Withdraw $withdraw Object of an instance of WeDevs\Dokan\Withdraw class
     *
     * @return array returned array of details
     */
    public function dokan_withdraw_is_valid_request($to_continue, $args) {
        global $WooFlutter_Flutterwave;
        if (isset($args['method']) && $args['method'] == $this->id && isset($args['id']) && $args['id'] > 0 && isset($_REQUEST['status']) && $_REQUEST['status'] == 'approved') {
            $currency = isset($args['currency'])?strtoupper($args['currency']):'NGN';
            if ($args['amount'] < 50) {
                return new \WP_Error('dokan_withdraw_insufficient_amount', sprintf(
                    __('Insufficient amount requested. Minimum amount to transfer is %d.', 'domain'),
                    50
                ));
            }
            // Setup api keys on flutterwave instance
            $WooFlutter_Flutterwave->set_api_key(false);
            $balance = $WooFlutter_Flutterwave->balances($currency);
            if (!$balance || is_wp_error($balance)) {
                return new \WP_Error('dokan_withdraw_invalid_balance', __('Error while trying to get account balance. Please contact to the support or developer.', 'domain'));
            }
            if (isset($balance['available_balance']) && $balance['available_balance'] < $args['amount']) {
                return new \WP_Error('dokan_withdraw_insufficient_balance', sprintf(
                    __('Insufficient balance. Minimum balance to transfer is %s. Your currenct account balance is %s', 'domain'),
                    number_format_i18n(50, 3), number_format_i18n((float) $balance['available_balance'], 3)
                ));
            }
            
            
            // 
            $userArgs = get_user_meta($args['user_id'], 'dokan_profile_settings', true);
            $seller = ($userArgs && isset($userArgs['payment']) && isset($userArgs['payment'][$this->id]))?(array) $userArgs['payment'][$this->id]:[];
            // 
            // print_r($seller);wp_die('Remal mahmud (mahmudremal@yahoo.com)', 'Development');
            // 
            $payOut = [
                'account_bank' => $seller['account_bank']??'',
                'account_number' => $seller['account_number']??'',
                'amount' => (int) $args['amount'] * 100,
                'narration' => (isset($args['note']) && !empty(trim($args['note'])))?$args['note']:__('N/A', 'wooflutter'),
                'currency' => $currency,
                'reference' => sprintf('withdraw id %d', $args['id']),
                'debit_currency' => $currency
            ];
            $transfer = $WooFlutter_Flutterwave->transfer($payOut);
            if ($transfer && isset($transfer['status']) && $transfer['status'] == 'success') {
                return $to_continue;
            }
            return new \WP_Error('dokan_withdraw_failed_transfer', __('Transfer request failed', 'domain'));
        }
        return $to_continue;
    }
    public function dokan_store_profile_saved($store_id, $dokan_settings) {
        // if ( isset( $_POST['settings']['custom']['value'] ) ) {
        //     $dokan_settings['payment']['custom'] = array(
        //         'value' => sanitize_text_field( $_POST['settings']['custom']['value'] ),
        //     );
        // }
        // update_user_meta( $store_id, 'dokan_profile_settings', $dokan_settings );
    }
    /**
     * Fired after withdraw approve request triggired.
     * 
     * @param Object|Withdraw $withdraw Instance of dokan Withdraw class
     * 
     * @return null;
     */
    public function dokan_withdraw_request_approved($withdraw) {}
    // 
}
