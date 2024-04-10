<?php
/**
 * Internationalize bundle managment
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;
class Shortcodes {
	use Singleton;
	private $translations = [];
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		// add_shortcode('wooflutter_status', [$this, 'wooflutter_status']);
	}
	public function wooflutter_status($args) {
		$args = wp_parse_args($args, []);
		// 
	}
}
