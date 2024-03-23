<?php
/**
 * Ajax request handler
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class Ajax {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		// add_action('wp_ajax_nopriv_ctto/ajax/post/content', [$this, 'overalies_texts'], 10, 0);
		// add_action('wp_ajax_ctto/ajax/post/content', [$this, 'overalies_texts'], 10, 0);
	}
	public function overalies_texts() {
		// 
	}
}
