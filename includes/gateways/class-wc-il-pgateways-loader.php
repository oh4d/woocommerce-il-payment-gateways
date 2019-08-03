<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_IL_PGateways_Loader
{
    /**
     * WC_IL_PGateways_Loader constructor.
     */
    public function __construct()
    {
        $includes_path = woocommerce_il_pgateways()->includes_path;

        require_once($includes_path . '/gateways/class-wc-ilpg-yaad-pay.php');
        require_once($includes_path . '/gateways/class-wc-ilpg-tranzila.php');
        require_once($includes_path . '/gateways/class-wc-ilpg-pelecard.php');
        require_once($includes_path . '/gateways/class-wc-ilpg-cardcom.php');
        require_once($includes_path . '/gateways/class-wc-ilpg-creditguard.php');

        add_filter('woocommerce_payment_gateways', array($this, 'payment_gateways'));
    }

    /**
     * Load Payment Gateways
     *
     * @param $methods
     * @return array
     */
    public function payment_gateways($methods = [])
    {
        $methods[] = 'WC_ILPG_Tranzila';
        $methods[] = 'WC_ILPG_CreditGuard';

        // $methods[] = 'WC_ILPG_Yaad_Pay';
        // $methods[] = 'WC_ILPG_Pelecard';
        // $methods[] = 'WC_ILPG_CardCom';

        return $methods;
    }
}
