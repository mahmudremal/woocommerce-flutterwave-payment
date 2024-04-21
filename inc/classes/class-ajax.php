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
		add_action('wp_ajax_nopriv_wooflutter/ajax/bank/branches', [$this, 'bank_branches'], 10, 0);
		add_action('wp_ajax_wooflutter/ajax/bank/branches', [$this, 'bank_branches'], 10, 0);
	}
	public function bank_branches() {
		global $WooFlutter_Flutterwave;$json = [];
		if (isset($_POST['bank_id'])) {
			try {
				$WooFlutter_Flutterwave->set_api_key((object) [
					'secret_key'        => 'FLWSECK_TEST-efbcc17ffdfd40b3f431944232a2e7fc-X',
					'live_encript_key'  => 'd0fa12b2f7dcc64a529d9897',
				]);
				$json = $WooFlutter_Flutterwave->get_branches($_POST['bank_id']);
				wp_send_json_success($json);
			} catch (\Exception $th) {
				//throw $th;
			}
		}
		wp_send_json_error($json);
	}
}
