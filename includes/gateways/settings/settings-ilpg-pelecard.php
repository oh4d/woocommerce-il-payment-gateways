<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

return [
    'terminal' => array(
        'title' => __('Terminal', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('This is the API Terminal. You will got from Pelecard.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    'username' => array(
        'title' => __('Username', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('Username. You will got from Pelecard.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    'password' => array(
        'title' => __('Password', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('Username. You will got from Pelecard.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
];