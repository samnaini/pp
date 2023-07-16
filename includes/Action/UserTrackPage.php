<?php

namespace ParcelPanel\Action;

use ParcelPanel\Api\Api;
use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\Table;
use ParcelPanel\Models\TrackingItems;
use ParcelPanel\Models\TrackingSettings;
use ParcelPanel\ParcelPanelFunction;
use const ParcelPanel\TEMPLATE_PATH;
use const ParcelPanel\VERSION;

class UserTrackPage
{
    use Singleton;

    private $order = null;

    private $order_id = 0;

    private $shipment_data = [];

    private $order_number = '';
    private $email = '';
    private $tracking_number = '';

    function enqueue_scripts()
    {
    }

    // 新版资源查询
    function get_pp_api_tracking($params)
    {
        // 获取 tracking 信息
        $trackingMessage = Api::userTrackingPage($params);
        if (is_wp_error($trackingMessage)) {
          return [];
        }

        return $trackingMessage['data'] ?? [];
    }

    // 新版资源注册
    function set_script_add($linkS)
    {
        $registers = $linkS['wp_register'] ?? [];
        if (!empty($registers)) {
            foreach ($registers as $k => $v) {
                if (empty($v['link'])) {
                    continue;
                }
                wp_register_script($k, $v['link'], $v['other'] ?? [], $v['ver'], $v['in_footer']);
                wp_enqueue_script($k);
            }
        }
    }

    /**
     * 注册网站公共资源
     */
    private function site_register_assets()
    {

        // Dayjs
        wp_register_script('pp-dayjs', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/dayjs/dayjs.min.js"), ['jquery'], VERSION);
        wp_register_script('pp-dayjs-format', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/dayjs/advancedFormat.js"), ['jquery'], VERSION);


        // User track page
        wp_register_style('pp-user-track-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('css/user-track-page.css'), [
            'pp-swiper',
        ], VERSION);
        wp_register_script('pp-user-track-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path("js/user-track-page.min.js"), [
            'jquery-core',
            'pp-google-translate',
            'wp-url',
            'pp-swiper',
            'pp-dayjs',
            'pp-dayjs-format'
        ], VERSION, true);

        // Google translate
        wp_register_script('pp-google-translate', (new ParcelPanelFunction)->parcelpanel_get_assets_path('plugins/google-translate/element.js'), [], VERSION, true);

        // Swiper
        $swiper_version = '8.3.2';
        wp_register_style('pp-swiper', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/swiper@{$swiper_version}/swiper-bundle.min.css"), [], null);
        wp_register_script('pp-swiper', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/swiper@{$swiper_version}/swiper-bundle.min.js"), [], null);

    }

    // 更新发货产品信息
    private function upTrackingProduct($tracking_data): array
    {
        $tracking = $tracking_data['tracking'] ?? [];
        $orderPro = $tracking_data['product'] ?? [];
        $checkPro = [];
        $checkProVar = [];
        foreach ($orderPro as $v) {
            $pro_id = $v['pro_id'] ?? '';
            $checkPro[$pro_id] = $v;
            $checkProVar[$pro_id] = $v;
        }
        foreach ($tracking as $k => $v) {
            $newProduct = [];
            $product = $v['product'] ?? [];
            foreach ($product as $vv) {
                $pro_id = $vv['id'] ?? 0;
                $var_id = $vv['var_id'] ?? 0;
                $quantity = $vv['quantity'] ?? 0;
                if (!empty($checkPro[$pro_id])) {
                    $checkPro[$pro_id]['quantity'] = $quantity;
                    $newProduct[] = $checkPro[$pro_id];
                } else if (!empty($checkProVar[$var_id])) {
                    $checkProVar[$var_id]['quantity'] = $quantity;
                    $newProduct[] = $checkProVar[$var_id];
                }
            }
            if (!empty($newProduct)) {
                $tracking_data['tracking'][$k]['product'] = $newProduct;
            }
        }

        return $tracking_data;
    }

    // 查询页面点击查询方法
    function get_track_info_ajax()
    {
        $this->tracking_number = wc_clean($_GET['nums'] ?? '');

        if (empty($this->tracking_number)) {
            $this->order_number = wc_clean($_GET['order'] ?? '');
            $this->email = wc_clean($_GET['email'] ?? '');
        }

        $rtn = [
            'tracking' => [],
            'order_number' => '',
            'email' => '',
            'tracking_number' => '',
            'product' => [],
        ];

        // 获取 tracking 信息
        $params = [
            'order' => $this->order_number ?? '',
            'token' => $this->email ?? '',
            'nums' => $this->tracking_number,
        ];
        $trackingData = $this->get_pp_api_tracking($params);
        $is_Going_new = $trackingData['is_new'] ?? false;
        $preview = $trackingData['preview'] ?? false;
        $tracking_data = $trackingData['tracking_data'] ?? [];

        if ($is_Going_new) {
            // new version
            // user order_id get products
            $order_id = $tracking_data['order_id'] ?? 0;
            $products_category = [];
            $order_products = [];
            if (!$preview && $order_id) {
                $productData = $this->get_products_new($order_id);
                $products_category = $productData['products_category'] ?? [];  // 订单商品分类列表
                $order_products = $productData['order_products'] ?? [];  // 订单商品列表
                $tracking_data['product'] = $productData['products'];  // 订单商品列表
                $tracking_data = $this->upTrackingProduct($tracking_data);
            }

            $rtn = $tracking_data;

            // 加载推荐的商品
            $rtn['recommend_products'] = self::get_recommend_products($products_category, $order_products);
        } else {
            if ($this->is_preview()) {
                // 预览数据

                $preview = true;

                $tracking = $this->get_preview_tracking_data();

                $rtn['tracking'] = $tracking ?? [];
                $rtn['order_number'] = $this->order_number;
                $rtn['email'] = $this->email;
                $rtn['product'] = self::get_preview_products();
                $rtn['recommend_products'] = self::get_preview_recommend_products();

            } else {

                if ($this->get_order_track()) {

                    $tracking = (new ParcelPanelFunction)->parcelpanel_handle_track_page_tracking_data($this->order, $this->shipment_data);

                    $rtn['tracking'] = $tracking ?? [];
                    $rtn['order_number'] = $this->order_number;
                    $rtn['email'] = $this->email;
                    $rtn['tracking_number'] = $this->tracking_number;
                    $productData = $this->get_products();
                    $order_products = $productData['order_products'] ?? [];  // 订单商品列表
                    $products_category = $productData['products_category'];  // 订单商品分类列表
                    $rtn['product'] = $productData['products'];  // 订单商品列表

                    // 加载推荐的商品
                    $rtn['recommend_products'] = self::get_recommend_products($products_category, $order_products);

                    $rtn = $this->data_do_more($rtn);
                }
            }
        }

        (new ParcelPanelFunction)->parcelpanel_json_response($rtn);
    }

    // 页面直接访问获取tracking信息方法
    function track_page_function()
    {

        $this->order_number = ltrim(sanitize_text_field($_GET['order'] ?? ''), '#');
        $token = wc_clean($_GET['token'] ?? '');
        $this->email = self::decode_email($token);
        $this->tracking_number = wc_clean($_GET['nums'] ?? '');

        // 获取 tracking 信息
        $params = [
            'order' => $this->order_number,
            'token' => $token,
            'nums' => $this->tracking_number,
        ];
        $trackingData = $this->get_pp_api_tracking($params);
        $is_Going_new = $trackingData['is_new'] ?? false;
        $preview = $trackingData['preview'] ?? false;
        $order_track_data = $trackingData['order_track_data'] ?? [];
        $tracking_data = $trackingData['tracking_data'] ?? [];
        $tracking_config = $trackingData['tracking_config'] ?? [];

        // 资源加载
        $this->site_register_assets();

        wp_enqueue_style('pp-user-track-page');

        // check is new
        $js = "var pp_track_version = {$is_Going_new};";
        wp_add_inline_script('pp-user-track-page', $js, 'before');

        if ($is_Going_new) {

            // new version

            // user order_id get products
            $order_id = $tracking_data['order_id'] ?? 0;
            $products_category = [];
            $order_products = [];
            if (!$preview && $order_id) {
                $productData = $this->get_products_new($order_id);
                $products_category = $productData['products_category'] ?? [];  // 订单商品分类列表
                $order_products = $productData['order_products'] ?? [];  // 订单商品列表
                $tracking_data['product'] = $productData['products'];  // 订单商品列表
                $tracking_data = $this->upTrackingProduct($tracking_data);
            }

            $linkS = $trackingData['linkS'] ?? [];
            $this->set_script_add($linkS);

            $pp_tracking_params = [
                'ajax_url' => admin_url('admin-ajax.php', 'relative'),
                'order_id' => '',
                'get_track_info_nonce' => wp_create_nonce('pp-track-info-get'),
                'assets_path' => (new ParcelPanelFunction)->parcelpanel_get_assets_path(),
            ];
            wp_localize_script('pp-user-track-page', 'pp_tracking_params', $pp_tracking_params);

            ob_start();

            foreach ($linkS as $k => $v) {
                if ($k == 'css') {
                    foreach ($v as $kk => $vv) {
                        echo "<link rel='stylesheet' id='{$kk}' href='{$vv}' media='all' />";
                    }
                }
            }

            $show_branding = $tracking_config['display_option']['show_branding'] ?? false;

            $tracking_config['status_keys'] = (new ParcelPanelFunction)->parcelpanel_get_status_keys($tracking_config['custom_order_status']);

            $css = $tracking_config['custom_css_and_html']['css'] ?? '';
            $html_top = $tracking_config['custom_css_and_html']['html_top'] ?? '';
            $html_bottom = $tracking_config['custom_css_and_html']['html_bottom'] ?? '';
            unset($tracking_config['custom_css_and_html']);

            // Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
            echo '<style>' . strip_tags($css) . '</style>';

            echo wp_kses_post($html_top);

            if ($tracking_config['product_recommend']['enabled']) {
                if (empty($tracking_data['recommend_products'])) {
                    // 加载推荐的商品
                    $tracking_config['recommend_products'] = $tracking_data['recommend_products'] = self::get_recommend_products($products_category, $order_products);
                } else {
                    $tracking_config['recommend_products'] = $tracking_data['recommend_products'] = self::get_preview_products_up($tracking_data['recommend_products']);
                }
            }

            $tracking_config_str = json_encode($tracking_config);

            $js = "var pp_track_config = {$tracking_config_str};";
            wp_add_inline_script('pp-user-track-page', $js, 'before');

            if (!empty($order_track_data) || $preview) {
                $tracking_data_json = json_encode($tracking_data);
                $js = "var pp_tracking_data = {$tracking_data_json};";
                wp_add_inline_script('pp-user-track-page', $js, 'before');
            }

            // 渲染参数
            $tracking_config['order'] = $tracking_data['order_number'] ?? $this->order_number;
            $tracking_config['email'] = $tracking_data['email'] ?? $this->email;
            $tracking_config['tracking_number'] = '';
            $tracking_config['show_branding'] = $show_branding;

            if (!empty($tracking_data['tracking_number'])) {
                $tracking_config['tracking_number'] = $tracking_data['tracking_number'] ?? $this->tracking_number;
            }

            $this->track_form_template($tracking_config);

            echo wp_kses_post($html_bottom);

        } else {

            wp_enqueue_script('pp-user-track-page');

            $tracking_config = TrackingSettings::instance()->get_settings();

            $show_branding = !get_option(\ParcelPanel\OptionName\REMOVE_BRANDING);

            $pp_tracking_params = [
                'ajax_url' => admin_url('admin-ajax.php', 'relative'),
                'order_id' => '',
                'get_track_info_nonce' => wp_create_nonce('pp-track-info-get'),
                'assets_path' => (new ParcelPanelFunction)->parcelpanel_get_assets_path(),
            ];
            wp_localize_script('pp-user-track-page', 'pp_tracking_params', $pp_tracking_params);

            $preview = false;

            $tracking_data = [
                'tracking' => [],
                'order_number' => '',
                'email' => '',
                'tracking_number' => '',
                'product' => [],
            ];

            // 订单商品分类 ID 列表
            $products_category = [];
            $order_products = [];

            if ($this->is_preview()) {
                // 预览数据

                $preview = true;

                $tracking = $this->get_preview_tracking_data();

                $tracking_data['tracking'] = $tracking ?? [];
                $tracking_data['order_number'] = $this->order_number;
                $tracking_data['email'] = $this->email;
                $tracking_data['product'] = self::get_preview_products();
                if ($tracking_config['product_recommend']['enabled']) {
                    $tracking_data['recommend_products'] = self::get_preview_recommend_products();
                }

            } else {

                $order_track_data = $this->get_order_track();

                if ($order_track_data) {

                    $tracking = (new ParcelPanelFunction)->parcelpanel_handle_track_page_tracking_data($this->order, $this->shipment_data);
                    $tracking_data['tracking'] = $tracking ?? [];
                    $tracking_data['order_number'] = $this->order_number;
                    $tracking_data['email'] = $this->email;
                    $tracking_data['tracking_number'] = $this->tracking_number;
                    $productData = $this->get_products();
                    $products_category = $productData['products_category'] ?? [];  // 订单商品分类列表
                    $order_products = $productData['order_products'] ?? [];  // 订单商品列表
                    $tracking_data['product'] = $productData['products'];  // 订单商品列表
                    // var_dump($productData);die;
                }
            }


            ob_start();

            $tracking_config['status_keys'] = (new ParcelPanelFunction)->parcelpanel_get_status_keys($tracking_config['custom_order_status'] ?? []);

            $css = $tracking_config['custom_css_and_html']['css'] ?? '';
            $html_top = $tracking_config['custom_css_and_html']['html_top'] ?? '';
            $html_bottom = $tracking_config['custom_css_and_html']['html_bottom'] ?? '';
            unset($tracking_config['custom_css_and_html']);

            // Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
            echo '<style>' . strip_tags($css) . '</style>';

            echo wp_kses_post($html_top);

            if ($tracking_config['product_recommend'] && $tracking_config['product_recommend']['enabled']) {
                // 加载推荐的商品
                $tracking_config['recommend_products'] = self::get_recommend_products($products_category, $order_products);
            }

            $tracking_config_str = json_encode($tracking_config);

            $js = "var pp_track_config = {$tracking_config_str};";
            wp_add_inline_script('pp-user-track-page', $js, 'before');

            if (!empty($order_track_data) || $preview) {

                $tracking_data = $this->data_do_more($tracking_data);

                $tracking_data_json = json_encode($tracking_data);

                $js = "var pp_tracking_data = {$tracking_data_json};";
                wp_add_inline_script('pp-user-track-page', $js, 'before');
            }


            // 渲染参数
            $tracking_config['order'] = $this->order_number;
            $tracking_config['email'] = $this->email;
            $tracking_config['tracking_number'] = '';
            $tracking_config['show_branding'] = $show_branding;

            if ($this->tracking_number) {
                $tracking_config['tracking_number'] = $this->tracking_number;
            }

            $this->track_form_template($tracking_config);

            echo wp_kses_post($html_bottom);

        }

        return ob_get_clean();
    }

    /**
     * 返回一维数组
     */
    private function get_tracking_data_by_tracking_number($tracking_number)
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;


        $SQL_SELECT_ORDER_ID_BY_TRACKING_NUMBER = "SELECT ti.order_id FROM {$TABLE_TRACKING} AS t JOIN {$TABLE_TRACKING_ITEMS} AS ti ON t.id = ti.tracking_id WHERE t.tracking_number = %s";

        $order_id = $wpdb->get_var($wpdb->prepare($SQL_SELECT_ORDER_ID_BY_TRACKING_NUMBER, $tracking_number));

        return $this->get_tracking_data_by_order_id($order_id);
    }

    /**
     * 获取发货信息 通过 order_id
     */
    private function get_tracking_data_by_order_id($order_id)
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        $SQL_SELECT_SHIPMENTS_BY_ORDER_ID = "SELECT * FROM {$TABLE_TRACKING_ITEMS} WHERE order_id = %d";

        $result_shipments = $wpdb->get_results($wpdb->prepare($SQL_SELECT_SHIPMENTS_BY_ORDER_ID, $order_id));

        if (empty($result_shipments)) {
            return [];
        }

        TrackingItems::format_result_data($result_shipments);

        /* 获取 tracking 信息 */

        $tracking_ids = array_column($result_shipments, 'tracking_id');

        $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_ids, '%d');

        $SQL_SELECT_TRACKINGS_BY_ID = <<<SQL
SELECT
id,tracking_number,courier_code,last_event,original_country,destination_country,origin_info,destination_info,transit_time,stay_time,fulfilled_at,updated_at
FROM {$TABLE_TRACKING}
WHERE id IN ({$placeholder_str})
SQL;
        $trackings_by_id = array_column($wpdb->get_results($wpdb->prepare($SQL_SELECT_TRACKINGS_BY_ID, $tracking_ids)), null, 'id');

        foreach ($result_shipments as $item) {

            // t_id: 0 means not shipped
            $t_id = $item->tracking_id;

            if (empty($trackings_by_id[$t_id])) {
                if (!empty($t_id)) {
                    continue;
                }
                $trackings_by_id[$t_id] = TrackingNumber::get_empty_tracking();
            }

            $_tracking = $trackings_by_id[$t_id];

            if (empty($_tracking->order_id)) {
                $_tracking->order_id = $item->order_id;
            }
            if (!isset($_tracking->product)) {
                $_tracking->product = [];
            }

            $_tracking->shipment_status = $item->shipment_status;
            $_tracking->custom_status_time = $item->custom_status_time;
            $_tracking->custom_shipment_status = $item->custom_shipment_status;

            $_shipment_product = $_tracking->product[] = new \stdClass;
            $_shipment_product->id = $item->order_item_id;
            $_shipment_product->quantity = $item->quantity;
        }

        foreach ($trackings_by_id as $item) {
            $has_full_shipped = false;
            $has_0_quantity = false;
            foreach ($item->product as $prod) {
                // 订单发货级别最高，其次是商品全发货
                if (empty($prod->id)) {
                    $has_full_shipped = true;
                    break;
                } elseif (empty($prod->quantity)) {
                    $has_0_quantity = true;
                    // 因为商品全发货的级别原因，这里不用 break 了
                }
            }
            if ($has_full_shipped) {
                $item->ship_type = 1;  // 按订单发货
                $item->product = [];
            } elseif ($has_0_quantity) {
                $item->ship_type = 2;  // 商品全发货
            } else {
                $item->ship_type = 0;  // 商品数发货
            }
        }

        if (empty($trackings_by_id)) {
            return [];
        }

        foreach ($trackings_by_id as $val) {
            self::format_tracking_data($val);
        }

        return array_values($trackings_by_id);
    }

    function get_tracking_data_by($where = [])
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;

        $FIELD_FORMAT = [
            'order_id' => '%d',
            'tracking_number' => '%s',
        ];

        $conditions = [];
        $values = [];

        foreach ($where as $field => $value) {

            if (!array_key_exists($field, $FIELD_FORMAT)) {
                continue;
            }

            $format = $FIELD_FORMAT[$field];

            $conditions[] = "`{$field}` = {$format}";
            $values[] = $value;
        }

        $conditions = implode(' AND ', $conditions);

        $prepare = $wpdb->prepare("SELECT * FROM {$TABLE_TRACKING} WHERE {$conditions}", $values);

        $res = $wpdb->get_results($prepare);

        if (empty($res)) {
            return [];
        }

        foreach ($res as $val) {
            self::format_tracking_data($val);
        }

        return $res;
    }

    static function format_tracking_data(\stdClass $data)
    {
        $data->id = absint($data->id);
        $data->order_id = absint($data->order_id);
        $data->shipment_status = absint($data->shipment_status);
        $data->origin_info = json_decode($data->origin_info, 1) ?: [];
        $data->destination_info = json_decode($data->destination_info, 1) ?: [];
        $data->fulfilled_at = absint($data->fulfilled_at);
        $data->updated_at = absint($data->updated_at);
    }

    /**
     * Adds data to the custom "Track" column in "My Account > Orders".
     *
     * @param \WC_Order $order the order object for the row
     */
    public function add_column_my_account_orders_pp_track_column($actions, \WC_Order $order)
    {
        $TRACK_BUTTON_ORDER_STATUS = AdminSettings::get_track_button_order_status_field();
        $TRACKING_SETTINGS = \ParcelPanel\Models\TrackingSettings::instance()->get_settings();

        $TRANSLATIONS = $TRACKING_SETTINGS['tracking_page_translations'];

        // 启用状态
        $is_enable_track = AdminSettings::get_orders_page_add_track_button_field();

        $order_status = $order->get_status();
        $_sync_status = $order->get_meta('_parcelpanel_sync_status');

        if (!$is_enable_track || '1' !== $_sync_status || (!in_array($order_status, $TRACK_BUTTON_ORDER_STATUS, true) && !in_array("wc-{$order_status}", $TRACK_BUTTON_ORDER_STATUS, true))) {
            return $actions;
        }

        $order_number = $order->get_order_number();
        $email = $order->get_billing_email();
        // if ( empty( $email ) ) {
        //     $user = wp_get_current_user();
        //     $email = $user->user_email;
        // }

        $track_url = (new ParcelPanelFunction)->parcelpanel_get_track_page_url(false, "#{$order_number}", $email);

        if (empty($track_url)) {
            return $actions;
        }

        $actions['pp-track'] = [
            'url' => $track_url,
            'name' => $TRANSLATIONS['track'],
        ];

        ?>
        <script>
            jQuery(document).ready(function () {
                jQuery('.pp-track').attr('target', '_blank')
            })
        </script>
        <?php

        return $actions;
    }

    function track_form_template($args = [])
    {
        wc_get_template('tracking/tracking-page.php', $args, 'parcelpanel-woocommerce/', TEMPLATE_PATH);
    }

    private function get_order_track()
    {
        $check_email = false;

        if ($this->tracking_number) {
            // 按物流单号查询

            $shipment_data = $this->get_tracking_data_by_tracking_number($this->tracking_number);

            $order_id = $shipment_data[0]->order_id ?? 0;

        } elseif ($this->order_number && $this->email) {
            // 按邮箱订单号查询

            $order_number = ltrim($this->order_number, '#');
            // 保留一个#号
            $this->order_number = "#{$order_number}";

            $order_id = absint((new ParcelPanelFunction)->parcelpanel_get_formatted_order_id($order_number));

            $shipment_data = $this->get_tracking_data_by_order_id($order_id);

            $check_email = true;

        } else {

            return null;
        }


        if (empty($order_id)) {
            return false;
        }

        /* @var \WC_Order */
        $order = wc_get_order($order_id);

        if (!$order) {
            // 订单不存在
            return false;
        }

        $_sync_status = (int)$order->get_meta('_parcelpanel_sync_status');
        if ($_sync_status !== 1) {
            return false;
        }

        if ($check_email && $this->email != $order->get_billing_email() && $this->email != $order->get_billing_phone()) {
            // 邮箱 或 手机号 不一致
            return false;
        }

        // todo 空单号已经包含了
        // if ( empty( $shipment_data ) ) {
        //     $shipment_data = [ TrackingNumber::get_empty_tracking() ];
        // }

        $this->order = $order;
        $this->order_id = $order_id;
        $this->shipment_data = $shipment_data;

        // 按单号查询时填充订单信息
        if ($this->tracking_number) {
            $this->order_number = '#' . $order->get_order_number();
            $this->email = $order->get_billing_email();
        }

        $this->order_tracked();

        return true;
    }

    static function encode_email($email)
    {
        if (false === strpos($email, '@')) {
            return $email;
        }

        $email = str_replace('@', '_-_', $email);

        return strrev($email);
    }

    static function decode_email($email)
    {
        if (false === strpos($email, '_-_')) {
            return $email;
        }

        $email = str_replace('_-_', '@', $email);

        return sanitize_email(strrev($email));
    }

    function is_preview(): bool
    {
        $order_number = ltrim($this->order_number, '#');
        return '1234' == $this->tracking_number || ('1234' == $order_number && 'parcelpanel100@gmail.com' == $this->email);
    }

    private function get_products_new($order_id)
    {
        if (empty($order_id)) {
            return [];
        }

        $order = wc_get_order($order_id);

        // + 商品列表
        $products = [];

        // + 商品分类列表
        $products_category = [];

        // 订单中的产品
        $order_products = [];

        /* @var \WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {

            /* @var \WC_Product $product */
            $product = $item->get_product();

            if (empty($product)) {
                continue;
            }

            $category_ids = $product->get_category_ids();
            if (!empty($category_ids)) {
                foreach ($category_ids as $v) {
                    if (!in_array($v, $products_category)) {
                        $products_category[] = $v;
                    }
                }
            }

            $permalink = get_permalink($product->get_id());

            $image = wp_get_attachment_url($product->get_image_id()) ?: '';

            $order_products[] = $item->get_name();

            $products[] = [
                'pro_id' => $product->get_id(),
                'id' => $item->get_id(),
                'name' => $item->get_name(),
                'sku' => $product->get_sku(),
                'quantity' => $item->get_quantity(),
                'image_url' => $image,
                'link' => $permalink,
            ];
        }

        return [
            'products' => $products,
            'products_category' => $products_category,
            'order_products' => $order_products,
        ];
    }

    /**
     * 获取订单商品列表数据
     */
    private function get_products(): array
    {
        if (empty($this->order)) {
            return [];
        }

        // + 商品列表
        $products = [];

        // + 商品分类列表
        $products_category = [];

        // 订单中的产品
        $order_products = [];

        // $product_no_img_list = [];

        // $product_num          = 0;
        // 最大显示图片数量
        // $product_img_show_max = 3;

        /* @var \WC_Order_Item_Product $item */
        foreach ($this->order->get_items() as $item) {

            /* @var \WC_Product $product */
            $product = $item->get_product();

            if (empty($product)) {
                continue;
            }

            $category_ids = $product->get_category_ids();
            if (!empty($category_ids)) {
                foreach ($category_ids as $v) {
                    if (!in_array($v, $products_category)) {
                        $products_category[] = $v;
                    }
                }
            }

            $permalink = get_permalink($product->get_id());

            $image = wp_get_attachment_url($product->get_image_id()) ?: '';

            // if ( $product_img_show_max <= $product_num ) {
            //     $image = '';
            // }
            //
            // ++$product_num;

            $order_products[] = $item->get_name();

            $products[] = [
                'id' => $item->get_id(),
                'name' => $item->get_name(),
                'sku' => $product->get_sku(),
                'quantity' => $item->get_quantity(),
                'image_url' => $image,
                'link' => $permalink,
            ];
        }


        return [
            'products' => $products,
            'products_category' => $products_category,
            'order_products' => $order_products,
        ];
        // - 商品列表
    }

    function pp_data_do_replace($str)
    {
        if (!empty($str)) {
            $str = str_replace('(1)', '#', $str);
            $str = str_replace('(2)', '/', $str);
        }
        return $str;
    }

    /**
     * 查询结果追加处理 ( 特别与额外处理 )
     */
    private function data_do_more($data)
    {
        $TRANSLATE_TRACKING = TrackingSettings::instance()->translate_tracking_detailed_info;

        if (!empty($data['tracking'])) {
            foreach ($data['tracking'] as &$value) {
                if (!empty($value['carrier']['code']) && 'cainiao' == $value['carrier']['code']) {
                    $value['carrier']['name'] = 'Cainiao';
                }
            }
        }

        // 对获取的data快递详情信息的进行翻译（用户自定义翻译） 手动翻译
        return (new ParcelPanelFunction)->parcelpanel_tracking_info__($TRANSLATE_TRACKING, $data);
    }

    // 获取产品分类所有层级数组
    private static function getProCate($category_all, $products_category)
    {

        if (empty($products_category)) {
            return [];
        }

        $cate_all = [];
        $cate_arr = [];
        foreach ($category_all as $v) {
            $cate_arr[$v->parent][] = $v->term_id;
        }

        $cate_all[0] = $cate_arr[0] ?? [];
        unset($cate_arr[0]);

        $res = self::getCateLv($cate_all[0], $cate_all, $cate_arr);

        $now_cate_arr = []; // 产品分类存在对应层级
        foreach ($products_category as $v) {
            foreach ($res as $k => $vv) {
                if (in_array($v, $vv)) {
                    $now_cate_arr[$k][] = $v;
                }
            }
        }
        sort($now_cate_arr);

        return [
            "cate_ids" => $now_cate_arr,
            "lv" => count($now_cate_arr),
        ];
    }

    private static function getCateLv($parent, $nowCateArr, $cate_arr, $lv = 1)
    {

        if (empty($cate_arr)) {
            return $nowCateArr;
        }

        foreach ($cate_arr as $k => $v) {
            if (in_array($k, $parent)) {
                foreach ($v as $vv) {
                    $nowCateArr[$lv][] = $vv;
                }
                unset($cate_arr[$k]);
            }
        }
        $parent = $nowCateArr[$lv];
        $lv++;

        if (!empty($cate_arr) && count($nowCateArr) != $lv) {
            $lv = $lv - 1;
            // 将现存的加到第二层与第三层
            foreach ($cate_arr as $k => $v) {
                if (!empty($nowCateArr[$lv - 1])) {
                    foreach ($v as $vv) {
                        $nowCateArr[$lv - 1][] = $vv;
                    }
                }
                if (!empty($nowCateArr[$lv - 2])) {
                    $nowCateArr[$lv - 2][] = $k;
                }
            }

            return $nowCateArr;
        }

        return self::getCateLv($parent, $nowCateArr, $cate_arr, $lv);
    }

    private static function get_preview_products_up($recommend_products)
    {
        foreach ($recommend_products as &$v) {
            $v['price_html'] = wc_price($v['price_html']);
        }
        return $recommend_products;
    }

    private static function get_recommend_products($products_category = [], $order_products = [], $advanced = 0): array
    {
        // 推荐 app 集合
        $recommend_products = [];

        // 获取用户设置产品分类
        $PRODUCT_RECOMMEND = TrackingSettings::instance()->product_recommend;
        $PRODUCT_RECOMMEND_advanced = $PRODUCT_RECOMMEND['advanced'] ?? false;
        $PRODUCT_RECOMMEND_CAT_ID = $PRODUCT_RECOMMEND['product_cat_id'];
        if (!empty($PRODUCT_RECOMMEND_advanced) && !empty($PRODUCT_RECOMMEND_CAT_ID)) {
            $back = self::get_recommend_products_by_cate_ids($recommend_products, $order_products, $PRODUCT_RECOMMEND_CAT_ID);
            return $back['recommend_products'] ?? [];
        }

        // 不存在分类直接返回空
        if (empty($products_category)) {
            return [];
        }

        // 获取所有分层
        $category_all = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'pad_counts' => false,
                'hide_empty' => false,
                // 'include'  => $products_category, // 获取对应产品分类的分类列表
                // 'fields'   => 'names',
            )
        );
        $cate_all = self::getProCate($category_all, $products_category);
        $cate_ids = $cate_all['cate_ids'] ?? []; // 分类不同层级分类id
        $cate_lv = $cate_all['lv'] ?? 0; // 分类层级
        // print_r($cate_ids);
        // print_r($category_names);die;
        // 取后三个层级的分类 id 获取推荐产品
        $first_cate_ids = $cate_ids[$cate_lv - 1] ?? [];
        $second_cate_ids = $cate_ids[$cate_lv - 2] ?? [];
        $third_cate_ids = $cate_ids[$cate_lv - 3] ?? [];
        // $all_cate_ids = array_merge($first_cate_ids, $second_cate_ids, $third_cate_ids);
        $get_pro = [];
        $get_pro[] = $first_cate_ids;
        $get_pro[] = $second_cate_ids;
        $get_pro[] = $third_cate_ids;
        foreach ($get_pro as $v) {
            if (!empty($v)) {
                $back = self::get_recommend_products_by_cate_ids($recommend_products, $order_products, $v);
                $recommend_products = $back['recommend_products'] ?? [];
                $order_products = $back['order_products'] ?? [];
            }
        }
        return $recommend_products;
    }

    // 获取推荐产品
    private static function get_recommend_products_by_cate_ids($recommend_products, $order_products, $cateIds)
    {

        // 获取推荐产品数量
        $count_pro = count($order_products) + 10;

        // 获取用户设置产品分类
        // $PRODUCT_RECOMMEND = TrackingSettings::instance()->product_recommend;
        // $PRODUCT_RECOMMEND_CAT_ID = $PRODUCT_RECOMMEND[ 'product_cat_id' ];

        // $recommend_products = [];
        $query_args = [
            'fields' => 'ids',
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $count_pro,
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $cateIds,// array( 'jazz', 'improv' )
                ],
            ],
        ];

        $WP_Query = new \WP_Query($query_args);
        foreach ($WP_Query->posts as $product_id) {
            $product = wc_get_product($product_id);

            if (in_array($product->get_name(), $order_products)) {
                // 排除订单中的产品 $order_products
                continue;
            }

            $attachment = wp_get_attachment_image_src($product->get_image_id(), 'full');
            if (is_array($attachment)) {
                $src = current($attachment);
            } else {
                $src = wc_placeholder_img_src();
            }

            $recommend_products[] = [
                'title' => $product->get_name(),
                'price_html' => wc_price($product->get_price()),
                'url' => $product->get_permalink(),
                'img' => $src,
            ];
            $order_products[] = $product->get_name();
            if (count($recommend_products) == 10) {
                break;
            }
        }

        return [
            'order_products' => $order_products,
            'recommend_products' => $recommend_products
        ];
    }

    private function get_preview_tracking_data(): array
    {
        $this->order_number = '#1234';
        $this->email = 'parcelpanel100@gmail.com';
        $this->tracking_number = '1234';

        $DISPLAY_OPTION = TrackingSettings::instance()->display_option;
        $MAP_POSITION = $DISPLAY_OPTION['map_coordinates_position'];  // 参考位置

        $MAP_DATA = [
            'lon' => '-81.3899328',
            'lat' => '28.5538409',
            'location' => 'SEMINOLE-ORLANDO FL DISTRIBUTION CENTER',
            'ship' => $MAP_POSITION,
        ];

        $origin_info = '{"courier_code":"usps","courier_phone":null,"weblink":"https:\\/\\/[www.usps.com](http://www.usps.com)\\/","reference_number":null,"received_date":"2020-11-13 17:59","dispatched_date":null,"departed_airport_date":null,"arrived_abroad_date":"2020-11-16 00:50","customs_received_date":null,"arrived_destination_date":"2020-11-16 07:10","trackinfo":[{"checkpoint_date":"2020-11-16 00:50","tracking_detail":"Arrived at USPS Regional Facility","location":"SEMINOLE-ORLANDO FL DISTRIBUTION CENTER","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit004"},{"checkpoint_date":"2020-11-15 23:49","tracking_detail":"Departed USPS Regional Facility","location":"LAKE MARY FL DISTRIBUTION CENTER","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit001"},{"checkpoint_date":"2020-11-15 06:12","tracking_detail":"Arrived at USPS Regional Destination Facility","location":"LAKE MARY FL DISTRIBUTION CENTER","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit003"},{"checkpoint_date":"2020-11-14 00:00","tracking_detail":"In Transit to Next Facility","location":"","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit001"},{"checkpoint_date":"2020-11-13 19:14","tracking_detail":"Departed USPS Regional Origin Facility","location":"LAS VEGAS NV DISTRIBUTION CENTER ANNEX","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit001"},{"checkpoint_date":"2020-11-13 19:14","tracking_detail":"Arrived at USPS Regional Origin Facility","location":"LAS VEGAS NV DISTRIBUTION CENTER ANNEX","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit001"},{"checkpoint_date":"2020-11-13 17:59","tracking_detail":"Accepted at USPS Origin Facility","location":"LAS VEGAS,NV,89118","checkpoint_delivery_status":"transit","checkpoint_delivery_substatus":"transit001","ItemNode":"ItemReceived"},{"checkpoint_date":"2020-11-12 18:02","tracking_detail":"Shipping Label Created, USPS Awaiting Item","location":"LAS VEGAS,NV,89118","checkpoint_delivery_substatus":"notfound001","checkpoint_delivery_status":"transit"}]}';
        $destination_info = '{"courier_code":null,"courier_phone":null,"weblink":null,"reference_number":null,"received_date":null,"dispatched_date":null,"departed_airport_date":null,"arrived_abroad_date":null,"customs_received_date":null,"arrived_destination_date":null,"trackinfo":[]}';

        $order = new \WC_Order();
        $order->set_date_created(1604068920);

        $shipment_data = new \stdClass();
        $shipment_data->tracking_number = '92055901755477000271990251';
        $shipment_data->courier_code = 'usps';
        $shipment_data->shipment_status = 2;
        $shipment_data->origin_info = json_decode($origin_info, 1);
        $shipment_data->destination_info = json_decode($destination_info, 1);
        $shipment_data->fulfilled_at = 1605081220;
        $shipment_data->custom_status_time = null;
        $shipment_data->custom_shipment_status = 0;

        $tracking = (new ParcelPanelFunction)->parcelpanel_handle_track_page_tracking_data($order, [$shipment_data]);

        foreach ($tracking as &$info) {
            $info['shipping_map'] = $MAP_DATA;
        }

        return $tracking;
    }

    private static function get_preview_products(): array
    {
        return [
            [
                'name' => 'Test product',
                'sku' => '',
                'quantity' => 1,
                'image_url' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/preview-product-image.jpg')),
                'link' => '',
            ],
        ];
    }

    private static function get_preview_recommend_products()
    {
        $title = 'Test product just for preview';
        return [
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(1.26),
                // 'product_id' => 1,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-1fdcsawz19.jpeg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(7.86),
                // 'product_id' => 2,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-1xnpvddcri.jpg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(4.34),
                // 'product_id' => 3,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-fr1rcnlt7p.jpg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(4.26),
                // 'product_id' => 4,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-gwmced961b.jpg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(10.31),
                // 'product_id' => 5,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-zi2a3u31nx.jpeg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(3.56),
                // 'product_id' => 6,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-kbh7mysjf3.jpg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(6.12),
                // 'product_id' => 7,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-r5yv423wbs.jpg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(9.81),
                // 'product_id' => 8,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-rblx4yy59h.jpeg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(26.85),
                // 'product_id' => 9,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-wzbwv8m02v.jpeg')),
            ],
            [
                'title' => $title,
                'url' => '',
                'price_html' => wc_price(1.84),
                // 'product_id' => 0,
                'img' => esc_url((new ParcelPanelFunction)->parcelpanel_get_assets_path('imgs/product/prod-y7b44fx94g.jpg')),
            ],
        ];
    }

    private function order_tracked()
    {
        $data = [
            'order_id' => $this->order_id,
            'order_number' => $this->order_number,
            'email' => $this->email,
            'tracking_number' => $this->tracking_number,
            'ua' => wc_clean($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];
        Api::order_tracked($data);
    }
}
