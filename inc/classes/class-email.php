<?php
/**
 * WP E-Signature integration plugin.
 *
 * @package GravityformsFlutterwaveAddons
 */
namespace WOOFLUTTER\Inc;
use WOOFLUTTER\Inc\Traits\Singleton;
class Email {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		// add_action('init', [$this, 'init'], 10, 0);
	}
	public function init() {
		// 
	}
}
