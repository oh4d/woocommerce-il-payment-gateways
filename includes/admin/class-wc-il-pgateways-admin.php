<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_IL_PGateways_Admin
{
    /**
     * WC_IL_PGateways_Admin constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'woo_ilpg_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    /**
     * @return void
     */
    public function woo_ilpg_admin_menu()
    {
        add_submenu_page('woocommerce', __('Payment Transactions', 'woocommerce-il-payment-gateways'), __('Payment Transactions', 'woocommerce-il-payment-gateways'),
            'manage_options', 'wc-ilpg-transactions', [$this, 'transactions_page']);
    }

    /**
     * @return void
     */
    public function transactions_page()
    {
        // wp_enqueue_script('inline-edit-post');

        echo '<div class="wrap">';

        new WC_ILPG_Transactions_List_Table();

        echo '</div>';
    }

    public function admin_enqueue_scripts()
    {
        wp_register_script('wc-ilpg-inline-transaction-view', woocommerce_il_pgateways()->includes_url . 'admin/assets/inline-transaction-view.js', ['jquery'], WC_IL_PGATEWAYS_VERSION, true);
    }
}