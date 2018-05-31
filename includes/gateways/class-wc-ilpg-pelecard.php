<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_ILPG_Pelecard extends WC_IL_PGateways
{
    public function __construct()
    {
        $this->has_fields = true;
        $this->id = 'ilpg_pelecard';
        $this->gateway_method_title = 'Pelecard';
        $this->gateway_method_description = 'Pelecard';

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

            // Todo Make Call Back To Pelecard With Params To Receive IFrame URL

            // $params = $this->params($order);
            // $response = woocommerce_il_pgateways()->gateway_request($this->config['gateway_url'] . 'PaymentGW/init', 'post', $params);
            // $response = json_decode($response, true);

            // if (!isset($response['URL']) || !isset($response['Error']['ErrCode'])) {
            //     throw new Exception(__('', ''));
            // }

            // if ($response['Error']['ErrCode'] != 0) {
            //     throw new Exception(__('Something Went Wrong, Please Try Again', ''));
            // }

            // Continue To Receipt Page After Store In Session The IFrame URL
            // WC()->session->get('iframe_url', $response['URL']);

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
        $order_payment_url = WC()->session->get('iframe_url');

        echo wc_ilpg_iframe($order_payment_url);
    }

    public function gateway_response()
    {
        $params = json_decode(file_get_contents("php://input"), true);

        if (!is_array($params) || empty($params))
            return;

        $order_id = isset($params['AdditionalDetailsParamX']) ? absint($params['AdditionalDetailsParamX']) : absint($params['ParamX']);
        // OR
        $order_id = $params['ResultData']['TransactionId'];

        $order = wc_get_order($order_id);
        $note = "(PaymentID: {$_GET['Id']})";

        $validation_params = [
            'ConfirmationKey' => $params['ConfirmationKey'],
            'TotalX100' => $params['DebitTotal'],
            'UniqueKey' => $order->get_order_key()
        ];

        // Validate Response
        $is_valid = woocommerce_il_pgateways()->gateway_request($this->config['gateway_url'] . 'PaymentGW/ValidateByUniqueKey', 'post', $validation_params);

        if (!$is_valid)
            return;

        WC()->session->set('iframe_url', '');

        if ((string) $params['StatusCode'] == '000') {
            $this->complete_order($order, $note);
            $redirect_url = $this->get_return_url($order);
        }

        $this->order_failed($order, $note);
        $redirect_url = wc_get_checkout_url();
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    public function params($order)
    {
        return [
            'terminal' => $this->settings['terminal'],
            'user' => $this->settings['username'],
            'password' => $this->settings['password'],
            'GoodURL' => '', // $this->get_return_url( $order ),
            'ErrorURL' => '', // $this->get_return_url( $order ),
            'CancelURL' => '', // 'yes' === $this->cancelbutton ? $order->get_cancel_order_url() : '',
            'ActionType' => 'J4',
            'Currency' => $this->currency,
            'Total' => $order->get_total() * 100,
            'FreeTotal' => $this->freetotal,
            'resultDataKeyName' => null,
            'ServerSideGoodFeedbackURL' => WC()->api_request_url( "{$this}" ),
            'ServerSideErrorFeedbackURL' => WC()->api_request_url( "{$this}" ),
            'NotificationGoodMail' => null,
            'NotificationErrorMail' => null,
            'NotificationFailMail' => null,
            'CreateToken' => WC()->session->save_payment_method,
            'TokenForTerminal' => null,
            'Language' => $this->language,
            'Track2Swipe' => false,
            'TokenCreditCardDigits' => null,
            'CardHolderName' => $this->cardholdername,
            'CustomerIdField' => $this->customeridfield,
            'Cvv2Field' => $this->cvvfield,
            'EmailField' => 'value' === $this->emailfield ? ( is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : $order->billing_email ) : $this->emailfield,
            'TelField' => 'value' === $this->telfield ? ( is_callable( array( $order, 'get_billing_phone' ) ) ? $order->get_billing_phone() : $order->billing_phone ) : $this->telfield,
            'SplitCCNumber' => $this->splitccnumber,
            'FeedbackOnTop' => true,
            'FeedbackDataTransferMethod' => 'POST',
            'UseBuildInFeedbackPage' => false,
            'MaxPayments' => $this->get_range( $order->get_total(), 'max', $this->maxpayments ),
            'MinPayments' => $this->get_range( $order->get_total(), 'min', $this->minpayments ),
            'MinPaymentsForCredit' => $this->mincredit,
            'DisabledPaymentNumbers' => null,
            'FirstPayment' => $this->firstpayment,
            'AuthNum' => null,
            'ShopNo' => '001',
            'ParamX' => $order->get_id(),
            'ShowXParam' => false,
            'AddHolderNameToXParam' => false,
            'UserKey' => $order->get_order_key(),
            'SetFocus' => $this->setfocus,
            'CssURL' => $this->cssurl,
            'TopText' => $this->toptext,
            'BottomText' => $this->bottomtext,
            'LogoURL' => $this->logourl,
            'ShowConfirmationCheckbox' => $this->confirmationcb,
            'TextOnConfirmationBox' => $this->confirmationtext,
            'ConfirmationLink' => $this->confirmationurl,
            'HiddenPelecardLogo' => $this->hiddenpelecard,
            'AllowedBINs' => null,
            'BlockedBINs' => null,
            'ShowSubmitButton' => true,
            'SupportedCards' => $this->supportedcards,
            'AccessibilityMode' => false,
            'CustomCanRetrayErrors' => []
        ];
    }
}