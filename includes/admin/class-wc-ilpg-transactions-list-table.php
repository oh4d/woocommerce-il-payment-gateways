<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WC_ILPG_Transactions_List_Table extends WP_List_Table
{
    /**
     * @var string
     */
    public $currency;

    /**
     * @var string
     */
    public $currency_sign;

    /**
     * @var array
     */
    public $gateways;

    /**
     * @var array
     */
    public $gateways_initialized = [];

    /**
     * WC_ILPG_Transactions_List_Table constructor.
     */
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Transactions', 'woocommerce-il-payment-gateways'),
            'plural' => __('Transactions', 'woocommerce-il-payment-gateways'),
            'ajax' => false
        ]);

        $this->currency = get_woocommerce_currency();
        $this->currency_sign = get_woocommerce_currency_symbol($this->currency);
        $this->gateways = woocommerce_il_pgateways()->get_gateways_methods();

        $this->prepare_items();
        $this->display();
    }

    /**
     * @param $gateway
     * @return mixed|null
     */
    protected function get_gateway($gateway)
    {
        $gateway_class = 'WC_ILPG_' . ucfirst($gateway);

        if (isset($this->gateways_initialized[$gateway_class])) {
            return $this->gateways_initialized[$gateway_class];
        }

        if (!in_array($gateway_class, $this->gateways) || !class_exists($gateway_class)) {
            return null;
        }

        return new $gateway_class;
    }

    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = [];
        $columns['cb'] = '<input type="checkbox" />';
        $columns['date'] = __('Date', 'woocommerce-il-payment-gateways');
        $columns['order_id'] = __('Order ID', 'woocommerce-il-payment-gateways');
        $columns['status'] = __('Status', 'woocommerce-il-payment-gateways');
        $columns['gateway'] = __('Gateway', 'woocommerce-il-payment-gateways');
        $columns['amount'] = __('Amount', 'woocommerce-il-payment-gateways');

        wp_enqueue_script( 'wc-ilpg-inline-transaction-view');

        return $columns;
    }

    /**
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'date' => ['date', true]
        ];
    }

    /**
     *
     * @param object $item
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id);
    }

    /**
     * @param $item
     * @return string
     */
    public function column_date($item)
    {
        $delete_nonce = wp_create_nonce('wc_ilpg_delete_transaction');
        $item_date = new DateTime($item->date);

        $title = '<strong>' . $item_date->format('d/m/Y') . '<br/>בשעה: ' . $item_date->format('H:i') . '</strong>';

        $actions = [
            'delete' => sprintf(
                '<a href="'.admin_url( 'admin.php?page=wc-ilpg-transactions&action=%s&transaction=%s&_wpnonce=%s' ).'">Delete</a>',
                esc_attr('delete'), absint($item->id), $delete_nonce),
            'inline-view' => '<a href="#" class="inline-view" aria-label="View">View</a>'
        ];

        // global $wpdb;
        // $t = (array) json_decode('{"Response":"004","o_tranmode":"VK","trBgColor":"","expmonth":"06","myid":"322313223","key":"wc_order_5b33c4724f029","ppnewwin":"no","email":"ohadon3@gmail.com","order":"11112","address":"\u05d1\u05d5\u05e8\u05dc\u05d0 18","sum":"115.00","benid":"aq6tm824tjjl0beqn1462q19k5","lang":"il","o_npay":"","trButtonColor":"","buttonLabel":"\u05d4\u05de\u05e9\u05da","contact":"\u05d0\u05d5\u05d4\u05d3 \u05d2\u05d5\u05dc\u05d3\u05e9\u05d8\u05d9\u05d9\u05df","currency":"1","city":"\u05d7\u05d9\u05e4\u05d4","nologo":"1","expyear":"19","supplier":"bettapet","remarks":"","pdesc":"%D7%98%D7%A4%D7%98+%D7%AA%D7%9C%D7%AA-%D7%9E%D7%99%D7%9E%D7%93+%D7%9C%D7%91%D7%9F+%D7%90%D7%A4%D7%95%D7%A8x1+85%26%238362%3B%0A","#8362;\r\n":"","company":"\u05d1\u05d3\u05d9\u05e7\u05d4","trTextColor":"","o_cred_type":"1","phone":"0522658217","cred_type":"1","ccno":"4242","IMaam":"0.17","tranmode":"VK","ConfirmationCode":"0000000","cardtype":"2","cardissuer":"2","cardaquirer":"6","index":"1","Tempref":"01070001","maxpay":"","woocommerce-login-nonce":null,"_wpnonce":null}');
        // $s = serialize($t);
        // $sql = "update {$wpdb->prefix}wc_ilpg_transactions set response = '{$s}'";
        // $result = $wpdb->get_results( $sql );

        // die(serialize($t));
        return $title . $this->row_actions($actions) . $this->get_inline_data($item);
    }

    /**
     * @param $item
     * @return string
     */
    public function column_gateway($item)
    {
        $gateway = $this->get_gateway($item->gateway);

        if (!$gateway) {
            return $item->gateway;
        }

        return ($gateway->icon) ? "<img src='{$gateway->icon}' alt='{$gateway->gateway_method_title}'/>" : "<span class='gateway-title'>{$gateway->gateway_method_title}</span>";
    }

    /**
     * @param $item
     * @return string
     */
    public function column_status($item)
    {
        $status = ($item->status) ? 'failed' : 'processing';
        return "<span class='order-status status-{$status} tips'><span>" . (($item->status) ? __('Approved', '') : __('Failed', '')) . "</span></span>";
    }

    /**
     *
     * @param object $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name)
    {
        return $item->{$column_name};
    }

    /**
     * @param $item
     * @return string
     */
    public function column_amount($item)
    {
        return wc_price($item->amount);
    }

    public function column_order_id($item)
    {
        echo '<a href="' . esc_url(admin_url( 'post.php?post=' . absint($item->order_id)) . '&action=edit') . '" class="order-view"><strong>#' . esc_attr($item->order_id) ./* ' ' . esc_html( $buyer ) . */'</strong></a>';
    }

    /**
     * Prepares the list of items for displaying.
     * @uses WP_List_Table::set_pagination_args()
     *
     * @abstract
     */
    public function prepare_items()
    {
        global $wpdb;

        $per_page = $this->get_items_per_page('leads_per_page', 10 );
        $current_page = $this->get_pagenum();

        $sql = "SELECT * FROM {$wpdb->prefix}wc_ilpg_transactions";

        /*$orderBy = (isset($_REQUEST['orderby']) && $_REQUEST['orderby'] !== 'date') ? $_REQUEST['orderby'] : 'timestamp';
        $sql .= ' ORDER BY ' . esc_sql( $orderBy );
        $sql .= (isset($_REQUEST['order']) && !empty($_REQUEST['order'])) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' DESC';*/

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $current_page - 1 ) * $per_page;

        $result = $wpdb->get_results( $sql );

        $this->_column_headers = array(
            $this->get_columns(),
            array(), //hidden columns if applicable
            $this->get_sortable_columns()
        );

        // $this->process_bulk_action();

        $this->set_pagination_args([
            'total_items' => $this->record_count(),
            'per_page'    => $per_page
        ]);

        $this->items = $result;
    }

    /**
     * Count Transactions Records
     *
     * @return null|string
     */
    public function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wc_ilpg_transactions";
        return $wpdb->get_var($sql);
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk actions available on this table.
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }

    /**
     * @param $item
     * @return string
     */
    public function get_inline_data($item)
    {
        $response = unserialize($item->response);

        $hidden = "<div class='hidden'>";

        foreach ($response as $field => $value) {
            if (is_null($value)) {
                continue;
            }

            $hidden .= "<div class='{$field}'>{$value}</div>";
        }

        $hidden .= "</div>";
        return $hidden;
    }

    /**
     *
     */
    public function inline_edit()
    {

    }

    /**
     * @global WP_Post $post
     *
     * @param $item
     */
    public function single_row($item) {
        echo '<tr id="wc-ilpg-transaction-'.$item->id.'">';
        $this->single_row_columns($item);
        echo '</tr>';
    }
}