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
use \WP_Query;

class Flutterwave {
	use Singleton;
	private $theTable;
	private $productID;
	private $settings;
	private $lastResult;
	private $userInfo;
	private $successUrl;
	private $cancelUrl;
	private $api_key; // Replace with your Flutterwave API key
	private $encryptionKey; // Replace with your Flutterwave API key
    private $base_url = 'https://api.flutterwave.com/v3';
    private $is_test_mode;
	
	protected function __construct() {
        $this->settings = WOOFLUTTER_OPTIONS;
		$this->api_key  = isset($this->settings['secretkey'])?$this->settings['secretkey']:false;
		$this->encryptionKey  = isset($this->settings['encryptionkey'])?$this->settings['encryptionkey']:false;
        $this->is_test_mode = true; // WOOFLUTTER_TEST_MODE;

		add_action('init', [ $this, 'on_init' ], 1, 0);

        add_filter('wooflutter/project/payment/getallsubaccounts', [$this, 'getAllSubAccounts'], 10, 0);
        
		add_filter('wooflutter/project/rewrite/rules', [ $this, 'rewriteRules' ], 10, 1);
		add_filter('query_vars', [ $this, 'query_vars' ], 10, 1);
		add_filter('template_include', [ $this, 'template_include' ], 10, 1);
		// add_filter('wooflutter/project/payment/stripe/handle/status', [$this, 'handleStatus'], 10, 3);
		add_filter('wooflutter/project/payment/flutterwave/verify', [$this, 'verify'], 10, 2);
		add_filter('wooflutter/project/payment/flutterwave/info', [$this, 'info'], 10, 1);

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
	}
	public function on_init() {
		global $wpdb;$this->theTable				= $wpdb->prefix . 'fwp_flutterwave_subscriptions';
        $this->settings                             = apply_filters('wooflutter/wc/get/settings', [], false);
		$this->productID							= 'prod_NJlPpW2S6i75vM';
		$this->lastResult							= false;$this->userInfo = false;
		$this->successUrl							= site_url('payment/flutterwave/{CHECKOUT_SESSION_ID}/success');
		$this->cancelUrl							= site_url('payment/flutterwave/{CHECKOUT_SESSION_ID}/cancel');

    }
    public function set_api_key($widget) {
        if ($widget === false) {
            $widget = wp_parse_args(apply_filters('wooflutter/wc/get/settings', [], false), [
                'testmode'          => 'no',
                'test_secret_key'   => '',
                'live_secret_key'   => '',
                'live_encript_key'  => '',
            ]);
            $isTestMode = ($widget['testmode'] == 'yes');
            $widget['secret_key'] = $isTestMode?$widget['test_secret_key']:$widget['live_secret_key'];
            $widget['live_encript_key'] = $isTestMode?$widget['live_encript_key']:$widget['live_encript_key'];
            $widget = (object) $widget;
        }
        // $this->api_key = $widget->public_key;
        $this->api_key = $widget->secret_key;
        $this->encryptionKey = $widget->live_encript_key;
    }
    public function execute($endpoint, $args = false) {
        $url = "{$this->base_url}/{$endpoint}";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->api_key}"
        ]);
        if ($args && !empty($args)) {
            $data_string = json_encode($args);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        }
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            throw new \Exception(__('Connection error', 'domain'), 1);
        } else {
            $response = json_decode($response, true);
            if (!$response) {
                throw new \Exception(__('Invalid gateway response', 'domain'), 1);
            }
            if ($response && isset($response['status'])) {
                if ($response['status'] != 'success' && isset($response['message'])) {
                    throw new \Exception($response['message'], 1);
                }
            }
            return $response;
        }
    }
	public function getToken() {
        // Check if a token is already stored in the database or cache
        $token = $this->getStoredToken();

        // If no token is stored or token is expired, generate or refresh a new token
        if (!$token || $this->isTokenExpired($token)) {
            $token = $this->generateToken();
        }

        return $token;
    }
    private function getStoredToken() {
        // Retrieve the stored token from the database or cache
        // Replace with your own implementation based on your storage mechanism
        $stored_token = null;
		$stored_token = get_option('flutterwave_last_token', false);
		$this->last_stored = $stored_token['time'];
		$stored_token = $stored_token['token'];
        // Retrieve the token and return it
        return $stored_token;
    }
    private function isTokenExpired($token) {
        // Check if the token has expired
        // Replace with your own implementation based on token expiration logic
        $expired = (strtotime('+24 hours', $this->last_stored) >= time());
        // Perform the expiration check and return the result
        return $expired;
    }
    private function generateToken() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "{$this->base_url}/token/create");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->api_key}"
       ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // print_r($response);

        if ($err) {
            // Handle error case
            return null;
        } else {
            $token = json_decode($response, true);

            // Store the token in the database or cache for future use
            $this->storeToken($token);

            return $token;
        }
    }
    private function storeToken($token) {
        // Store the token in the database or cache for future use
        // Replace with your own implementation based on your storage mechanism
		$token = ['time'=>time(), 'token'=> $token];
		update_option('flutterwave_last_token', $token, true);
    }



    public function createPayment($args) {
        $args = wp_parse_args($args, [
            // 'txref' => '',
            // 'amount' => '',
            // 'currency' => '',
            'redirect_url' => site_url('/payment/flutterwave/'.$args['tx_ref'].'/status/'),
            // 'customer_info' => [
            //     'email' => '',
            //     // 'customer_email' => '',
			// 	'customer_name' => '',
			// 	'customer_phone' => ''
            // ],
            // 'payment_options' => [
            //     'card' => '1',
            //     'mobile_money' => '1',
            //     'bank_transfer' => '1',
            //     'ussd' => '1',
            //     'qr' => '1',
            //     'barter' => '1',
            //     'bank_account' => '1',
            //     'credit' => '1',
            //     'debit' => '1',
            //     'transfer' => '1'
            // ]
        ]);
        if (isset($args['customer_info'])) {
            $args['customer_info']['email'] = ($args['customer_info']['email'] == '')?get_bloginfo('admin_email'):$args['customer_info']['email'];
        }
        try {
            $response = $this->execute("payments", $args);
            if ($response && isset($response['status'])) {
                if ($response['status'] == 'success') {
                    return (isset($response['data']) && isset($response['data']['link']))?$response['data']['link']:false;
                }
            }
            throw new \Exception(__('Error getting payment intend link.', 'domain'), 1);
        } catch (\Exception $th) {
            //throw $th;
            return false;
        }
    }
    public function createSplitPayment($txref, $amount, $currency, $redirect_url, $customer_info, $sub_account_id, $sub_account_amount) {
        $url = "{$this->base_url}/payments";
    
        $data = array(
            "tx_ref" => $txref,// transaction reference
            "amount" => $amount,
            "currency" => $currency,
            "redirect_url" => $redirect_url,
            "customer" => $customer_info,
            "subaccounts" => [
                [
                    "id" => $sub_account_id,
                    "transaction_charge_type" => "flat_subaccount",
                    "transaction_charge" => $sub_account_amount
                ]
            ]
       );
    
        $data_string = json_encode($data);
    
        $curl = curl_init();
    
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->api_key}"
       ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            // Handle error case
            return null;
        } else {
            $payment_request = json_decode($response, true);

            // Process the payment request and return the result
            return $payment_request;
        }
    }
    public function processCardPayment($args) {
        if(!isset($args['client']) || empty($args['client'])) {
            $args = wp_parse_args($args, [
                'tx_ref'            => '',
                'name'              => 'N/A',
                'amount'            => '',
                'currency'          => 'NGN',
                'customer_email'    => get_bloginfo('admin_email'),
                'redirect_url'      => site_url('/payment/flutterwave/'.$args['tx_ref'].'/status/'),
                
                'card_number'      => '',
                'expiry_month'     => '',
                'expiry_year'      => '',
                'cvv'              => '',
                'otp'              => '',
                'subaccounts'       => [],
            ]);
        
            $chargeData = [
                "tx_ref" => $args['tx_ref'],
                "amount" => $args['amount'],
                "currency" => $args['currency'],
                "email" => $args['customer_email'],
                "fullname" => $args['name'],

                "card_number" => $args['card_number'],
                "cvv" => $args['cvv'],
                "expiry_month" => $args['expiry_month'],
                "expiry_year" => $args['expiry_year'],

                "redirect_url" => $args['redirect_url']
            ];
            if(isset($args['subaccounts']) &&count($args['subaccounts'])>=1) {
                $chargeData['subaccounts'] = $args['subaccounts'];
            }
        }
        // Step 1: Charge the card to get the OTP prompt
        $chargeUrl = "{$this->base_url}/charges?type=card";
        $chargeHeaders = [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->api_key}"
        ];
        // $chargePayload = $this->encryptPayload(json_encode($chargeData));
        $chargePayload = ['client' => $args['client']];
        $chargeCh = curl_init();
        curl_setopt($chargeCh, CURLOPT_URL, $chargeUrl);
        curl_setopt($chargeCh, CURLOPT_POST, true);
        curl_setopt($chargeCh, CURLOPT_POSTFIELDS, $chargePayload);
        curl_setopt($chargeCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chargeCh, CURLOPT_HTTPHEADER, $chargeHeaders);
        $chargeResult = curl_exec($chargeCh);
        curl_close($chargeCh);

        // print_r($chargeResult);wp_die();
        
        if(curl_errno($chargeCh)) {throw new \Exception('Communication Error: ' . curl_error($chargeCh));}
        // if(curl_getinfo($chargeCh, CURLINFO_HTTP_CODE) !== 200) {throw new \Exception($chargeResult);}
        $chargeResponse = json_decode($chargeResult, true);
        if(isset($chargeResponse['error_id'])) {throw new \Exception('Flutterwave ' . $chargeResponse['message']);}
        if($chargeResponse['status'] == 'success') {
            return $chargeResponse;
        } else {
            throw new \Exception('Something error happens while tring to issue this card.');
        }
    }
    public function processCardVerify($args) {
        // Step 2: Submit the OTP for payment authorization
        $otpUrl = "{$this->base_url}/validate-charge";
        $otpData = array(
            "otp" => $args['otp'],
            "flw_ref" => $args['flw_ref']
       );
        $otpHeaders = array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->api_key}",
       );
        $otpPayload = json_encode($otpData);
        $otpCh = curl_init();
        curl_setopt($otpCh, CURLOPT_URL, $otpUrl);
        curl_setopt($otpCh, CURLOPT_POST, true);
        curl_setopt($otpCh, CURLOPT_POSTFIELDS, $otpPayload);
        curl_setopt($otpCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($otpCh, CURLOPT_HTTPHEADER, $otpHeaders);
        $otpResult = curl_exec($otpCh);
        curl_close($otpCh);
        if (curl_errno($otpCh)) {throw new \Exception('Communication Error: ' . curl_error($otpCh));}
        // if(curl_getinfo($otpCh, CURLINFO_HTTP_CODE) !== 200) {throw new \Exception('Payment Failed: ' . $otpResult);}
        $otpResult = json_decode($otpResult, true);
        if($otpResult['status'] == 'error') {throw new \Exception($otpResult['message']);}
        // if($otpResult['status'] == 'success') {
        //     $otpUrl = "{$this->base_url}/transactions/{$otpResult['data']['transaction_id']}/verify";
        //     $otpData = ["transaction_id" => $otpResult['data']['transaction_id']];
        //     $otpPayload = json_encode($otpData);
        //     $otpCh = curl_init();
        //     curl_setopt($otpCh, CURLOPT_URL, $otpUrl);
        //     curl_setopt($otpCh, CURLOPT_POST, true);
        //     curl_setopt($otpCh, CURLOPT_POSTFIELDS, $otpPayload);
        //     curl_setopt($otpCh, CURLOPT_RETURNTRANSFER, true);
        //     curl_setopt($otpCh, CURLOPT_HTTPHEADER, $otpHeaders);
        //     $otpResult = curl_exec($otpCh);
        //     curl_close($otpCh);
        // }
        return $otpResult;
    }
    public function encryptPayload($payload) {
        $iv = openssl_random_pseudo_bytes(8);
        $encryptedPayload = openssl_encrypt($payload, 'DES-EDE3', $this->encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedPayload = base64_encode($encryptedPayload);
        $encryptedPayload = bin2hex($iv) . $encryptedPayload;
        return $encryptedPayload;
    }
    public static function register_payment_block_template($templates) {
        // Register your custom payment method block template file
        $templates['FlutterWave'] = WOOFLUTTER_DIR_PATH . '/inc/blocks/FlutterWave.php';
        // print_r($templates);wp_die();
        return $templates;
    }
    

    public function getAllSubAccounts() {
        $url = "{$this->base_url}/subaccounts";
        if(!$this->is_test_mode) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer {$this->api_key}"
                ]
            ]);
            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            $result = json_decode($response, true);

            if (curl_errno($curl)) {throw new \Exception('Communication Error: '.curl_error($curl));}
            if(isset($result['status']) && $result['status']=='error') {throw new \Exception('Flutterwave '. $result['message']);}
            
            if($result && isset($result['data']) && isset($result['status']) && $result['status'] == 'success') {
                return $result['data'];
            }
            return [];
        } else {
            $error = false;
            $response = '{"status":"success","message":"Subaccounts fetched","meta":{"page_info":{"total":6,"current_page":1,"total_pages":1}},"data":[{"id":12121,"account_number":"0047826178","account_bank":"044","business_name":"Olusetire Mayowa","full_name":"OLUSETIRE JOHN OLUMAYOWA","created_at":"2020-05-18T16:39:32.000Z","meta":[{"swift_code":""}],"account_id":128989,"split_ratio":1,"split_type":"percentage","split_value":0.115,"subaccount_id":"RS_4283E678FFC8F333938A4F0D753B6DC0","bank_name":"ACCESS BANK NIGERIA","country":"NG"},{"id":6270,"account_number":"0000745342","account_bank":"058","business_name":"Association of Telecoms Companies of Nigeria","full_name":"ASS OF TELECOMM CO OF NIGERIA","created_at":"2020-02-21T14:09:21.000Z","meta":[{"swift_code":""}],"account_id":97479,"split_ratio":1,"split_type":"percentage","split_value":0.025,"subaccount_id":"RS_A66BD37EFD525CE91C5B5EF2F6404873","bank_name":"GTBANK PLC","country":"NG"},{"id":6269,"account_number":"0599948014","account_bank":"214","business_name":"Ikeja Golf Club Bar","full_name":"IKEJA GOLF CLUB","created_at":"2020-02-21T14:01:04.000Z","meta":[{"swift_code":""}],"account_id":97477,"split_ratio":1,"split_type":"percentage","split_value":0.025,"subaccount_id":"RS_8C5B213F80BFE1EF65279F2790C963FE","bank_name":"FIRST CITY MONUMENT BANK PLC","country":"NG"},{"id":6268,"account_number":"2122011891","account_bank":"050","business_name":"Ikeja Golf Club Office","full_name":"IKEJA GOLF CLUB","created_at":"2020-02-21T13:57:46.000Z","meta":[{"swift_code":""}],"account_id":97476,"split_ratio":1,"split_type":"percentage","split_value":0.025,"subaccount_id":"RS_1D3B547192398961C575B7885981553A","bank_name":"ECOBANK NIGERIA LIMITED","country":"NG"},{"id":6267,"account_number":"0007314334","account_bank":"058","business_name":"Howson Wright Estate","full_name":"HOWSON-WRIGHT EST.RESIDENT ASS","created_at":"2020-02-21T13:13:34.000Z","meta":[{"swift_code":""}],"account_id":97470,"split_ratio":1,"split_type":"percentage","split_value":0.025,"subaccount_id":"RS_21573CD8AA0F96BFFC3FECA2C04B9C2F","bank_name":"GTBANK PLC","country":"NG"},{"id":5493,"account_number":"9200181686","account_bank":"221","business_name":"DigiServe Paypoint","full_name":"OLANREWAJU PETER AJAYI","created_at":"2019-11-20T14:48:09.000Z","meta":[{"swift_code":""},{},{},{},{},{},{},{},{}],"account_id":87432,"split_ratio":1,"split_type":"percentage","split_value":0.035,"subaccount_id":"RS_53C41E2945EC5D2A8FA4DE1DD66C0509","bank_name":"STANBIC IBTC BANK PLC","country":"NG"}]}';
            $response = json_decode($response, true);
            return $response['data'];
        }
    }


    public function refund($transaction_id, $amount) {
        $url = "{$this->base_url}/transactions/{$transaction_id}/refund";

        $data = array(
            "amount" => $amount
       );

        $data_string = json_encode($data);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->api_key}"
       ));

        $response = curl_exec($curl);
        curl_close($curl);

        if(curl_error($curl) || curl_errno($curl)) {throw new \Exception('Communication Error: ' . curl_error($curl));}
        // if(curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {throw new \Exception('Payment Failed: ' . $response);}
        $refund_status = json_decode($response, true);
        if($refund_status['status'] === 'error') {
            throw new \Exception('Flutterwave '. $refund_status['message']);
        } else {
            return $refund_status;
        }
    }
    /**
     * Get the payment intend Info which would be called directly from flutterwave server.
     * 
     * @param string $transaction_id The transection ID of a payment intend
     * 
     * @return object Return an object value of a transection.
     * @return bool Return an false if transection not found of any error happens.
     */
    public function info($transaction_id) {
        try {
            $response = $this->execute("transactions/{$transaction_id}/verify", $args);
            if ($response && isset($response['status'])) {
                if ($response['status'] == 'success') {
                    return (array) $response['data'];
                }
            }
            throw new \Exception(__('Error getting transection info.', 'domain'), 1);
        } catch (\Exception $th) {
            //throw $th;
            return false;
        }
    }
    /**
     * Get the status of a pyment intend ofg a transection.
     * 
     * @param string $transaction_id The transection ID of a payment intend
     * @param string $status The status text to verify is it True of Not.
     * 
     * @return bool Return true of false of a status against the transection.
     */
	public function verify($transaction_id, $status) {
		return ($this->status($transaction_id) == $status);
	}
    /**
     * Get the status of a transection by transection ID.
     * 
     * @param string $transaction_id The transection ID of a payment intend
     * 
     * @return string String value of the payment status
     */
	public function status($transaction_id) {
        $info = $this->info($transaction_id);
        // print_r([$info, $this->api_key]);
        return ($info && isset($info['status']))?$info['status']:false;
    }
	
	public function rewriteRules($rules) {
		$rules[] = [ 'payment/flutterwave/([^/]*)/([^/]*)/?', 'index.php?transaction_id=$matches[1]&payment_status=$matches[2]', 'top' ];
		return $rules;
	}
	public function query_vars($query_vars) {
		$query_vars[] = 'status';
		$query_vars[] = 'tx_ref';
		$query_vars[] = 'transaction_id';
		$query_vars[] = 'payment_status';
    	return $query_vars;
	}
	public function template_include($template) {
		$transaction_id		= (get_query_var('transaction_id') != '')?get_query_var('transaction_id'):get_query_var('tx_ref');
		$payment_status		= (get_query_var('payment_status') != '')?get_query_var('payment_status'):get_query_var('status');
		$file				= WOOFLUTTER_DIR_PATH . '/templates/payments/flutterwave.php';
        
        // return $template;
        // $payment_status&&!empty($payment_status)&&
		if($transaction_id && file_exists($file)&& !is_dir($file)) {
			return $file;
		} else {
			return $template;
		}
	}
	public function handleStatus($status, $transaction_id, $payment_status) {
		return $status;
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

    /**
     * Transfer an specific amount to flutterwave to another targeted bank account.
     * 
     * @since 3.7.10
     *
     * @param array $args Arguments to request a transfer
     *
     * @return array returned array of transfer request data
     */
    public function transfer($args) {
        $args = wp_parse_args($args, [
            // 'account_bank'          => '044',
            // 'account_number'        => '00000000000',
            // 'amount'                => 5500,
            'narration'             => sprintf(
                'Narration not provided for this transfer. This transfer made on %s, using flutterwave woocommerce payment addon.',
                date('M d, Y H:i')
            ),
            // 'currency'              => 'NGN',
            // 'reference'             => '',
            'callback_url'          => site_url('/payment/flutterwave/'.$args['reference'].'/status/'),
            // 'debit_currency'        => 'NGN'
        ]);
        try {
            $response = $this->execute("transfers", $args);
            if ($response && isset($response['status'])) {
                if ($response['status'] == 'success') {
                    return (array) $response;
                }
            }
            throw new \Exception(__('Error getting banks list.', 'domain'), 1);
        } catch (\Exception $th) {
            //throw $th;
            return false;
        }
    }
    /**
     * Get all banks from a country code
     * 
     * @since 3.7.10
     *
     * @param string $country Get available bank accounts from the country code. Default Nigeria (NG)
     *
     * @return array returned array of all available bank accounts.
     */
    public function get_banks($country = 'NG') {
        try {
            $response = $this->execute("banks/{$country}");
            if ($response && isset($response['status'])) {
                if ($response['status'] == 'success') {
                    return (array) $response['data'];
                }
            }
            throw new \Exception(__('Error getting banks list.', 'domain'), 1);
        } catch (\Exception $th) {
            //throw $th;
            return false;
        }
    }
    /**
     * Get all branch information from a bank id.
     * 
     * @since 3.7.10
     *
     * @param string $bank_id Get all available braches list form bank ID.
     *
     * @return array returned array of all available branches of a bank.
     */
    public function get_branches($bank_id = 0) {
        try {
            $response = $this->execute("banks/{$bank_id}/branches");
            if ($response && isset($response['status'])) {
                if ($response['status'] == 'success') {
                    return (array) $response['data'];
                }
            }
            throw new \Exception(__('Error getting branches.', 'domain'), 1);
        } catch (\Exception $th) {
            //throw $th;
            return false;
        }
    }
    /**
     * Get account balance information
     * 
     * @since 3.7.10
     *
     * @param string $currency Currency code string on uppercase letter.
     *
     * @return bool|Exception|array returned array of all available currencies balances or specific currency balance.
     */
    public function balances($currency = false) {
        // $args = wp_parse_args($args, []);
        try {
            $response = $this->execute('balances');
            if ($response && isset($response['status'])) {
                if ($response['status'] == 'success') {
                    $balances = [];
                    foreach ((array) $response['data'] as $index => $row) {
                        $row['index']                   = $index;
                        $balances[$row['currency']]     = $row;
                        if ($currency && $row['currency'] == $currency) {return $row;}
                    }
                    return $balances;
                }
            }
            throw new \Exception(__('Error getting bank informations.', 'domain'), 1);
        } catch (\Exception $th) {
            //throw $th;
            return false;
        }
    }
    
}
