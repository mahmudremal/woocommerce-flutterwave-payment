<?php
/**
 * This plugin ordered by a client and done by Remal Mahmud (github.com/mahmudremal). Authority dedicated to that cient.
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Flutterwave Payments
 * Plugin URI:        https://github.com/mahmudremal/woocommerce-flutterwave-payment/
 * Description:       Flutterwave WooCommerce payment extension takes payments on multiple methods more securely and reliably.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Remal Mahmud
 * Author URI:        https://github.com/mahmudremal/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wooflutter
 * Domain Path:       /languages
 * 
 * @package WooFlutter
 * @author  Remal Mahmud (https://github.com/mahmudremal)
 * @version 1.0.2
 * @link https://github.com/mahmudremal/woocommerce-flutterwave-payment/
 * @category	WordPress Plugin
 * @copyright	Copyright (c) 2024-26
 * 
 */
/**
 * Bootstrap the plugin.
 */


defined('WOOFLUTTER_FILE__') || define('WOOFLUTTER_FILE__', untrailingslashit(__FILE__));
defined('WOOFLUTTER_DIR_PATH') || define('WOOFLUTTER_DIR_PATH', untrailingslashit(plugin_dir_path(WOOFLUTTER_FILE__)));
defined('WOOFLUTTER_DIR_URI') || define('WOOFLUTTER_DIR_URI', untrailingslashit(plugin_dir_url(WOOFLUTTER_FILE__)));
defined('WOOFLUTTER_BUILD_URI') || define('WOOFLUTTER_BUILD_URI', untrailingslashit(WOOFLUTTER_DIR_URI) . '/assets/build');
defined('WOOFLUTTER_BUILD_PATH') || define('WOOFLUTTER_BUILD_PATH', untrailingslashit(WOOFLUTTER_DIR_PATH) . '/assets/build');
defined('WOOFLUTTER_BUILD_JS_URI') || define('WOOFLUTTER_BUILD_JS_URI', untrailingslashit(WOOFLUTTER_DIR_URI) . '/assets/build/js');
defined('WOOFLUTTER_BUILD_JS_DIR_PATH') || define('WOOFLUTTER_BUILD_JS_DIR_PATH', untrailingslashit(WOOFLUTTER_DIR_PATH) . '/assets/build/js');
defined('WOOFLUTTER_BUILD_IMG_URI') || define('WOOFLUTTER_BUILD_IMG_URI', untrailingslashit(WOOFLUTTER_DIR_URI) . '/assets/build/src/img');
defined('WOOFLUTTER_BUILD_CSS_URI') || define('WOOFLUTTER_BUILD_CSS_URI', untrailingslashit(WOOFLUTTER_DIR_URI) . '/assets/build/css');
defined('WOOFLUTTER_BUILD_CSS_DIR_PATH') || define('WOOFLUTTER_BUILD_CSS_DIR_PATH', untrailingslashit(WOOFLUTTER_DIR_PATH) . '/assets/build/css');
defined('WOOFLUTTER_BUILD_LIB_URI') || define('WOOFLUTTER_BUILD_LIB_URI', untrailingslashit(WOOFLUTTER_DIR_URI) . '/assets/build/library');
defined('WOOFLUTTER_ARCHIVE_POST_PER_PAGE') || define('WOOFLUTTER_ARCHIVE_POST_PER_PAGE', 9);
defined('WOOFLUTTER_SEARCH_RESULTS_POST_PER_PAGE') || define('WOOFLUTTER_SEARCH_RESULTS_POST_PER_PAGE', 9);
defined('WOOFLUTTER_OPTIONS') || define('WOOFLUTTER_OPTIONS', get_option('ctto'));
defined('WOOFLUTTER_UPLOAD_DIR') || define('WOOFLUTTER_UPLOAD_DIR', wp_upload_dir()['basedir'].'/custom_popup/');
defined('WOOFLUTTER_AUDIO_DURATION') || define('WOOFLUTTER_AUDIO_DURATION', 20);

require_once WOOFLUTTER_DIR_PATH . '/inc/helpers/autoloader.php';
// require_once WOOFLUTTER_DIR_PATH . '/inc/helpers/template-tags.php';


try {
	if (!function_exists('wooflutter_get_instance')) {
		function wooflutter_get_instance() {\WOOFLUTTER\inc\Project::get_instance();}
		wooflutter_get_instance();
	}
} catch (\Exception $e) {
	// echo "Exception: " . $e->getMessage();
} catch (\Error $e) {
	// echo "Error: " . $e->getMessage();
} finally {
	// Optional code that always runs
	// echo "Finally block executed.";
}
