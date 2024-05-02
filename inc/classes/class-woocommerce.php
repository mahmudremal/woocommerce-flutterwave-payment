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
}
