<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

return [
    'gateway_url' => array(
        'title' => __('Gateway Url', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('This is the Api Gateway Url.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    'username' => array(
        'title' => __('Username', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('This is the API Username.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    'password' => array(
        'title' => __('Password', 'woocommerce-il-payment-gateways'),
        'type' => 'password',
        'default' => '',
    ),
    'mid' => array(
        'title' => __('Mid', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'default' => '',
    ),
    'terminal' => array(
        'title' => __('Terminal Number', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'default' => '',
    ),
];
