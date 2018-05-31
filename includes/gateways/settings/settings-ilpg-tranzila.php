<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'terminal' => array(
        'title' => __('Terminal', 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'description' => __('This is the API Terminal. You will got from Tranzila.', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'desc_tip'    => true
    ),
    'iframe' => array(
        'title' => __('iFrame Mode' , 'woocommerce-il-payment-gateways'),
        'type' => 'checkbox',
        'label' => __('Enable Tranzila iFrame Checkout.', 'woocommerce-il-payment-gateways'),
        'default' => false,
        'description' => sprintf(__('If Left Disable Will Use Tranzila API. An iFrame module gives you the option of embedding a payment window inside your site without redirecting to another site. The Success and Error URL is: %s' , 'woocommerce-il-payment-gateways'), WC()->api_request_url("WC_Gateway_{$this->id}")),
        'desc_tip' => false
    ),
    'iframe_width' => array(
        'title' => __('iFrame Width' , 'woocommerce-il-payment-gateways'),
        'type' => 'number',
        'label' => __('Enable Tranzila iFrame Checkout', 'woocommerce-il-payment-gateways'),
        'default' => 600
    ),
    'iframe_height' => array(
        'title' => __('iFrame Height' , 'woocommerce-il-payment-gateways'),
        'type' => 'number',
        'label' => __('Enable Tranzila iFrame Checkout', 'woocommerce-il-payment-gateways'),
        'default' => 600
    ),
    'iframe_handshake' => array(
        'title' => __('iFrame Handshake Transaction' , 'woocommerce-il-payment-gateways'),
        'type' => 'checkbox',
        'label' => __('Enable Tranzila iFrame Handshake Transaction Key.', 'woocommerce-il-payment-gateways'),
        'default' => false,
        'description' => __('' , 'woocommerce-il-payment-gateways'),
        'desc_tip' => false
    ),
    'tranzila_pw' => array(
        'title' => __('Tranzila PW' , 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'label' => __('Tranzila PW', 'woocommerce-il-payment-gateways'),
        'default' => '',
        'description' => __('Case Handshake Mode Is Enable, Tranzila Need To Generate Custom Password For This Service, And Dont Forget To Also Enable This In Tranzila Settings' , 'woocommerce-il-payment-gateways'),
        'desc_tip' => false
    ),
    'iframe_notify_url' => array(
        'title' => __('iFrame Notify URL' , 'woocommerce-il-payment-gateways'),
        'type' => 'checkbox',
        'label' => __('Enable Tranzila Notify URL Checkout', 'woocommerce-il-payment-gateways'),
        'default' => false,
        'description' => sprintf(__('If Enable Add This Link To Tranzila Notify URL Field: %s' , 'woocommerce-il-payment-gateways'), WC()->api_request_url("WC_Gateway_{$this->id}_webhook")),
        'desc_tip' => false
    ),
    'notify_trusted_ip' => array(
        'title' => __('Notify URL Trusted IP Address' , 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'label' => __('Enable Tranzila Notify URL Checkout', 'woocommerce-il-payment-gateways'),
        'default' => '80.244.170.7',
        'description' => __('The Plugin Will Complete Order Only If The Request To The Notify URL Was Made From Given IP. Leave Empty To Cancel This Check.' , 'woocommerce-il-payment-gateways'),
        'desc_tip' => true
    ),
    'iframe_lang' => array(
        'title' => __('iFrame language', 'woocommerce-il-payment-gateways'),
        'type' => 'select',
        'options' => array(
            'auto' => __('Auto', 'woocommerce-il-payment-gateways'),
            'he_IL' => __('Hebrew', 'woocommerce-il-payment-gateways'),
            'en_US' => __('English', 'woocommerce-il-payment-gateways'),
            'ru_RU' => __('Russian', 'woocommerce-il-payment-gateways'),
            'es_ES' => __('Spanish', 'woocommerce-il-payment-gateways'),
            'de_DE' => __('German', 'woocommerce-il-payment-gateways'),
            'fr_FR' => __('French', 'woocommerce-il-payment-gateways'),
            'ja' => __('Japanese', 'woocommerce-il-payment-gateways'),
        ),
        'label' => __('iFrame lang', 'woocommerce-il-payment-gateways'),
        'default' => 'auto'
    ),
    'iframe_submit' => array(
        'title' => __('IFrame Submit Button Text' , 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'label' => __('IFrame Button Text' , 'woocommerce-il-payment-gateways'),
        'default' => '',
    ),
    'iframe_bg_color' => array(
        'title' => __('IFrame Background Color' , 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'label' => __('IFrame Background Color' , 'woocommerce-il-payment-gateways'),
        'class' => 'colorpick',
        'default' => '',
    ),
    'iframe_button_bg' => array(
        'title' => __('IFrame Button Background Color' , 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'label' => __('IFrame Button Background Color' , 'woocommerce-il-payment-gateways'),
        'class' => 'colorpick',
        'default' => '',
    ),
    'iframe_text_color' => array(
        'title' => __('IFrame Text Color' , 'woocommerce-il-payment-gateways'),
        'type' => 'text',
        'label' => __('IFrame Text Color' , 'woocommerce-il-payment-gateways'),
        'class' => 'colorpick',
        'default' => '',
    )
];