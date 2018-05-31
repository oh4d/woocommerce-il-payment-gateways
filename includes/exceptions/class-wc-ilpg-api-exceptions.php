<?php

class WC_ILPG_Yaad_Sarig_Exceptions extends Exception
{
    public function __construct($response)
    {
        parent::__construct(__('An error occurred while calling the API.', 'woocommerce-il-payment-gateways'));
    }
}