<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_ILPG_Cardcom extends WC_IL_PGateways
{
    public function __construct()
    {
        $this->has_fields = true;
        $this->id = 'ilpg_cardcom';
        $this->gateway_method_title = 'Cardcom';
        $this->gateway_method_description = 'Cardcom';

        parent::__construct();
    }
}