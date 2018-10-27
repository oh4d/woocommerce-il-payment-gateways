<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_IL_PGateways_Install
{
    /**
     * WC_IL_PGateways_Install constructor.
     */
    public function __construct()
    {
        $this->installed_version = get_option('wc_il_pgateways_version');

        if (!$this->installed_version)
            $this->installer();
        else
            $this->upgrade();
    }

    /**
     * Upgrade Plugin
     */
    protected function upgrade()
    {
        update_option('wc_il_pgateways_version', WC_IL_PGATEWAYS_VERSION);
    }

    /**
     * Install Plugin
     */
    protected function installer()
    {
        $this->migrations();

        update_option('wc_il_pgateways_version', WC_IL_PGATEWAYS_VERSION);
    }

    /**
     * Migrate database tables
     */
    protected function migrations()
    {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wc_ilpg_transactions` (
               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
               `order_id` bigint(20) unsigned NOT NULL,
               `amount` float(11) NOT NULL,
               `date` timestamp NOT NULL,
               `status` int(1) NOT NULL,
               `method` varchar(100) NOT NULL,
               `note` text NOT NULL,
               `response` longtext NOT NULL,
               `ip` varchar(50) NOT NULL,
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        dbDelta( $sql );
    }
}