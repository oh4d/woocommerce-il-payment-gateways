<?php
/**
 * Plugin Name: WooCommerce IL Payment Gateways
 * Plugin URI: https://github.com/oh4d/woocommerce-il-payment-gateways
 * Description: Israel Payment Gateways For Woocommerce Plugin
 * Version: 1.0.0
 * Author: Ohad Goldstein
 * Author URI: https://www.ohadg.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 3.4
 * Text Domain: woocommerce-il-payment-gateways
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!function_exists('woocommerce_il_pgateways')) {
    /**
     * Initialize The Plugin
     *
     * @return WC_IL_PGateways_Init
     */
    function woocommerce_il_pgateways()
    {
        static $plugin;

        if (!isset($plugin)) {
            require_once('includes/class-wc-il-pgateways-init.php');
            $plugin = new WC_IL_PGateways_Init(__FILE__);
        }

        return $plugin;
    }

    woocommerce_il_pgateways()->load();
}