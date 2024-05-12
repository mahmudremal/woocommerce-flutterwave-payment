<?php
/**
 * Theme Sidebars.
 *
 * @package GravityformsFlutterwaveAddons
 */
namespace WOOFLUTTER\Inc;
defined('ABSPATH') || die();
add_action('wp', ['WOOFLUTTER\Inc\GFFlutterWave', 'maybe_thankyou_page'], 5);
\GFForms::include_payment_addon_framework();
class GFFlutterWave extends \GFPaymentAddOn {
	protected $_version = WOOFLUTTER_VERSION;
	protected $_min_gravityforms_version = '1.9.3';
	protected $_slug = 'wooflutter';
	protected $_path = WOOFLUTTER_FILE__;
	protected $_full_path = WOOFLUTTER_FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms FlutterWave Standard Add-On';
	protected $_short_title = 'Flutterwave';
	protected $_supports_callbacks = true;
	private $production_url = 'https://www.flutterwave.com/cgi-bin/webscr/';
	private $sandbox_url = 'https://www.sandbox.flutterwave.com/cgi-bin/webscr/';
	// Members plugin integration
	protected $_capabilities = ['gravityforms_flutterwave', 'gravityforms_flutterwave_uninstall'];
	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_flutterwave';
	protected $_capabilities_form_settings = 'gravityforms_flutterwave';
	protected $_capabilities_uninstall = 'gravityforms_flutterwave_uninstall';
    protected $_requires_credit_card = false;
	// Automatic upgrade enabled
	protected $_enable_rg_autoupgrade = true;
	private static $_instance = null;
	public static function get_instance() {
		if (self::$_instance == null) {
			self::$_instance = new GFFlutterWave();
		}
		return self::$_instance;
	}
	private function __clone() {
	} /* do nothing */
	public function init_frontend() {
		parent::init_frontend();
		add_filter('gform_disable_post_creation', [$this, 'delay_post'], 10, 3);
		add_filter('gform_disable_notification', [$this, 'delay_notification'], 10, 4);
	}
	/**
	 * Returns what should be used to prepare the payment amount; form_total or the ID of a specific product field.
	 *
	 * @since 3.3
	 *
	 * @param array $feed The current feed.
	 *
	 * @return string
	 */
	public function get_payment_field($feed) {
		switch (rgars($feed, 'meta/transactionType')) {
			case 'subscription':
				$key = 'recurringAmount';
				break;
			case 'product':
			case 'donation':
				$key = 'paymentAmount';
				break;
		}
		return rgars($feed, 'meta/' . $key, 'form_total');
	}
	//----- SETTINGS PAGES ----------//
	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 3.2
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return file_get_contents(apply_filters('wooflutter/path/fix/slashes', WOOFLUTTER_BUILD_PATH . '/icons/flutterwave.svg'));
	}
	/**
	 * Register needed styles.
	 *
	 * @since  3.2
	 *
	 * @return array $styles
	 */
	public function styles() {
		// $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG || isset($_GET['gform_debug']) ? '' : '.min';
		$styles = [
            [
                'handle'  => 'gform_flutterwave_form_settings_css',
				'src'     => WOOFLUTTER_BUILD_CSS_URI . "/gform_admin.css", // {$min}
				'version' => apply_filters('wooflutter/function/filemtime', apply_filters('wooflutter/path/fix/slashes', WOOFLUTTER_BUILD_CSS_DIR_PATH . "/gform_admin.css")),
				'enqueue' => [
                    [
                        'admin_page' => ['form_settings', 'plugin_settings'],
                        'tab'        => $this->_slug
                    ],
                ],
            ],
        ];
		return array_merge(parent::styles(), $styles);
	}
	/**
	 * Register needed scripts.
	 *
	 * @since  3.2
	 *
	 * @return array $scripts
	 */
	public function scripts() {
		// $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG || isset($_GET['gform_debug']) ? '' : '.min';
		$scripts = [
            [
                'handle'  => 'gform_flutterwave_form_settings_js',
				'src'     => WOOFLUTTER_BUILD_JS_URI . "/gform_admin.js", // {$min}
				'version' => apply_filters('wooflutter/function/filemtime', apply_filters('wooflutter/path/fix/slashes', WOOFLUTTER_BUILD_JS_DIR_PATH . "/gform_admin.js")),
				'enqueue' => [
                    [
                        'admin_page' => ['form_settings', 'plugin_settings'], // form_editor, form_settings, plugin_settings, plugin_page, entry_view, entry_detail, results, block_editor
                        'tab'        => $this->_slug
                    ],
                ],
            ],
        ];
        // 
        // wp_localize_script('gform_flutterwave_form_settings_js', 'fwpSiteConfig', apply_filters('wooflutter/project/javascript/siteconfig', []));
		return array_merge(parent::scripts(), $scripts);
	}
	public function plugin_settings_fields() {
		/*
			$description = '
				<p style="text-align: left;">' .
				esc_html__('Gravity Forms requires IPN to be enabled on your FlutterWave account. Follow the following steps to confirm IPN is enabled.', 'wooflutter') .
				'</p>
				<!--
				<ul>
					<li>' . sprintf(esc_html__('Navigate to your FlutterWave %sIPN Settings page.%s', 'wooflutter'), '<a href="https://www.flutterwave.com/us/cgi-bin/webscr?cmd=_profile-ipn-notify" target="_blank">', '</a>') . '</li>' .
					'<li>' . esc_html__('If IPN is already enabled, you will see your current IPN settings along with a button to turn off IPN. If that is the case, just check the confirmation box below and you are ready to go!', 'wooflutter') . '</li>' .
					'<li>' . esc_html__("If IPN is not enabled, click the 'Choose IPN Settings' button.", 'wooflutter') . '</li>' .
					'<li>' . sprintf(esc_html__('Click the box to enable IPN and enter the following Notification URL: %s', 'wooflutter'), '<strong>' . esc_url($this->get_callback_url()) . '</strong>') . '</li>' .
				'</ul>
				-->
			<br/>';
		*/
		$description = sprintf(
			__('Gravity Forms integration with FlutterWave payments will work on both Gravity Forms and WooCommerce plugins. A secret key is mostly required to connect with FlutterWave. If you don\'t have this API key, you can %sfollow this link.%s', 'wooflutter'),
			'<a href="https://app.flutterwave.com/dashboard/settings/apis/live/" target="_blank">', '</a>'
		);
		return [
            [
                'title'       => '',
				'description' => $description,
				'fields'      => [
                    [
                        'name'    => 'gf_flutterwave_testMode',
						'label'   => esc_html__('Test mode', 'wooflutter'),
						'type'    => 'checkbox',
						'choices' => [
                            ['label' => esc_html__('Check if you want to enable test mode.', 'wooflutter'), 'name' => 'gf_flutterwave_testMode'],
                        ]
                    ],
                    [
                        'name'    => 'gf_flutterwave_publickey',
						'label'   => esc_html__('Public Key', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Public Key', 'wooflutter') . '</h6>' . esc_html__('Enter your Public Key, if you do not have a key you can register for one at the provided link.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_secretkey',
						'label'   => esc_html__('Secret Key', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Secret Key', 'wooflutter') . '</h6>' . esc_html__('Enter your Secret Key, if you do not have a key you can register for one at the provided link.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_encryptionkey',
						'label'   => esc_html__('Encryption Key', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Encryption Key', 'wooflutter') . '</h6>' . esc_html__('Enter your Encryption Key, if you do not have a key you can register for one at the provided link.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_statusBtnLink',
						'label'   => esc_html__('Success page link', 'wooflutter'),
						'type'    => 'select',
                        'choices' => [
                            ['label' => __('Form page', 'wooflutter'), 'name' => 'form'],
                            ['label' => __('Home page', 'wooflutter'), 'name' => 'home'],
                        ],
						'tooltip'  => '<h6>' . esc_html__('Success page link', 'wooflutter') . '</h6>' . esc_html__('Select a Success page link. Payment returned status page button link. Set homepage to setup Back to home like, site homepage link. Selecting form will set button link form entry screen link.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_amountZeroMsg',
						'label'   => esc_html__('Amount required message', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Amount required message', 'wooflutter') . '</h6>' . esc_html__('This message will be the default message on settings field if the calculated amount is zero.
                        ', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_paymentSuccess',
						'label'   => esc_html__('Success status', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Success status', 'wooflutter') . '</h6>' . esc_html__('Give here a long success message that will be display on payment success page. With the confirmation message that the form submitted successfully.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_paymentFailed',
						'label'   => esc_html__('Failed status', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Failed status', 'wooflutter') . '</h6>' . esc_html__('Give here a long error message that will be display on payment failed/cancelled/denaid status. With the confirmation message that the form didn\'t submitted.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_paymentReminderSubject',
						'label'   => esc_html__('Mail reminder subject', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Mail reminder subject', 'wooflutter') . '</h6>' . esc_html__('Give here a long error message that will be display on payment failed/cancelled/denaid status. With the confirmation message that the form didn\'t submitted.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_paymentReminder',
						'label'   => esc_html__('Payment Reminder', 'wooflutter'),
						'type'    => 'button',
                        'value'   => __('Edit template', 'wooflutter'),
						'tooltip'  => '<h6>' . esc_html__('Payment Reminder', 'wooflutter') . '</h6>' . esc_html__('Give here any html template that will be applied for payment reminder email template from Entry list screen. Following tags could be applicable on this template. {{mailImagePath}}, {{customFullName}}, {{senderFullName}}, {{dateMMMMdd}}, {{dateYYYMMDD}}, {{productName}}, {{invoiceNumber}}, {{siteEmail}}, {{siteURL}}, {{siteAddress}}, {{customAddressFull}}, {{invoiceIssuedOn}}, {{invoiceUnit}}, {{invoiceTotal}}, {{invoiceTax}}, {{invoiceSubtotal}}', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_enableReadOnly',
						'label'   => esc_html__('Default value for sub accounts commission', 'wooflutter'),
						'type'    => 'checkbox',
                        'choices' => [
                            ['label' => __('Enable read only', 'wooflutter'), 'name' => 'enableReadOnly', 'tooltip'  => '<h6>' . esc_html__('Read Only', 'wooflutter') . '</h6>' . esc_html__('Enable read only on the default value for sub accounts commission. In this case, user will no longer set comission from form settings.', 'wooflutter')]
                        ],
                    ],
                    [
                        'name'    => 'gf_flutterwave_defaultComission-client',
						'label'   => esc_html__('Service provider percentage Commission', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Comission', 'wooflutter') . '</h6>' . esc_html__('Set a default comission for the following sub account.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_defaultComission-partner',
						'label'   => esc_html__('Partner percentage Commission', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Comission', 'wooflutter') . '</h6>' . esc_html__('Set a default comission for the following sub account.', 'wooflutter')
                    ],
                    [
                        'name'    => 'gf_flutterwave_defaultComission-staff',
						'label'   => esc_html__('Agent percentage Commission', 'wooflutter'),
						'type'    => 'text',
						'tooltip'  => '<h6>' . esc_html__('Comission', 'wooflutter') . '</h6>' . esc_html__('Set a default comission for the following sub account.', 'wooflutter')
                    ],
                ],
            ]
        ];
	}
    private function isConfigured() {
        $settings = $this->get_plugin_settings();
        return (
            !empty(rgar($settings, 'gf_flutterwave_publickey')) && 
            !empty(rgar($settings, 'gf_flutterwave_secretkey')) && 
            true
		);
    }
	public function feed_list_no_item_message() {
		if (! $this->isConfigured()) {
			return sprintf(esc_html__('To get started, please configure your %sFlutterWave Settings%s!', 'wooflutter'), '<a href="' . admin_url('admin.php?page=gf_settings&subview=' . $this->_slug) . '">', '</a>');
		} else {
			return parent::feed_list_no_item_message();
		}
	}
	public function feed_settings_fields() {
		$default_settings = parent::feed_settings_fields();
		$fields = [
			[
				'name'     => 'flutterwaveEmail',
				'label'    => esc_html__('FlutterWave Email Address ', 'wooflutter'),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => true,
				'tooltip'  => '<h6>' . esc_html__('FlutterWave Email Address', 'wooflutter') . '</h6>' . esc_html__('Enter the FlutterWave email address where payment should be received.', 'wooflutter')
			],
			[
				'name'          => 'mode',
				'label'         => esc_html__('Mode', 'wooflutter'),
				'type'          => 'radio',
				'choices'       => [
					['id' => 'gf_flutterwave_mode_production', 'label' => esc_html__('Production', 'wooflutter'), 'value' => 'production'],
					['id' => 'gf_flutterwave_mode_test', 'label' => esc_html__('Test', 'wooflutter'), 'value' => 'test']
				],
				'horizontal'    => true,
				'default_value' => 'production',
				'tooltip'       => '<h6>' . esc_html__('Mode', 'wooflutter') . '</h6>' . esc_html__('Select Production to receive live payments. Select Test for testing purposes when using the FlutterWave development sandbox.', 'wooflutter')
			],
		];
		$default_settings = parent::add_field_after('feedName', $fields, $default_settings);
		//--------------------------------------------------------------------------------------
		//--add donation to transaction type drop down
		$transaction_type = parent::get_field('transactionType', $default_settings);
		$choices          = $transaction_type['choices'];
		$add_donation     = true;
		foreach ($choices as $choice) {
			//add donation option if it does not already exist
			if ($choice['value'] == 'donation') {
				$add_donation = false;
			}
		}
		if ($add_donation) {
			//add donation transaction type
			$choices[] = ['label' => __('Donations', 'wooflutter'), 'value' => 'donation'];
		}
		$transaction_type['choices'] = $choices;
		$default_settings            = $this->replace_field('transactionType', $transaction_type, $default_settings);
		//-------------------------------------------------------------------------------------------------
		//--add Image URL, Cancel URL
		$fields = [
			[
				'name'     => 'imageURL',
				'label'    => esc_html__('Image URL', 'wooflutter'),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => false,
				'tooltip'  => '<h6>' . esc_html__('Image URL', 'wooflutter') . '</h6>' . esc_html__('This option allows you to enter the URL of the 150x50-pixel image displayed as your logo in the upper left corner of the FlutterWave checkout pages. Default is your business name, if you have a FlutterWave Business account or your email address, if you have FlutterWave Premier or Personal account.', 'wooflutter')
			],
			[
				'name'     => 'cancelUrl',
				'label'    => esc_html__('Cancel URL', 'wooflutter'),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => false,
				'tooltip'  => '<h6>' . esc_html__('Cancel URL', 'wooflutter') . '</h6>' . esc_html__('Enter the URL the user should be sent to should they cancel before completing their FlutterWave payment.', 'wooflutter')
			],
			[
				'name'    => 'options',
				'label'   => esc_html__('Options', 'wooflutter'),
				'type'    => 'options',
				'tooltip' => '<h6>' . esc_html__('Options', 'wooflutter') . '</h6>' . esc_html__('Turn on or off the available FlutterWave checkout options.', 'wooflutter'),
				'choices' => [
					['label' => esc_html__('Do not prompt buyer to include a shipping address.', 'wooflutter'), 'name'  => 'disableShipping'],
					['label' => esc_html__('Do not prompt buyer to include a note with payment.', 'wooflutter'), 'name'  => 'disableNote']
				],
			]
		];
		if ($this->get_setting('delayNotification') || ! $this->is_gravityforms_supported('1.9.12')) {
			$fields[] = [
				'name'    => 'notifications',
				'label'   => esc_html__('Notifications', 'wooflutter'),
				'type'    => 'notifications',
				'tooltip' => '<h6>' . esc_html__('Notifications', 'wooflutter') . '</h6>' . esc_html__("Enable this option if you would like to only send out this form's notifications for the 'Form is submitted' event after payment has been received. Leaving this option disabled will send these notifications immediately after the form is submitted. Notifications which are configured for other events will not be affected by this option.", 'wooflutter')
			];
		}
		//Add post fields if form has a post
		$form = $this->get_current_form();
		if (\GFCommon::has_post_field($form['fields'])) {
			$post_settings = [
				'name'    => 'post_checkboxes',
				'label'   => esc_html__('Posts', 'wooflutter'),
				'type'    => 'checkbox',
				'tooltip' => '<h6>' . esc_html__('Posts', 'wooflutter') . '</h6>' . esc_html__('Enable this option if you would like to only create the post after payment has been received.', 'wooflutter'),
				'choices' => [
					['label' => esc_html__('Create post only when payment is received.', 'wooflutter'), 'name' => 'delayPost']
				],
			];
			if ($this->get_setting('transactionType') == 'subscription') {
				$post_settings['choices'][] = [
					'label'    => esc_html__('Change post status when subscription is canceled.', 'wooflutter'),
					'name'     => 'change_post_status',
					'onChange' => 'var action = this.checked ? "draft" : ""; jQuery("#update_post_action").val(action);',
				];
			}
			$fields[] = $post_settings;
		}
		//Adding custom settings for backwards compatibility with hook 'gform_flutterwave_add_option_group'
		$fields[] = [
			'name'  => 'custom_options', 'label' => '', 'type'  => 'custom'
		];
		$default_settings = $this->add_field_after('billingInformation', $fields, $default_settings);
		//-----------------------------------------------------------------------------------------
		//--get billing info section and add customer first/last name
		$billing_info   = parent::get_field('billingInformation', $default_settings);
		$billing_fields = $billing_info['field_map'];
		$add_first_name = true;
		$add_last_name  = true;
		foreach ($billing_fields as $mapping) {
			//add first/last name if it does not already exist in billing fields
			if ($mapping['name'] == 'firstName') {
				$add_first_name = false;
			} else if ($mapping['name'] == 'lastName') {
				$add_last_name = false;
			}
		}
		if ($add_last_name) {
			//add last name
			array_unshift($billing_info['field_map'], ['name' => 'lastName', 'label' => esc_html__('Last Name', 'wooflutter'), 'required' => false]);
		}
		if ($add_first_name) {
			array_unshift($billing_info['field_map'], ['name' => 'firstName', 'label' => esc_html__('First Name', 'wooflutter'), 'required' => false]);
		}
		$default_settings = parent::replace_field('billingInformation', $billing_info, $default_settings);
		//----------------------------------------------------------------------------------------------------
		//hide default display of setup fee, not used by FlutterWave Standard
		$default_settings = parent::remove_field('setupFee', $default_settings);
		//--add trial period
		$trial_period     = [
			'name'    => 'trialPeriod',
			'label'   => esc_html__('Trial Period', 'wooflutter'),
			'type'    => 'trial_period',
			'hidden'  => ! $this->get_setting('trial_enabled'),
			'tooltip' => '<h6>' . esc_html__('Trial Period', 'wooflutter') . '</h6>' . esc_html__('Select the trial period length.', 'wooflutter')
		];
		$default_settings = parent::add_field_after('trial', $trial_period, $default_settings);
		//-----------------------------------------------------------------------------------------
		//--Add Try to bill again after failed attempt.
		$recurring_retry  = [
			'name'       => 'recurringRetry',
			'label'      => esc_html__('Recurring Retry', 'wooflutter'),
			'type'       => 'checkbox',
			'horizontal' => true,
			'choices'    => [
				['label' => esc_html__('Try to bill again after failed attempt.', 'wooflutter'), 'name' => 'recurringRetry', 'value' => '1']
			],
			'tooltip'    => '<h6>' . esc_html__('Recurring Retry', 'wooflutter') . '</h6>' . esc_html__('Turn on or off whether to try to bill again after failed attempt.', 'wooflutter')
		];
		$default_settings = parent::add_field_after('recurringTimes', $recurring_retry, $default_settings);
		//-----------------------------------------------------------------------------------------------------
		/**
		 * Filter through the feed settings fields for the FlutterWave feed
		 *
		 * @param array $default_settings The Default feed settings
		 * @param array $form The Form object to filter through
		 */
		return apply_filters('gform_flutterwave_feed_settings_fields', $default_settings, $form);
	}
    //This function returns the default_settings
    public static function feed_settings_fields_fields() {
        return parent::feed_settings_fields();
    }
	public function supported_billing_intervals() {
		$billing_cycles = [
			'day'   => ['label' => esc_html__('day(s)', 'wooflutter'), 'min' => 1, 'max' => 90],
			'week'  => ['label' => esc_html__('week(s)', 'wooflutter'), 'min' => 1, 'max' => 52],
			'month' => ['label' => esc_html__('month(s)', 'wooflutter'), 'min' => 1, 'max' => 24],
			'year'  => ['label' => esc_html__('year(s)', 'wooflutter'), 'min' => 1, 'max' => 5],
		];
		return $billing_cycles;
	}
	public function field_map_title() {
		return esc_html__('FlutterWave Field', 'wooflutter');
	}
	public function settings_trial_period($field, $echo = true) {
		//use the parent billing cycle function to make the drop down for the number and type
		$html = parent::settings_billing_cycle($field, false);
		if ($echo) {echo $html;}
		return $html;
	}
	public function set_trial_onchange($field) {
		//return the javascript for the onchange event
		$row_id = $this->is_gravityforms_supported('2.5-dev-1') ? '#gform_setting_trialPeriod' : '#gaddon-setting-row-trialPeriod';
		return "
		if (jQuery(this).prop('checked')) {
			jQuery('#{$field['name']}_product').show('slow');
			jQuery('{$row_id}').show('slow');
			if (jQuery('#{$field['name']}_product').val() == 'enter_amount'){
				jQuery('#{$field['name']}_amount').show('slow');
			}
			else{
				jQuery('#{$field['name']}_amount').hide();
			}
		}
		else {
			jQuery('#{$field['name']}_product').hide('slow');
			jQuery('#{$field['name']}_amount').hide();
			jQuery('{$row_id}').hide('slow');
		}";
	}
	public function settings_options($field, $echo = true) {
		$html = $this->settings_checkbox($field, false);
		//--------------------------------------------------------
		//For backwards compatibility.
		ob_start();
		do_action('gform_flutterwave_action_fields', $this->get_current_feed(), $this->get_current_form());
		$html .= ob_get_clean();
		//--------------------------------------------------------
		if ($echo) {echo $html;}
		return $html;
	}
	public function settings_custom($field, $echo = true) {
		ob_start();
		?>
		<div id='gf_flutterwave_custom_settings'>
			<?php
			do_action('gform_flutterwave_add_option_group', $this->get_current_feed(), $this->get_current_form());
			?>
		</div>
		<?php
		$html = ob_get_clean();
		if ($echo) {echo $html;}
		return $html;
	}
	public function settings_notifications($field, $echo = true) {
		$checkboxes = [
			'name'    => 'delay_notification',
			'type'    => 'checkboxes',
			'onclick' => 'ToggleNotifications();',
			'choices' => [
				['label' => esc_html__("Send notifications for the 'Form is submitted' event only when payment is received.", 'wooflutter'), 'name'  => 'delayNotification']
			]
		];
		$html = $this->settings_checkbox($checkboxes, false);
		$html .= $this->settings_hidden(['name' => 'selectedNotifications', 'id' => 'selectedNotifications'], false);
		$form                      = $this->get_current_form();
		$has_delayed_notifications = $this->get_setting('delayNotification');
		ob_start();
		?>
		<ul id="gf_flutterwave_notification_container" style="padding-left:20px; margin-top:10px; <?php echo $has_delayed_notifications ? '' : 'display:none;' ?>">
			<?php
			if (! empty($form) && is_array($form['notifications'])) {
				$selected_notifications = $this->get_setting('selectedNotifications');
				if (! is_array($selected_notifications)) {
					$selected_notifications = [];
				}
				//$selected_notifications = empty($selected_notifications) ? [] : json_decode($selected_notifications);
				$notifications = \GFCommon::get_notifications('form_submission', $form);
				foreach ($notifications as $notification) {
					?>
					<li class="gf_flutterwave_notification">
						<input type="checkbox" class="notification_checkbox" value="<?php echo $notification['id'] ?>" onclick="SaveNotifications();" <?php checked(true, in_array($notification['id'], $selected_notifications)) ?> />
						<label class="inline" for="gf_flutterwave_selected_notifications"><?php echo $notification['name']; ?></label>
					</li>
				<?php
				}
			}
			?>
		</ul>
		<script type='text/javascript'>
			function SaveNotifications() {
				var notifications = [];
				jQuery('.notification_checkbox').each(function () {
					if (jQuery(this).is(':checked')) {
						notifications.push(jQuery(this).val());
					}
				});
				jQuery('#selectedNotifications').val(jQuery.toJSON(notifications));
			}
			function ToggleNotifications() {
				var container = jQuery('#gf_flutterwave_notification_container');
				var isChecked = jQuery('#delaynotification').is(':checked');
				if (isChecked) {
					container.slideDown();
					jQuery('.gf_flutterwave_notification input').prop('checked', true);
				}
				else {
					container.slideUp();
					jQuery('.gf_flutterwave_notification input').prop('checked', false);
				}
				SaveNotifications();
			}
		</script>
		<?php
		$html .= ob_get_clean();
		if ($echo) {
			echo $html;
		}
		return $html;
	}
	public function checkbox_input_change_post_status($choice, $attributes, $value, $tooltip) {
		$markup = $this->checkbox_input($choice, $attributes, $value, $tooltip);
		$dropdown_field = [
			'name'     => 'update_post_action',
			'choices'  => [
				['label' => ''],
				['label' => esc_html__('Mark Post as Draft', 'wooflutter'), 'value' => 'draft'],
				['label' => esc_html__('Delete Post', 'wooflutter'), 'value' => 'delete'],
			],
			'onChange' => "var checked = jQuery(this).val() ? 'checked' : false; jQuery('#change_post_status').attr('checked', checked);",
		];
		$markup .= '&nbsp;&nbsp;' . $this->settings_select($dropdown_field, false);
		return $markup;
	}
	/**
	 * Prevent the GFPaymentAddOn version of the options field being added to the feed settings.
	 * 
	 * @return bool
	 */
	public function option_choices() {
		
		return false;
	}
	public function save_feed_settings($feed_id, $form_id, $settings) {
		//--------------------------------------------------------
		//For backwards compatibility
		$feed = $this->get_feed($feed_id);
		//Saving new fields into old field names to maintain backwards compatibility for delayed payments
		$settings['type'] = $settings['transactionType'];
		if (isset($settings['recurringAmount'])) {
			$settings['recurring_amount_field'] = $settings['recurringAmount'];
		}
		$feed['meta'] = $settings;
		$feed         = apply_filters('gform_flutterwave_save_config', $feed);
		
		//call hook to validate custom settings/meta added using gform_flutterwave_action_fields or gform_flutterwave_add_option_group action hooks
		$is_validation_error = apply_filters('gform_flutterwave_config_validation', false, $feed);
		if ($is_validation_error) {
			//fail save
			return false;
		}
		$settings = $feed['meta'];
		
		//--------------------------------------------------------
		return parent::save_feed_settings($feed_id, $form_id, $settings);
	}
	public function check_ipn_request() {
		$dismiss = isset($_GET['dismiss_ipn_check']);
		if ($dismiss) {
			add_option('dismiss_ipn_check', 1);
		}
		if (get_option('dismiss_ipn_check')) {
			return;
		}
		$can_verify_ipn = get_option('can_verify_ipn');
		if ($can_verify_ipn == 'yes') {
			return;
		}
		if (empty($can_verify_ipn)) {
			$url = 'https://www.sandbox.flutterwave.com/cgi-bin/webscr/';
			$request  = new \WP_Http();
			$response = $request->post($url, ['httpversion' => '1.1', 'sslverify' => false, 'ssl' => true, 'body' => 'cmd=_notify-validate', 'timeout' => 20]);
			if (! is_wp_error($response) && rgar($response, 'body') == 'INVALID') {
				$can_verify_ipn = 'yes';
			} else {
				wp_mail(get_bloginfo('admin_email'), 'Immediate Action Required: SSL certificate is outdated', 'WARNING: Your web server does not currently support the SHA-2 SSL Certificate standard required by FlutterWave.  <a href="https://devblog.flutterwave.com/flutterwave-ssl-certificate-changes/">For details see FlutterWave\'s changeover announcement</a>. Please contact your web host to resolve this issue as soon as possible.');
				$can_verify_ipn = 'no';
			}
			update_option('can_verify_ipn', $can_verify_ipn);
		}
		if ($can_verify_ipn == 'no') {
			//display message
			echo '<div class="error"> <p><strong>WARNING:</strong> Your web server does not currently support the SHA-2 SSL Certificate standard required by FlutterWave.  <a href="https://devblog.flutterwave.com/flutterwave-ssl-certificate-changes/">For details see FlutterWave\'s changeover announcement</a>. Please contact your web host to resolve this issue as soon as possible. <a href="' . add_query_arg(['dismiss_ipn_check' => 1]) . '">Dismiss</a></p></div>';
		}
	}
	//------ SENDING TO FlutterWave -----------//
	public function redirect_url($feed, $submission_data, $form, $entry) {
		//Don't process redirect url if request is a FlutterWave return
		if (! rgempty('gf_flutterwave_return', $_GET)) {
			return false;
		}
		//updating lead's payment_status to Processing
		\GFAPI::update_entry_property($entry['id'], 'payment_status', 'Processing');
		//Getting Url (Production or Sandbox)
		$url = $feed['meta']['mode'] == 'production' ? $this->production_url : $this->sandbox_url;
		$invoice_id = apply_filters('gform_flutterwave_invoice', '', $form, $entry);
		$invoice = empty($invoice_id) ? '' : "&invoice={$invoice_id}";
		//Current Currency
		$currency = rgar($entry, 'currency');
		//Customer fields
		$customer_fields = $this->customer_query_string($feed, $entry);
		//Image URL
		$image_url = ! empty($feed['meta']['imageURL']) ? '&image_url=' . urlencode($feed['meta']['imageURL']) : '';
		//Set return mode to 2 (FlutterWave will post info back to page). rm=1 seems to create lots of problems with the redirect back to the site. Defaulting it to 2.
		$return_mode = '2';
		$return_url = '&return=' . urlencode($this->return_url($form['id'], $entry['id'])) . "&rm={$return_mode}";
		//Cancel URL
		$cancel_url = ! empty($feed['meta']['cancelUrl']) ? '&cancel_return=' . urlencode($feed['meta']['cancelUrl']) : '';
		//Don't display note section
		$disable_note = ! empty($feed['meta']['disableNote']) ? '&no_note=1' : '';
		//Don't display shipping section
		$disable_shipping = ! empty($feed['meta']['disableShipping']) ? '&no_shipping=1' : '';
		//URL that will listen to notifications from FlutterWave
		$ipn_url = urlencode($this->get_callback_url());
		$business_email = urlencode(trim($feed['meta']['flutterwaveEmail']));
		$custom_field   = $entry['id'] . '|' . wp_hash($entry['id']);
		$url .= "?notify_url={$ipn_url}&charset=UTF-8&currency_code={$currency}&business={$business_email}&custom={$custom_field}{$invoice}{$customer_fields}{$image_url}{$cancel_url}{$disable_note}{$disable_shipping}{$return_url}";
		$query_string = '';
		switch ($feed['meta']['transactionType']) {
			case 'product' :
				//build query string using $submission_data
				$query_string = $this->get_product_query_string($submission_data, $entry['id']);
				break;
			case 'donation' :
				$query_string = $this->get_donation_query_string($submission_data, $entry['id']);
				break;
			case 'subscription' :
				$query_string = $this->get_subscription_query_string($feed, $submission_data, $entry['id']);
				break;
		}
		$query_string = gf_apply_filters('gform_flutterwave_query', $form['id'], $query_string, $form, $entry, $feed, $submission_data);
		if (! $query_string) {
			$this->log_debug(__METHOD__ . '(): NOT sending to FlutterWave: The price is either zero or the gform_flutterwave_query filter was used to remove the querystring that is sent to FlutterWave.');
			return '';
		}
		$url .= $query_string;
		//add the bn code (build notation code)
		$url .= '&bn=Rocketgenius_SP';
		$url = gf_apply_filters('gform_flutterwave_request', $form['id'], $url, $form, $entry, $feed, $submission_data);
		
		$this->log_debug(__METHOD__ . "(): Sending to FlutterWave: {$url}");
		return $url;
	}
	public function get_product_query_string($submission_data, $entry_id) {
		if (empty($submission_data)) {
			return false;
		}
		$query_string   = '';
		$payment_amount = rgar($submission_data, 'payment_amount');
		$setup_fee      = rgar($submission_data, 'setup_fee');
		$trial_amount   = rgar($submission_data, 'trial');
		$line_items     = rgar($submission_data, 'line_items');
		$discounts      = rgar($submission_data, 'discounts');
		$product_index = 1;
		$shipping      = '';
		$discount_amt  = 0;
		$cmd           = '_cart';
		$extra_qs      = '&upload=1';
		//work on products
		if (is_array($line_items)) {
			foreach ($line_items as $item) {
				$product_name = urlencode($item['name']);
				$quantity     = $item['quantity'];
				$unit_price   = $item['unit_price'];
				$options      = rgar($item, 'options');
				$product_id   = $item['id'];
				$is_shipping  = rgar($item, 'is_shipping');
				if ($is_shipping) {
					//populate shipping info
					$shipping .= ! empty($unit_price) ? "&shipping_1={$unit_price}" : '';
				} else {
					//add product info to querystring
					$query_string .= "&item_name_{$product_index}={$product_name}&amount_{$product_index}={$unit_price}&quantity_{$product_index}={$quantity}";
				}
				//add options
				if (! empty($options)) {
					if (is_array($options)) {
						$option_index = 1;
						foreach ($options as $option) {
							// Trim option label to prevent FlutterWave displaying an error instead of the cart.
							$option_label = urlencode(substr($option['field_label'], 0, 64));
							$option_name  = urlencode($option['option_name']);
							$query_string .= "&on{$option_index}_{$product_index}={$option_label}&os{$option_index}_{$product_index}={$option_name}";
							$option_index ++;
						}
					}
				}
				$product_index ++;
			}
		}
		//look for discounts
		if (is_array($discounts)) {
			foreach ($discounts as $discount) {
				$discount_full = abs($discount['unit_price']) * $discount['quantity'];
				$discount_amt += $discount_full;
			}
			if ($discount_amt > 0) {
				$query_string .= "&discount_amount_cart={$discount_amt}";
			}
		}
		$query_string .= "{$shipping}&cmd={$cmd}{$extra_qs}";
		
		//save payment amount to lead meta
		gform_update_meta($entry_id, 'payment_amount', $payment_amount);
		return $payment_amount > 0 ? $query_string : false;
	}
	public function get_donation_query_string($submission_data, $entry_id) {
		if (empty($submission_data)) {
			return false;
		}
		$query_string   = '';
		$payment_amount = rgar($submission_data, 'payment_amount');
		$line_items     = rgar($submission_data, 'line_items');
		$purpose        = '';
		$cmd            = '_donations';
		//work on products
		if (is_array($line_items)) {
			foreach ($line_items as $item) {
				$product_name    = $item['name'];
				$quantity        = $item['quantity'];
				$quantity_label  = $quantity > 1 ? $quantity . ' ' : '';
				$options         = rgar($item, 'options');
				$is_shipping     = rgar($item, 'is_shipping');
				$product_options = '';
				if (! $is_shipping) {
					//add options
					if (! empty($options)) {
						if (is_array($options)) {
							$product_options = ' (';
							foreach ($options as $option) {
								$product_options .= $option['option_name'] . ', ';
							}
							$product_options = substr($product_options, 0, strlen($product_options) - 2) . ')';
						}
					}
					$purpose .= $quantity_label . $product_name . $product_options . ', ';
				}
			}
		}
		if (! empty($purpose)) {
			$purpose = substr($purpose, 0, strlen($purpose) - 2);
		}
		$purpose = urlencode($purpose);
		//truncating to maximum length allowed by FlutterWave
		if (strlen($purpose) > 127) {
			$purpose = substr($purpose, 0, 124) . '...';
		}
		$query_string = "&amount={$payment_amount}&item_name={$purpose}&cmd={$cmd}";
		
		//save payment amount to lead meta
		gform_update_meta($entry_id, 'payment_amount', $payment_amount);
		return $payment_amount > 0 ? $query_string : false;
	}
	public function get_subscription_query_string($feed, $submission_data, $entry_id) {
		if (empty($submission_data)) {
			return false;
		}
		$query_string         = '';
		$payment_amount       = rgar($submission_data, 'payment_amount');
		$setup_fee            = rgar($submission_data, 'setup_fee');
		$trial_enabled        = rgar($feed['meta'], 'trial_enabled');
		$line_items           = rgar($submission_data, 'line_items');
		$discounts            = rgar($submission_data, 'discounts');
		$recurring_field      = rgar($submission_data, 'payment_amount'); //will be field id or the text 'form_total'
		$product_index        = 1;
		$shipping             = '';
		$discount_amt         = 0;
		$cmd                  = '_xclick-subscriptions';
		$extra_qs             = '';
		$name_without_options = '';
		$item_name            = '';
		//work on products
		if (is_array($line_items)) {
			foreach ($line_items as $item) {
				$product_id     = $item['id'];
				$product_name   = $item['name'];
				$quantity       = $item['quantity'];
				$quantity_label = $quantity > 1 ? $quantity . ' ' : '';
				$unit_price  = $item['unit_price'];
				$options     = rgar($item, 'options');
				$product_id  = $item['id'];
				$is_shipping = rgar($item, 'is_shipping');
				$product_options = '';
				if (! $is_shipping) {
					//add options
					if (! empty($options) && is_array($options)) {
						$product_options = ' (';
						foreach ($options as $option) {
							$product_options .= $option['option_name'] . ', ';
						}
						$product_options = substr($product_options, 0, strlen($product_options) - 2) . ')';
					}
					$item_name .= $quantity_label . $product_name . $product_options . ', ';
					$name_without_options .= $product_name . ', ';
				}
			}
			//look for discounts to pass in the item_name
			if (is_array($discounts)) {
				foreach ($discounts as $discount) {
					$product_name   = $discount['name'];
					$quantity       = $discount['quantity'];
					$quantity_label = $quantity > 1 ? $quantity . ' ' : '';
					$item_name .= $quantity_label . $product_name . ' (), ';
					$name_without_options .= $product_name . ', ';
				}
			}
			if (! empty($item_name)) {
				$item_name = substr($item_name, 0, strlen($item_name) - 2);
			}
			//if name is larger than max, remove options from it.
			if (strlen($item_name) > 127) {
				$item_name = substr($name_without_options, 0, strlen($name_without_options) - 2);
				//truncating name to maximum allowed size
				if (strlen($item_name) > 127) {
					$item_name = substr($item_name, 0, 124) . '...';
				}
			}
			$item_name = urlencode($item_name);
		}
		$trial = '';
		//see if a trial exists
		if ($trial_enabled) {
			$trial_amount        = rgar($submission_data, 'trial') ? rgar($submission_data, 'trial') : 0;
			$trial_period_number = rgar($feed['meta'], 'trialPeriod_length');
			$trial_period_type   = $this->convert_interval(rgar($feed['meta'], 'trialPeriod_unit'), 'char');
			$trial               = "&a1={$trial_amount}&p1={$trial_period_number}&t1={$trial_period_type}";
		}
		//check for recurring times
		$recurring_times = rgar($feed['meta'], 'recurringTimes') ? '&srt=' . rgar($feed['meta'], 'recurringTimes') : '';
		$recurring_retry = rgar($feed['meta'], 'recurringRetry') ? '1' : '0';
		$billing_cycle_number = rgar($feed['meta'], 'billingCycle_length');
		$billing_cycle_type   = $this->convert_interval(rgar($feed['meta'], 'billingCycle_unit'), 'char');
		$query_string = "&cmd={$cmd}&item_name={$item_name}{$trial}&a3={$payment_amount}&p3={$billing_cycle_number}&t3={$billing_cycle_type}&src=1&sra={$recurring_retry}{$recurring_times}";
		//save payment amount to lead meta
		gform_update_meta($entry_id, 'payment_amount', $payment_amount);
		
		return $payment_amount > 0 ? $query_string : false;
	}
	public function customer_query_string($feed, $entry) {
		$fields = '';
		foreach ($this->get_customer_fields() as $field) {
			$field_id = $feed['meta'][ $field['meta_name'] ];
			$value    = rgar($entry, $field_id);
			if ($field['name'] == 'country') {
				$value = class_exists('GF_Field_Address') ? \GF_Fields::get('address')->get_country_code($value) : \GFCommon::get_country_code($value);
			} elseif ($field['name'] == 'state') {
				$value = class_exists('GF_Field_Address') ? \GF_Fields::get('address')->get_us_state_code($value) : \GFCommon::get_us_state_code($value);
			}
			if (! empty($value)) {
				$fields .= "&{$field['name']}=" . urlencode($value);
			}
		}
		return $fields;
	}
	public function return_url($form_id, $lead_id) {
		$pageURL = \GFCommon::is_ssl() ? 'https://' : 'http://';
		$server_port = apply_filters('gform_flutterwave_return_url_port', $_SERVER['SERVER_PORT']);
		if ($server_port != '80') {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}
		$ids_query = "ids={$form_id}|{$lead_id}";
		$ids_query .= '&hash=' . wp_hash($ids_query);
		$url = add_query_arg('gf_flutterwave_return', base64_encode($ids_query), $pageURL);
		$query = 'gf_flutterwave_return=' . base64_encode($ids_query);
		/**
		 * Filters FlutterWave's return URL, which is the URL that users will be sent to after completing the payment on FlutterWave's site.
		 * Useful when URL isn't created correctly (could happen on some server configurations using PROXY servers).
		 *
		 * @since 2.4.5
		 *
		 * @param string  $url 	The URL to be filtered.
		 * @param int $form_id	The ID of the form being submitted.
		 * @param int $entry_id	The ID of the entry that was just created.
		 * @param string $query	The query string portion of the URL.
		 */
		return apply_filters('gform_flutterwave_return_url', $url, $form_id, $lead_id, $query);
	}
	public static function maybe_thankyou_page() {
		$instance = self::get_instance();
		if (! $instance->is_gravityforms_supported()) {
			return;
		}
		if ($str = rgget('gf_flutterwave_return')) {
			$str = base64_decode($str);
			parse_str($str, $query);
			if (wp_hash('ids=' . $query['ids']) == $query['hash']) {
				list($form_id, $lead_id) = explode('|', $query['ids']);
				$form = \GFAPI::get_form($form_id);
				$lead = \GFAPI::get_entry($lead_id);
				if (! class_exists('GFFormDisplay')) {
					require_once(\GFCommon::get_base_path() . '/form_display.php');
				}
				$confirmation = \GFFormDisplay::handle_confirmation($form, $lead, false);
				if (is_array($confirmation) && isset($confirmation['redirect'])) {
					header("Location: {$confirmation['redirect']}");
					exit;
				}
				\GFFormDisplay::$submission[ $form_id ] = ['is_confirmation' => true, 'confirmation_message' => $confirmation, 'form' => $form, 'lead' => $lead];
			}
		}
	}
	public function get_customer_fields() {
		return [
            ['name' => 'first_name', 'label' => 'First Name', 'meta_name' => 'billingInformation_firstName'],
            ['name' => 'last_name', 'label' => 'Last Name', 'meta_name' => 'billingInformation_lastName'],
            ['name' => 'email', 'label' => 'Email', 'meta_name' => 'billingInformation_email'],
            ['name' => 'address1', 'label' => 'Address', 'meta_name' => 'billingInformation_address'],
            ['name' => 'address2', 'label' => 'Address 2', 'meta_name' => 'billingInformation_address2'],
            ['name' => 'city', 'label' => 'City', 'meta_name' => 'billingInformation_city'],
            ['name' => 'state', 'label' => 'State', 'meta_name' => 'billingInformation_state'],
            ['name' => 'zip', 'label' => 'Zip', 'meta_name' => 'billingInformation_zip'],
            ['name' => 'country', 'label' => 'Country', 'meta_name' => 'billingInformation_country'],
        ];
	}
	public function convert_interval($interval, $to_type) {
		//convert single character into long text for new feed settings or convert long text into single character for sending to FlutterWave
		//$to_type: text (change character to long text), OR char (change long text to character)
		if (empty($interval)) {
			return '';
		}
		$new_interval = '';
		if ($to_type == 'text') {
			//convert single char to text
			switch (strtoupper($interval)) {
				case 'D' :
					$new_interval = 'day';
					break;
				case 'W' :
					$new_interval = 'week';
					break;
				case 'M' :
					$new_interval = 'month';
					break;
				case 'Y' :
					$new_interval = 'year';
					break;
				default :
					$new_interval = $interval;
					break;
			}
		} else {
			//convert text to single char
			switch (strtolower($interval)) {
				case 'day' :
					$new_interval = 'D';
					break;
				case 'week' :
					$new_interval = 'W';
					break;
				case 'month' :
					$new_interval = 'M';
					break;
				case 'year' :
					$new_interval = 'Y';
					break;
				default :
					$new_interval = $interval;
					break;
			}
		}
		return $new_interval;
	}
	public function delay_post($is_disabled, $form, $entry) {
		if (! $this->is_payment_gateway) {
			return $is_disabled;
		}
		$feed = $this->current_feed;
		return ! rgempty('delayPost', $feed['meta']);
	}
	public function delay_notification($is_disabled, $notification, $form, $entry) {
		if (! $this->is_payment_gateway || rgar($notification, 'event') != 'form_submission') {
			return $is_disabled;
		}
		$feed                   = $this->current_feed;
		$selected_notifications = is_array(rgar($feed['meta'], 'selectedNotifications')) ? rgar($feed['meta'], 'selectedNotifications') : [];
		
		return isset($feed['meta']['delayNotification']) && in_array($notification['id'], $selected_notifications) ? true : $is_disabled;
	}

	//------- PROCESSING FlutterWave IPN (Callback) -----------//
	public function callback() {
		if (! $this->is_gravityforms_supported()) {
			return false;
		}
		$this->log_debug(__METHOD__ . '(): IPN request received. Starting to process => ' . print_r($_POST, true));
		// Valid IPN requests must have a custom field
		$custom_field = rgpost('custom');
		if (empty($custom_field)) {
			$this->log_error(__METHOD__ . '(): IPN request does not have a custom field, so it was not created by Gravity Forms. Aborting.');
			return false;
		}

		//------- Send request to flutterwave and verify it has not been spoofed ---------------------//
		$is_verified = $this->verify_flutterwave_ipn();
		if (is_wp_error($is_verified)) {
			$this->log_error(__METHOD__ . '(): IPN verification failed with an error. Aborting with a 500 error so that IPN is resent.');
			return new WP_Error('IPNVerificationError', 'There was an error when verifying the IPN message with FlutterWave', ['status_header' => 500]);
		} elseif (! $is_verified) {
			$this->log_error(__METHOD__ . '(): IPN request could not be verified by FlutterWave. Aborting.');
			return false;
		}
		$this->log_debug(__METHOD__ . '(): IPN message successfully verified by FlutterWave');

		//------ Getting entry related to this IPN ----------------------------------------------//
		$entry = $this->get_entry($custom_field);
		//Ignore orphan IPN messages (ones without an entry)
		if (! $entry) {
			$this->log_error(__METHOD__ . '(): Entry could not be found. Aborting.');
			return false;
		}
		$this->log_debug(__METHOD__ . '(): Entry has been found => ' . print_r($entry, true));
		if ($entry['status'] == 'spam') {
			$this->log_error(__METHOD__ . '(): Entry is marked as spam. Aborting.');
			return false;
		}

		//------ Getting feed related to this IPN ------------------------------------------//
		$feed = $this->get_payment_feed($entry);
		//Ignore IPN messages from forms that are no longer configured with the FlutterWave add-on
		if (! $feed || ! rgar($feed, 'is_active')) {
			$this->log_error(__METHOD__ . "(): Form no longer is configured with FlutterWave Addon. Form ID: {$entry['form_id']}. Aborting.");
			return false;
		}
		$this->log_debug(__METHOD__ . "(): Form {$entry['form_id']} is properly configured.");

		//----- Making sure this IPN can be processed -------------------------------------//
		if (! $this->can_process_ipn($feed, $entry)) {
			$this->log_debug(__METHOD__ . '(): IPN cannot be processed.');
			return false;
		}

		//----- Processing IPN ------------------------------------------------------------//
		$this->log_debug(__METHOD__ . '(): Processing IPN...');
		$action = $this->process_ipn($feed, $entry, rgpost('payment_status'), rgpost('txn_type'), rgpost('txn_id'), rgpost('parent_txn_id'), rgpost('subscr_id'), rgpost('mc_gross'), rgpost('pending_reason'), rgpost('reason_code'), rgpost('mc_amount3'));
		$this->log_debug(__METHOD__ . '(): IPN processing complete.');
		if (rgempty('entry_id', $action)) {
			return false;
		}
		return $action;
	}
	public function get_payment_feed($entry, $form = false) {
		$feed = parent::get_payment_feed($entry, $form);
		if (empty($feed) && ! empty($entry['id'])) {
			//looking for feed created by legacy versions
			$feed = $this->get_flutterwave_feed_by_entry($entry['id']);
		}
		$feed = apply_filters('gform_flutterwave_get_payment_feed', $feed, $entry, $form ? $form : \GFAPI::get_form($entry['form_id']));
		return $feed;
	}
	private function get_flutterwave_feed_by_entry($entry_id) {
		$feed_id = gform_get_meta($entry_id, 'flutterwave_feed_id');
		$feed    = $this->get_feed($feed_id);
		return ! empty($feed) ? $feed : false;
	}
	public function post_callback($callback_action, $callback_result) {
		if (is_wp_error($callback_action) || ! $callback_action) {
			return false;
		}
		//run the necessary hooks
		$entry          = \GFAPI::get_entry($callback_action['entry_id']);
		$feed           = $this->get_payment_feed($entry);
		$transaction_id = rgar($callback_action, 'transaction_id');
		$amount         = rgar($callback_action, 'amount');
		$subscriber_id  = rgar($callback_action, 'subscriber_id');
		$pending_reason = rgpost('pending_reason');
		$reason         = rgpost('reason_code');
		$status         = rgpost('payment_status');
		$txn_type       = rgpost('txn_type');
		$parent_txn_id  = rgpost('parent_txn_id');
		//run gform_flutterwave_fulfillment only in certain conditions
		if (rgar($callback_action, 'ready_to_fulfill') && ! rgar($callback_action, 'abort_callback')) {
			$this->fulfill_order($entry, $transaction_id, $amount, $feed);
		} else {
			if (rgar($callback_action, 'abort_callback')) {
				$this->log_debug(__METHOD__ . '(): Callback processing was aborted. Not fulfilling entry.');
			} else {
				$this->log_debug(__METHOD__ . '(): Entry is already fulfilled or not ready to be fulfilled, not running gform_flutterwave_fulfillment hook.');
			}
		}
		do_action('gform_post_payment_status', $feed, $entry, $status, $transaction_id, $subscriber_id, $amount, $pending_reason, $reason);
		if (has_filter('gform_post_payment_status')) {
			$this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_post_payment_status.');
		}
		do_action('gform_flutterwave_ipn_' . $txn_type, $entry, $feed, $status, $txn_type, $transaction_id, $parent_txn_id, $subscriber_id, $amount, $pending_reason, $reason);
		if (has_filter('gform_flutterwave_ipn_' . $txn_type)) {
			$this->log_debug(__METHOD__ . "(): Executing functions hooked to gform_flutterwave_ipn_{$txn_type}.");
		}
		do_action('gform_flutterwave_post_ipn', $_POST, $entry, $feed, false);
		if (has_filter('gform_flutterwave_post_ipn')) {
			$this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_flutterwave_post_ipn.');
		}
	}
	private function verify_flutterwave_ipn() {
		$req = 'cmd=_notify-validate';
		foreach ($_POST as $key => $value) {
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
		}
		$url = rgpost('test_ipn') ? $this->sandbox_url : apply_filters('gform_flutterwave_ipn_url', $this->production_url);
		$this->log_debug(__METHOD__ . "(): Sending IPN request to FlutterWave for validation. URL: $url - Data: $req");
		$url_info = parse_url($url);
		//Post back to FlutterWave system to validate
		$request  = new \WP_Http();
		$headers  = ['Host' => $url_info['host']];
		$sslverify = (bool) get_option('gform_flutterwave_sslverify');
		/**
		 * Allow sslverify be modified before sending requests
		 *
		 * @since 2.5.1
		 *
		 * @param bool $sslverify Whether to verify SSL for the request. Default true for new installations, false for legacy installations.
		 */
		$sslverify = apply_filters('gform_flutterwave_sslverify', $sslverify);
		$this->log_debug(__METHOD__ . '(): sslverify: ' . $sslverify);
		$response = $request->post($url, ['httpversion' => '1.1', 'headers' => $headers, 'sslverify' => $sslverify, 'ssl' => true, 'body' => $req, 'timeout' => 20]);
		$this->log_debug(__METHOD__ . '(): Response: ' . print_r($response, true));
		if (is_wp_error($response)) {
			return $response;
		}
		$body = trim($response['body']);
		if (! in_array($body, ['VERIFIED', 'INVALID'])) {
			return new WP_Error('IPNVerificationError', 'Unexpected content in the response body.');
		}
		return $body == 'VERIFIED';
	}
	private function process_ipn($config, $entry, $status, $transaction_type, $transaction_id, $parent_transaction_id, $subscriber_id, $amount, $pending_reason, $reason, $recurring_amount) {
		$this->log_debug(__METHOD__ . "(): Payment status: {$status} - Transaction Type: {$transaction_type} - Transaction ID: {$transaction_id} - Parent Transaction: {$parent_transaction_id} - Subscriber ID: {$subscriber_id} - Amount: {$amount} - Pending reason: {$pending_reason} - Reason: {$reason}");
		$action = [];
		switch (strtolower($transaction_type)) {
			case 'subscr_payment' :
				//transaction created
				$action['id']               = $transaction_id;
				$action['transaction_id']   = $transaction_id;
				$action['type']             = 'add_subscription_payment';
				$action['subscription_id']  = $subscriber_id;
				$action['amount']           = $amount;
				$action['entry_id']         = $entry['id'];
				$action['payment_method']	= 'Flutterwave';
				return $action;
				break;
			case 'subscr_signup' :
				//no transaction created
				$action['id']               = $subscriber_id . '_' . $transaction_type;
				$action['type']             = 'create_subscription';
				$action['subscription_id']  = $subscriber_id;
				$action['amount']           = $recurring_amount;
				$action['entry_id']         = $entry['id'];
				$action['ready_to_fulfill'] = ! $entry['is_fulfilled'] ? true : false;
				
				if (! $this->is_valid_initial_payment_amount($entry['id'], $recurring_amount)) {
					//create note and transaction
					$this->log_debug(__METHOD__ . '(): Payment amount does not match subscription amount. Subscription will not be activated.');
					GFPaymentAddOn::add_note($entry['id'], sprintf(__('Payment amount (%s) does not match subscription amount. Subscription will not be activated. Transaction ID: %s', 'wooflutter'), \GFCommon::to_money($recurring_amount, $entry['currency']), $subscriber_id));
					GFPaymentAddOn::insert_transaction($entry['id'], 'payment', $subscriber_id, $recurring_amount);
					$action['abort_callback'] = true;
				}
				return $action;
				break;
			case 'subscr_cancel' :
				//no transaction created
				$action['id'] = $subscriber_id . '_' . $transaction_type;
				$action['type']            = 'cancel_subscription';
				$action['subscription_id'] = $subscriber_id;
				$action['entry_id']        = $entry['id'];
				return $action;
				break;
			case 'subscr_eot' :
				//no transaction created
				if (empty($transaction_id)) {
					$action['id'] = $subscriber_id . '_' . $transaction_type;
				} else {
					$action['id'] = $transaction_id;
				}
				$action['type']            = 'expire_subscription';
				$action['subscription_id'] = $subscriber_id;
				$action['entry_id']        = $entry['id'];
				return $action;
				break;
			case 'subscr_failed' :
				//no transaction created
				if (empty($transaction_id)) {
					$action['id'] = $subscriber_id . '_' . $transaction_type;
				} else {
					$action['id'] = $transaction_id;
				}
				$action['type']            = 'fail_subscription_payment';
				$action['subscription_id'] = $subscriber_id;
				$action['entry_id']        = $entry['id'];
				$action['amount']          = $amount;
				return $action;
				break;
			default:
				//handles products and donation
				switch (strtolower($status)) {
					case 'completed' :
						//creates transaction
						$action['id']               = $transaction_id . '_' . $status;
						$action['type']             = 'complete_payment';
						$action['transaction_id']   = $transaction_id;
						$action['amount']           = $amount;
						$action['entry_id']         = $entry['id'];
						$action['payment_date']     = gmdate('y-m-d H:i:s');
						$action['payment_method']	= 'Flutterwave';
						$action['ready_to_fulfill'] = ! $entry['is_fulfilled'] ? true : false;
						
						if (! $this->is_valid_initial_payment_amount($entry['id'], $amount)) {
							//create note and transaction
							$this->log_debug(__METHOD__ . '(): Payment amount does not match product price. Entry will not be marked as Approved.');
							GFPaymentAddOn::add_note($entry['id'], sprintf(__('Payment amount (%s) does not match product price. Entry will not be marked as Approved. Transaction ID: %s', 'wooflutter'), \GFCommon::to_money($amount, $entry['currency']), $transaction_id));
							GFPaymentAddOn::insert_transaction($entry['id'], 'payment', $transaction_id, $amount);
							$action['abort_callback'] = true;
						}
						return $action;
						break;
					case 'reversed' :
						//creates transaction
						$this->log_debug(__METHOD__ . '(): Processing reversal.');
						\GFAPI::update_entry_property($entry['id'], 'payment_status', 'Refunded');
						GFPaymentAddOn::add_note($entry['id'], sprintf(__('Payment has been reversed. Transaction ID: %s. Reason: %s', 'wooflutter'), $transaction_id, $this->get_reason($reason)));
						GFPaymentAddOn::insert_transaction($entry['id'], 'refund', $action['transaction_id'], $action['amount']);
						break;
					case 'canceled_reversal' :
						//creates transaction
						$this->log_debug(__METHOD__ . '(): Processing a reversal cancellation');
						\GFAPI::update_entry_property($entry['id'], 'payment_status', 'Paid');
						GFPaymentAddOn::add_note($entry['id'], sprintf(__('Payment reversal has been canceled and the funds have been transferred to your account. Transaction ID: %s', 'wooflutter'), $entry['transaction_id']));
						GFPaymentAddOn::insert_transaction($entry['id'], 'payment', $action['transaction_id'], $action['amount']);
						break;
					case 'processed' :
					case 'pending' :
						$action['id']             = $transaction_id . '_' . $status;
						$action['type']           = 'add_pending_payment';
						$action['transaction_id'] = $transaction_id;
						$action['entry_id']       = $entry['id'];
						$action['amount']         = $amount;
						$action['entry_id']       = $entry['id'];
						$amount_formatted         = \GFCommon::to_money($action['amount'], $entry['currency']);
						$action['note']           = sprintf(__('Payment is pending. Amount: %s. Transaction ID: %s. Reason: %s', 'wooflutter'), $amount_formatted, $action['transaction_id'], $this->get_pending_reason($pending_reason));
						return $action;
						break;
					case 'refunded' :
						$action['id']             = $transaction_id . '_' . $status;
						$action['type']           = 'refund_payment';
						$action['transaction_id'] = $transaction_id;
						$action['entry_id']       = $entry['id'];
						$action['amount']         = $amount;
						return $action;
						break;
					case 'voided' :
						$action['id']             = $transaction_id . '_' . $status;
						$action['type']           = 'void_authorization';
						$action['transaction_id'] = $transaction_id;
						$action['entry_id']       = $entry['id'];
						$action['amount']         = $amount;
						return $action;
						break;
					case 'denied' :
					case 'failed' :
						$action['id']             = $transaction_id . '_' . $status;
						$action['type']           = 'fail_payment';
						$action['transaction_id'] = $transaction_id;
						$action['entry_id']       = $entry['id'];
						$action['amount']         = $amount;
						return $action;
						break;
				}
				break;
		}
	}
	public function get_entry($custom_field) {
		//Getting entry associated with this IPN message (entry id is sent in the 'custom' field)
		list($entry_id, $hash) = explode('|', $custom_field);
		$hash_matches = wp_hash($entry_id) == $hash;
		//allow the user to do some other kind of validation of the hash
		$hash_matches = apply_filters('gform_flutterwave_hash_matches', $hash_matches, $entry_id, $hash, $custom_field);
		//Validates that Entry Id wasn't tampered with
		if (! rgpost('test_ipn') && ! $hash_matches) {
			$this->log_error(__METHOD__ . "(): Entry ID verification failed. Hash does not match. Custom field: {$custom_field}. Aborting.");
			return false;
		}
		$this->log_debug(__METHOD__ . "(): IPN message has a valid custom field: {$custom_field}");
		$entry = \GFAPI::get_entry($entry_id);
		if (is_wp_error($entry)) {
			$this->log_error(__METHOD__ . '(): ' . $entry->get_error_message());
			return false;
		}
		return $entry;
	}
	public function can_process_ipn($feed, $entry) {
		$this->log_debug(__METHOD__ . '(): Checking that IPN can be processed.');
		//Only process test messages coming fron SandBox and only process production messages coming from production FlutterWave
		if (($feed['meta']['mode'] == 'test' && ! rgpost('test_ipn')) || ($feed['meta']['mode'] == 'production' && rgpost('test_ipn'))) {
			$this->log_error(__METHOD__ . "(): Invalid test/production mode. IPN message mode (test/production) does not match mode configured in the FlutterWave feed. Configured Mode: {$feed['meta']['mode']}. IPN test mode: " . rgpost('test_ipn'));
			return false;
		}
		/**
		 * Filter through your FlutterWave business email (Checks to make sure it matches)
		 *
		 * @param string $feed['meta']['flutterwaveEmail'] The FlutterWave Email to filter through (Taken from the feed object under feed meta)
		 * @param array $feed The Feed object to filter through and use for modifications
		 * @param array $entry The Entry Object to filter through and use for modifications
		 */
		$business_email = apply_filters('gform_flutterwave_business_email', $feed['meta']['flutterwaveEmail'], $feed, $entry);
		$recipient_email = rgempty('business') ? rgpost('receiver_email') : rgpost('business');
		if (strtolower(trim($recipient_email)) != strtolower(trim($business_email))) {
			$this->log_error(__METHOD__ . '(): FlutterWave email does not match. Email entered on FlutterWave feed:' . strtolower(trim($business_email)) . ' - Email from IPN message: ' . $recipient_email);
			return false;
		}
		//Pre IPN processing filter. Allows users to cancel IPN processing
		$cancel = apply_filters('gform_flutterwave_pre_ipn', false, $_POST, $entry, $feed);
		if ($cancel) {
			$this->log_debug(__METHOD__ . '(): IPN processing cancelled by the gform_flutterwave_pre_ipn filter. Aborting.');
			do_action('gform_flutterwave_post_ipn', $_POST, $entry, $feed, true);
			if (has_filter('gform_flutterwave_post_ipn')) {
				$this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_flutterwave_post_ipn.');
			}
			return false;
		}
		return true;
	}
	public function cancel_subscription($entry, $feed, $note = null) {
		parent::cancel_subscription($entry, $feed, $note);
		$this->modify_post(rgar($entry, 'post_id'), rgars($feed, 'meta/update_post_action'));
		return true;
	}
	public function modify_post($post_id, $action) {
		$result = false;
		if (! $post_id) {
			return $result;
		}
		switch ($action) {
			case 'draft':
				$post = get_post($post_id);
				$post->post_status = 'draft';
				$result = wp_update_post($post);
				$this->log_debug(__METHOD__ . "(): Set post (#{$post_id}) status to \"draft\".");
				break;
			case 'delete':
				$result = wp_delete_post($post_id);
				$this->log_debug(__METHOD__ . "(): Deleted post (#{$post_id}).");
				break;
		}
		return $result;
	}
	private function get_reason($code) {
		switch (strtolower($code)) {
			case 'adjustment_reversal':
				return esc_html__('Reversal of an adjustment', 'wooflutter');
			case 'buyer-complaint':
				return esc_html__('A reversal has occurred on this transaction due to a complaint about the transaction from your customer.', 'wooflutter');
			case 'chargeback':
				return esc_html__('A reversal has occurred on this transaction due to a chargeback by your customer.', 'wooflutter');
			case 'chargeback_reimbursement':
				return esc_html__('Reimbursement for a chargeback.', 'wooflutter');
			case 'chargeback_settlement':
				return esc_html__('Settlement of a chargeback.', 'wooflutter');
			case 'guarantee':
				return esc_html__('A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.', 'wooflutter');
			case 'other':
				return esc_html__('Non-specified reason.', 'wooflutter');
			case 'refund':
				return esc_html__('A reversal has occurred on this transaction because you have given the customer a refund.', 'wooflutter');
			default:
				return empty($code) ? esc_html__('Reason has not been specified. For more information, contact FlutterWave Customer Service.', 'wooflutter') : $code;
		}
	}
	/**
	 * Determines if the current request is to the IPN URL.
	 *
	 * Support for page=gf_flutterwave_ipn remains so IPNs will continue to be processed for existing subscriptions.
	 *
	 * @since unknown
	 * @since 3.4 Added support for requests to the frameworks default callback=slug URL.
	 *
	 * @return bool
	 */
	public function is_callback_valid() {
		return parent::is_callback_valid() || rgget('page') === 'gf_flutterwave_ipn';
	}
	/**
	 * Returns the URL to be used for IPN processing.
	 *
	 * @since 3.4
	 *
	 * @return string
	 */
	public function get_callback_url() {
		return add_query_arg('callback', $this->_slug, home_url('/', 'https'));
	}
	private function get_pending_reason($code) {
		switch (strtolower($code)) {
			case 'address':
				return esc_html__('The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences are set to allow you to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'wooflutter');
			case 'authorization':
				return esc_html__('You set the payment action to Authorization and have not yet captured funds.', 'wooflutter');
			case 'echeck':
				return esc_html__('The payment is pending because it was made by an eCheck that has not yet cleared.', 'wooflutter');
			case 'intl':
				return esc_html__('The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'wooflutter');
			case 'multi-currency':
				return esc_html__('You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'wooflutter');
			case 'order':
				return esc_html__('You set the payment action to Order and have not yet captured funds.', 'wooflutter');
			case 'paymentreview':
				return esc_html__('The payment is pending while it is being reviewed by FlutterWave for risk.', 'wooflutter');
			case 'unilateral':
				return esc_html__('The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'wooflutter');
			case 'upgrade':
				return esc_html__('The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status in order to receive the funds. Upgrade can also mean that you have reached the monthly limit for transactions on your account.', 'wooflutter');
			case 'verify':
				return esc_html__('The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'wooflutter');
			case 'other':
				return esc_html__('Reason has not been specified. For more information, contact FlutterWave Customer Service.', 'wooflutter');
			default:
				return empty($code) ? esc_html__('Reason has not been specified. For more information, contact FlutterWave Customer Service.', 'wooflutter') : $code;
		}
	}
	//------- ADMIN FUNCTIONS/HOOKS -----------//
	public function init_admin() {
		parent::init_admin();
		//add actions to allow the payment status to be modified
		add_action('gform_payment_status', [$this, 'admin_edit_payment_status'], 3, 3);
		add_action('gform_payment_date', [$this, 'admin_edit_payment_date'], 3, 3);
		add_action('gform_payment_transaction_id', [$this, 'admin_edit_payment_transaction_id'], 3, 3);
		add_action('gform_payment_amount', [$this, 'admin_edit_payment_amount'], 3, 3);
		add_action('gform_after_update_entry', [$this, 'admin_update_payment'], 4, 2);
		//checking if webserver is compatible with FlutterWave SSL certificate
		add_action('admin_notices', [$this, 'check_ipn_request']);
	}
	/**
	 * Add supported notification events.
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array
	 */
	public function supported_notification_events($form) {
		if (! $this->has_feed($form['id'])) {
			return false;
		}
		return [
            'complete_payment'          => esc_html__('Payment Completed', 'wooflutter'),
            'refund_payment'            => esc_html__('Payment Refunded', 'wooflutter'),
            'fail_payment'              => esc_html__('Payment Failed', 'wooflutter'),
            'add_pending_payment'       => esc_html__('Payment Pending', 'wooflutter'),
            'void_authorization'        => esc_html__('Authorization Voided', 'wooflutter'),
            'create_subscription'       => esc_html__('Subscription Created', 'wooflutter'),
            'cancel_subscription'       => esc_html__('Subscription Canceled', 'wooflutter'),
            'expire_subscription'       => esc_html__('Subscription Expired', 'wooflutter'),
            'add_subscription_payment'  => esc_html__('Subscription Payment Added', 'wooflutter'),
            'fail_subscription_payment' => esc_html__('Subscription Payment Failed', 'wooflutter'),
        ];
	}
	public function admin_edit_payment_status($payment_status, $form, $entry) {
		if ($this->payment_details_editing_disabled($entry)) {
			return $payment_status;
		}
		//create drop down for payment status
		$payment_string = gform_tooltip('flutterwave_edit_payment_status', '', true);
		$payment_string .= '<select id="payment_status" name="payment_status">';
		$payment_string .= '<option value="' . $payment_status . '" selected>' . $payment_status . '</option>';
		$payment_string .= '<option value="Paid">Paid</option>';
		$payment_string .= '</select>';
		return $payment_string;
	}
	public function admin_edit_payment_date($payment_date, $form, $entry) {
		if ($this->payment_details_editing_disabled($entry)) {
			return $payment_date;
		}
		$payment_date = $entry['payment_date'];
		if (empty($payment_date)) {
			$payment_date = gmdate('y-m-d H:i:s');
		}
		$input = '<input type="text" id="payment_date" name="payment_date" value="' . $payment_date . '">';
		return $input;
	}
	public function admin_edit_payment_transaction_id($transaction_id, $form, $entry) {
		if ($this->payment_details_editing_disabled($entry)) {
			return $transaction_id;
		}
		$input = '<input type="text" id="flutterwave_transaction_id" name="flutterwave_transaction_id" value="' . $transaction_id . '">';
		return $input;
	}
	public function admin_edit_payment_amount($payment_amount, $form, $entry) {
		if ($this->payment_details_editing_disabled($entry)) {
			return $payment_amount;
		}
		if (empty($payment_amount)) {
			$payment_amount = \GFCommon::get_order_total($form, $entry);
		}
		$input = '<input type="text" id="payment_amount" name="payment_amount" class="gform_currency" value="' . $payment_amount . '">';
		return $input;
	}
	public function admin_update_payment($form, $entry_id) {
		check_admin_referer('gforms_save_entry', 'gforms_save_entry');
		//update payment information in admin, need to use this function so the lead data is updated before displayed in the sidebar info section
		$entry = \GFFormsModel::get_lead($entry_id);
		if ($this->payment_details_editing_disabled($entry, 'update')) {
			return;
		}
        
		//get payment fields to update
		$payment_status = rgpost('payment_status');
		//when updating, payment status may not be editable, if no value in post, set to lead payment status
		if (empty($payment_status)) {
			$payment_status = $entry['payment_status'];
		}
		$payment_amount      = \GFCommon::to_number(rgpost('payment_amount'));
		$payment_transaction = rgpost('flutterwave_transaction_id');
		$payment_date        = rgpost('payment_date');
		$status_unchanged = $entry['payment_status'] == $payment_status;
		$amount_unchanged = $entry['payment_amount'] == $payment_amount;
		$id_unchanged     = $entry['transaction_id'] == $payment_transaction;
		$date_unchanged   = $entry['payment_date'] == $payment_date;
		if ($status_unchanged && $amount_unchanged && $id_unchanged && $date_unchanged) {
			return;
		}
		if (empty($payment_date)) {
			$payment_date = gmdate('y-m-d H:i:s');
		} else {
			//format date entered by user
			$payment_date = date('Y-m-d H:i:s', strtotime($payment_date));
		}
		global $current_user;
		$user_id   = 0;
		$user_name = 'System';
		if ($current_user && $user_data = get_userdata($current_user->ID)) {
			$user_id   = $current_user->ID;
			$user_name = $user_data->display_name;
		}
		$entry['payment_status'] = $payment_status;
		$entry['payment_amount'] = $payment_amount;
		$entry['payment_date']   = $payment_date;
		$entry['transaction_id'] = $payment_transaction;
		// if payment status does not equal approved/paid or the lead has already been fulfilled, do not continue with fulfillment
		if (($payment_status == 'Approved' || $payment_status == 'Paid') && ! $entry['is_fulfilled']) {
			$action['id']             = $payment_transaction;
			$action['type']           = 'complete_payment';
			$action['transaction_id'] = $payment_transaction;
			$action['amount']         = $payment_amount;
			$action['entry_id']       = $entry['id'];
			$this->complete_payment($entry, $action);
			$this->fulfill_order($entry, $payment_transaction, $payment_amount);
		}
		//update lead, add a note
		\GFAPI::update_entry($entry);
		\GFFormsModel::add_note($entry['id'], $user_id, $user_name, sprintf(esc_html__('Payment information was manually updated. Status: %s. Amount: %s. Transaction ID: %s. Date: %s', 'wooflutter'), $entry['payment_status'], \GFCommon::to_money($entry['payment_amount'], $entry['currency']), $payment_transaction, $entry['payment_date']));
	}
	public function fulfill_order(&$entry, $transaction_id, $amount, $feed = null) {
		if (! $feed) {
			$feed = $this->get_payment_feed($entry);
		}
		$form = \GFFormsModel::get_form_meta($entry['form_id']);
		if (rgars($feed, 'meta/delayPost')) {
			$this->log_debug(__METHOD__ . '(): Creating post.');
			$entry['post_id'] = \GFFormsModel::create_post($form, $entry);
			$this->log_debug(__METHOD__ . '(): Post created.');
		}
		if (rgars($feed, 'meta/delayNotification')) {
			//sending delayed notifications
			$notifications = $this->get_notifications_to_send($form, $feed);
			\GFCommon::send_notifications($notifications, $form, $entry, true, 'form_submission');
		}
		do_action('gform_flutterwave_fulfillment', $entry, $feed, $transaction_id, $amount);
		if (has_filter('gform_flutterwave_fulfillment')) {
			$this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_flutterwave_fulfillment.');
		}
	}
	/**
	 * Retrieve the IDs of the notifications to be sent.
	 *
	 * @param array $form The form which created the entry being processed.
	 * @param array $feed The feed which processed the entry.
	 *
	 * @return array
	 */
	public function get_notifications_to_send($form, $feed) {
		$notifications_to_send  = [];
		$selected_notifications = rgars($feed, 'meta/selectedNotifications');
		if (is_array($selected_notifications)) {
			// Make sure that the notifications being sent belong to the form submission event, just in case the notification event was changed after the feed was configured.
			foreach ($form['notifications'] as $notification) {
				if (rgar($notification, 'event') != 'form_submission' || ! in_array($notification['id'], $selected_notifications)) {
					continue;
				}
				$notifications_to_send[] = $notification['id'];
			}
		}
		return $notifications_to_send;
	}
	private function is_valid_initial_payment_amount($entry_id, $amount_paid) {
		//get amount initially sent to flutterwave
		$amount_sent = gform_get_meta($entry_id, 'payment_amount');
		if (empty($amount_sent)) {
			return true;
		}
		$epsilon    = 0.00001;
		$is_equal   = abs(floatval($amount_paid) - floatval($amount_sent)) < $epsilon;
		$is_greater = floatval($amount_paid) > floatval($amount_sent);
		//initial payment is valid if it is equal to or greater than product/subscription amount
		if ($is_equal || $is_greater) {
			return true;
		}
		return false;
	}
	public function flutterwave_fulfillment($entry, $flutterwave_config, $transaction_id, $amount) {
		//no need to do anything for flutterwave when it runs this function, ignore
		return false;
	}
	/**
	 * Editing of the payment details should only be possible if the entry was processed by FlutterWave, if the payment status is Pending or Processing, and the transaction was not a subscription.
	 *
	 * @param array $entry The current entry
	 * @param string $action The entry detail page action, edit or update.
	 *
	 * @return bool
	 */
	public function payment_details_editing_disabled($entry, $action = 'edit') {
		if (! $this->is_payment_gateway($entry['id'])) {
			// Entry was not processed by this add-on, don't allow editing.
			return true;
		}
		$payment_status = rgar($entry, 'payment_status');
		if ($payment_status == 'Approved' || $payment_status == 'Paid' || rgar($entry, 'transaction_type') == 2) {
			// Editing not allowed for this entries transaction type or payment status.
			return true;
		}
		if ($action == 'edit' && rgpost('screen_mode') == 'edit') {
			// Editing is allowed for this entry.
			return false;
		}
		if ($action == 'update' && rgpost('screen_mode') == 'view' && rgpost('action') == 'update') {
			// Updating the payment details for this entry is allowed.
			return false;
		}
		// In all other cases editing is not allowed.
		return true;
	}
	/**
	 * Activate sslverify by default for new installations.
	 *
	 * Transform data when upgrading from legacy flutterwave.
	 *
	 * @param $previous_version
	 */
	public function upgrade($previous_version) {
		if (empty($previous_version)) {
			$previous_version = get_option('gf_flutterwave_version');
		}
		if (empty($previous_version)) {
			update_option('gform_flutterwave_sslverify', true);
		}
		$previous_is_pre_addon_framework = ! empty($previous_version) && version_compare($previous_version, '2.0.dev1', '<');
		if ($previous_is_pre_addon_framework) {
			//copy plugin settings
			$this->copy_settings();
			//copy existing feeds to new table
			$this->copy_feeds();
			//copy existing flutterwave transactions to new table
			$this->copy_transactions();
			//updating payment_gateway entry meta to 'wooflutter' from 'Flutterwave'
			$this->update_payment_gateway();
			//updating entry status from 'Approved' to 'Paid'
			$this->update_lead();			
			
		}
		// Remove TLS 1.2 warning.
		if (! empty($previous_version) && version_compare($previous_version, '3.2', '<')) {
			delete_transient('gravityformsflutterwave_tlstest_response');
		}
	}
	public function uninstall(){
		parent::uninstall();
		delete_option('gform_flutterwave_sslverify');
	}
	public static function get_entry_table_name() {
		return version_compare(self::get_gravityforms_db_version(), '2.3-dev-1', '<') ? \GFFormsModel::get_lead_table_name() : \GFFormsModel::get_entry_table_name();
 	}
	public static function get_entry_meta_table_name() {
		return version_compare(self::get_gravityforms_db_version(), '2.3-dev-1', '<') ? \GFFormsModel::get_lead_meta_table_name() : \GFFormsModel::get_entry_meta_table_name();
 	}
	public static function get_gravityforms_db_version() {
		if (method_exists('GFFormsModel', 'get_database_version')) {
			$db_version = \GFFormsModel::get_database_version();
		} else {
			$db_version = \GFForms::$version;
		}
		return $db_version;
	}
	//------ FOR BACKWARDS COMPATIBILITY ----------------------//
	public function update_feed_id($old_feed_id, $new_feed_id) {
		global $wpdb;
		$entry_meta_table = self::get_entry_meta_table_name();
		$sql = $wpdb->prepare("UPDATE {$entry_meta_table} SET meta_value=%s WHERE meta_key='flutterwave_feed_id' AND meta_value=%s", $new_feed_id, $old_feed_id);
		$wpdb->query($sql);
	}
	public function add_legacy_meta($new_meta, $old_feed) {
		$known_meta_keys = ['email', 'mode', 'type', 'style', 'continue_text', 'cancel_url', 'disable_note', 'disable_shipping', 'recurring_amount_field', 'recurring_times', 'recurring_retry', 'billing_cycle_number', 'billing_cycle_type', 'trial_period_enabled', 'trial_amount', 'trial_period_number', 'trial_period_type', 'delay_post', 'update_post_action', 'delay_notifications', 'selected_notifications', 'flutterwave_conditional_enabled', 'flutterwave_conditional_field_id', 'flutterwave_conditional_operator', 'flutterwave_conditional_value', 'customer_fields'];
		foreach ($old_feed['meta'] as $key => $value) {
			if (! in_array($key, $known_meta_keys)) {
				$new_meta[ $key ] = $value;
			}
		}
		return $new_meta;
	}
	public function update_payment_gateway() {
		global $wpdb;
		$entry_meta_table = self::get_entry_meta_table_name();
		$sql = $wpdb->prepare("UPDATE {$entry_meta_table} SET meta_value=%s WHERE meta_key='payment_gateway' AND meta_value='flutterwave'", $this->_slug);
		$wpdb->query($sql);
	}
	public function update_lead() {
		global $wpdb;
		$entry_table = self::get_entry_table_name();
		$entry_meta_table = self::get_entry_meta_table_name();
		$entry_id_column = version_compare(self::get_gravityforms_db_version(), '2.3-dev-1', '<') ? 'lead_id' : 'entry_id';
		$sql = $wpdb->prepare(
			"UPDATE {$entry_table}
			 SET payment_status='Paid', payment_method='Flutterwave'
		     WHERE payment_status='Approved'
		     		AND ID IN (
					  	SELECT {$entry_id_column} FROM {$entry_meta_table} WHERE meta_key='payment_gateway' AND meta_value=%s
				   	)",
			$this->_slug);
		$wpdb->query($sql);
	}
	public function copy_settings() {
		//copy plugin settings
		$old_settings = get_option('gf_flutterwave_configured');
		$new_settings = ['gf_flutterwave_configured' => $old_settings];
		$this->update_plugin_settings($new_settings);
	}
	public function copy_feeds() {
		//get feeds
		$old_feeds = $this->get_old_feeds();
		if ($old_feeds) {
			$counter = 1;
			foreach ($old_feeds as $old_feed) {
				$feed_name       = 'Feed ' . $counter;
				$form_id         = $old_feed['form_id'];
				$is_active       = $old_feed['is_active'];
				$customer_fields = $old_feed['meta']['customer_fields'];
				$new_meta = [
					'feedName'                     => $feed_name,
					'flutterwaveEmail'             => rgar($old_feed['meta'], 'email'),
					'mode'                         => rgar($old_feed['meta'], 'mode'),
					'transactionType'              => rgar($old_feed['meta'], 'type'),
					'type'                         => rgar($old_feed['meta'], 'type'), //For backwards compatibility of the delayed payment feature
					'cancelUrl'                    => rgar($old_feed['meta'], 'cancel_url'),
					'disableNote'                  => rgar($old_feed['meta'], 'disable_note'),
					'disableShipping'              => rgar($old_feed['meta'], 'disable_shipping'),
					'recurringAmount'              => rgar($old_feed['meta'], 'recurring_amount_field') == 'all' ? 'form_total' : rgar($old_feed['meta'], 'recurring_amount_field'),
					'recurring_amount_field'       => rgar($old_feed['meta'], 'recurring_amount_field'), //For backwards compatibility of the delayed payment feature
					'recurringTimes'               => rgar($old_feed['meta'], 'recurring_times'),
					'recurringRetry'               => rgar($old_feed['meta'], 'recurring_retry'),
					'paymentAmount'                => 'form_total',
					'billingCycle_length'          => rgar($old_feed['meta'], 'billing_cycle_number'),
					'billingCycle_unit'            => $this->convert_interval(rgar($old_feed['meta'], 'billing_cycle_type'), 'text'),
					'trial_enabled'                => rgar($old_feed['meta'], 'trial_period_enabled'),
					'trial_product'                => 'enter_amount',
					'trial_amount'                 => rgar($old_feed['meta'], 'trial_amount'),
					'trialPeriod_length'           => rgar($old_feed['meta'], 'trial_period_number'),
					'trialPeriod_unit'             => $this->convert_interval(rgar($old_feed['meta'], 'trial_period_type'), 'text'),
					'delayPost'                    => rgar($old_feed['meta'], 'delay_post'),
					'change_post_status'           => rgar($old_feed['meta'], 'update_post_action') ? '1' : '0',
					'update_post_action'           => rgar($old_feed['meta'], 'update_post_action'),
					'delayNotification'            => rgar($old_feed['meta'], 'delay_notifications'),
					'selectedNotifications'        => rgar($old_feed['meta'], 'selected_notifications'),
					'billingInformation_firstName' => rgar($customer_fields, 'first_name'),
					'billingInformation_lastName'  => rgar($customer_fields, 'last_name'),
					'billingInformation_email'     => rgar($customer_fields, 'email'),
					'billingInformation_address'   => rgar($customer_fields, 'address1'),
					'billingInformation_address2'  => rgar($customer_fields, 'address2'),
					'billingInformation_city'      => rgar($customer_fields, 'city'),
					'billingInformation_state'     => rgar($customer_fields, 'state'),
					'billingInformation_zip'       => rgar($customer_fields, 'zip'),
					'billingInformation_country'   => rgar($customer_fields, 'country'),
				];
				$new_meta = $this->add_legacy_meta($new_meta, $old_feed);
				//add conditional logic
				$conditional_enabled = rgar($old_feed['meta'], 'flutterwave_conditional_enabled');
				if ($conditional_enabled) {
					$new_meta['feed_condition_conditional_logic']        = 1;
					$new_meta['feed_condition_conditional_logic_object'] = [
						'conditionalLogic' =>[
							'actionType' => 'show',
							'logicType'  => 'all',
							'rules'      => [
								[
									'fieldId'  => rgar($old_feed['meta'], 'flutterwave_conditional_field_id'),
									'operator' => rgar($old_feed['meta'], 'flutterwave_conditional_operator'),
									'value'    => rgar($old_feed['meta'], 'flutterwave_conditional_value')
								]
							]
						]
					];
				} else {
					$new_meta['feed_condition_conditional_logic'] = 0;
				}

				$new_feed_id = $this->insert_feed($form_id, $is_active, $new_meta);
				$this->update_feed_id($old_feed['id'], $new_feed_id);
				$counter ++;
			}
		}
	}
	public function copy_transactions() {
		//copy transactions from the flutterwave transaction table to the add payment transaction table
		global $wpdb;
		$old_table_name = $this->get_old_transaction_table_name();
		if (! $this->table_exists($old_table_name)) {
			return false;
		}
		$this->log_debug(__METHOD__ . '(): Copying old FlutterWave transactions into new table structure.');
		$new_table_name = $this->get_new_transaction_table_name();
		$sql = "INSERT INTO {$new_table_name} (lead_id, transaction_type, transaction_id, is_recurring, amount, date_created)
					SELECT entry_id, transaction_type, transaction_id, is_renewal, amount, date_created FROM {$old_table_name}";
		$wpdb->query($sql);
		$this->log_debug(__METHOD__ . "(): transactions: {$wpdb->rows_affected} rows were added.");
	}
	
	public function get_old_transaction_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'rg_flutterwave_transaction';
	}
	public function get_new_transaction_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'gf_addon_payment_transaction';
	}
	public function get_old_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_flutterwave';
		if (! $this->table_exists($table_name)) {
			return false;
		}
		$form_table_name = \GFFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
					FROM {$table_name} s
					INNER JOIN {$form_table_name} f ON s.form_id = f.id";
		$this->log_debug(__METHOD__ . "(): getting old feeds: {$sql}");
		$results = $wpdb->get_results($sql, ARRAY_A);
		$this->log_debug(__METHOD__ . "(): error?: {$wpdb->last_error}");
		$count = sizeof($results);
		$this->log_debug(__METHOD__ . "(): count: {$count}");
		for ($i = 0; $i < $count; $i ++) {
			$results[ $i ]['meta'] = maybe_unserialize($results[ $i ]['meta']);
		}
		return $results;
	}
	//This function kept static for backwards compatibility
	public static function get_config_by_entry($entry) {
		$flutterwave = GFFlutterWave::get_instance();
		$feed = $flutterwave->get_payment_feed($entry);
		if (empty($feed)) {
			return false;
		}
		return $feed['addon_slug'] == $flutterwave->_slug ? $feed : false;
	}
	//This function kept static for backwards compatibility
	//This needs to be here until all add-ons are on the framework, otherwise they look for this function
	public static function get_config($form_id) {
		$flutterwave = GFFlutterWave::get_instance();
		$feed   = $flutterwave->get_feeds($form_id);
		//Ignore IPN messages from forms that are no longer configured with the FlutterWave add-on
		if (! $feed) {
			return false;
		}
		return $feed[0]; //only one feed per form is supported (left for backwards compatibility)
	}
	//------------------------------------------------------
}