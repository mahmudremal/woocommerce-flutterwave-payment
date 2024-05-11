<?php
/**
 * Internationalize bundle managment
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;
class Affiliatewp {
	use Singleton;
	private $payout_method = false;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		$this->payout_method = 'flutterwave';
		add_filter('affwp_payout_methods', [$this, 'add_payout_method']);
		add_filter('affwp_is_payout_method_enabled', [$this, 'affwp_is_payout_method_enabled'], 10, 2);
		add_action('affwp_notices_registry_init', [$this, 'register_admin_notices']);

		add_action('affwp_process_flutterwave_connect_completion', [$this, 'complete_connection']);
		add_action('affwp_flutterwave_reconnect', [$this, 'reconnect_site']);
		add_action('affwp_flutterwave_disconnect', [$this, 'disconnect_site']);

		add_action('affwp_preview_payout_note_' . $this->payout_method, [$this, 'preview_payout_message']);
		add_action('affwp_process_payout_' . $this->payout_method, [$this, 'process_payout'], 10, 5);

		add_action('affwp_preview_payout_after_referrals_total_' . $this->payout_method, [$this, 'display_fee'], 10, 2);

		add_filter('affwp_settings_tabs', [$this, 'setting_tab']);
		add_filter('affwp_settings', [$this, 'register_settings_legacy']);

	}

	/**
	 * Determines if we are in test mode
	 *
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	public function is_test_mode() {
		return (bool) affiliate_wp()->settings->get('flutterwave_test_mode', false);
	}
	/**
	 * Adds 'Flutterwave' as a payout method to AffiliateWP.
	 *
	 * @since 2.4
	 *
	 * @param array $payout_methods Payout methods.
	 * @return array Filtered payout methods.
	 */
	public function add_payout_method($payout_methods) {

		$vendor_id         = affiliate_wp()->settings->get('flutterwave_vendor_id', 0);
		$access_key        = affiliate_wp()->settings->get('flutterwave_access_key', '');
		$connection_status = affiliate_wp()->settings->get('flutterwave_connection_status', '');

		if ('active' !== $connection_status || ! ($vendor_id && $access_key)) {
			/* translators: 1: Flutterwave settings link */
			$payout_methods[$this->payout_method] = sprintf(__('Flutterwave - <a href="%s">Register and/or connect</a> your account to enable this payout method', 'wooflutter'), affwp_admin_url('settings', ['tab' => $this->payout_method]));
		} else {
			$payout_methods[$this->payout_method] = __('Flutterwave', 'wooflutter');
		}

		return $payout_methods;
	}

	/**
	 * Checks if the 'Flutterwave' payout method is enabled.
	 *
	 * @since 2.4
	 *
	 * @param bool   $enabled       True if the payout method is enabled. False otherwise.
	 * @param string $payout_method Payout method.
	 * @return bool True if the payout method is enabled. False otherwise.
	 */
	public function affwp_is_payout_method_enabled($enabled, $payout_method) {

		if ($this->payout_method === $payout_method) {
			$vendor_id         = affiliate_wp()->settings->get('flutterwave_vendor_id', 0);
			$access_key        = affiliate_wp()->settings->get('flutterwave_access_key', '');
			$connection_status = affiliate_wp()->settings->get('flutterwave_connection_status', '');

			if ('active' !== $connection_status || ! ($vendor_id && $access_key)) {
				$enabled = false;
			}
		}

		return $enabled;
	}

	/**
	 * Adds a note to the preview page for a payout being made via the service.
	 *
	 * @since 2.4
	 *
	 * @return void.
	 */
	public function preview_payout_message() {
		?>
		<h2><?php esc_html_e('Note', 'wooflutter'); ?></h2>
		<p><?php echo esc_html(_x('It takes approximately two weeks for each payout to be deposited into each affiliates bank account when the Flutterwave invoice has been paid.', 'Note shown on the preview payout page for a Flutterwave payout', 'wooflutter')); ?></p>
		<p><?php echo esc_html(_x('For affiliates located in the United States, it takes approximately a week.', 'Note shown on the preview payout page for a Flutterwave payout', 'wooflutter')); ?></p>
		<?php
	}

	/**
	 * Displays the service fee on the preview payout page.
	 *
	 * @since 2.4
	 *
	 * @param float $referrals_total Referrals total.
	 * @param array $data            Payout data.
	 * @return void.
	 */
	public function display_fee($referrals_total, $data) {

		if (empty($data)) {
			return;
		}

		$body_args = array(
			'payout_data'   => $data,
			'currency'      => affwp_get_currency(),
			'affwp_version' => AFFILIATEWP_VERSION,
		);

		$headers = affwp_get_flutterwave_http_headers();

		$args = array(
			'body'      => $body_args,
			'headers'   => $headers,
			'timeout'   => 60,
			'sslverify' => false,
		);

		$request = wp_remote_get(flutterwave_URL . '/wp-json/payouts/v1/fee', $args);

		$response_code = wp_remote_retrieve_response_code($request);
		$response      = json_decode(wp_remote_retrieve_body($request));

		if (! is_wp_error($request) && 200 === (int) $response_code) {

			$payout_service_fee = $response->payout_service_fee;
			$payout_total       = $referrals_total + $payout_service_fee;

		} else {

			$payout_total       = $referrals_total;
			$payout_service_fee = __('Can&#8217;t retrieve Flutterwave fee at the moment', 'wooflutter');

		}

		?>

		<tr class="form-row">

			<th scope="row">
				<?php esc_html_e('Flutterwave Fee', 'wooflutter'); ?>
			</th>

			<td>
				<?php if (is_numeric($payout_service_fee)) : ?>
					<?php echo affwp_currency_filter(affwp_format_amount($payout_service_fee)); ?>
				<?php else : ?>
					<?php echo esc_attr($payout_service_fee); ?>
				<?php endif; ?>
			</td>

		</tr>

		<tr class="form-row">

			<th scope="row">
				<?php echo esc_html(_x('Total', 'Total amount for a Flutterwave payout', 'wooflutter')); ?>
			</th>

			<td>
				<?php echo affwp_currency_filter(affwp_format_amount($payout_total)); ?>
			</td>

		</tr>

		<?php

	}

	/**
	 * Processes payouts in bulk for a specified time frame via the service.
	 *
	 * @since 2.4
	 *
	 * @param string $start         Referrals start date.
	 * @param string $end           Referrals end date data.
	 * @param int    $minimum       Minimum payout.
	 * @param int    $affiliate_id  Affiliate ID.
	 * @param string $payout_method Payout method.
	 *
	 * @return void
	 */
	public function process_payout($start, $end, $minimum, $affiliate_id, $payout_method) {

		$vendor_id         = affiliate_wp()->settings->get('flutterwave_vendor_id', 0);
		$access_key        = affiliate_wp()->settings->get('flutterwave_access_key', '');
		$connection_status = affiliate_wp()->settings->get('flutterwave_connection_status', '');

		if ('active' !== $connection_status || ! ($vendor_id && $access_key)) {

			$message = __('Your website is not connected to the Flutterwave', 'wooflutter');

			$redirect = affwp_admin_url('referrals', array(
				'affwp_notice'     => 'flutterwave_error',
				'affwp_ps_message' => urlencode($message),
			));

			wp_redirect($redirect);
			exit;

		}

		$headers = affwp_get_flutterwave_http_headers();

		$args = array(
			'headers'   => $headers,
			'timeout'   => 60,
			'sslverify' => false,
		);

		$flutterwave_url = add_query_arg(array(
			'affwp_version' => AFFILIATEWP_VERSION,
		), flutterwave_URL . '/wp-json/payouts/v1/vendor');

		$request = wp_remote_get($flutterwave_url, $args);

		$error_redirect_args = array(
			'affwp_notice' => 'flutterwave_error',
		);

		if (is_wp_error($request)) {

			$error_redirect_args['affwp_ps_message'] = urlencode($request->get_error_message());

			$redirect = affwp_admin_url('referrals', $error_redirect_args);

			wp_redirect($redirect);
			exit;

		} else {

			$response      = json_decode(wp_remote_retrieve_body($request));
			$response_code = wp_remote_retrieve_response_code($request);

			if (200 === (int) $response_code) {

				$args = array(
					'status'       => 'unpaid',
					'date'         => array(
						'start' => $start,
						'end'   => $end,
					),
					'number'       => -1,
					'affiliate_id' => $affiliate_id,
				);

				// Final  affiliate / referral data to be paid out.
				$data = array();

				// The affiliates that have earnings to be paid.
				$affiliates = array();

				// The affiliates that can't be paid out.
				$invalid_affiliates = array();

				// Retrieve the referrals from the database.
				$referrals = affiliate_wp()->referrals->get_referrals($args);

				if ($referrals) {

					foreach ($referrals as $referral) {

						if (in_array($referral->affiliate_id, $invalid_affiliates)) {
							continue;
						}

						if (in_array($referral->affiliate_id, $affiliates)) {

							// Add the amount to an affiliate that already has a referral in the export.
							$amount = $data[ $referral->affiliate_id ]['amount'] + $referral->amount;

							$data[ $referral->affiliate_id ]['amount']      = $amount;
							$data[ $referral->affiliate_id ]['referrals'][] = $referral->referral_id;

						} else {

							$payout_service_account = affwp_get_flutterwave_account($referral->affiliate_id);

							if (false !== $payout_service_account['valid']) {

								$data[ $referral->affiliate_id ] = array(
									'account_id' => $payout_service_account['account_id'],
									'amount'     => $referral->amount,
									'referrals'  => array($referral->referral_id),
								);

								$affiliates[] = $referral->affiliate_id;

							} else {

								$invalid_affiliates[] = $referral->affiliate_id;

							}
						}
					}

					$payouts = array();

					$i = 0;

					foreach ($data as $affiliate_id => $payout) {

						if ($minimum > 0 && $payout['amount'] < $minimum) {
							// Ensure the minimum amount was reached.
							unset($data[ $affiliate_id ]);

							// Skip to the next affiliate.
							continue;
						}

						$payouts[ $affiliate_id ] = array(
							'account_id'   => $payout['account_id'],
							'affiliate_id' => $affiliate_id,
							'amount'       => $payout['amount'],
							'referrals'    => $payout['referrals'],
						);

						$i++;
					}

					$response = $this->send_payout_request($payouts);

					if (is_wp_error($response)) {

						$error_redirect_args['affwp_ps_message'] = $response->get_error_message();

						$redirect = affwp_admin_url('referrals', $error_redirect_args);

						// A header is used here instead of wp_redirect() due to the esc_url() bug that removes [] from URLs.
						header('Location:' . $redirect);
						exit;

					} else {

						$payout_invoice_url = esc_url($response->payment_link);
						$payouts_data       = affwp_object_to_array($response->payout_data);

						// We now know which referrals should be marked as paid.
						foreach ($payouts_data as $affiliate_id => $payout) {

							$payout_method = affwp_get_affiliate_meta($affiliate_id, 'flutterwave_payout_method', true);

							if ('bank_account' === $payout_method['payout_method']) {
								$payout_account = $payout_method['bank_name'] . ' (' . $payout_method['account_no'] . ')';
							} else {
								$payout_account = $payout_method['card'];
							}

							$payout_id = affwp_add_payout(array(
								'status'               => 'processing',
								'affiliate_id'         => $affiliate_id,
								'referrals'            => $payout['referrals'],
								'amount'               => $payout['amount'],
								'payout_method'        => $this->payout_method,
								'service_account'      => $payout_account,
								'service_id'           => $response->payout_id,
								'service_invoice_link' => $response->payment_link,
							));

						}

						wp_redirect($payout_invoice_url);
						exit;

					}

				}

			} else {

				$message = $response->message;

				if (empty($message)) {
					$message = __('Unable to process payout request at the moment. Please try again later.', 'wooflutter');
				}

				$error_redirect_args['affwp_ps_message'] = urldecode($message);

				$redirect = affwp_admin_url('referrals', $error_redirect_args);

				wp_redirect($redirect);
				exit;

			}
		}
	}

	/**
	 * Sends a payout request to the service.
	 *
	 * @since 2.4
	 *
	 * @param array $data Optional. Payout Data. Default empty array.
	 * @return bool|WP_Error
	 */
	public function send_payout_request($payouts = array()) {

		$body_args = array(
			'payout_data'   => $payouts,
			'currency'      => affwp_get_currency(),
			'affwp_version' => AFFILIATEWP_VERSION,
		);

		$headers = affwp_get_flutterwave_http_headers();

		$args = array(
			'body'      => $body_args,
			'headers'   => $headers,
			'timeout'   => 60,
			'sslverify' => false,
		);

		affiliate_wp()->utils->log('send_payout_request()', $body_args);

		$request = wp_remote_post(flutterwave_URL . '/wp-json/payouts/v1/payout', $args);

		$response      = json_decode(wp_remote_retrieve_body($request));
		$response_code = wp_remote_retrieve_response_code($request);

		if (200 !== $response_code) {
			$error_response = new \WP_Error($response_code, $response->message);

			affiliate_wp()->utils->log('send_payout_request() request failed', $error_response);

			return $error_response;
		}

		return $response;
	}

	/**
	 * Completes a connection request with the Flutterwave.
	 *
	 * @since 2.4
	 *
	 * @param array $data Optional. Payout Data. Default empty array.
	 * @return void
	 */
	public function complete_connection($data = array()) {

		$errors = new \WP_Error();

		if (! isset($data['token'])) {
			$errors->add('missing_token', 'The token was missing when attempting to complete the Flutterwave connection.');
		}

		if (! current_user_can('manage_affiliate_options')) {
			$errors->add('permission_denied', 'The current user does not have permission to complete the Flutterwave connection.');
		}

		if (headers_sent()) {
			$errors->add('headers_already_sent', 'Headers were already sent by the time the Flutterwave connection completion was attempted.');
		}

		$has_errors = method_exists($errors, 'has_errors') ? $errors->has_errors() : ! empty($errors->errors);

		if (true === $has_errors) {
			affiliate_wp()->utils->log('Flutterwave: complete_connection() failed', $errors);

			return;
		}

		$headers = affwp_get_flutterwave_http_headers(false);

		$body = array(
			'token'    => sanitize_text_field($data['token']),
			'site_url' => home_url(),
		);

		$args = array(
			'body'    => $body,
			'headers' => $headers,
			'timeout' => 60,
		);

		$api_url       = flutterwave_URL . '/wp-json/payouts/v1/vendor/validate-access-key';
		$response      = wp_remote_post($api_url, $args);
		$response_code = wp_remote_retrieve_response_code($response);

		if (is_wp_error($response) || 200 !== $response_code) {
			// Add a debug log entry.
			affiliate_wp()->utils->log('flutterwave_connection_error', $response);

			// Dump a user-friendly error message to the UI.
			$message  = '<p>';
			/* translators: 1: Flutterwave name retrieved from the flutterwave_NAME constant, 2: Flutterwave settings URL */
			$message .= sprintf(__('There was an error connecting to the %1$s. Please <a href="%2$s">try again</a>. If you continue to have this problem, please contact support.', 'wooflutter'),
				flutterwave_NAME,
				esc_url(affwp_admin_url('settings', array('tab' => 'flutterwave')))
			);
			$message .= '</p>';

			wp_die($message);
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);

		$settings = array(
			'flutterwave_access_key'        => $data['access_key'],
			'flutterwave_vendor_id'         => $data['vendor_id'],
			'flutterwave_connection_status' => 'active',
		);

		affiliate_wp()->settings->set($settings, true);

		wp_safe_redirect(affwp_admin_url('settings', array(
			'tab'          => 'flutterwave',
			'affwp_notice' => 'flutterwave_site_connected'
		)));
		exit;
	}

	/**
	 * Reconnect a site to the Flutterwave
	 *
	 * @access public
	 * @since 2.4
	 *
	 * @param array $data Payout Data.
	 *
	 * @return void
	 */
	public function reconnect_site($data = array()) {

		if (! current_user_can('manage_affiliate_options')) {
			wp_die(__('You do not have permission to disconnect the site from the Flutterwave payments', 'wooflutter'));
		}

		if (! isset($_GET['flutterwave_reconnect_nonce']) || ! wp_verify_nonce($_GET['flutterwave_reconnect_nonce'], 'flutterwave_reconnect')) {
			return;
		}

		$headers = affwp_get_flutterwave_http_headers();

		$body = array(
			'site_url' => home_url(),
		);

		$args = array(
			'body'    => $body,
			'headers' => $headers,
			'timeout' => 60,
		);

		$api_url       = flutterwave_URL . '/wp-json/payouts/v1/vendor/reconnect';
		$response      = wp_remote_post($api_url, $args);
		$response_code = wp_remote_retrieve_response_code($response);

		if (is_wp_error($response) || 200 !== $response_code) {
			// Add a debug log entry.
			affiliate_wp()->utils->log('flutterwave_reconnection_failure', $response);

			// Dump a user-friendly error message to the UI.
			/* translators: 1: Flutterwave name retrieved from the flutterwave_NAME constant, 2: Flutterwave settings URL */
			$message = '<p>' . sprintf(__('Unable to reconnect to the %1$s. Please <a href="%2$s">try again</a>. If you continue to have this problem, please contact support.', 'wooflutter'), flutterwave_NAME, esc_url(affwp_admin_url('settings', array('tab' => 'flutterwave')))) . '</p>';
			wp_die($message);
		}

		$settings = array(
			'flutterwave_connection_status' => 'active'
		);

		affiliate_wp()->settings->set($settings, true);

		wp_safe_redirect(affwp_admin_url('settings', array('tab' => 'commissions', 'affwp_notice' => 'flutterwave_site_reconnected')));
		exit;
	}

	/**
	 * Disconnect a site from the Flutterwave
	 *
	 * @access public
	 * @since 2.4
	 *
	 * @param array $data Payout Data.
	 *
	 * @return void
	 */
	public function disconnect_site($data = array()) {

		if (! current_user_can('manage_affiliate_options')) {
			wp_die(__('You do not have permission to disconnect the site from the Flutterwave payments', 'wooflutter'));
		}

		if (! isset($_GET['flutterwave_disconnect_nonce']) || ! wp_verify_nonce($_GET['flutterwave_disconnect_nonce'], 'flutterwave_disconnect')) {
			return;
		}

		$headers = affwp_get_flutterwave_http_headers();

		$body = array(
			'site_url' => home_url(),
		);

		$args = array(
			'body'    => $body,
			'headers' => $headers,
			'timeout' => 60,
		);

		$api_url       = flutterwave_URL . '/wp-json/payouts/v1/vendor/disconnect';
		$response      = wp_remote_post($api_url, $args);
		$response_code = wp_remote_retrieve_response_code($response);

		if (is_wp_error($response) || 200 !== $response_code) {
			// Add a debug log entry.
			affiliate_wp()->utils->log('flutterwave_disconnection_failure', $response);

			/* translators: 1: Flutterwave name retrieved from the flutterwave_NAME constant, 2: Flutterwave settings URL */
			$message = '<p>' . sprintf(__('Unable to disconnect from the %1$s. Please <a href="%2$s">try again</a>. If you continue to have this problem, please contact support.', 'wooflutter'), flutterwave_NAME, esc_url(affwp_admin_url('settings', array('tab' => 'flutterwave')))) . '</p>';
			wp_die($message);
		}

		$settings = array(
			'flutterwave_connection_status' => 'inactive'
		);

		affiliate_wp()->settings->set($settings, true);

		wp_safe_redirect(affwp_admin_url('settings', array(
			'tab'          => 'flutterwave',
			'affwp_notice' => 'flutterwave_site_disconnected'
		)));
		exit;
	}

	/**
	 * Admin notices for success and error messages
	 *
	 * @since 2.4
	 *
	 * @param \AffWP\Admin\Notices_Registry $registry Registry instance.
	 * @return void
	 */
	public function register_admin_notices($registry) {

		if (affwp_is_admin_page() && isset($_REQUEST['affwp_ps_message'])) {

			$message = ! empty($_REQUEST['affwp_ps_message']) ? urldecode($_REQUEST['affwp_ps_message']) : '';

			$registry->add_notice('flutterwave_error', array(
				'class'   => 'error',
				'message' => '<strong>' . __('Error:', 'wooflutter') . '</strong> ' . esc_html($message),
			));
		}
	}

	/**
	 * Register the new settings tab
	 *
	 * @since 1.0
	 *
	 * @param array $tabs The array of tabs.
	 *
	 * @return array
	 */
	public function setting_tab(array $tabs) : array {
		$tabs[$this->payout_method] = __('Fluterwave Payouts', 'wooflutter');
		return $tabs;
	}
	/**
	 * Register the settings for our Flutterwave Payouts tab for older AffiliateWP versions.
	 *
	 * @since 1.0
	 *
	 * @since 1.4.1 It know uses the get_settings() method to get all settings.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return array
	 */
	public function register_settings_legacy($settings) {
		$settings[$this->payout_method] = $this->get_settings();
		return $settings;
	}
	/**
	 * Return the list of settings.
	 *
	 * @since 1.4.1
	 * @return array
	 */
	private function get_settings() {
		return array(
			'flutterwave_payout_mode' => array(
				'name' => __('Payout API to Use', 'wooflutter'),
				'desc' => __('Select the payout method you wish to use. Flutterwave MassPay is an older technology not available to all accounts. See <a href="https://affiliatewp.com/docs/Flutterwave-payouts-installation-and-usage/" target="_blank" rel="noopener noreferrer">documentation</a> for assistance.', 'wooflutter'),
				'type' => 'select',
				'options' => array(
					'api'     => __('API Application', 'wooflutter'),
					'masspay' => __('MassPay', 'wooflutter')
				)
			),
			'flutterwave_test_mode' => array(
				'name' => __('Test Mode', 'wooflutter'),
				'desc' => __('Check this box if you would like to use Flutterwave Payouts in Test Mode', 'wooflutter'),
				'type' => 'checkbox'
			),
			'flutterwave_api_header' => array(
				'name' => __('Flutterwave API Application Credentials', 'wooflutter'),
				'desc' => __('Enter your Flutterwave API Application credentials.', 'wooflutter'),
				'type' => 'header'
			),
			'flutterwave_live_client_id' => array(
				'name' => __('Client ID', 'wooflutter'),
				'desc' => __('Enter your Flutterwave Application\'s Client ID. Create or retrieve these from <a href="https://developer.Flutterwave.com/developer/applications/" target="_blank">Flutterwave\'s Developer portal</a>.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_live_secret' => array(
				'name' => __('Secret', 'wooflutter'),
				'desc' => __('Enter your Flutterwave Application\'s Secret.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_test_client_id' => array(
				'name' => __('Test Client ID', 'wooflutter'),
				'desc' => __('Enter your Sandbox Flutterwave Application\'s Client ID.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_test_secret' => array(
				'name' => __('Test Secret', 'wooflutter'),
				'desc' => __('Enter your Sandbox Flutterwave Application\'s Secret.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_masspay_header' => array(
				'name' => __('Flutterwave MassPay Credentials', 'wooflutter'),
				'desc' => __('Enter your Test API Username.', 'wooflutter'),
				'type' => 'header'
			),
			'flutterwave_test_username' => array(
				'name' => __('Test API Username', 'wooflutter'),
				'desc' => __('Enter your Test API Username.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_test_password' => array(
				'name' => __('Test API Password', 'wooflutter'),
				'desc' => __('Enter your Test API Password.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_test_signature' => array(
				'name' => __('Test API Signature', 'wooflutter'),
				'desc' => __('Enter your Test API Signature.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_live_username' => array(
				'name' => __('Live API Username', 'wooflutter'),
				'desc' => __('Enter your Live API Username.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_live_password' => array(
				'name' => __('Live API Password', 'wooflutter'),
				'desc' => __('Enter your Live API Password.', 'wooflutter'),
				'type' => 'text'
			),
			'flutterwave_live_signature' => array(
				'name' => __('Live API Signature', 'wooflutter'),
				'desc' => __('Enter your Live API Signature.', 'wooflutter'),
				'type' => 'text'
			)
		);
	}

}
