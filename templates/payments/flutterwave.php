<?php
/**
 * Flutterwave Payment Request Handler
 *
 * @package WooFlutter
 */

global $WooFlutter_Flutterwave;
$settings = WOOFLUTTER_OPTIONS;

$transaction_id     = get_query_var('transaction_id');
$payment_status     = get_query_var('status');
$tx_ref             = get_query_var('tx_ref');

$order_id = wc_get_order_id_by_order_key($tx_ref);
$order = wc_get_order($order_id);

$payment_gateway    = WC()->payment_gateways->payment_gateways();
$settings = isset($payment_gateway['flutterwave'])?$payment_gateway['flutterwave']->settings:false;
if ($settings) {
    $settings = (object) $settings;
    $settings->testmode = $settings->testmode == 'yes';
    $settings->public_key = $settings->testmode ? $settings->test_public_key : $settings->live_public_key;
    $settings->secret_key = $settings->testmode ? $settings->test_secret_key : $settings->live_secret_key;
    $WooFlutter_Flutterwave->set_api_key($settings);
}
$trxInfo = apply_filters('wooflutter/project/payment/flutterwave/info', $transaction_id);
$trxStatus = (isset($trxInfo['data']) && isset($trxInfo['data']['status']))?$trxInfo['data']['status']:false;
$isVerified = $trxStatus == $payment_status;
$isSuccessful = $payment_status == 'successful';
if ($isSuccessful) {
    $order->update_status('processing');
    $order->add_order_note(
        sprintf(
            __('Payment marked as Successful on %s Using %s, Transection ID #%s. Order status chaged to %s.', 'wooflutter'),
            wp_date('M d, Y H:i'),
            __('Flutterwave', 'wooflutter'),
            $transaction_id,
            'processing'
        )
    );
}

$order->update_meta_data('_flutterwave_trx_info', $trxInfo);
$order->set_transaction_id($transaction_id);
$order->save();

switch ($payment_status) {
    case 'successful':
        $_messageIcon = 'fa-check-circle';
        $_messageClass = '_success';
        break;
    default:
        $_messageIcon = 'fa-times-circle';
        $_messageClass = '_failed';
        break;
}
// 
// print_r([$trxInfo]);
// 
if ($isVerified) {
    get_header();
    // echo do_shortcode('[]', true);
    ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="message-box <?php echo esc_attr($_messageClass); ?>">
                     <i class="fa <?php echo esc_attr($_messageIcon); ?>" aria-hidden="true"></i>
                     <?php
                     switch ($payment_status) {
                        case 'successful':
                            echo sprintf(
                                '<h2>%s</h2><p>%s</p>',
                                __('Your payment was successful', 'wooflutter'),
                                __('Thank you for your payment. we will be in contact with more details shortly', 'wooflutter')
                            );
                            break;
                        default:
                            echo sprintf(
                                '<h2>%s</h2><p>%s</p>',
                                __('Your payment failed', 'wooflutter'),
                                __('Try again later', 'wooflutter'),
                            );
                            break;
                     }
                     ?>    
                </div>
            </div>
        </div>
    </div>
    <style>._failed{border-bottom:solid 4px red!important}._failed i{color:red!important}._success{box-shadow:0 15px 25px #00000019;padding:45px;width:100%;text-align:center;margin:40px auto;border-bottom:solid 4px #28a745}._success i{font-size:55px;color:#28a745}._success h2{margin-bottom:12px;font-size:40px;font-weight:500;line-height:1.2;margin-top:10px}._success p{margin-bottom:0;font-size:18px;color:#495057;font-weight:500}</style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" />
    <?php
    get_footer();
} else {
    wp_die(__('Something went wrong!', 'wooflutter'));
}