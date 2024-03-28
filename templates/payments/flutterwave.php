<?php
/**
 * Flutterwave Payment Request Handler
 *
 * @package WooFlutter
 */

$settings = WOOFLUTTER_OPTIONS;

$transaction_id     = get_query_var('transaction_id');
$payment_status     = get_query_var('status');
$tx_ref             = get_query_var('tx_ref');
print_r([
    $transaction_id,
    $payment_status,
    $tx_ref
]);
$verify = apply_filters('wooflutter/project/payment/flutterwave/verify', $transaction_id, $payment_status);
print_r(
    $verify?'is verified':'not varified'
);