<?php
/**
 * Flutterwave Payment Request Handler
 *
 * @package WooFlutter
 */

global $WooFlutter_Flutterwave;global $trxStatus;
$settings = (array) WOOFLUTTER_OPTIONS;global $pageTitle;

$transaction_id     = get_query_var('transaction_id');
$payment_status     = get_query_var('status');
$tx_ref             = get_query_var('tx_ref');
$type               = explode('.', $transaction_id)[0];

do_action('wooflutter/payment/flutterwave/status', $type, $transaction_id, $payment_status, $tx_ref);
$backtoText = apply_filters('wooflutter/payment/flutterwave/status/back2text', __('Back', 'wooflutter'), $type, $transaction_id, $payment_status, $tx_ref);
$backtoLink = apply_filters('wooflutter/payment/flutterwave/status/back2link', false, $type, $transaction_id, $payment_status, $tx_ref);



get_header(); ?>
<div class="wrapper">
    <section class="fltrwv__payment-status-content overflow-hidden container">
        <div class="fltrwv__row no-gutters align-items-center fltrwv__bg-white my-5">      
            <div class="fltrwv__col-md-12 fltrwv__col-lg-6 fltrwv__align-self-center">
                <div class="fltrwv__row justify-content-center pt-5">
                    <div class="fltrwv__col-md-8">
                        <div class="fltrwv__card fltrwv__d-flex fltrwv__justify-content-center mb-0">
                            <div class="fltrwv__card-body">
                                <h2 class="fltrwv__mt-3 fltrwv__mb-4">
                                    <?php echo esc_html(in_array($payment_status, ['success','successful'])?__('Payment Successful', 'wooflutter'):__( 'Payment Failed',   'wooflutter')); ?>
                                </h2>
                                <?php if (isset($settings['paymentSuccess'])): ?>
                                    <p class="cnf-mail mb-1"><?php echo wp_kses_post(in_array($payment_status, ['success','successful'])?stripslashes($settings['paymentSuccess']):stripslashes($settings['paymentFailed'])); ?></p>
                                <?php endif; ?>
                                <div class="fltrwv__d-inline-block fltrwv__w-100">
                                <?php if ($backtoLink && !empty($backtoLink)) : ?>
                                    <a href="<?php echo esc_url($backtoLink); ?>" class="fltrwv__btn fltrwv__btn-primary fltrwv__mt-3 btn button"><?php echo esc_html($backtoText); ?></a>
                                <?php endif; ?>

                                <?php
                                if(!in_array($payment_status, ['success','successful'])):
                                    $retry = apply_filters('wooflutter/payment/flutterwave/status/retry', false, $type, $transaction_id, $payment_status, $tx_ref);
                                    if ($retry && !empty($retry)) :
                                    ?>
                                    <a href="<?php echo esc_url($retry); ?>" class="fltrwv__btn fltrwv__btn-primary fltrwv__mt-3 btn button"><?php esc_html_e('Try again', 'wooflutter'); ?></a>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                </div>   
            
            </div>
            <div class="fltrwv__col-lg-6 fltrwv__d-lg-block fltrwv__p-0 overflow-hidden">
                <img alt="images" loading="lazy" data-src="<?php echo esc_url(WOOFLUTTER_BUILD_URI.'/icons/Card Payment_Monochromatic.svg'); ?>" class="fltrwv__img-fluid fltrwv__gradient-main lazyloaded" src="<?php echo esc_url(WOOFLUTTER_BUILD_URI.'/icons/Card Payment_Monochromatic.svg'); ?>">
                <noscript>
                    <img src="<?php echo esc_url(WOOFLUTTER_BUILD_URI.'/icons/Card Payment_Monochromatic.svg'); ?>" class="fltrwv__img-fluid fltrwv__gradient-main" alt="images" loading="lazy">
                </noscript>
            </div>
        </div>
    </section>
</div>
<style>
    @media (max-width: 47.99em) {.payment-status-content > .row > div:last-child {order: -1;text-align: center;}}
</style>
<?php get_footer();