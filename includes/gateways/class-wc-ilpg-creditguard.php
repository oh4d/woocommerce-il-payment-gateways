<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_ILPG_CreditGuard extends WC_IL_PGateways
{
    /**
     * @var string
     */
    protected $transactions_meta_key;

    public function __construct()
    {
        $this->has_fields = true;
        $this->id = 'ilpg_creditguard';
        $this->gateway_method_title = 'CreditGuard';
        $this->gateway_method_description = 'CreditGuard';
        $this->icon = woocommerce_il_pgateways()->plugin_url . 'assets/images/creditguard.png';
        $this->order_button_text  = __('Continue to payment', 'woocommerce-il-payment-gateways');
        $this->transactions_meta_key = "{$this->id}_transactions";

        $this->supports = ['products', 'refunds'];

        parent::__construct();
    }

    /**
     * Initialize Actions
     */
    public function init_actions()
    {
        add_action("woocommerce_receipt_{$this->id}", array($this, 'receipt_page'));
        add_action("woocommerce_api_wc_gateway_{$this->id}", array($this, 'gateway_response'));
    }

    /**
     * Refund transaction.
     *
     * @param int $order_id
     * @param null $amount
     * @param string $reason
     * @return boolean|WP_Error True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        $transaction_id = $order->get_transaction_id();

        if ($amount != $order->get_total()) {
            return new WP_Error('error', 'For refund transaction with CreditGuard please fill the total order amount.');
        }

        try {
            $response = $this->refund_transaction($transaction_id);
        } catch (\Exception $exception) {
            return new WP_Error('error', $exception->getMessage());
        }

        $order->add_order_note(
            sprintf(__('CreditCard refund completed. transaction ID: %s for amount: %s', 'woocommerce-il-payment-gateways'),
                $transaction_id, (($response->total) ? ($response->total / 100 . $response->currency) : 0)
            )
        );

        return true;
    }

    /**
     * Process Payment By Selected Flow
     * Optional Validate Custom Credentials
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /**
     * Display IFrame Payment
     * The Src Of The IFrame Will Be
     * To Custom Route (Achieved By Woocommerce API Action)
     *
     * @param $order_id
     */
    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);

        try {
            $transaction = $this->create_transaction($order);
        } catch (\Exception $exception) {
            echo '<div class="unavailable_payment" style="color:red;text-align:center;"><p>'.$exception->getCode().', '.$exception->getMessage().'</p></div>';
            return;
        }

        $meta_data = [
            'token' => (string) $transaction->token,
            'uniqueid' => (string) $transaction->uniqueid,
            'mpiHostedPageUrl' => (string) $transaction->mpiHostedPageUrl,
            'cgUid' => (string) $transaction->cgUid
        ];

        $order_transactions = $this->get_stored_meta_transactions($order);

        $order_transactions[$meta_data['cgUid']] = $meta_data;
        update_post_meta($order->get_id(), $this->transactions_meta_key, $order_transactions);

        echo '<iframe src="' . $transaction->mpiHostedPageUrl . '"
                    id="chekout_frame" class="ilpg_chekout_frame" name="chekout_frame"
                    style="border:none;width:100%;height:550px"></iframe>';
    }

    /**
     * Gateway Response From IFrame Flow
     *
     * @url SITE_URL/wc-api/WC_Gateway_ilpg_creditguard/
     * @return void
     */
    public function gateway_response()
    {
        $transaction_id = $_GET['txId'];

        echo '<script>window.parent.jQuery("div.woocommerce:not(.widget)").block({message: null,overlayCSS: {background: "#fff",opacity: 0.6}});</script>';

        try {
            $transaction_details = $this->check_transaction($transaction_id);
        } catch (\Exception $exception) {
            $order_id = $_GET['id'];
            $status_text = $exception->getMessage();

            // Store The Error Message In Session
            wc_add_notice(
                $status_text ? sprintf(__('Gateway returned with error: %s', 'woocommerce-il-payment-gateways'), $status_text) : __('Something went wrong, please try again', 'woocommerce-il-payment-gateways'),
                'error'
            );

            if ($order_id) {
                $order = wc_get_order($order_id);
                $redirect_url = $order->get_checkout_payment_url(($exception->getCode() == 302) ? false : true);

                $this->update_transaction_meta(
                    $order, $transaction_id, ['statusText' => $status_text]
                );
            } else {
                $redirect_url = home_url('/');
            }

            echo '<script>window.top.location.href = "' . $redirect_url . '";</script>';
            exit;
        }

        $do_deal_details = $transaction_details->row->cgGatewayResponseXML->ashrait->response->doDeal;

        // In Success transaction only relay on the id that sent while creating the transaction.
        $order_id = (int) $do_deal_details->user;
        $order = wc_get_order($order_id);

        $reference_keys = [
            'tranId' => (string) $transaction_details->row->cgGatewayResponseXML->ashrait->response->tranId,
            'statusText' => (string) $transaction_details->row->statusText,
            'cgGatewayResponseCode' => (string) $transaction_details->row->cgGatewayResponseCode,
            'cgGatewayResponseText' => (string) $transaction_details->row->cgGatewayResponseText,
            'total' => (string) ((int) $do_deal_details->total) / 100,
            'currency' => (string) $do_deal_details->currency,
            'creditType' => (string) $do_deal_details->creditType,
        ];

        if ($reference_keys['creditType'] === 'Payments') {
            $reference_keys['numberOfPayments'] = (string) $do_deal_details->creditType;
            $reference_keys['firstPayment'] = (string) $do_deal_details->firstPayment;
        }

        $this->update_transaction_meta(
            $order, $transaction_id, $reference_keys
        );

        $this->complete_order($order, implode(', ', array_map(
            function($v, $k) { return sprintf("%s: '%s' ", $k, $v); }, $reference_keys, array_keys($reference_keys)
        )), $reference_keys['tranId']);

        echo '<script>window.top.location.href = "' . $this->get_return_url($order) . '";</script>';
        exit;
    }

    /**
     * Get Language for the api request.
     *
     * @return mixed
     */
    public function get_settings_lang_param()
    {
        return (get_locale() === 'he_IL') ? 'HEB' : 'EN';
    }

    /**
     * Get the transaction credit type
     * case the max number payment not configured or 0
     * will return regular credit payment else will return payments transaction.
     *
     * @return string
     */
    public function get_transaction_credit_type()
    {
        return ($this->settings['payments'] && $this->settings['payments'] > 0) ? 'Payments' : 'RegularCredit';
    }

    /**
     * Get the max number of payments for transaction configured.
     *
     * @return string
     */
    public function get_transaction_number_of_payments()
    {
        return ($this->get_transaction_credit_type() === 'Payments') ? $this->settings['payments'] : '';
    }

    /**
     * Send post request to CreditGuard gateway.
     *
     * @param $xml
     * @param $command
     * @return SimpleXMLElement
     *
     * @throws \Exception
     */
    private function api_request($xml, $command = null)
    {
        $xml = to_xml($xml);

        $request_data = [
            'user' => $this->settings['username'],
            'password' => $this->settings['password'],
            'int_in' => $xml,
        ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->settings['gateway_url']);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($request_data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FAILONERROR,true);

        $result = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if (!empty($error)) {
            throw new \Exception((string) $error, 500);
        }

        if ($this->get_settings_lang_param() === 'HEB') {
            $result = iconv("utf-8", "iso-8859-8", $result);
        }

        $xmlObj = simplexml_load_string($result);

        $this->log($xmlObj);

        if (!$xmlObj->response || $xmlObj->response->result != '000') {
            $message = $xmlObj->response ? $xmlObj->response->userMessage : 'Internal Error';
            $code = $xmlObj->response ? $xmlObj->response->result : '500';
            throw new \Exception((string) $message, (string) $code);
        }

        if ($command && !$xmlObj->response->{$command}) {
            throw new \Exception(__('Something went wrong, please try again', 'woocommerce-il-payment-gateways'), 500);
        }

        return ($command) ? $xmlObj->response->{$command} : $xmlObj;
    }

    /**
     * Send request for creating new transaction
     *
     * @param WC_Order $order
     * @return mixed
     *
     * @throws \Exception
     */
    protected function create_transaction($order)
    {
        $params = [
            'ashrait' => [
                'request' => [
                    'version' => $this->config['version'],
                    'language' => $this->get_settings_lang_param(),
                    'dateTime' => '',
                    'command' => 'doDeal',
                    'doDeal' => [
                        'cardNo' => 'CGMPI',
                        'validation' => 'TxnSetup',
                        'transactionCode' => 'Phone',
                        'transactionType' => 'Debit',
                        'mpiValidation' => 'AutoComm',
                        'creditType' => $this->get_transaction_credit_type(),
                        'numberOfPayments' => $this->get_transaction_number_of_payments(),
                        'mid' => $this->settings['mid'],
                        'terminalNumber' => $this->settings['terminal'],
                        'uniqueid' => gen_uuid(),
                        'clientIP' => get_request_ip(),
                        'user' => $order->get_id(),
                        'successUrl' => WC()->api_request_url("WC_Gateway_{$this->id}") . '?id=' . $order->get_id(),
                        'cancelUrl' => WC()->api_request_url("WC_Gateway_{$this->id}") . '?id=' . $order->get_id(),
                        'errorUrl' => WC()->api_request_url("WC_Gateway_{$this->id}") . '?id=' . $order->get_id(),
                        'description' => preg_replace('/,|\./', ' ', $this->transform_order_info($order)),
                        'email' => $order->get_billing_email(),
                        'total' => $order->get_total() * 100,
                        'currency' => $order->get_currency(),

                        // 'customerData' => '',
                        // 'dealerNumber' => '',

                        // 'authNumber' => '',
                        // 'numberOfPayments' => '',
                        // 'firstPayment' => '',
                        // 'periodicalPayment' => '',
                    ]
                ]
            ]
        ];

        $response = $this->api_request($params, 'doDeal');

        if (!$response->mpiHostedPageUrl) {
            throw new \Exception(__('Something went wrong, please try again', 'woocommerce-il-payment-gateways'), 500);
        }

        return $response;
    }

    /**
     * Send request to credit guard gateway
     * to retrieving the current transaction status.
     *
     * @param $transaction_id
     * @return mixed
     *
     * @throws Exception
     */
    protected function check_transaction($transaction_id)
    {
        if (!$transaction_id) {
            throw new \Exception(__('No transaction id found.', 'woocommerce-il-payment-gateways'), 422);
        }

        $params = [
            'ashrait' => [
                'request' => [
                    'language' => $this->get_settings_lang_param(),
                    'command' => 'inquireTransactions',
                    'inquireTransactions' => [
                        'queryName' => 'mpiTransaction',
                        'mid' => $this->settings['mid'],
                        'terminalNumber' => $this->settings['terminal'],
                        'mpiTransactionId' => $transaction_id
                    ]
                ]
            ]
        ];

        $response = $this->api_request($params, 'inquireTransactions');

        if (!$response->row || !$response->row->cgGatewayResponseCode || $response->row->cgGatewayResponseCode != '000') {
            $status_text = ($response->row) ? ucwords((string) $response->row->cgGatewayResponseText) : null;
            $error_code = ($response->row && (string) $response->row->statusText == 'CANCELLED') ? 302 : 422;

            throw new \Exception($status_text, $error_code);
        }

        return $response;
    }

    /**
     * @param $transaction_id
     * @return SimpleXMLElement
     * @throws Exception
     */
    protected function refund_transaction($transaction_id)
    {
        if (!$transaction_id) {
            throw new \Exception(__('No transaction id found.', 'woocommerce-il-payment-gateways'), 422);
        }

        $params = [
            'ashrait' => [
                'request' => [
                    'language' => $this->get_settings_lang_param(),
                    'command' => 'refundDeal',
                    'refundDeal' => [
                        'terminalNumber' => $this->settings['terminal'],
                        'tranId' => $transaction_id,
                    ]
                ]
            ]
        ];

        $response = $this->api_request($params, 'refundDeal');

        return $response;
    }

    /**
     * Get transactions meta data.
     *
     * @param WC_Order $order
     * @param string|null $uniqueId
     * @param string|null $cgUid
     * @param string|null $token
     * @return array
     */
    protected function get_stored_meta_transactions($order, $uniqueId = null, $cgUid = null, $token = null)
    {
        $order_transactions = get_post_meta($order->get_id(), $this->transactions_meta_key, true);

        if ($order_transactions && count($order_transactions)
            && ($uniqueId || $cgUid || $token)) {

            $searchByKey = ($uniqueId) ? 'uniqueId' : (
                $cgUid ? 'cgUid' : 'token'
            );

            $searchByValue = ($uniqueId) ?: (
                $cgUid ?: $token
            );

            foreach ($order_transactions as $transaction) {
                if (isset($transaction[$searchByKey]) && $transaction[$searchByKey] == $searchByValue) {
                    return $transaction;
                }
            }

            return null;
        }

        return ($order_transactions && is_array($order_transactions)) ? $order_transactions : [];
    }

    /**
     * Update transaction meta values
     * by transaction unique id search.
     *
     * @param WC_Order $order
     * @param string $transaction_id
     * @param array $merge
     * @return void
     */
    protected function update_transaction_meta($order, $transaction_id, $merge = [])
    {
        $order_transactions = $this->get_stored_meta_transactions($order);

        if (!$order_transactions || !count($order_transactions)) {
            return;
        }

        $transaction_index = null;

        foreach ($order_transactions as $index => $_transaction) {
            if (isset($_transaction['token']) && $_transaction['token'] == $transaction_id) {
                $transaction_index = $index;
                break;
            }
        }

        if (is_null($transaction_index)) {
            return;
        }

        $order_transactions[$transaction_index] = array_merge($order_transactions[$transaction_index], $merge);
        update_post_meta($order->get_id(), $this->transactions_meta_key, $order_transactions);
    }
}
