<?php
/**
 * Post request handler
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class Post {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_action('admin_post_nopriv_ctto/project/ajax/taxonomy/order', [$this, 'taxonomy_order'], 10, 0);
		add_action('admin_post_ctto/project/ajax/taxonomy/order', [$this, 'taxonomy_order'], 10, 0);
	}
	public function taxonomy_order() {
		if (check_admin_referer('ctto/project/ajax/taxonomy/order', 'texonomy_order') === false) {
			wp_die(__('Something went wrong. Please try again.', 'wooflutter'), __('Invalid request', 'wooflutter'));
		}
		// 
		wp_redirect(wp_get_referer());
	}
}
