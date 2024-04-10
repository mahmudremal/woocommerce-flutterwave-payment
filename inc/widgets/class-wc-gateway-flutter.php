<?php
/**
 * WooCommerce Payment Widget Register
 *
 * @link https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md
 * 
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class WC_Gateway_Flutter extends \WC_Payment_Gateway {
	/**
	 * Checkout page title
	 *
	 * @var string
	 */
	public $title;
	/**
	 * Checkout page description
	 *
	 * @var string
	 */
	public $description;
	/**
	 * Is gateway enabled?
	 *
	 * @var bool
	 */
	public $enabled;
	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public bool $testmode;
	/**
	 * Should orders be marked as complete after payment?
	 *
	 * @var bool
	 */
	public bool $autocomplete_order;
	/**
	 * Text displayed as the title of the payment modal
	 *
	 * @var string
	 */
	public string $custom_title;
	/**
	 * Text displayed as a short modal description
	 *
	 * @var string
	 */
	public string $custom_desc;
	/**
	 * Image to be displayed on the payment popup
	 *
	 * @var string
	 */
	public string $custom_logo;
	/**
	 * Flutterwave test public key.
	 *
	 * @var string
	 */
	public string $test_public_key;
	/**
	 * Flutterwave test secret key.
	 *
	 * @var string
	 */
	public string $test_secret_key;
	/**
	 * Flutterwave live public key.
	 *
	 * @var string
	 */
	public string $live_public_key;
	/**
	 * Flutterwave live secret key.
	 *
	 * @var string
	 */
	public string $live_secret_key;
	/**
	 * Flutterwave live encription key.
	 *
	 * @var string
	 */
	public string $live_encript_key;
	/**
	 * API public key.
	 *
	 * @var string
	 */
	public string $public_key;
	/**
	 * API secret key.
	 *
	 * @var string
	 */
	public string $secret_key;
	/**
	 * Should we save customer cards?
	 *
	 * @var bool
	 */
	public bool $saved_cards;
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'flutterwave';
		$this->method_title       = 'Flutterwave';
		$this->icon               = 'dashicons dashicons-money-alt';
		$this->method_description = sprintf('WooCommerce integration with Flutterwave payment method. The easiest way to collect payments from customers around the world. Get your <a href="%1$s" target="_blank">API keys</a> here.', 'https://dashboard.flutterwave.com/dashboard/settings/apis');
		$this->has_fields = true;
		$this->supports = [
			'refunds',
			'products',
			'tokenization',
			'subscriptions',
			'multiple_subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer'
		];
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Get setting values.
		$this->title              = $this->get_option('title');
		$this->description        = $this->get_option('description');
		$this->enabled            = $this->get_option('enabled');
		$this->testmode           = $this->get_option('testmode') === 'yes';
		$this->autocomplete_order = $this->get_option('autocomplete_order') === 'yes';
		$this->custom_title	= $this->get_option('custom_title');
		$this->custom_desc	= $this->get_option('custom_desc');
		$this->custom_logo	= $this->get_option('custom_logo');
		$this->test_public_key = $this->get_option('test_public_key');
		$this->test_secret_key = $this->get_option('test_secret_key');
		$this->live_public_key = $this->get_option('live_public_key');
		$this->live_secret_key = $this->get_option('live_secret_key');
		$this->live_encript_key = $this->get_option('live_encript_key');
		$this->public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
		$this->secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;
		$this->saved_cards = $this->get_option('saved_cards') === 'yes';

		// Hooks.
		add_action('wp_enqueue_scripts', [$this, 'payment_scripts'], 10, 0);
		add_action('admin_notices', [$this, 'admin_notices']);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
		// add_action('woocommerce_admin_order_totals_after_total', [$this, 'display_flutterwave_fee']);
		// add_action('woocommerce_admin_order_totals_after_total', [$this, 'display_order_payout'], 20);
		add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
		add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);
		// Payment listener/API hook.
		add_action('woocommerce_api_'.$this->id.'_gateway', [$this, 'verify_flutterwave_transaction']);
		// Webhook listener/API hook.
		add_action('woocommerce_api_'.$this->id.'_webhook', [$this, 'process_webhooks']);
		
	}
	/**
	 * Display the payment icon on the checkout page
	 */
	public function get_icon() {
		// $icon = '<img src="'.esc_url(WOOFLUTTER_BUILD_IMG_URI.'/trusted-by-flutterwave.png').'" alt="cards"/>';
		// $icon = 'gform-icon gform-icon--api';
		$icon = WOOFLUTTER_BUILD_PATH . '/icons/flutterwave.svg';
		$icon = file_get_contents($icon);
		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}
	/**
	 * Check if Flutterwave merchant details is filled
	 */
	public function admin_notices() {
		if ('no' === $this->enabled) {return;}
		// Check required fields.
		if (! ($this->public_key && $this->secret_key)) {
			echo '<div class="error"><p>' . sprintf('Please fillup your flutterwave information from <a href="%s">here</a> to make if functional.', admin_url('admin.php?page=wc-settings&tab=checkout&section=flutterwave')) . '</p></div>';
		}
	}
	/**
	 * Check if Flutterwave gateway is enabled.
	 */
	public function is_available() {
		if ('yes' !== $this->enabled) {return false;}
		if (!$this->public_key || !$this->secret_key) {return false;}
		if (empty($this->public_key) || empty($this->secret_key)) {return false;}
		return parent::is_available();
	}
	/**
	 * Admin Panel Options
	 */
	public function admin_options() {
		?>
        <h3><?php echo esc_html(__('Flutterwave', 'domain')); ?></h3>
        <p>
			Set your payment fallback URL <code><?php echo esc_url(site_url('/payment/flutterwave/{transaction_id}/{status}/')); ?></code>.<br/>
			To avoid situations where bad network makes it sometimes impossible to verify transactions, set your webhook URL <code><?php echo WC()->api_request_url($this->id.'_webhook'); ?></code> <a href="https://app.flutterwave.com/dashboard/settings/webhooks/" target="_blank" rel="noopener noreferrer">On this Link</a> to avoid these kind of inconvenience.
		</p>
		<?php
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}
	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __('Enable/Disable', 'wooflutter'),
				'label'       => __('Switch on this field to enable Flutterwave payment on woocommerce.', 'wooflutter'),
				'type'        => 'checkbox',
				'description' => __('Enable Flutterwave as a payment option on your woocommerce store. Easily pause it form here.', 'wooflutter'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'title'              => array(
				'title'       => __('Checkout page title', 'wooflutter'),
				'type'        => 'text',
				'description' => __('The payment method title which will be apear on the checkout screen.', 'wooflutter'),
				'desc_tip'    => true,
				'default'     => __('Flutterwave', 'wooflutter'),
			),
			'description'        => array(
				'title'       => __('Description', 'wooflutter'),
				'type'        => 'textarea',
				'description' => __('This controls the payment method description which the user sees during checkout.', 'wooflutter'),
				'desc_tip'    => true,
				'default'     => __('Make payment using your debit, credit card & bank account', 'wooflutter'),
			),
			'testmode'           => array(
				'title'       => __('Test mode', 'wooflutter'),
				'label'       => __('Enable Test Mode', 'wooflutter'),
				'type'        => 'checkbox',
				'description' => __('Test mode enables you to test payments before going live. <br />Once you are live uncheck this.', 'wooflutter'),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'test_public_key'    => array(
				'title'       => __('Test Public Key', 'wooflutter'),
				'type'        => 'text',
				'description' => __('Required: Enter your Test Public Key here.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'test_secret_key'    => array(
				'title'       => __('Test Secret Key', 'wooflutter'),
				'type'        => 'password',
				'description' => __('Required: Enter your Test Secret Key here', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'live_public_key'    => array(
				'title'       => __('Live Public Key', 'wooflutter'),
				'type'        => 'text',
				'description' => __('Required: Enter your Live Public Key here.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'live_secret_key'    => array(
				'title'       => __('Live Secret Key', 'wooflutter'),
				'type'        => 'password',
				'description' => __('Required: Enter your Live Secret Key here.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'live_encript_key'    => array(
				'title'       => __('Live Encryption Key', 'wooflutter'),
				'type'        => 'password',
				'description' => __('Required: Enter your Live Encryption Key here.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'custom_title'       => array(
				'title'       => __('Model Title', 'wooflutter'),
				'type'        => 'text',
				'description' => __('Optional: Text to be displayed as the title of the payment modal.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'custom_desc'        => array(
				'title'       => __('Custom Description', 'wooflutter'),
				'type'        => 'text',
				'description' => __('Optional: Text to be displayed as a short modal description.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'custom_logo'        => array(
				'title'       => __('Custom Logo', 'wooflutter'),
				'type'        => 'text',
				'description' => __('Optional: Enter the link to a image to be displayed on the payment popup. Preferably a square image.', 'wooflutter'),
				'default'     => '',
				'desc_tip'    => true,
			),
			// 'saved_cards'        => array(
			// 	'title'       => __('Saved Cards', 'wooflutter'),
			// 	'label'       => __('Enable Payment via Saved Cards', 'wooflutter'),
			// 	'type'        => 'checkbox',
			// 	'description' => __('If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Flutterwave servers, not on your store.<br>Note that you need to have a valid SSL certificate installed.', 'wooflutter'),
			// 	'default'     => 'no',
			// 	'desc_tip'    => true,
			//),
			// 'autocomplete_order' => array(
			// 	'title'       => __('Autocomplete Order After Payment', 'wooflutter'),
			// 	'label'       => __('Autocomplete Order', 'wooflutter'),
			// 	'type'        => 'checkbox',
			// 	'description' => __('If enabled, the order will be marked as complete after successful payment', 'wooflutter'),
			// 	'default'     => 'no',
			// 	'desc_tip'    => true,
			//),
			// 'autoconfirm_order' => array(
			// 	'title'       => __('Auto Confirm', 'wooflutter'),
			// 	'label'       => __('Autoconfirm Order After successful Payment from checkout page', 'wooflutter'),
			// 	'type'        => 'checkbox',
			// 	'description' => __('If enabled, the order will be marked as paid after successful payment', 'wooflutter'),
			// 	'default'     => 'no',
			// 	'desc_tip'    => true,
			//),
			// 'autosubmit_form' => array(
			// 	'title'       => __('Auto Submit', 'wooflutter'),
			// 	'label'       => __('Auto submit form after payment successful. Disable recommanded.', 'wooflutter'),
			// 	'type'        => 'checkbox',
			// 	'description' => __('If enabled, all of the form will be submitted by default after successful payment. Disabling this field comes with a possibility to define this auto submit features from single form settings.', 'wooflutter'),
			// 	'default'     => 'no',
			// 	'desc_tip'    => true,
			//),
		);
	}
	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		// We can place here HTML structure for the slice
		?>
		<div class="wooflutter__method">
			<div class="wooflutter__wrap">
				<div class="wooflutter__head"></div>
				<div class="wooflutter__body">
					<div class="wooflutter__desc">
						<?php if ($this->description) {echo wpautop(wptexturize($this->description));} ?>
					</div>
				</div>
				<div class="wooflutter__foot"></div>
			</div>
		</div>
		<?php
		if (!is_ssl()) {return;}
		// if ($this->saved_cards && $this->supports('tokenization') && is_checkout() && is_user_logged_in()) {
		// 	$this->tokenization_script();
		// 	$this->saved_payment_methods();
		// 	$this->save_payment_method_checkbox();
		// }
	}
	/**
	 * Outputs scripts used by Flutterwave.
	 */
	public function payment_scripts() {
		global $WooFlutter_Assets;
		// if (is_checkout() && !is_checkout_pay_page()) {wp_redirect($order->get_checkout_payment_url());}

		wp_enqueue_style('wooflutter-payment', WOOFLUTTER_BUILD_CSS_URI . '/checkout.css', [], $WooFlutter_Assets->filemtime(WOOFLUTTER_BUILD_CSS_DIR_PATH . '/checkout.css'), 'all');

		if(!(is_checkout_pay_page() || is_checkout())) {return;}
		// if('no'===$this->enabled) {return;}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// $order_key = urldecode(sanitize_text_field($_GET['key']));
		// $order_id  = absint(get_query_var('order-pay'));
		// $order = wc_get_order($order_id);
		// $payment_method = method_exists($order, 'get_payment_method') ? $order->get_payment_method() : $order->payment_method;
		// if ($this->id !== $payment_method) {return;}
		// print_r([$this->id, $order->payment_method]);
		// $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		// wp_enqueue_script('jquery');
		// wp_enqueue_script('checkout-flutterwave');
		
		
		// wp_enqueue_script('wooflutter-payment');
		$flutterwave_params = array(
			'public_key' => $this->public_key,
		);
		// if (is_checkout_pay_page() && get_query_var('order-pay')) {
		// 	$email         = $order->get_billing_email();
		// 	$billing_phone = $order->get_billing_phone();
		// 	$first_name    = $order->get_billing_first_name();
		// 	$last_name     = $order->get_billing_last_name();
		// 	$amount = $order->get_total();
		// 	$txnref = 'WC|' . $order_id . '|' . time();
		// 	$the_order_id  = $order->get_id();
		// 	$the_order_key = $order->get_order_key();
		// 	$currency = $order->get_currency();
		// 	$meta = array();
		// 	if ($the_order_id == $order_id && $the_order_key == $order_key) {
		// 		$meta['Order ID'] = $order_id;
		// 		$customer_name = trim("$first_name $last_name");
		// 		$location = wc_get_base_location();
		// 		$flutterwave_params['txref']          = $txnref;
		// 		$flutterwave_params['amount']         = $amount;
		// 		$flutterwave_params['currency']       = $currency;
		// 		$flutterwave_params['country']        = $location['country'];
		// 		$flutterwave_params['customer_email'] = $email;
		// 		$flutterwave_params['customer_phone'] = $billing_phone;
		// 		$flutterwave_params['customer_name']  = $customer_name;
		// 		$flutterwave_params['custom_title']   = $this->custom_title;
		// 		$flutterwave_params['custom_desc']    = $this->custom_desc;
		// 		$flutterwave_params['custom_logo']    = $this->custom_logo;
		// 		$flutterwave_params['meta']           = $meta;
		// 		$order->update_meta_data('_rave_txn_ref', $txnref);
		// 		$order->save();
		// 	}
		// }
		// wp_localize_script('wooflutter-payment', 'fwpSiteConfig', apply_filters('wooflutter/project/javascript/siteconfig', ['config' => $flutterwave_params]));
	}
	/*
	 * Fields validation, more in Step 5
	 */
	public function validate_fields() {
		// if (empty($_POST['billing_first_name'])) {
		// 	wc_add_notice('First name is required!', 'error');
		// 	return false;
		// }
		return true;
	}
	/**
	 * Displays the Rave fee
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @since 2.1.0
	 *
	 */
	public function display_flutterwave_fee($order_id) {
		$order = wc_get_order($order_id);
		$fee      = $order->get_meta('_rave_fee', true);
		$currency = $order->get_meta('_rave_currency', true);
		if (! $fee || ! $currency) {return;}
		?>
        <tr>
            <td class="label rave-fee">
				<?php echo wc_help_tip(__('This represents the fee Flutterwave collects for the transaction.', 'wooflutter')); ?>
				<?php esc_html_e(__('Flutterwave Fee:', 'wooflutter')); ?>
            </td>
            <td width="1%"></td>
            <td class="total">
                -&nbsp;<?php echo wc_price($fee, array('currency' => $currency)); ?>
            </td>
        </tr>
		<?php
	}
	/**
	 * Displays the net total of the transaction without the charges of Rave.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @since 2.1.0
	 *
	 */
	public function display_order_payout($order_id) {
		$order = wc_get_order($order_id);
		$net      = $order->get_meta('_rave_net', true);
		$currency = $order->get_meta('_rave_currency', true);
		if (! $net || ! $currency) {return;}
		?>
        <tr>
            <td class="label rave-payout">
				<?php $message = __('This represents the net total that will be credited to your bank account for this order.', 'wooflutter'); ?>
				<?php if ($net >= $order->get_total()) : ?>
					<?php $message .= __(' Flutterwave transaction fees was passed to the customer.', 'wooflutter'); ?>
				<?php endif; ?>
				<?php echo wc_help_tip($message); ?>
				<?php esc_html_e(__('Flutterwave Payout:', 'wooflutter')); ?>
            </td>
            <td width="1%"></td>
            <td class="total">
				<?php echo wc_price($net, array('currency' => $currency)); ?>
            </td>
        </tr>
		<?php
	}
	/**
	 * Process payment
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return array|void
	 */
	public function process_payment($order_id) {
		global $WooFlutter_Flutterwave;
		// if (is_checkout()) {}
		// print_r($payment_request);wp_die('Remal mahmud (mahmudremal@yahoo.com)', 'Development');
		$order = wc_get_order($order_id);
		// $order->get_transaction_id()
		$order_data = $order->get_data();
		$WooFlutter_Flutterwave->set_api_key($this);
		
		$payIntend = $WooFlutter_Flutterwave->createPayment([
			'tx_ref'			=> $order->get_order_key(),
			'amount'			=> $order->get_total(),
			'currency'			=> $order->get_currency(),

			'customer'	=> [
				'name'			=> implode(' ', [$order->get_billing_first_name(), $order->get_billing_last_name()]),
				'email'			=> $order->get_billing_email(),
				'phonenumber'	=> $order->get_billing_phone(),
				'address'		=> $order->get_formatted_billing_address()
			],
			'customizations'	=> [
				'title'			=> sprintf('%s Payments', bloginfo('name')),
				'logo'			=> WOOFLUTTER_BUILD_URI . '/icons/flutterwave.svg',
				// 'description'	=> sprintf('', '')
			],
			// 'meta'				=> [], // (optional): An object containing any extra information you'd like to store alongside the transaction e.g {consumer_id: 23, consumer_mac: '92a3-912ba-1192a'}
			// 'payment_options'		=> ['card', 'account', 'banktransfer', 'mpesa', 'mobilemoneyghana', 'mobilemoneyfranco', 'mobilemoneyuganda', 'mobilemoneyrwanda', 'mobilemoneyzambia', 'barter', 'nqr', 'ussd', 'credit'],
			// 'payment_plan'		=> '', // (optional): The payment plan ID (for when you're collecting a recurring payment) https://developer.flutterwave.com/docs/recurring-payments/payment-plans/
		]);
		
		if ($payIntend && !empty($payIntend)) {
			// Mark order as processing
			if ($order->get_total() > 0) {
				// Mark as processing (we're awaiting the payment).
				$order->update_status(apply_filters('woocommerce_' . $this->id . '_process_payment_order_status', 'pending', $order), __('Payment intend created, waiting for payment.', 'wooflutter'));
			} else {
				$order->payment_complete();
			}
			// Reduce stock levels
			$order->reduce_order_stock();
			// Remove cart
			// WC()->cart->empty_cart();
			// // Save order
			// $order->save();
			return [
				'result'   => 'processing', // success
				// 'redirect' => $order->get_checkout_payment_url(true),
				// 'redirect' => $order->get_transaction_id(true),
				'redirect' => $payIntend,
			];
		} else {
			wc_add_notice('Flutterwave Payment API request Error.', 'error');
			return [
				'result'		=> 'error',
				'redirect'		=> false, // $order->get_checkout_payment_url(true),
				
			];
		}
	}
	/**
	 * Process Refund
	 *
	 * @param int $order_id WC Order ID.
	 * @param float|null $amount WC Order amount to refund.
	 * @param string $reason Reason text for the refund operation.
	 *
	 * @return bool|\WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund($order_id, $amount = null, $reason = '') {
		global $WooFlutter_Flutterwave;$order = wc_get_order($order_id);
		
		if (! $order || ! $order->get_id()) {
			return new \WP_Error('invalid_order', __('Invalid order.', 'wooflutter'));
		}
	
		// Get the total amount paid for the order
		$total_paid = $order->get_total();
	
		// If no refund amount is specified, use the total amount paid
		if (is_null($amount)) {
			$amount = $total_paid;
		}
	
		// Make sure the refund amount is valid
		if ($amount < 0 || $amount > $total_paid) {
			return new \WP_Error('invalid_refund_amount', __('Invalid refund amount.', 'wooflutter'));
		}

		// Make sure the transection id exists
		$transaction_id = $order->get_transaction_id();
		if (!$transaction_id || empty($transaction_id)) {
			return new \WP_Error('invalid_transection_id', __('Transection ID not found', 'wooflutter'));
		}
	
		
		try {
			// Register flutterwave settings
			$WooFlutter_Flutterwave->set_api_key($this);
			// Process flutterwave refund function
			$isRefund = $WooFlutter_Flutterwave->refund($transaction_id, $amount);
			// print_r($isRefund);wp_die('process_refund');
			
			// Process refund
			$refund_id = $order->add_order_note(
				sprintf(
					__('Refunded %s for reason: %s', 'wooflutter'),
					wc_price($amount),
					$reason
				)
			);
		
			if (is_wp_error($refund_id)) {
				return $refund_id;
			}
		
			// Reduce order total and save
			$order->set_total($total_paid - $amount);
			$order->save();
		
			// Return true to indicate successful refund
			return true;
		} catch (\Exception $th) {
			//throw $th;
			return new \WP_Error('flutterwave_refund_failed', $th->getMessage());
		}
		return false;
	}
	/**
	 * Process a token payment
	 */
	public function process_token_payment($token, $order_id) {
		if ($token && $order_id) {
			$order = wc_get_order($order_id);
			$txnref = 'WC|' . $order_id . '|' . uniqid();
			$order_amount   = $order->get_total();
			$order_currency = $order->get_currency();
			$first_name     = $order->get_billing_first_name();
			$last_name      = $order->get_billing_last_name();
			$email          = $order->get_billing_email();
			$ip_address = $order->get_customer_ip_address();
			$headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->secret_key,
			);
			if (strpos($token, '##') !== false) {
				$payment_token = explode('##', $token);
				$token_code    = $payment_token[0];
			} else {
				$token_code = $token;
			}
			$body = array(
				'token'     => $token_code,
				'email'     => $email,
				'currency'  => $order_currency,
				'amount'    => $order_amount,
				'tx_ref'    => $txnref,
				'firstname' => $first_name,
				'lastname'  => $last_name,
				'ip'        => $ip_address,
				'meta'      => array(
					array(
						'metaname'  => 'Order ID',
						'metavalue' => $order_id,
					),
				),
			);
			$args = array(
				'headers' => $headers,
				'body'    => wp_json_encode($body),
				'timeout' => 60,
			);
			$tokenized_url = 'https://api.flutterwave.com/v3/tokenized-charges';
			$request = wp_remote_post($tokenized_url, $args);
			if (! is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
				$response = json_decode(wp_remote_retrieve_body($request));
				$status           = $response->data->status;
				$payment_currency = $response->data->currency;
				$gateway_symbol   = get_woocommerce_currency_symbol($payment_currency);
				if ('successful' === $status) {
					if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'), true)) {
                        // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
						wp_redirect($this->get_return_url($order));
						exit;
					}
					$order_currency = $order->get_currency();
					$currency_symbol = get_woocommerce_currency_symbol($order_currency);
					$order_total = $order->get_total();
					$amount_paid = $response->data->amount;
					$txn_ref     = $response->data->tx_ref;
					$payment_ref = $response->data->flw_ref;
					$amount_charged = $response->data->charged_amount;
					$flutterwave_fee = $response->data->app_fee;
					$flutterwave_net = $amount_charged - $flutterwave_fee;
					$order->update_meta_data('_rave_fee', $flutterwave_fee);
					$order->update_meta_data('_rave_net', $flutterwave_net);
					$order->update_meta_data('_rave_currency', $payment_currency);
					// check if the amount paid is equal to the order amount.
					if ($amount_paid < $order_total) {
						$order->update_status('on-hold', '');
						$order->set_transaction_id($txn_ref);
						$notice      = 'Thank you for shopping with us.<br />Your payment was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
						$notice_type = 'notice';
						// Add Customer Order Note.
						$order->add_order_note($notice, 1);
						// Add Admin Order Note.
						$order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>' . $currency_symbol . $amount_paid . '</strong> while the total order amount is <strong>' . $currency_symbol . $order_total . '</strong><br /><strong>Transaction ID:</strong> ' . $txn_ref . ' | <strong>Payment Reference:</strong> ' . $payment_ref);
						wc_reduce_stock_levels($order_id);
						wc_add_notice($notice, $notice_type);
					} else {
						if ($payment_currency !== $order_currency) {
							$order->update_status('on-hold');
							$order->set_transaction_id($txn_ref);
							$notice      = 'Thank you for shopping with us.<br />Your payment was successful, but the payment currency is different from the order currency.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
							$notice_type = 'notice';
							// Add Customer Order Note.
							$order->add_order_note($notice, 1);
							// Add Admin Order Note.
							$order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Order currency is different from the payment currency.<br /> Order Currency is <strong>' . $order_currency . ' (' . $currency_symbol . ')</strong> while the payment currency is <strong>' . $payment_currency . ' (' . $gateway_symbol . ')</strong><br /><strong>Transaction ID:</strong> ' . $txn_ref . ' | <strong>Payment Reference:</strong> ' . $payment_ref);
							wc_reduce_stock_levels($order_id);
							wc_add_notice($notice, $notice_type);
						} else {
							$order->payment_complete($txn_ref);
							$order->add_order_note(sprintf('Payment via Flutterwave successful (<strong>Transaction ID:</strong> %s | <strong>Payment Reference:</strong> %s)', $txn_ref, $payment_ref));
							if ($this->autocomplete_order) {
								$order->update_status('completed');
							}
						}
					}
					$order->save();
					$this->save_subscription_payment_token($order_id, $token);
					wc_empty_cart();
					return true;
				}
				$order = wc_get_order($order_id);
				$order->update_status('failed', 'Payment was declined by Flutterwave.');
				wc_add_notice('Payment Failed. Try again.', 'error');
				return false;
			}
			wc_add_notice('Payment failed using the saved card. Kindly use another payment method.', 'error');
			return false;
		}
		wc_add_notice('Payment Failed.', 'error');
		return false;
	}
	/**
	 * Process subscription payment.
	 *
	 * @param  float     $amount
	 * @param  WC_Order  $order
	 * @return void
	 */
	// public function process_subscription_payment( $amount, $order ) {
	// 	$payment_result = $this->get_option( 'result' );
	// 	if ( 'success' === $payment_result ) {
	// 		$order->payment_complete();
	// 	} else {
	// 		$message = __( 'Order payment failed. To make a successful payment using Dummy Payments, please review the gateway settings.', 'woocommerce-gateway-dummy' );
	// 		throw new Exception( $message );
	// 	}
	// }
	/**
	 * Show new card can only be added when placing an order notice
	 */
	public function add_payment_method() {
		wc_add_notice(__('You can only add a new card when placing an order.', 'wooflutter'), 'error');
	}
	/**
	 * Displays the payment page
	 */
	public function receipt_page($order_id) {
		$order = wc_get_order($order_id);
		echo '<div id="flutterwave_addons_wrap">';
		echo '<p>Thank you for your order! To complete your payment via Flutterwave, please click the button below.</p>';
		echo '<form id="order_review" method="post" action="' . WC()->api_request_url($this->id.'_gateway') . '"></form><button class="button alt" id="wc-checkout-payment-button">Pay Now</button> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">Cancel order &amp; restore cart</a>';
		echo '</div>';
	}
	/**
	 * Verify Rave payment
	 */
	public function verify_flutterwave_transaction() {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@ob_clean();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_REQUEST['tbz_wc_verified_transactionref'])) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$txn_id = sanitize_text_field($_REQUEST['tbz_wc_verified_transactionref']);
			$verified_transaction = $this->verify_transaction($txn_id);
			$status = $verified_transaction->data->status ?? 'failed';
			if ('successful' === $status) {
				$payment_currency = $verified_transaction->data->currency;
				$gateway_symbol   = get_woocommerce_currency_symbol($payment_currency);
				$order_details    = explode('|', $verified_transaction->data->tx_ref);
				$order_id = (int) $order_details[1];
				$order = wc_get_order($order_id);
				if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'), true)) {
					wp_redirect($this->get_return_url($order));
					exit;
				}
				$order_currency = $order->get_currency();
				$currency_symbol = get_woocommerce_currency_symbol($order_currency);
				$order_total = $order->get_total();
				$amount_paid     = $verified_transaction->data->amount;
				$txn_ref         = $verified_transaction->data->tx_ref;
				$payment_ref     = $verified_transaction->data->flw_ref;
				$amount_charged  = $verified_transaction->data->charged_amount;
				$flutterwave_fee = $verified_transaction->data->app_fee;
				$flutterwave_net = $amount_charged - $flutterwave_fee;
				$order->update_meta_data('_rave_fee', $flutterwave_fee);
				$order->update_meta_data('_rave_net', $flutterwave_net);
				$order->update_meta_data('_rave_currency', $payment_currency);
				// check if the amount paid is equal to the order amount.
				if ($amount_paid < $order_total) {
					$order->update_status('on-hold');
					$order->set_transaction_id($txn_ref);
					$notice      = 'Thank you for shopping with us.<br />Your payment was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
					$notice_type = 'notice';
					// Add Customer Order Note
					$order->add_order_note($notice, 1);
					// Add Admin Order Note
					$order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>' . $currency_symbol . $amount_paid . '</strong> while the total order amount is <strong>' . $currency_symbol . $order_total . '</strong><br /><strong>Transaction ID:</strong> ' . $txn_ref . ' | <strong>Payment Reference:</strong> ' . $payment_ref);
					wc_reduce_stock_levels($order_id);
					wc_add_notice($notice, $notice_type);
				} else {
					if ($payment_currency !== $order_currency) {
						$order->update_status('on-hold');
						$order->set_transaction_id($txn_ref);
						$notice      = 'Thank you for shopping with us.<br />Your payment was successful, but the payment currency is different from the order currency.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
						$notice_type = 'notice';
						// Add Customer Order Note
						$order->add_order_note($notice, 1);
						// Add Admin Order Note
						$order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Order currency is different from the payment currency.<br /> Order Currency is <strong>' . $order_currency . ' (' . $currency_symbol . ')</strong> while the payment currency is <strong>' . $payment_currency . ' (' . $gateway_symbol . ')</strong><br /><strong>Transaction ID:</strong> ' . $txn_ref . ' | <strong>Payment Reference:</strong> ' . $payment_ref);
						wc_reduce_stock_levels($order_id);
						wc_add_notice($notice, $notice_type);
					} else {
						$order->payment_complete($txn_ref);
						$order->add_order_note(sprintf('Payment via Flutterwave successful (<strong>Transaction ID:</strong> %s | <strong>Payment Reference:</strong> %s)', $txn_ref, $payment_ref));
						if ($this->autocomplete_order) {
							$order->update_status('completed');
						}
					}
				}
				$order->save();
				$this->save_card_details($verified_transaction, $order->get_user_id(), $order_id);
				wc_empty_cart();
				wp_redirect($this->get_return_url($order));
				exit;
			} else {
				// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
				$order_txn_ref = sanitize_text_field($_REQUEST['tbz_wc_flutterwave_order_txnref']);
				$order_details = explode('|', $order_txn_ref);
				$order_id      = (int) $order_details[1];
				$order         = wc_get_order($order_id);
				if ($order) {
					$order->add_order_note(sprintf('Unable to retrieve transaction details from Flutterwave. This could be due to invalid Flutterwave API keys on the settings page. <strong>Transaction ID:</strong> %s', $order_txn_ref));
				}
				wc_add_notice('Unable to retrieve transaction details from Flutterwave. If debited contact website owner.', 'error');
				wp_redirect(wc_get_page_permalink('checkout'));
				exit;
			}
		}
		wc_add_notice('Payment failed. Try again.', 'error');
		wp_redirect(wc_get_page_permalink('checkout'));
		exit;
	}
	/*
	 * In case you need a webhook, like PayPal IPN etc
	 */
	// public function webhook() {}
	/**
	 * Process Webhook
	 */
	public function process_webhooks() {
		// if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'])) {exit;}
		sleep(10);
		$body = file_get_contents('php://input');
		if ($this->is_json($body)) {
			$webhook_body = (array) json_decode($body, true);
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$webhook_body = $_POST;
		}
		if (isset($webhook_body['data']['id'])) {
			$transaction_id = $webhook_body['data']['id'];
		} elseif (isset($webhook_body['id'])) {
			$transaction_id = $webhook_body['id'];
		} else {
			$transaction_id = '';
		}
		if(empty($transaction_id)) {exit;}
		$verified_transaction = $this->verify_transaction($transaction_id);
		if (! isset($verified_transaction->data->status)) {
			return;
		}
		// Payment failed.
		if ('successful' !== strtolower($verified_transaction->data->status)) {
			$order_details = explode('|', $verified_transaction->data->tx_ref);
			$order_id = (int) $order_details[1];
			$order = wc_get_order($order_id);
			if ($order) {
				$order->update_status('failed', 'Payment was declined by Flutterwave.');
			}
			exit;
		}
		$payment_currency = $verified_transaction->data->currency;
		$gateway_symbol   = get_woocommerce_currency_symbol($payment_currency);
		$order_details = explode('|', $verified_transaction->data->tx_ref);
		$order_id = (int) $order_details[1];
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}
		$rave_txn_ref = $order->get_meta('_rave_txn_ref');
		if (strtolower($verified_transaction->data->tx_ref) !== strtolower($rave_txn_ref)) {
			exit;
		}
		http_response_code(200);
		if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'), true)) {
			exit;
		}
		$order_currency = $order->get_currency();
		$currency_symbol = get_woocommerce_currency_symbol($order_currency);
		$order_total = $order->get_total();
		$amount_paid     = $verified_transaction->data->amount;
		$txn_ref         = $verified_transaction->data->tx_ref;
		$payment_ref     = $verified_transaction->data->flw_ref;
		$amount_charged  = $verified_transaction->data->charged_amount;
		$flutterwave_fee = $verified_transaction->data->app_fee;
		$flutterwave_net = $amount_charged - $flutterwave_fee;
		$order->update_meta_data('_rave_fee', $flutterwave_fee);
		$order->update_meta_data('_rave_net', $flutterwave_net);
		$order->update_meta_data('_rave_currency', $payment_currency);
		// check if the amount paid is equal to the order amount.
		if ($amount_paid < $order_total) {
			$order->update_status('on-hold');
			$order->set_transaction_id($txn_ref);
			$notice = 'Thank you for shopping with us.<br />Your payment was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
			// Add Customer Order Note
			$order->add_order_note($notice, 1);
			// Add Admin Order Note.
			$order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>' . $currency_symbol . $amount_paid . '</strong> while the total order amount is <strong>' . $currency_symbol . $order_total . '</strong><br /><strong>Transaction ID:</strong> ' . $txn_ref . ' | <strong>Payment Reference:</strong> ' . $payment_ref);
			wc_reduce_stock_levels($order_id);
		} elseif ($payment_currency !== $order_currency) {
			$order->update_status('on-hold', '');
			$order->set_transaction_id($txn_ref);
			$notice = 'Thank you for shopping with us.<br />Your payment was successful, but the payment currency is different from the order currency.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
			// Add Customer Order Note.
			$order->add_order_note($notice, 1);
			// Add Admin Order Note.
			$order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Order currency is different from the payment currency.<br /> Order Currency is <strong>' . $order_currency . ' (' . $currency_symbol . ')</strong> while the payment currency is <strong>' . $payment_currency . ' (' . $gateway_symbol . ')</strong><br /><strong>Transaction ID:</strong> ' . $txn_ref . ' | <strong>Payment Reference:</strong> ' . $payment_ref);
			wc_reduce_stock_levels($order_id);
		} else {
			$order->payment_complete($txn_ref);
			$order->add_order_note(sprintf('Payment via Flutterwave successful (<strong>Transaction ID:</strong> %s | <strong>Payment Reference:</strong> %s)', $txn_ref, $payment_ref));
			if ($this->autocomplete_order) {
				$order->update_status('completed');
			}
		}
		$order->save();
		$this->save_card_details($verified_transaction, $order->get_user_id(), $order_id);
		wc_empty_cart();
		exit;
	}
	/**
	 * Save Customer Card Details.
	 */
	public function save_card_details($verified_transaction, $user_id, $order_id) {
		$token_code     = $verified_transaction->data->card->token ?? '';
		$customer_email = $verified_transaction->data->customer->email ?? '';
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}
		if (empty($token_code) || empty($customer_email)) {
			$order->delete_meta_data('_wc_rave_save_card');
			$order->save();
			return;
		}
		$card_token = "$token_code###$customer_email";
		$this->save_subscription_payment_token($order_id, $card_token);
		$save_card = $order->get_meta('_wc_rave_save_card');
		if ($user_id && $this->saved_cards && $save_card) {
			$brand       = $verified_transaction->data->card->type;
			$last4       = $verified_transaction->data->card->last_4digits;
			$expiry_date = explode('/', $verified_transaction->data->card->expiry);
			$exp_month   = $expiry_date[0];
			$exp_year    = $expiry_date[1];
			if (2 === strlen($exp_year)) {
				$exp_year = date_create_from_format('y', $exp_year);
				$exp_year = $exp_year->format('Y');
			}
			$token = new \WC_Payment_Token_CC();
			$token->set_token($card_token);
			$token->set_gateway_id('tbz_rave');
			$token->set_card_type($brand);
			$token->set_last4($last4);
			$token->set_expiry_month($exp_month);
			$token->set_expiry_year($exp_year);
			$token->set_user_id($user_id);
			$token->save();
		}
		$order->delete_meta_data('_wc_rave_save_card');
		$order->save();
	}
	/**
	 * Save payment token to the order for automatic renewal for further subscription payment
	 */
	public function save_subscription_payment_token($order_id, $payment_token) {
		if (!function_exists('wcs_order_contains_subscription')) {return;}
		if (empty($payment_token)) {return;}
		if (! $this->order_contains_subscription($order_id)) {return;}
		// Also store it on the subscriptions being purchased or paid for in the order.
		if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
			$subscriptions = wcs_get_subscriptions_for_order($order_id);
		} elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
		} else {
			$subscriptions = array();
		}
		if (empty($subscriptions)) {
			return;
		}
		foreach ($subscriptions as $subscription) {
			$subscription->update_meta_data('_tbz_rave_wc_token', $payment_token);
			$subscription->save();
		}
	}
	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ($this->description) {
			echo wp_kses_post(wpautop(wptexturize($this->description)));
		}
	}
	/**
	 * @param $string
	 *
	 * @return bool
	 */
	public function is_json($string) {
		return is_string($string) && is_array(json_decode($string, true)) ? true : false;
	}
	private function verify_transaction($txn_id) {
		$api_url = "https://api.flutterwave.com/v3/transactions/$txn_id/verify";
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->secret_key,
		);
		$args = array(
			'headers' => $headers,
			'timeout' => 60,
		);
		$request = wp_remote_get($api_url, $args);
		return json_decode(wp_remote_retrieve_body($request));
	}


}