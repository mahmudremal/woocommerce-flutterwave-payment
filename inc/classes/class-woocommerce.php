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
		add_filter('wooflutter/wc/get/settings', [$this, 'get_settings'], 0, 2);
		add_action('wooflutter/payment/flutterwave/status', [$this, 'wooflutter_payment_flutterwave_status'], 10, 4);
		add_action('wooflutter/payment/flutterwave/status/back2text', [$this, 'wooflutter_payment_flutterwave_status_back2text'], 10, 5);
		add_action('wooflutter/payment/flutterwave/status/back2link', [$this, 'wooflutter_payment_flutterwave_status_back2link'], 10, 5);
		add_action('wooflutter/payment/flutterwave/status/retry', [$this, 'wooflutter_payment_flutterwave_status_retry'], 10, 5);
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

}
