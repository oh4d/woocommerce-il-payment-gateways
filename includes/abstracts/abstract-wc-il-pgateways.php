<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

abstract class WC_IL_PGateways extends WC_Payment_Gateway
{
    public $config;

    public $locale;

    public $currency;

    public $gateway_method_title;

    public $gateway_method_description;

    protected $card_fields;

    public function __construct()
    {
        $this->method_title = __("IL Payment Gateways {$this->gateway_method_title}");
        $this->method_description = __("IL Payment Gateways {$this->gateway_method_description}");
        $this->order_button_text  = ($this->order_button_text) ? $this->order_button_text : __("Continue to payment", "woocommerce-il-payment-gateways");

        $this->init_settings();

        $this->title = $this->get_option('title', "{$this->gateway_method_title}");
        $this->description = $this->get_option('description', "Pay via {$this->gateway_method_title}");

        $payment_icon = $this->get_option("payment_logo", false);
        $payment_icon = ($payment_icon) ? wp_get_attachment_image_url( $payment_icon, 'thumbnail' ) : false;
        $this->icon = ($payment_icon) ?: $this->icon;

        add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));
        $this->init_actions();

        if (!is_admin()) {
        } else {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $gateway_settings = str_replace('_', '-', $this->id);

        $this->form_fields = array_merge(include(woocommerce_il_pgateways()->includes_path . "/gateways/settings/settings-il-pgateways.php"),
            include(woocommerce_il_pgateways()->includes_path . "/gateways/settings/settings-{$gateway_settings}.php"));
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_conf()
    {
        $gateway_settings = str_replace('_', '-', $this->id);

        $this->config = include(woocommerce_il_pgateways()->includes_path . "/gateways/config/config-{$gateway_settings}.php");
    }

    /**
     * Enqueues Admin Scripts And Styles
     */
    public function enqueue_scripts()
    {
        wp_enqueue_media();
        wp_enqueue_script( 'wc-il-pgateways-settings', woocommerce_il_pgateways()->plugin_url . '/assets/js/wc-il-pgateways-settings.js', array( 'jquery' ), false, true );
    }

    /**
     * Process Payment
     * return the success and redirect in an array. e.g:
     *        return array(
     *            'result'   => 'success',
     *            'redirect' => $this->get_return_url($order)
     *        );
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Thank You Page
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    public function payment_fields()
    {
        parent::payment_fields();
    }

    /**
     * Init Settings For Gateway.
     */
    public function init_settings()
    {
        $this->init_form_fields();

        parent::init_settings();

        $this->init_conf();

        $this->locale = get_locale();
        $this->currency = get_woocommerce_currency();
    }

    /**
     * Initialize Actions
     */
    public function init_actions() {}

    /**
     * Gateway Response
     *
     * @url SITE_URL/wc-api/WC_Gateway_{$this->id}
     */
    public function gateway_response() {}

    /**
     * @return bool|void
     */
    public function process_admin_options()
    {
        parent::process_admin_options();
    }

    /**
     * @param WC_Order $order
     * @param string $note
     * @param string $reference
     */
    public function complete_order($order, $note = '', $reference = '')
    {
        $order->payment_complete($reference);

        $order->add_order_note(sprintf(__("{$this->title}, Order Successfully Paid %s"), $note));

        WC()->cart->empty_cart();

        do_action("wc_{$this->id}_payment_completed", $order);
    }

    /**
     * @param WC_Order $order
     * @param string $note
     */
    public function order_failed($order, $note = '')
    {
        $order->update_status('on-hold', __("{$this->title}, Payment Failed"));

        $order->add_order_note('on-hold', sprintf(__("{$this->title}, Payment Failed %s"), $note));
    }

    /**
     *
     */
    protected function validate_credentials() {}

    /**
     * Get Locale For Yaad Pay IFrame Request
     *
     * @return string
     */
    protected function preview_locale()
    {
        return (isset($this->config['languages'][$this->locale]))
            ? $this->config['languages'][$this->locale] : $this->config['languages']['default'];
    }

    /**
     * Get Currency For Yaad Pay IFrame Request
     *
     * @return string
     */
    protected function gateway_currency()
    {
        return (isset($this->config['currencies'][$this->currency]))
            ? $this->config['currencies'][$this->currency] : $this->config['currencies']['default'];
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        woocommerce_il_pgateways()->log->add("wc_{$this->id}", is_string($message) ? $message : json_encode($message), WC_Log_Levels::ALERT);
    }

    /**
     * Create Order Details Lined To Send Tranzila
     *
     * @param WC_Order $order
     * @return string
     */
    public function transform_order_info($order)
    {
        $items = $order->get_items();

        if (!count($items))
            return '';

        $p_desk = '';
        foreach ($items as $item) {
            if (!$item->get_quantity())
                return '';

            $p_desk .= $item->get_quantity() . ' ' . $item->get_name() . ' ' . $item->get_total() . get_woocommerce_currency() . " \n";
        }

        return $p_desk;
    }

    /**
     * @param WP_Error $errors
     * @param array $custom_card_fields
     */
    public function validate_credit_card(&$errors, $custom_card_fields = [])
    {
        $card_fields = [
            "{$this->id}-card-number" => [
                'name' => 'number',
                'required' => 1,
                'label' => __('Number', '')
            ],
            "{$this->id}-card-expiry" => [
                'name' => 'exp',
                'required' => 1,
                'label' => __('Expiry Date', '')
            ],
            "{$this->id}-card-cvc" => [
                'name' => 'cvv',
                'required' => 1,
                'label' => __('CVC', '')
            ],
        ];

        foreach (array_merge($custom_card_fields, $card_fields) as $field => $card_field) {
            if (!isset($_POST[$field]) && !$card_field['required'])
                continue;

            if (!isset($_POST[$field])) {
                $message = sprintf(__('Credit Card %s is a required field.', 'woocommerce'), '<strong>' . esc_html( $card_field['label'] ) . '</strong>');
                wc_add_notice($message,  'error');
                $errors->add( 'required-field', $message);
                continue;
            }

            $this->card_fields[$card_field['name']] = $_POST[$field];

            if (!$card_field['required'] || !empty($_POST[$field]))
                continue;

            $message = sprintf(__('Credit Card %s is a required field.', 'woocommerce-il-payment-gateways'), '<strong>' . esc_html( $card_field['label'] ) . '</strong>');
            wc_add_notice($message,  'error');
            $errors->add( 'required-field', $message);
        }
    }

    /**
     * @param WP_Error $errors
     */
    public function is_card_fields_valid(&$errors)
    {
        // Check General Array
        if (!$this->card_fields) {
            $message = __('Credit Card %s is a required field.', 'woocommerce-il-payment-gateways');
            $errors->add( 'required-field', $message);
            return;
        }

        // Check Card Holder ID Number
        if (strlen((int) $this->card_fields['chid']) < 9) {
            $message = __('Credit Card Holder ID is invalid.', 'woocommerce-il-payment-gateways');
            $errors->add( 'invalid-field', $message);
        }

        // Clean Card Number
        $this->card_fields['number'] = str_replace(array(' ', '-'), '', $this->card_fields['number']);

        // Check Card Number
        if (!luhn_check($this->card_fields['number'])) {
            $message = __('Credit Card Number is invalid.', 'woocommerce-il-payment-gateways');
            $errors->add( 'invalid-field', $message);
        }

        // Make Card Exp Date As Date Time Object
        $this->card_fields['exp'] = explode('/', str_replace(' ', '', $this->card_fields['exp']));
        $year_prefix = substr(date("Y"), 0, 2);

        // Check If Need To Add Prefix (Exp 20) For Given Year
        if (strlen($this->card_fields['exp'][1]) == 2)
            $this->card_fields['exp'][1] = $year_prefix . $this->card_fields['exp'][1];

        $this->card_fields['exp'] = date_create("{$this->card_fields['exp'][1]}-{$this->card_fields['exp'][0]}-1");

        // Check Card Exp Date
        if($this->card_fields['exp'] < new DateTime()) {
            $message = __('Credit Card Exp Date is invalid.', 'woocommerce-il-payment-gateways');
            $errors->add( 'invalid-field', $message);
        }
    }

    /**
     * Generate Image HTML.
     * Copyright To WooCommerce PayPal Express Checkout Gateway
     *
     * @param  mixed $key
     * @param  mixed $data
     * @since  1.5.0
     * @return string
     */
    public function generate_image_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data  = wp_parse_args( $data, $defaults );
        $value = $this->get_option( $key );

        // Hide show add remove buttons.
        $maybe_hide_add_style    = '';
        $maybe_hide_remove_style = '';

        // For backwards compatibility (customers that already have set a url)
        $value_is_url            = filter_var( $value, FILTER_VALIDATE_URL ) !== false;

        if ( empty( $value ) || $value_is_url ) {
            $maybe_hide_remove_style = 'display: none;';
        } else {
            $maybe_hide_add_style = 'display: none;';
        }

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $this->get_tooltip_html( $data ); ?>
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>

            <td class="image-component-wrapper">
                <div class="image-preview-wrapper">
                    <?php
                    if ( ! $value_is_url ) {
                        echo wp_get_attachment_image( $value, 'thumbnail' );
                    } else {
                        echo sprintf( __( 'Already using URL as image: %s', 'woocommerce-il-payment-gateways' ), $value );
                    }
                    ?>
                </div>

                <button
                    class="button ilpg_image_upload"
                    data-field-id="<?php echo esc_attr( $field_key ); ?>"
                    data-media-frame-title="<?php echo esc_attr( __( 'Select a image to upload', 'woocommerce-il-payment-gateways' ) ); ?>"
                    data-media-frame-button="<?php echo esc_attr( __( 'Use this image', 'woocommerce-il-payment-gateways' ) ); ?>"
                    data-add-image-text="<?php echo esc_attr( __( 'Add image', 'woocommerce-il-payment-gateways' ) ); ?>"
                    style="<?php echo esc_attr( $maybe_hide_add_style ); ?>"
                >
                    <?php echo esc_html__( 'Add image', 'woocommerce-il-payment-gateways' ); ?>
                </button>

                <button
                    class="button ilpg_image_remove"
                    data-field-id="<?php echo esc_attr( $field_key ); ?>"
                    style="<?php echo esc_attr( $maybe_hide_remove_style ); ?>"
                >
                    <?php echo esc_html__( 'Remove image', 'woocommerce-il-payment-gateways' ); ?>
                </button>

                <input type="hidden"
                       name="<?php echo esc_attr( $field_key ); ?>"
                       id="<?php echo esc_attr( $field_key ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                />
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }
}
