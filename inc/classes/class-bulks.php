<?php
/**
 * Archive Settings
 *
 * @package GravityformsFlutterwaveAddons
 */
namespace WOOFLUTTER\Inc;
use WOOFLUTTER\Inc\Traits\Singleton;
class Bulks {
	use Singleton;
	private $args;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
	}

}
