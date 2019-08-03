<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_IL_PGateways_Checkout_Handler
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueues Frontend Scripts And Styles
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style('wc-il-pgateways-frontend-checkout', woocommerce_il_pgateways()->plugin_url . 'assets/css/wc-il-pgateways-frontend-checkout.css');
    }
}
