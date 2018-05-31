<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce-il-payment-gateways'),
        'label' => __("Enable {$this->gateway_method_title}", 'woocommerce-il-payment-gateways'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    'title' => array(
        'title'       => __('Title', 'woocommerce-il-payment-gateways'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-il-payment-gateways'),
        'default'     => __($this->gateway_method_title, 'woocommerce-il-payment-gateways'),
        'desc_tip'    => true,
    ),
    'description' => array(
        'title'       => __('Description', 'woocommerce-il-payment-gateways'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __('This controls the description which the user sees during checkout.'),
        'default'     => __("Pay via {$this->gateway_method_title}", 'woocommerce-il-payment-gateways'),
    ),
    'payment_logo' => array(
        'title'       => __('Payment Image', 'woocommerce-il-payment-gateways'),
        'type'        => 'image',
        // 'description' => __('The Image Will Be ', ''),
        'default'     => '',
        // 'desc_tip'    => true,
        'placeholder' => __('Optional', 'woocommerce-il-payment-gateways'),
    ),
];