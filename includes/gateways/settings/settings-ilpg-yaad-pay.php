<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'signature' => array(
        'title' => __('Password Signature', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('This is the API Signature. You will got from Yaad Pay.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    'term_number' => array(
        'title' => __('Term No.', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('This is the your Term No. You will got from Yaad Pay.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    /*'paymentinstallments' => array(
        'title' => __('Payment Installments', 'woothemes'),
        'type' => 'text',
        'description' => '',
        'default' => '1',
        'disable' => true
    ),*/
    'postpone' => array(
        'title' => __('Postpone', 'woocommerce-il-payment-gateways'),
        'label' => __('Postpone', 'woocommerce-il-payment-gateways'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    'products' => array(
        'title' => __('Products', 'woocommerce-il-payment-gateways'),
        'label' => __('Products', 'woocommerce-il-payment-gateways'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    /*'locale' => array(
        'title' => __('Language'),
        'type' => 'select',
        'description' => '',
        'options' => array(
            'HEB' => 'Hebrew',
            'ENG' => 'English',
            'auto' => 'Auto'
        ),
        'default' => 'False'
    ),
    'checkout_type' => array(
        'title' => __('Checkout Type'),
        'type' => 'select',
        'description' => '',
        'options' => array(
            'iframe' => 'IFrame',
            'form' => 'Form'
        ),
        'default' => 'iframe'
    ),*/
    'iframe_width' => array(
        'title' => __('IFrame Width', 'woocommerce-il-payment-gateways'),
        'type' => 'number',
        'description' => '',
        'default' => '600',
        'disable' => true
    ),
    'iframe_height' => array(
        'title' => __('IFrame Height', 'woocommerce-il-payment-gateways'),
        'type' => 'number',
        'description' => '',
        'default' => '600',
        'disable' => true
    ),
];