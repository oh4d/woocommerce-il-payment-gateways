<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_ILPG_Tranzila extends WC_IL_PGateways
{
    /**
     * WC_ILPG_Tranzila constructor.
     */
    public function __construct()
    {
        $this->has_fields = true;
        $this->id = 'ilpg_tranzila';
        $this->gateway_method_title = 'Tranzila';
        $this->gateway_method_description = 'Tranzila';
        $this->icon = 'http://www.tranzila.com/images/tranzila-wallet.gif';
        $this->order_button_text  = __('Continue to payment', 'woocommerce-il-payment-gateways');

        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        parent::__construct();
    }

    /**
     * Initialize Actions
     */
    public function init_actions()
    {
        // IFrame Flow
        if ($this->settings['iframe'] === 'yes') {
            add_action("woocommerce_api_wc_gateway_{$this->id}", array($this, 'gateway_response'));

            // Notify URL
            if ($this->notify_url_check()) {
                add_action("woocommerce_api_wc_gateway_{$this->id}_webhook", array($this, 'gateway_webhook'));
            }

            add_action("woocommerce_receipt_{$this->id}", array($this, 'receipt_page'));
            add_action("woocommerce_api_wc_iframe_{$this->id}", array($this, 'iframe_form'));
        } else {
            // For API Flow
            add_action( 'woocommerce_credit_card_form_start', array( $this, 'add_card_id_field' ) );
        }
    }

    public function payment_scripts()
    {
        wp_register_script( 'wc-il-pgateways-tranzila-pending', woocommerce_il_pgateways()->plugin_url . 'assets/js/wc-il-pgateways-tranzila-pending.js', array('jquery', 'jquery-blockui'), false, true );
        wp_localize_script('wc-il-pgateways-tranzila-pending', 'base', ['url' => WC()->api_request_url('')]);
    }

    /**
     * Only Case Of API Flow
     * Add Credit Card Form Into The Checkout Form
     */
    public function payment_fields()
    {
        parent::payment_fields();

        if ($this->settings['iframe'] !== 'yes') {
            $cc = new WC_Payment_Gateway_CC();
            $cc->id = $this->id;

            $cc->form();
        }
    }

    /**
     * Adding Card Holder ID
     * To Credit Card Form
     *
     * @param $id
     */
    public function add_card_id_field($id)
    {
        echo '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-id">' . esc_html__( 'Card Holder ID', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-id" name="' . esc_attr( $this->id ) . '-card-id" class="input-text wc-credit-card-form-card-id" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'ID', 'woocommerce' ) . '"/>
			</p>';
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

        try {
            $this->validate_credentials();

            return $this->process_payment_by_transaction_flow($order);

        } catch (\Exception $e) {
            wc_add_notice( $e->getMessage(), 'error');

            return array();
        }
    }

    /**
     * Continue Payment, Checking The Selected Flow
     * IFrame, Will Redirect To Checkout Page There Will Display The IFrame
     * API, Will Continue To Make POST Request To Tranzila Servers
     *
     * @param WC_Order $order
     * @return array
     */
    protected function process_payment_by_transaction_flow($order)
    {
        if (isset($this->settings['iframe']) && $this->settings['iframe'] === 'yes') {
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        // Continue To API Call
        try {
            $transaction = $this->create_transaction($order);

            $this->complete_order($order, '', $transaction['reference']);

            // Thank You Page
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } catch (\Exception $exception) {
            wc_add_notice( __('Payment error: ', 'woothemes') . $exception->getMessage(), 'error' );
            return array(
                'result' => 'error',
            );
        }
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
        if (!isset($this->settings['iframe']) || $this->settings['iframe'] == 'no')
            return;

        $order = wc_get_order($order_id);

        if ($this->notify_url_check()) {
            wp_enqueue_script('wc-il-pgateways-tranzila-pending');
        }

        echo '<iframe src="' . WC()->api_request_url("WC_IFrame_{$this->id}") .'?order_key='.$order->get_order_key() . '"
                    id="chekout_frame" class="ilpg_chekout_frame" name="chekout_frame"
                    style="border:none;'.$this->iframe_style().'" scrolling="no"></iframe>';

        if ($this->handshake_feature_check())
            echo $this->get_front_blockui_onload();
    }

    /**
     * @return string
     */
    protected function iframe_style()
    {
        $style = '';

        if (isset($this->settings['iframe_width']) && $this->settings['iframe_width'])
            $style = 'width:'.$this->settings['iframe_width'].'px;';

        if (isset($this->settings['iframe_height']) && $this->settings['iframe_height'])
            $style .= 'height:'.$this->settings['iframe_height'].'px;';

        return $style;
    }

    /**
     * Display Custom Route
     * For IFrame Communication Between Gateway
     * To Achieve Little Beat More Secure Flow
     *
     * But Really Its Just Hide The Problem...
     * @throws \Exception
     */
    public function iframe_form()
    {
        global $woocommerce;
        $order_id = wc_get_order_id_by_order_key($_GET['order_key']);
        $order = wc_get_order($order_id);

        if (!$order) {
            $home_url = get_home_url();
            $this->log('Cant Find Related Order To Order Key');
            echo '<script>window.parent.location.href = "'+$home_url+'"</script>';
            return;
        }

        // Handshake Is Enable
        if ($this->handshake_feature_check()) {
            try {
                $thtk = $this->create_handshake_request($order);
                update_post_meta($order->get_id(), "{$this->id}_handshake", $thtk);

            } catch (\Exception $exception) {
                wc_add_notice( __('Payment error: ', 'woocommerce-il-payment-gateways') . $exception->getMessage(), 'error' );

                // Redirect to Checkout Page
                $redirect_url = $order->get_checkout_payment_url();
                echo '<script>window.parent.location.href = "' . $redirect_url . '";</script>';
                return;
            }
        }

        echo $this->make_form_view($order, isset($thtk) ? $thtk : false);
        echo $this->get_front_blockui_onload(false, 'document.forms["'.$this->id.'"].submit()');
        return;
    }

    /**
     * Make Form HTML Output
     * To Be Auto Submitted From JavaScript
     *
     * Pretty Much Security Alert
     * There Is Not Better Solution For
     * IFrame Payment Flow As I Can See For Now
     *
     * @param WC_Order $order
     * @param array|false $thtk
     * @return string
     */
    protected function make_form_view($order, $thtk)
    {
        $params = $this->iframe_params($order);

        if ($thtk)
            $params = array_merge($params, ['thtk' => $thtk]);

        $fields = '';
        foreach ($params as $key => $value) {
            $fields .= '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'"/>';
        }

        return '<form action="' . $this->config['gateway_direct_payment_url'] . $this->settings['terminal'] . '/iframe.php" method="post" name="'.$this->id.'">'.$fields.'</form>';
    }

    /**
     * @throws Exception
     */
    public function validate_credentials()
    {
        if (!$this->settings['terminal'])
            throw new \Exception(__('Cant Process Payment, Content Site Manager', 'woocommerce-il-payment-gateways'));
    }

    /**
     * Validate frontend fields.
     * Validate payment fields on the frontend.
     *
     * @return bool
     */
    public function validate_fields()
    {
        $errors = new WP_Error();

        if (!isset($this->settings['iframe']) || $this->settings['iframe'] == 'no')
            $this->validate_credit_card($errors, [
                "{$this->id}-card-id" => [
                    'name' => 'chid',
                    'required' => 1,
                    'label' => __('Card Holder ID', '')
                ],
            ]);

        return (count($errors->get_error_messages())) ? false : true;
    }

    /**
     * Return Repeated Params
     *
     * @param WC_Order $order
     * @return array
     */
    protected function global_params($order)
    {
        return array(
            'cred_type' => 8, // 1 = Regular credit, 3 - Direct credit, 8 - Payments credit
            'tranmode' => 'A',  // Mode for verify transaction, / VK
            'sum' => (string) $order->get_total(),
            'currency' => $this->gateway_currency(),
            // 'npay' => 11,
            // 'fpay' => $this->getFirstPaymentSum(),
        );
    }

    /**
     * @Only case of API Request
     */
    protected function getFirstPaymentSum()
    {

    }

    /**
     * @Only case of API Request
     */
    protected function calcPaymentPerMonth()
    {

    }

    /**
     * Make IFrame Request Params
     *
     * @param WC_Order $order
     * @return array
     */
    protected function iframe_params($order)
    {
        return array_merge($this->global_params($order), array(
            'company' => $order->get_billing_company(),
            'contact' => $order->get_formatted_billing_full_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'address' => $order->get_billing_address_1() . " " . $order->get_billing_address_2() ,
            'city' => $order->get_billing_city(),
            'order' => $order->get_id(),
            'IMaam' => 0.17,
            'nologo' => '1',
            'lang' => $this->get_settings_lang_param(),
            'remarks' => $order->get_customer_note(),
            'key' => $order->get_order_key(),
            'pdesc' => urlencode($this->transform_order_info($order)),
            'ppnewwin' => 'no', // paypal button
            'trBgColor' => (isset($this->settings['iframe_bg_color'])) ? str_replace('#', '', $this->settings['iframe_bg_color']) : '',
            'trTextColor' => (isset($this->settings['iframe_text_color'])) ? str_replace('#', '', $this->settings['iframe_text_color']) : '',
            'trButtonColor' => (isset($this->settings['iframe_button_bg'])) ? str_replace('#', '', $this->settings['iframe_button_bg']) : '',
            'buttonLabel' => (isset($this->settings['iframe_submit']) && $this->settings['iframe_submit']) ? $this->settings['iframe_submit'] : __('Process', 'woocommerce-il-payment-gateways'),
            'maxpay' => 10 // TODO Add Settings Select Box For Number OF Payments
            // 'opensum' => 1, // allow kind of donation, customer select the price
            // 'u71' => '1',
            // 'orderid' => '511',
            // 'orderurl' => 'gotopay.co.il',
            // 'orderid' => $order->get_id(),
        ));
    }

    /**
     * Make API Request Params
     *
     * @param WC_Order $order
     * @return array
     */
    protected function api_params($order)
    {
        return array_merge($this->global_params($order), array(
            'supplier' => $this->settings['terminal'],
            'ccno' => $this->card_fields['number'],
            'expdate' => $this->card_fields['exp']->format('m') . $this->card_fields['exp']->format('y'), // mmyy
            'mycvv' => $this->card_fields['cvv'],
            'myid' => $this->card_fields['chid'],
            // 'TranzilaTK' => '',
        ));
    }

    /**
     * Make Handshake Request Params
     *
     * @param WC_Order $order
     * @return array
     */
    protected function handshake_params($order)
    {
        return array(
            'supplier' => $this->settings['terminal'],
            'sum' => (string) $order->get_total(),
            'currency' => $this->gateway_currency(),
            'op' => 1,
            'IMaam' => 0.17,
            'order' => $order->get_id(),
            'TranzilaPW' => $this->settings['tranzila_pw']
        );
    }

    /**
     * Gateway Response From IFrame Flow
     *
     * @url SITE_URL/wc-api/WC_Gateway_ilpg_tranzila/
     * @return void
     */
    public function gateway_response()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'pending_request') {
            return $this->pending_webhook_request();
        }

        if (!isset($_REQUEST['order']) || !$_REQUEST['order']) {
            wp_redirect(home_url('/'));
        }

        $order_id = $_REQUEST['order'];
        $order = wc_get_order($order_id);

        if (!$order) {
            $redirect_url = home_url('/');
            echo '<script>window.top.location.href = "' . $redirect_url . '";</script>';
            return;
        }

        $status = $order->get_status();
        $this->log(['iFrame Flow.', $_REQUEST['Response'], get_request_ip(), $_REQUEST, $status]);

        // In Case Of Not True Error Code Can Safety Redirect (No Need To Check\Wait With Notify URL)
        if (!isset($_REQUEST['Response']) || $_REQUEST['Response'] != '000') {
            $response_code = $this->get_code_response_message(isset($_REQUEST['Response']) ? $_REQUEST['Response'] : null);

            // Store The Error Message In Session
            wc_add_notice($response_code, 'error' );

            // Redirect to Checkout Page
            $redirect_url = $order->get_checkout_payment_url(true);
            echo '<script>window.top.location.href = "' . $redirect_url . '";</script>';
            return;
        }

        // Check If Notify URL Is Disable
        if (!$this->notify_url_check()) {

            if ($this->validate_response_params($order, $_REQUEST)) {
                $this->complete_order($order, '', $this->output_transaction_reference($_REQUEST));
                // Thank You Page
                $redirect_url = $this->get_return_url($order);
            } else {
                wc_add_notice(__('Something Went Wrong, Contact Shop Manager', 'woocommerce-il-payment-gateways'), 'error' );
                $redirect_url = $order->get_checkout_payment_url();
            }

            echo '<script>window.top.location.href = "' . $redirect_url . '";</script>';
            return;
        }

        // Case Webhook Already Entered
        if ($status === 'processing') {
            $redirect_url = $this->get_return_url($order);
            echo '<script>window.top.location.href = "' . $redirect_url . '";</script>';
            return;
        }

        // Case Of Delay With The Notify URL, Wait 10sec with 2 ajax requests and check the Status
        WC()->session->set("{$this->id}_pending_webhook", ['order_id' => $order_id, 'tries' => 0]);
        echo '<script>window.parent.pendingResponse.init()</script>';
    }

    /**
     * Notify From Tranzila Servers Act As WebHook Check
     *
     * @url http://SITE_URL/wc-api/WC_Gateway_ilpg_tranzila_webhook/
     */
    public function gateway_webhook()
    {
        $this->log(['iFrame Flow. Webhook', get_request_ip(), $_REQUEST]);

        $request_ip = get_request_ip();

        if (isset($this->settings['notify_trusted_ip']) && $this->settings['notify_trusted_ip'] &&
            $this->settings['notify_trusted_ip'] != $request_ip) {
            exit;
        }

        if (!isset($_REQUEST['Response']) || $_REQUEST['Response'] != '000') {
            exit;
        }

        $order = wc_get_order(isset($_REQUEST['order']) ? $_REQUEST['order'] : false);

        if (!$order) {
            exit;
        }

        if (!$this->validate_response_params($order, $_REQUEST))
            exit;

        $this->complete_order($order, '(Notify URL)', $this->output_transaction_reference($_REQUEST));
        echo 'ok';
        exit;
    }

    /**
     * Case Notify URL Enter After The Client Was Redirected
     * Client Will Make Ajax Request To Check The Status Of The Order
     * The Order ID Is Store In His Session Along With The Number Of Attempts
     *
     * @return mixed
     */
    protected function pending_webhook_request()
    {
        $pending_data = WC()->session->get("{$this->id}_pending_webhook", false);

        if (!$pending_data) {
            echo json_encode(['result' => 'failure', 'redirect' => get_home_url()]);
            exit;
        }

        $order = wc_get_order(isset($pending_data['order_id']) ? $pending_data['order_id'] : false);

        if (!$order) {
            echo json_encode(['result' => 'failure', 'redirect' => get_home_url()]);
            exit;
        }

        $status = $order->get_status();

        // Case Payment Successfully Completed
        if ($status === 'processing') {
            WC()->session->set("{$this->id}_pending_webhook", false);
            echo json_encode(['result' => 'success', 'redirect' => $this->get_return_url($order)]);
            exit;
        }

        echo json_encode(['result' => '', 'redirect' => $order->get_checkout_payment_url()]);

        if (isset($pending_data['tries']) && $pending_data['tries'] == 2){
            WC()->session->set("{$this->id}_pending_webhook", false);
        } else {
            $pending_data['tries'] = (isset($pending_data['tries'])) ? $pending_data['tries'] + 1 : 1;
            WC()->session->set("{$this->id}_pending_webhook", $pending_data);
        }

        exit;
    }

    /**
     * Validate Response From Tranzila Redirect / Notify URL
     * With Handshake Check Case Enabled
     *
     * @param WC_Order $order
     * @param array $request
     * @return bool
     */
    protected function validate_response_params($order, $request)
    {
        if ($this->handshake_feature_check()) {
            // Check Handshake
            if (!$this->check_handshake_response($order, $request)) {
                $this->log(['Handshake Validation', $_POST]);
                return false;
            }
        }

        $validate = [
            'sum' => (string) $order->get_total(),
            'currency' => $this->gateway_currency(),
            'IMaam' => 0.17
        ];

        foreach ($validate as $param_name => $param_original_value) {
            $param = isset($request[$param_name]) ? $request[$param_name] : 0;

            if ($param_original_value != $param) {
                $this->log(['Validation Field Error' => $param_name, $request]);
                return false;
            }
        }

        return true;
    }

    /**
     * Compare Handshake With The User's Session Handshake
     *
     * @param WC_Order $order
     * @param array $request
     * @return bool
     */
    protected function check_handshake_response($order, $request)
    {
        if (!isset($request['thtk']) || !$request['thtk'])
            return false;

        $order_id = $order->get_id();
        $response_thtk = $request['thtk'];

        $thtk = get_post_meta($order_id, "{$this->id}_handshake", true);

        if (!$thtk || $response_thtk != $thtk)
            return false;

        return true;
    }

    /**
     * Create Handshake Request Before Continue To Payment
     * Will Get Token For Verification In The Response (Webhook \ Regular)
     * Remember, Cant Trust This Token... Since It Is Given For The User In Input Hidden
     * But Will Check Anyway For Conformation.
     *
     * IMPORTANT !
     * If This Option Is Enable Client Will Have To Enable In Tranzila Settings
     * Otherwise The All Flow Will Break And The User Wont Be Able To Pay, Or Worse
     * He Did Pay But The Handshake Test Is Enable So It Will Return Failure Response
     *
     * @param $order
     * @throws Exception
     * @return string
     */
    protected function create_handshake_request($order)
    {
        $url = $this->config['gateway_url'] . $this->config['gateway_url_prefix_71dt'];

        $params = $this->handshake_params($order);

        try {
            $response = $this->send_api_request($url, $params);
            $this->log(['Handshake Response', $response, get_request_ip(), $_POST]);

        } catch (\Exception $exception) {
            throw new \Exception(__('Error Has Occurs'));
        }

        if (!isset($response['thtk']) || !$response['thtk']) {
            throw new \Exception(__('Error Has Occurs, Contact Shop Manager'));
        }

        return $response['thtk'];
    }

    /**
     * @param WC_Order $order
     * @throws Exception
     * @return array
     */
    public function create_transaction($order)
    {
        $url = $this->config['gateway_url'] . $this->config['gateway_url_prefix_71u'];

        $errors = new WP_Error();
        $this->is_card_fields_valid($errors);

        if (count($errors->get_error_messages())) {
            foreach ($errors->get_error_messages() as $error_message) {
                wc_add_notice($error_message,  'error');
            }

            throw new \Exception(__('Unable To Process Payment'));
        }

        // Continue To Send API Call
        $params = $this->api_params($order);
        // $response = woocommerce_il_pgateways()->gateway_request($url, 'post', $params);

        try {
            $response = $this->send_api_request($url, $params);
            $this->log(json_encode(['API Flow. Response', $response, get_request_ip()]));

        } catch (\Exception $exception) {
            throw new \Exception(__('Error Has Occurs'));
        }

        if (!isset($response['Response'])) {
            throw new \Exception(__('Error Has Occurs'));
        }

        if ((string) $response['Response'] !== '000') {
            throw new \Exception($this->get_code_response_message($response['Response']));
        }

        return ['success' => true, 'reference' => $this->output_transaction_reference($response)];
    }

    /**
     * Get Response Code Message
     *
     * @param string|null $response_code
     * @return string
     */
    public function get_code_response_message($response_code = null)
    {
        return ($response_code && $this->config['status_codes'][$response_code]) ?
            $this->config['status_codes'][$response_code] : __('Transaction failed', 'woocommerce-il-payment-gateways');
    }

    /**
     * Get IFrame Language From Select Setting
     * Default Is AUTO
     *
     * @return mixed
     */
    public function get_settings_lang_param()
    {
        $locale = (!isset($this->settings['iframe_lang']) || $this->settings['iframe_lang'] === 'auto') ?
            get_locale() : $this->settings['iframe_lang'];

        return isset($this->config['languages'][$locale]) ?
            $this->config['languages'][$locale] : $this->config['languages']['default'];
    }

    /**
     * @param $url
     * @param $params
     * @return array
     * @throws Exception
     */
    protected function send_api_request($url, $params)
    {
        $params_string = '';

        foreach ($params as $name => $value) {
            $params_string .= $name . '=' . $value . '&';
        }

        $params_string = substr($params_string, 0, -1); // Remove trailing '&'

        $cr = curl_init();
        curl_setopt($cr, CURLOPT_URL, $url);
        curl_setopt($cr, CURLOPT_POST, 1);
        curl_setopt($cr, CURLOPT_FAILONERROR, true);
        curl_setopt($cr, CURLOPT_POSTFIELDS, $params_string);
        curl_setopt($cr, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cr, CURLOPT_SSL_VERIFYPEER, 0);

        // Execute request
        $result = curl_exec($cr);
        $error = curl_error($cr);

        if (!empty($error)) {
            throw new \Exception($error);
        }

        curl_close($cr);

        $response = [];
        $response_array = explode('&', $result);

        if (!$response_array)
            throw new \Exception(__('Something Went Wrong, Contact Shop Manager', 'woocommerce-il-payment-gateways'));

        if (!count($response_array))
            return $response;

        foreach ($response_array as $value) {
            $tmp = explode('=', $value);

            if (count($tmp) > 1) {
                $response[$tmp[0]] = $tmp[1];
            }
        }

        return $response;
    }

    /**
     * Get Transaction Reference Keys Output Inline For Completed Orders Comment
     *
     * @param $response
     * @return string
     */
    protected function output_transaction_reference($response)
    {
        $reference = '';

        if (isset($response['ConfirmationCode']))
            $reference = 'ConfirmationCode: ' . $response['ConfirmationCode'] . ' ';

        if (isset($response['Refnr']))
            $reference .= 'Ref.nr: ' . $response['Refnr'] . ' ';

        if (isset($response['Tempref']))
            $reference .= 'Temp.ref: ' . $response['Tempref'];

        return $reference;
    }

    /**
     *
     * @throws Exception
     */
    public function create_token()
    {
        $url = $this->config['gateway_url'] . $this->config['gateway_url_prefix_71u'];
        $params = [
            'supplier' => $this->settings['terminal'],
            'TranzilaPW' => $this->settings['terminal'],
            'ccno' => '',
        ];
        $response = woocommerce_il_pgateways()->gateway_request($url, 'post', $params);
        $response = json_decode($response, true);

        if (!isset($response['TranzilaTK'])) {
            throw new \Exception(__('Error Has Occurs'));
        }

        $transaction_token = $response['TranzilaTK'];
    }

    /**
     * Check If Notify URL Feature Is Enable
     *
     * @return boolean
     */
    public function notify_url_check()
    {
        return isset($this->settings['iframe_notify_url']) && $this->settings['iframe_notify_url'] == 'yes';
    }

    /**
     * Check If Handshake Feature Is Enable
     *
     * @return boolean
     */
    public function handshake_feature_check()
    {
        return isset($this->settings['iframe_handshake']) && $this->settings['iframe_handshake'] == 'yes';
    }
}
