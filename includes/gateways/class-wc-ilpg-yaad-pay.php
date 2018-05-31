<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_ILPG_Yaad_Pay extends WC_IL_PGateways
{
    /**
     * WC_ILPG_Yaad_Pay constructor.
     */
    public function __construct()
    {
        $this->icon = 'https://yaadpay.yaad.net/wp-content/uploads/2016/07/BW-secure-payment-logos_HEB-09.gif';
        $this->has_fields = false;
        $this->id = 'ilpg_yaad_pay';
        $this->gateway_method_title = 'Yaad Pay';
        $this->gateway_method_description = 'Return URL: ' . WC()->api_request_url("WC_Gateway_{$this->id}");

        parent::__construct();

        add_action("woocommerce_receipt_{$this->id}", array($this, 'receipt_page'));
    }

    /**
     * Process To Receipt Page To Show IFrame
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        try {
            $this->validate_credentials();

            // Todo Make Call Back To Yaad Sarig With Params To Receive IFrame URL
            // $params = $this->params($order);
            // $response = woocommerce_il_pgateways()->gateway_request($this->config['gateway_url'], 'post', $params);

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        } catch (\Exception $e) {
            wc_add_notice( $e->getMessage(), 'error');

            return array();
        }
    }

    /**
     * @param $order_id
     */
    public function receipt_page($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        $this->generate_iframe();

        die(print_r([$this->settings, $this->params($order)]));
    }

    /**
     *
     */
    public function generate_iframe()
    {
        echo wc_ilpg_iframe('', $this->settings['iframe_width'], $this->settings['iframe_height']);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    public function params($order)
    {
        return [
            'action' => 'pay',
            'Masof' => $this->settings['term_number'],
            'Amount' => $order->get_total(),
            'Info' => $order->get_id(),
            'Order' => $order->get_id(),
            'sendemail' => true,
            'Sign' => true,
            'PageLang' => $this->preview_locale(),
            'Coin' => $this->gateway_currency(),
            'ClientLName' => $order->billing_last_name,
            'ClientName' => $order->billing_first_name,
            'street' => $order->billing_address_1 . " " . $order->billing_address_2,
            'city' => $order->billing_city,
            'zip' => $order->billing_postcode,
            'phone' => $order->billing_phone,
            'cell' => $order->billing_phone,
            'email' => $order->billing_email,
            'UTF8' => true,
            'UTF8out' => true,
            // UserId => '000000000', // Case Lang EN
            // SendHesh => '',
            // heshDesc => '',
            // 'Postpone' => '',
            // 'Tash' => '',
        ];
    }

    /**
     *
     */
    public function gateway_response()
    {
        if (!isset($_GET['Order']) || !$_GET['Order']) {
            // Something Wrong
            return;
        }

        if (!$this->check_hash()) {
            // Hash Is Wrong
            return;
        }

        $order = wc_get_order($_GET['Order']);
        $note = "(PaymentID: {$_GET['Id']})";

        // $_GET['CCode'] != 0 Everything Is OK

        if ($_GET['CCode'] == 0 || $_GET['CCode'] = 800) {
            $this->complete_order($order, $note);
            $redirect_url = $this->get_return_url($order);
        }

        $this->order_failed($order, $note);
        $redirect_url = wc_get_checkout_url();
    }

    /**
     * Verify Yaad Pay Hash In The Return Params
     *
     * @return bool
     */
    public function check_hash()
    {
        $hash = $_GET['Sign'];

        $params = array(
            'Id' => $_GET['Id'],
            'CCode' => $_GET['CCode'],
            'Amount' => $_GET['Amount'],
            'ACode' => $_GET['ACode'],
            'Order' => $_GET['Order'],
            'Fild1' => rawurlencode($_GET['Fild1']),
            'Fild2' => rawurlencode($_GET['Fild2']),
            'Fild3' => rawurlencode($_GET['Fild3'])
        );

        $string = '';
        foreach ($params as $key => $val) {
            $string .= $key . '=' . $val . '&';
        }
        // Remove Last "&"
        $string = substr($string, 0, -1);

        $verify = hash_hmac('SHA256', $string, $this->settings['signature']);

        if ($verify === $hash)
            return true;

        return false;
    }

    /**
     * Validate Basic Credentials
     *
     * @throws Exception
     * @return void
     */
    public function validate_credentials()
    {
        if (!$this->settings['signature'] || !$this->settings['term_number']) {
            throw new \Exception(__('Cant Continue The Payment Contact Site Admin', 'woocommerce-il-payment-gateways'));
        }

        $currency = get_woocommerce_currency();
        if (!isset($this->config['currencies'][$currency])) {
            throw new \Exception(__('Cant Support Currency', 'woocommerce-il-payment-gateways'));
        }
    }
}