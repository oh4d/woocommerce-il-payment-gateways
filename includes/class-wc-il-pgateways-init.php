<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_IL_PGateways_Init
{
    /**
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var string
     */
    public $plugin_path;

    /**
     * @var string
     */
    public $plugin_url;

    /**
     * @var string
     */
    public $includes_path;

    /**
     * @var string
     */
    public $includes_url;

    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    public $bootstrap_warning_message;

    /**
     * @var WC_IL_PGateways_Loader
     */
    public $gateways_loader;

    /**
     * @var WC_Logger
     */
    public $log;

    /**
     * WC_IL_Payments_Init constructor.
     *
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;

        $this->plugin_path = plugin_dir_path($this->file);
        $this->includes_path = plugin_dir_path($this->file) . 'includes';

        $this->plugin_url = plugin_dir_url($this->file);
        $this->includes_url = plugin_dir_url($this->file) . 'includes/';

        $this->define_constants();
    }

    /**
     * Load The Plugin
     *
     * @return void
     */
    public function bootstrap()
    {
        try
        {
            $this->dependencies();
            $this->includes();

            if (is_admin()) {
                $this->admin();
                // add_action('init', [$this, 'admin']);
            }
        }
        catch (\Exception $e)
        {
            $this->bootstrap_warning_message = $e->getMessage();

            add_action('admin_notices', array($this, 'bootstrap_warning'));
        }
    }

    /**
     * Adding The Plugin Loaded Hook
     *
     * @return void
     */
    public function load()
    {
        // Disable for now the installation hook, not completed
        // register_activation_hook($this->file, array($this, 'activate'));

        add_action('plugins_loaded', array($this, 'bootstrap'));
    }

    /**
     * Plugin Activate Hook
     *
     * @return void
     */
    public function activate()
    {
        require_once($this->includes_path . '/class-wc-il-pgateways-install.php');

        new WC_IL_PGateways_Install();
    }

    /**
     * Check Plugin Dependencies
     *
     * @throws Exception
     * @return void
     */
    public function dependencies()
    {
        if (!function_exists('WC')) {
            throw new Exception(__('IL Payments requires WooCommerce to be activated', 'woocommerce-il-payment-gateways'));
        }

        if (version_compare(WC()->version,'2.5','<')) {
            throw new Exception(__('IL Payments requires WooCommerce version 2.5 or greater', 'woocommerce-il-payment-gateways'));
        }
    }

    /**
     * Echo Bootstrap Warning Message
     *
     * @return void
     */
    public function bootstrap_warning()
    {
        if (!empty($this->bootstrap_warning_message)) :
            ?>
            <div class="error fade">
                <p>
                    <strong><?php echo $this->bootstrap_warning_message; ?></strong>
                </p>
            </div>
        <?php
        endif;
    }

    /**
     * Send HTTP Request To Gateway
     *
     * @param $url
     * @param $method
     * @param null $params
     * @return string
     */
    public function gateway_request($url, $method, $params = null)
    {
        $params_string = '';

        if($params)
            $params_string = json_encode($params);

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Length' => strlen($params_string)
            ),
            'method' => strtoupper($method),
            'body' => $params_string,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'sslverify' => false,
            'blocking' => true,
            'cookies' => array()
        );

        $response = wp_remote_post( $url, $args );
        $body = wp_remote_retrieve_body( $response );

        return $body;
    }

    /**
     * Load localisation files
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('woocommerce-il-payment-gateways', false, "{$this->plugin_path}languages");
    }

    /**
     * Include Plugin Files
     *
     * @return void
     */
    public function includes()
    {
        require_once($this->includes_path . '/functions.php');
        require_once($this->includes_path . '/class-wc-il-pgateways-checkout-handler.php');
        require_once($this->includes_path . '/abstracts/abstract-wc-il-pgateways.php');
        require_once($this->includes_path . '/gateways/class-wc-il-pgateways-loader.php');

        if (is_admin()) {
            require_once($this->includes_path . '/admin/class-wc-il-pgateways-admin.php');
            require_once($this->includes_path . '/admin/class-wc-ilpg-transactions-list-table.php');
        }

        $this->log = new WC_Logger();
        $this->gateways_loader = new WC_IL_PGateways_Loader();
        $this->checkout = new WC_IL_PGateways_Checkout_Handler();
    }

    /**
     * @return array
     */
    public function get_gateways_methods()
    {
        if (!$this->gateways_loader) {
            $this->gateways_loader = new WC_IL_PGateways_Loader();
        }

        return $this->gateways_loader->payment_gateways();
    }

    /**
     * Initialize admin panel hooks.
     *
     * @return void
     */
    public function admin()
    {
        // Hide transactions table list page for now (in progress).
        // new WC_IL_PGateways_Admin();
    }

    /**
     * Define constants
     */
    public function define_constants()
    {
        define('WC_IL_PGATEWAYS_VERSION', $this->version);
    }
}
