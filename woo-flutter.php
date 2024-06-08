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

defined('WOOFLUTTER_VERSION') || define('WOOFLUTTER_VERSION', '1.0.2');
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
defined('WOOFLUTTER_OPTIONS') || define('WOOFLUTTER_OPTIONS', get_option('wooflutter'));
// , 'gform'
defined('WOOFLUTTER_WIDGETS') || define('WOOFLUTTER_WIDGETS', (array) get_option('wooflutter-widgets', ['woo', 'dokan', 'affiliatewp']));

defined('WOOFLUTTER_TEST_MODE') || define('WOOFLUTTER_TEST_MODE', (bool)(isset(WOOFLUTTER_OPTIONS['testMode']) && WOOFLUTTER_OPTIONS['testMode']));
defined('WOOFLUTTER_MAX_COMISSION') || define('WOOFLUTTER_MAX_COMISSION', 98.6);
defined('WOOFLUTTER_ENABLE_CARD_FEATURE') || define('WOOFLUTTER_ENABLE_CARD_FEATURE', false);

require_once WOOFLUTTER_DIR_PATH . '/inc/helpers/autoloader.php';
// require_once WOOFLUTTER_DIR_PATH . '/inc/helpers/template-tags.php';


try {
	if (!function_exists('wooflutter_get_instance')) {
		function wooflutter_print($args = []) {
			echo '<pre>';print_r($args);wp_die('Remal Mahmud (mahmudremal@yahoo.com)');echo '</pre>';
		}
		function wooflutter_get_instance() {\WOOFLUTTER\inc\Project::get_instance();}
		wooflutter_get_instance();
	}
} catch (\Exception $e) {
	wp_die($e->getMessage(), 'Exception');
} catch (\Error $e) {
	wp_die($e->getMessage(), 'Error');
} finally {
	// Optional code that always runs
	// echo "Finally block executed.";
}

