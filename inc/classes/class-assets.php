<?php
/**
 * Enqueue plugin assets
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class Assets {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_action('wp_enqueue_scripts', [$this, 'register_styles']);
		add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
		
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 10, 1);
		// 
		add_filter('wooflutter/project/javascript/siteconfig', [$this, 'siteConfig'], 1, 2);
		add_filter('wooflutter/function/filemtime', [$this, 'filemtime'], 0, 1);
	}
	/**
	 * Enqueue frontend Styles.
	 * @return null
	 */
	public function register_styles() {
		if( $this->allow_enqueue('public', is_admin())) {
			wp_enqueue_style('flutter-public', WOOFLUTTER_BUILD_CSS_URI . '/public.css', [], $this->filemtime(WOOFLUTTER_BUILD_CSS_DIR_PATH . '/public.css'), 'all');
		}
	}
	/**
	 * Enqueue frontend Scripts.
	 * @return null
	 */
	public function register_scripts() {
		if( $this->allow_enqueue('public', is_admin())) {
			wp_enqueue_script('flutter-public', WOOFLUTTER_BUILD_JS_URI . '/public.js', ['jquery'], $this->filemtime(WOOFLUTTER_BUILD_JS_DIR_PATH.'/public.js'), true);
			wp_localize_script('flutter-public', 'fwpSiteConfig', apply_filters('wooflutter/project/javascript/siteconfig', []));
		}
	}
	/**
	 * Enqueue backend Scripts and stylesheet.
	 * @return null
	 */
	public function admin_enqueue_scripts($curr_page) {
		global $post;
		
		if( $this->allow_enqueue('admin', is_admin())) {
			// if(!in_array($curr_page, ['post-new.php', 'post.php', 'edit.php', 'order-terms'])) {return;}
			wp_enqueue_style('flutter-admin', WOOFLUTTER_BUILD_CSS_URI . '/admin.css', [], $this->filemtime(WOOFLUTTER_BUILD_CSS_DIR_PATH . '/admin.css'), 'all');
			wp_enqueue_script('flutter-admin', WOOFLUTTER_BUILD_JS_URI . '/admin.js', ['jquery'], $this->filemtime(WOOFLUTTER_BUILD_JS_DIR_PATH . '/admin.js'), true);
			wp_localize_script('flutter-admin', 'fwpSiteConfig', apply_filters('wooflutter/project/javascript/siteconfig', [], true));
		}
	}
	public function filemtime($path) {
		return (file_exists($path)&&!is_dir($path))?filemtime($path):false;
	}
	public function siteConfig($args, $is_admin = false) {
		$args = wp_parse_args([
			'ajaxUrl'    		=> admin_url('admin-ajax.php'),
			'ajax_nonce' 		=> wp_create_nonce('futurewordpress/project/ctto/verify/nonce'),
			'is_admin' 			=> is_admin(),
			'buildPath'  		=> WOOFLUTTER_BUILD_URI,
			'local'				=> apply_filters('ctto/project/system/get_locale', get_user_locale()),
			'post_id'			=> is_singular()?get_the_ID():false
		], (array) $args);
		
		if ($is_admin) {
			// admin scripts here
		} else {
			// public scripts here.
			$args['notifications'] = apply_filters('ctto/project/assets/notifications', false, []);
		}
		// 
		return $args;
	}
	/**
	 * Return bool for allowing specific enqueue methods
	 * 
	 * @param string $for is the key of the addon to check the allowance of the script
	 * @param bool $isAdmin is to verify whether it is admin side of frontend side.
	 * 
	 * @return bool Return the result true if it is allowed either false for now allowing.
	 */
	public function allow_enqueue(string $for, bool $isAdmin) {
		return true;
	}
}
