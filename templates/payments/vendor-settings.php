<?php
global $WooFlutter_Flutterwave;global $WooFlutter_Assets;
$settings = isset($store_settings)?(array) $store_settings:[];
$settings['payment'] = isset($settings['payment'])?(array) $settings['payment']:[];
$settings['payment']['flutterwave'] = isset($settings['payment']['flutterwave'])?(array) $settings['payment']['flutterwave']:[];
$flutterwave = $settings['payment']['flutterwave'];

$WooFlutter_Flutterwave->set_api_key(false);


// wp_enqueue_script('wooflutter-dokan', WOOFLUTTER_BUILD_JS_URI . '/dokan.js', ['jquery'], $WooFlutter_Assets->filemtime(WOOFLUTTER_BUILD_JS_DIR_PATH.'/dokan.js'), true);

// print_r($store_settings);
?>

<div class="dokan-bank-settings-template">
    
    <?php if (dokan_is_seller_dashboard()) : ?>
        <div class="dokan-form-group">
            <div class="dokan-w8">
                <input name="dokan_update_payment_settings" type="hidden">
                <button class="ajax_prev disconnect dokan_payment_disconnect_btn dokan-btn dokan-btn-danger <?php echo empty($email) ? 'dokan-hide' : ''; ?>" type="button" name="settings[flutterwave][disconnect]">
                    <?php esc_attr_e('Disconnect', 'wooflutter'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="dokan-form-group">
        <div>
            <label for="test_mode"><?php echo esc_html(__('Test Mode', 'domain')); ?> </label>
        </div>
        <div class="dokan-w10">
            <input id="test_mode" name="settings[flutterwave][test_mode]" value="yes" class="dokan-form-control" type="checkbox" <?php echo esc_attr(isset($flutterwave['test_mode'])?'checked':''); ?>>
            <span class="error-container"></span>
        </div>
    </div>

    <?php
    $fields = [
        'live_public_key'   => __('Live public key', 'domain'),
        'live_secret_key'   => __('Live secret key', 'domain'),
        'live_encript_key'  => __('Live encript key', 'domain'),
        'test_public_key'   => __('Test public key', 'domain'),
        'test_secret_key'   => __('Test secret key', 'domain'),
        'account_bank'      => __('Select Bank', 'domain'),
        'account_number'    => __('Account number', 'domain'),
        'split_accounts'    => __('Split Accounts', 'domain'),
    ];
    foreach ($fields as $field_key => $field_title) :
        switch ($field_key) {
            case 'account_bank':
                $field_value = isset($flutterwave[$field_key])?$flutterwave[$field_key]:'';
                ?>
                <div class="dokan-form-group">
                    <div>
                        <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_title); ?> </label>
                    </div>
                    <div class="dokan-w10">
                        <select id="<?php echo esc_attr($field_key); ?>" name="settings[flutterwave][<?php echo esc_attr($field_key); ?>]" value="<?php echo esc_attr($field_value); ?>" class="dokan-form-control" required="">
                            <?php
                            switch ($field_key) {
                                case 'account_bank':
                                    $options = [['name' => __('Please Select...', 'domain'), 'id' => false, 'code' => '']];
                                    try {
                                        foreach ($WooFlutter_Flutterwave->get_banks() as $bank) {
                                            if ($bank['code'] == $field_value) {
                                                $bank['selected'] = true;
                                            }
                                            $bank['name'] = sprintf('%s - (%s)', $bank['name'], $bank['id']);
                                            $bank['attr'] = ['data-bank_id' => $bank['id']];
                                            $options[] = $bank;
                                        }
                                    } catch (\Exception $th) {
                                        //throw $th;
                                    }
                                    break;
                                default:
                                    // 
                                    break;
                            }
                            ?>
                            <?php foreach ($options as $option) : ?>
                                <option value="<?php echo esc_attr($option['code']); ?>" <?php echo esc_attr(isset($option['selected'])?'selected':''); ?> <?php
                                foreach ($option['attr'] as $_key => $_value) {echo esc_attr($_key) . '="' . esc_attr($_value) . '" ';}
                                    ?>> <?php echo esc_html($option['name']); ?> </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-container"></span>
                    </div>
                </div>
                <?php
                break;
            case 'split_accounts':
                $splits = isset($flutterwave[$field_key])?(array) $flutterwave[$field_key]:[];
                ?>
                <div class="dokan-form-group">
                    <div>
                        <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_title); ?> </label>
                    </div>
                    <div class="dokan-w10">
                        <div class="dokan-form-card p2">
                            <div id="split_root" data-splits="<?php echo esc_attr(json_encode($splits)); ?>"></div>
                        </div>
                        <span class="error-container"></span>
                    </div>
                </div>
                <?php
                break;
            default:
                switch ($field_key) {
                    // case 'account_number':
                    //     $input_type = 'number';
                    //     break;
                    default:
                        $input_type = 'text';
                        break;
                }
                ?>

                <div class="dokan-form-group">
                    <div>
                        <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_title); ?> </label>
                    </div>
                    <div class="dokan-w10">
                        <input id="<?php echo esc_attr($field_key); ?>" name="settings[flutterwave][<?php echo esc_attr($field_key); ?>]" value="<?php echo esc_attr(isset($flutterwave[$field_key])?$flutterwave[$field_key]:''); ?>" class="dokan-form-control" type="<?php echo esc_attr($input_type); ?>">
                    <span class="error-container"></span>
                    </div>
                </div>

                <?php
                break;
        }
    endforeach;
    ?>

    <!-- <div class="dokan-form-group dokan-text-left">
        <img decoding="async" alt="bank check" src="https://testbed.com.ng/wp-content/plugins/dokan-lite/assets/images/withdraw-methods/bank-check.png">
    </div> -->

    <div class="dokan-form-group dokan-text-left">
        <input id="declaration" name="settings[flutterwave][declaration]" checked="" type="checkbox">
        <label for="declaration"><?php echo esc_html(__('I attest that I am the owner and have full authorization to this information', 'domain')); ?></label>
    </div>

    <div class="data-warning">
        <div class="left-icon-container">
            <i class="fa fa-info-circle fa-2x" aria-hidden="true"></i>
        </div>
        <div class="vr-separator"></div>
        <div class="dokan-text-left">
            <span class="display-block"><b><?php echo esc_html(__('Please double-check your account information!', 'domain')); ?></b></span>
            <br>
            <span class="display-block"><?php echo esc_html(__('Incorrect or mismatched account name and number can result in withdrawal delays and fees', 'domain')); ?></span>
        </div>
    </div>
    
    <p class="bottom-note"></p>

    <div class="bottom-actions">
        <button class="ajax_prev save dokan-btn dokan-btn-theme" type="submit" name="dokan_update_payment_settings"><?php echo esc_html(__('Update Account', 'domain')); ?></button>
        <a href="<?php echo esc_url(site_url('/dashboard/settings/payment/')); ?>">Cancel</a>
        <input type="hidden" name="dokan_update_payment_settings">
        <button class="ajax_prev disconnect dokan_payment_disconnect_btn dokan-btn dokan-btn-danger dokan-hide" type="button" name="settings[flutterwave][disconnect]"><?php echo esc_html(__('Disconnect', 'domain')); ?></button>
    </div>
</div>
