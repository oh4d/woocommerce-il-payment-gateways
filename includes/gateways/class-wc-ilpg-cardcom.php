<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_ILPG_Cardcom extends WC_IL_PGateways
{
    /**
     * WC_ILPG_Cardcom constructor.
     */
    public function __construct()
    {
        $this->has_fields = true;
        $this->id = 'ilpg_cardcom';
        $this->gateway_method_title = 'CardCom';
        $this->gateway_method_description = 'CardCom';

        parent::__construct();
    }

    /**
     * Process Payment
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $this->complete_order($order, '', uniqid());

        return parent::process_payment($order_id);
    }
}