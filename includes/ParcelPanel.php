<?php
/**
 * @author Lijiahao
 * @date   2023/2/25 9:33
 */

namespace ParcelPanel;

use ParcelPanel\Action\AdminAccount;
use ParcelPanel\Action\AdminHome;
use ParcelPanel\Action\AdminIntegration;
use ParcelPanel\Action\AdminSettings;
use ParcelPanel\Action\AdminShipments;
use ParcelPanel\Action\AdminTrackingPage;
use ParcelPanel\Action\Common;
use ParcelPanel\Action\Courier;
use ParcelPanel\Action\Email;
use ParcelPanel\Action\ShopOrder;
use ParcelPanel\Action\TrackingNumber;
use ParcelPanel\Action\Upload;
use ParcelPanel\Action\UserTrackPage;
use ParcelPanel\Api\Api;
use ParcelPanel\Api\Configs;
use ParcelPanel\Api\RestApi;
use ParcelPanel\Libs\ArrUtils;
use ParcelPanel\Libs\HooksTracker;
use ParcelPanel\Libs\Notice;
use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\Table;

/**
 * Class ParcelPanel
 * 与 wordpress 交互控制器
 *
 * @package ParcelPanel
 */
final class ParcelPanel

{
    use Singleton;


    /**
     * DB updates and callbacks that need to be run per version.
     *
     * @var array
     */
    private static $db_updates = [
        '2.0.0' => [
            'parcelpanel_update_200_migrate_tracking_data',
            'parcelpanel_update_200_db_version',
        ],
        '2.2.0' => [
            'parcelpanel_update_220_migrate_tracking_data',
            'parcelpanel_update_220_db_version',
        ],
        '2.8.0' => [
            'parcelpanel_update_280_enable_integration',
            'parcelpanel_update_280_db_version',
        ],
    ];


    /**
     * 构造函数。构造时会执行的一些事件
     */
    private function __construct()
    {
        $this->load_plugin_textdomain();  // "本地化"初始化
        $this->define_constants();  // 定义一些常用常量
        $this->define_tables();
        $this->init_hooks();  // 初始化钩子方法
        $this->init_ajax();
        $this->init_shortcode();
    }

    /**
     * 定义一些常量
     *
     * @author: Chuwen
     * @date  : 2021/7/20 18:16
     */
    private function define_constants()
    {
        // 父级菜单的 slag
        define('ParcelPanel\PP_MENU_SLAG', 'pp-admin');

        // template path
        define('ParcelPanel\TEMPLATE_PATH', PLUGIN_PATH . '/templates/');

        // Track Page ID
        define('ParcelPanel\OptionName\TRACK_PAGE_ID', 'parcelpanel_track_page_id');

        // DB Version
        define('ParcelPanel\OptionName\DB_VERSION', 'parcelpanel_db_version');
        // Plugin Version
        define('ParcelPanel\OptionName\PLUGIN_VERSION', 'parcelpanel_plugin_version');

        // Tracking Settings
        define('ParcelPanel\OptionName\TRACKING_PAGE_OPTIONS', 'parcelpanel_tracking_page_options');
        // 常用运输商数据
        define('ParcelPanel\OptionName\SELECTED_COURIER', 'parcelpanel_selected_courier');

        // 单号导入记录
        define('ParcelPanel\OptionName\TRACKING_NUMBER_IMPORT_RECORD_IDS', 'parcelpanel_tracking_number_import_record_ids');
        define('ParcelPanel\OptionName\TRACKING_NUMBER_IMPORT_RECORD_DATA', 'parcelpanel_tracking_number_import_record_%s');

        // 额度数据
        define('ParcelPanel\OptionName\QUOTA_CONFIG', 'parcelpanel_quota_config');

        define('ParcelPanel\OptionName\REGISTERED_AT', 'parcelpanel_registered_at');
        // 连接时间
        define('ParcelPanel\OptionName\CONNECTED_AT', 'parcelpanel_connected_at');
        define('ParcelPanel\OptionName\LAST_ATTEMPT_CONNECT_AT', 'parcelpanel_last_attempt_connect_at');
        // 认证码
        define('ParcelPanel\OptionName\CLIENT_CODE', 'parcelpanel_client_code');
        // API KEY
        define('ParcelPanel\OptionName\API_KEY', 'parcelpanel_api_key');
        // ParcelPanel 注册用户的 ID
        define('ParcelPanel\OptionName\API_UID', 'parcelpanel_api_uid');
        // Website ID
        define('ParcelPanel\OptionName\API_BID', 'parcelpanel_api_bid');
        define('ParcelPanel\OptionName\REMOVE_BRANDING', 'parcelpanel_remove_branding');

        define('ParcelPanel\OptionName\CLOSE_QUOTA_NOTICE', 'parcelpanel_close_quota_notice');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FEEDBACK', 'parcelpanel_admin_notice_ignore_feedback');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_QUESTION', 'parcelpanel_admin_notice_ignore_question');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_NPS', 'parcelpanel_admin_notice_ignore_nps');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_PLUGINS_FEEDBACK', 'parcelpanel_admin_notice_ignore_plugins_feedback');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_REMOVE_BRANDING', 'parcelpanel_admin_notice_ignore_remove_branding');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FREE_SYNC_ORDERS', 'parcelpanel_admin_notice_ignore_free_sync_orders');
        define('ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FREE_UPGRADE', 'parcelpanel_admin_notice_ignore_free_upgrade');

        // 套餐总额度
        define('ParcelPanel\OptionName\PLAN_QUOTA', 'parcelpanel_plan_quota');
        // 套餐剩余额度
        define('ParcelPanel\OptionName\PLAN_QUOTA_REMAIN', 'parcelpanel_plan_quota_remain');
        // 是否免费套餐
        define('ParcelPanel\OptionName\IS_FREE_PLAN', 'parcelpanel_is_free_plan');
        // 是否不限额度套餐
        define('ParcelPanel\OptionName\IS_UNLIMITED_PLAN', 'parcelpanel_is_unlimited_plan');

        // 首次同步完成时间戳
        define('ParcelPanel\OptionName\FIRST_SYNCED_AT', 'parcelpanel_first_synced_at');

        define('ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON', 'parcelpanel_orders_page_add_track_button');
        define('ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION', 'parcelpanel_email_notification_add_tracking_section');
        define('ParcelPanel\OptionName\TRACKING_SECTION_ORDER_STATUS', 'parcelpanel_tracking_section_order_status');
        define('ParcelPanel\OptionName\TRACK_BUTTON_ORDER_STATUS', 'parcelpanel_track_button_order_status');
        define('ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK', 'parcelpanel_admin_order_actions_add_track');
        define('ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS', 'parcelpanel_admin_order_actions_add_track_order_status');

        define('ParcelPanel\OptionName\STATUS_SHIPPED', 'parcelpanel_status_shipped');

        define('ParcelPanel\OptionName\INTEGRATION_APP_ENABLED', 'parcelpanel_integration_app_enabled_%d');
    }

    /**
     * 定义一些数据表
     *
     * @author: Chuwen
     * @date  : 2021/7/20 18:16
     */
    private function define_tables()
    {
        global $wpdb;

        Table::$courier = "{$wpdb->prefix}parcelpanel_courier";
        Table::$tracking = "{$wpdb->prefix}parcelpanel_tracking";
        Table::$tracking_items = "{$wpdb->prefix}parcelpanel_tracking_items";
        Table::$location = "{$wpdb->prefix}parcelpanel_location";
    }

    /**
     * 初始化钩子方法
     *
     * @author: Chuwen
     * @date  : 2021/7/20 18:17
     */
    private function init_hooks()
    {
        // 激活插件事件
        add_action('activated_plugin', [$this, 'app_activated']);

        // 禁用插件事件
        add_action('deactivated_plugin', [$this, 'app_deactivated']);

        // 更新插件完成动作 升级程序完成时触发
        add_action('upgrader_process_complete', [Configs::class, 'update_plugin_complete']);

        // 卸载插件事件
        // register_uninstall_hook(__FILE__, [$this]);

        // Site initialization
        add_action('init', [$this, 'site_init']);

        add_action('admin_notices', [Notice::class, 'init']);

        // 管理后台添加菜单
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // 应用注册
        add_action('admin_init', [$this, 'register_app']);
        // 页面初始化
        add_action('admin_init', [$this, 'admin_init']);
        // 邮件预览
        add_action('admin_init', [Email::instance(), 'preview_emails']);

        add_action('load-parcelpanel_page_pp-shipments', [AdminShipments::instance(), 'load_shipments_table'], 0);
        add_filter('set_screen_option_parcelpanel_page_pp_shipments_per_page', [$this, 'set_screen_option'], 10, 3);

        add_action('rest_api_init', [$this, 'rest_api_init']);
        add_filter('determine_current_user', [RestApi::class, 'authenticate']);
        add_filter('rest_authentication_errors', [RestApi::class, 'authentication_fallback']);
        add_filter('rest_authentication_errors', [RestApi::class, 'check_authentication_error'], 15);

        // 订单发货详情邮件模块
        add_action('woocommerce_email_before_order_table', [Email::instance(), 'order_shipment_info'], 0, 4);
        add_action('parcelpanel_email_order_details', [Email::instance(), 'shipment_email_order_details'], 10, 5);

        // 在页脚插入一些东西
        add_action('admin_footer', [$this, 'footer_function']);
        add_action('admin_footer', [$this, 'deactivate_scripts']);

        add_action('post_updated', [$this, 'post_updated_track_page'], 10, 3);

        // 在 WooCommerce 的"订单页面"添加 meta box
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        // 保存 shop_order 时保存 meta box
        add_action('woocommerce_process_shop_order_meta', [ShopOrder::instance(), 'save_meta_box'], 0, 2);


        // 新订单钩子
        add_action('woocommerce_new_order', [ShopOrder::instance(), 'new_order'], 50);
        add_action('woocommerce_update_order', [ShopOrder::instance(), 'wc_update_order'], 99);
        // 删除文章钩子
        add_action('deleted_post', [ShopOrder::class, 'delete_shop_order'], 10, 2);
        // 删除订单钩子
        add_action('woocommerce_delete_order', [ShopOrder::class, 'delete_shop_order']);

        // hook for when order status is changed
        add_filter('woocommerce_email_classes', [$this, 'init_custom_emails']);
        add_filter('woocommerce_email_actions', [$this, 'register_custom_email_actions'], 10);

        // 订单列表筛选
        add_action('restrict_manage_posts', [ShopOrder::instance(), 'filter_orders_by_shipment_status'], 20);
        add_filter('request', [ShopOrder::instance(), 'filter_orders_by_shipment_status_query']);

        // 订单列表自定义列
        add_filter('manage_edit-shop_order_columns', [ShopOrder::instance(), 'add_shop_order_columns_header'], 20);
        add_action('manage_shop_order_posts_custom_column', [ShopOrder::instance(), 'render_shop_order_columns']);

        add_filter('woocommerce_admin_order_actions', [ShopOrder::instance(), 'admin_order_actions'], 100, 2);

        // rename order status, rename bulk action, rename filter
        add_filter('wc_order_statuses', [$this, 'wc_renaming_order_status']);
        add_filter('woocommerce_register_shop_order_post_statuses', [$this, 'filter_woocommerce_register_shop_order_post_statuses'], 10);
        add_filter('bulk_actions-edit-shop_order', [$this, 'modify_bulk_actions'], 50);

        // register order status
        add_action('init', [$this, 'register_partial_shipped_order_status']);
        // add status after completed
        add_filter('wc_order_statuses', [$this, 'add_partial_shipped_to_order_statuses']);
        // Custom Statuses in admin reports
        add_filter('woocommerce_reports_order_statuses', [$this, 'include_partial_shipped_order_status_to_reports'], 20);
        // for automate woo to check order is paid
        add_filter('woocommerce_order_is_paid_statuses', [$this, 'partial_shipped_woocommerce_order_is_paid_statuses']);
        add_filter('woocommerce_order_is_download_permitted', [$this, 'add_partial_shipped_to_download_permission'], 10, 2);
        // add bulk action
        add_filter('bulk_actions-edit-shop_order', [$this, 'add_bulk_actions_partial_shipped'], 50);
        // add reorder button
        add_filter('woocommerce_valid_order_statuses_for_order_again', [$this, 'add_reorder_button_partial_shipped'], 50);


        // 用户订单页 Action 按钮
        add_filter('woocommerce_my_account_my_orders_actions', [
            UserTrackPage::instance(),
            'add_column_my_account_orders_pp_track_column',
        ], 10, 2);


        // Track Page Assets
        add_action('wp_enqueue_scripts', [UserTrackPage::instance(), 'enqueue_scripts']);


        // 单号同步任务
        add_action('parcelpanel_tracking_sync', [TrackingNumber::class, 'sync_tracking'], 10, 2);

        // 单号运输商同步任务
        add_action('parcelpanel_tracking_courier_sync', [TrackingNumber::class, 'sync_tracking_courier']);

        // 运输商同步任务
        add_action('parcelpanel_update_courier_list', [Courier::instance(), 'update_courier_list']);

        // 订单同步
        add_action('parcelpanel_order_sync', [ShopOrder::instance(), 'sync_order']);
        add_action('parcelpanel_order_updated', [ShopOrder::instance(), 'order_updated']);

        $this->init_app_1001_integration();
        $this->init_app_1002_integration();
        $this->init_app_1003_integration();

        $plugin_basename = plugin_basename(\ParcelPanel\PLUGIN_FILE);
        add_filter("plugin_action_links_{$plugin_basename}", [$this, 'plugin_action_links']);

        // wc change order name do
        add_filter("woocommerce_admin_settings_sanitize_option", [$this, 'wc_setting_action'], 10, 3);
        // add_action("woocommerce_update_option", [$this, 'wc_setting_action'], 10, 1);
        // add_action("update_option", [$this, 'wc_setting_action'], 10, 3);

        if (\ParcelPanel\DEBUG) {
            $this->register_debug_hooks();
        }
    }

    public function wc_setting_action($value, $option, $raw_value)
    {
        // var_dump($option);
        // die;
        $check = $option['id'] ?? '';
        $checkArr = [
            'wt_sequencial_settings_page', // Sequential Order Numbers for WooCommerce   By WebToffee |
            'alg_wc_custom_order_numbers_options', // Custom Order Numbers for WooCommerce
            'wcj_order_numbers_module_options', // Booster for WooCommerce
        ];
        if (in_array($check, $checkArr)) {
            Api::sync_orders(90, 10);
        }
        return $value;
    }

    private $delete_list = [];
    private $add_list = [];

    public function __destruct()
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;
        $exec = false;
        $add_list = array_filter($this->add_list, function ($item) {
            return $item['success'] ?? false;
        });
        $delete_list = array_filter($this->delete_list, function ($item) {
            return $item['success'] ?? false;
        });

        if ($delete_list) {
            $tracking_numbers = array_values(array_column($delete_list, 'tracking_number'));
            $placeholder = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_numbers);
            $tracking_id_and_numbers = (array)$wpdb->get_results($wpdb->prepare(
                "SELECT id,tracking_number FROM {$TABLE_TRACKING} WHERE tracking_number IN ({$placeholder})",
                $tracking_numbers
            ));
            $tracking_number_tracking_id = array_column($tracking_id_and_numbers, 'id', 'tracking_number');
            foreach ($delete_list as $item) {
                if (!isset($tracking_number_tracking_id[$item['tracking_number']])) {
                    continue;
                }

                $wpdb->delete($TABLE_TRACKING_ITEMS, [
                    'order_id' => $item['order_id'],
                    'order_item_id' => $item['order_item_id'],
                    'quantity' => 0,
                    'tracking_id' => $tracking_number_tracking_id[$item['tracking_number']],
                ]);

                $exec = true;
            }
        }

        $tracking_numbers = [];
//		$duplicate_filter = [];
//		foreach ( $this->add_list as $key => $item ) {
//			$order_id        = $item['order_id'];
//			$tracking_number = $item['tracking_number'];
//			if ( ! array_key_exists( $tracking_number, $duplicate_filter ) ) {
//				$duplicate_filter[ $tracking_number ] = $order_id;
//				continue;
//			}
//
//			if ( $duplicate_filter[ $tracking_number ] !== $order_id ) {
//				unset( $this->add_list[ $key ] );
//			}
//		}

        if (!$add_list) {
            if ($exec) {
                foreach ($delete_list as $item) {
                    ShopOrder::adjust_unfulfilled_shipment_items($item['order_id']);
                }
            }

            return;
        }

        $tracking_numbers = array_values(array_unique(array_column($add_list, 'tracking_number')));
        /* tracking numbers 入库 */
        /* 有新单号或需要更新单号 */
        $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_numbers);
        $SQL_RETRIEVE_TRACKINGS_BY_TRACKING_NUMBERS = <<<SQL
SELECT id,tracking_number
FROM {$TABLE_TRACKING}
WHERE tracking_number IN ({$placeholder_str})
SQL;
        $tracking_data = (array)$wpdb->get_results($wpdb->prepare(
            $SQL_RETRIEVE_TRACKINGS_BY_TRACKING_NUMBERS,
            $tracking_numbers
        ));
        $is_insert_tracking = false;
        $new_tracking_numbers = array_diff(
            $tracking_numbers,
            array_column($tracking_data, 'tracking_number')
        );
        $now = time();
        foreach ($new_tracking_numbers as $_tracking_number) {
            $tracking_item_data = ShopOrder::get_tracking_item_data($_tracking_number, null, $now);
            $res = $wpdb->insert($TABLE_TRACKING, $tracking_item_data);
            if (!is_wp_error($res)) {
                $_tracking_datum = $tracking_data[] = new \stdClass;
                $_tracking_datum->id = $wpdb->insert_id;
                $_tracking_datum->tracking_number = $_tracking_number;

                $is_insert_tracking = true;
            }
        }
        if ($is_insert_tracking) {
            TrackingNumber::schedule_tracking_sync_action();
        }
        $tracking_number_tracking_id = array_column($tracking_data, 'id', 'tracking_number');


        // 筛选允许处理的单号
        $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_data, '%d');
        $SQL_RETRIEVE_TRACKINGS_ORDER_IDS = <<<SQL
SELECT order_id,tracking_id
FROM {$TABLE_TRACKING_ITEMS}
WHERE tracking_id IN({$placeholder_str})
SQL;
        $trackings_order_ids = (array)$wpdb->get_results($wpdb->prepare(
            $SQL_RETRIEVE_TRACKINGS_ORDER_IDS,
            array_column($tracking_data, 'id')
        ));
        $tracking_id_order_id = array_column($trackings_order_ids, 'order_id', 'tracking_id');
        foreach ($add_list as $key => &$item) {
            if (!array_key_exists($item['tracking_number'], $tracking_number_tracking_id)) {
                unset($add_list[$key]);
                continue;
            }

            $item['tracking_id'] = $tracking_number_tracking_id[$item['tracking_number']];
        }
        unset($item);
        foreach ($add_list as $key => $item) {
            if (array_key_exists($item['tracking_id'], $tracking_id_order_id) && $tracking_id_order_id[$item['tracking_id']] != $item['order_id']) {
                unset($add_list[$key]);
            }
        }

        $add_list_order_ids_uniq = array_values(array_unique(array_column($add_list, 'order_id')));
        $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($add_list_order_ids_uniq, '%d');
        $SQL_RETRIEVE_SHIPMENTS_BY_ORDER_ID = <<<SQL
SELECT *
FROM {$TABLE_TRACKING_ITEMS}
WHERE order_id IN ({$placeholder_str})
SQL;
        $shipments = (array)$wpdb->get_results($wpdb->prepare(
            $SQL_RETRIEVE_SHIPMENTS_BY_ORDER_ID,
            $add_list_order_ids_uniq
        ));


        $shipments_by_tracking_id = array_column($shipments, null, 'tracking_id');
        foreach ($add_list as $tracking_datum) {
            $_shipment = null;
            if (array_key_exists($tracking_datum['tracking_id'], $shipments_by_tracking_id)) {
                $_shipment = $shipments_by_tracking_id[$tracking_datum['tracking_id']];
            }
            $_shipment_status = $_shipment->shipment_status ?? 1;
            $_custom_status_time = $_shipment->custom_status_time ?? '';
            $_custom_shipment_status = $_shipment->custom_shipment_status ?? 0;
            $wpdb->insert($TABLE_TRACKING_ITEMS, [
                'order_id' => $tracking_datum['order_id'],
                'order_item_id' => $tracking_datum['order_item_id'],
                'tracking_id' => $tracking_datum['tracking_id'],
                'shipment_status' => $_shipment_status,
                'custom_status_time' => $_custom_status_time,
                'custom_shipment_status' => $_custom_shipment_status,
            ]);

            $exec = true;
        }

        if ($exec) {
            foreach ($add_list_order_ids_uniq as $order_id) {
                ShopOrder::adjust_unfulfilled_shipment_items($order_id);
            }
        }
    }

    /**
     * Rename WooCommerce Order Status
     */
    public function wc_renaming_order_status($order_statuses)
    {
        $KEY_WC_COMPLETED = 'wc-completed';
        if (!AdminSettings::get_status_shipped_field()) {
            return $order_statuses;
        }

        if (array_key_exists($KEY_WC_COMPLETED, $order_statuses)) {
            $order_statuses[$KEY_WC_COMPLETED] = esc_html__('Shipped', 'parcelpanel');
        }

        return $order_statuses;
    }

    /**
     * define the woocommerce_register_shop_order_post_statuses callback
     * rename filter
     * rename from completed to shipped
     */
    public function filter_woocommerce_register_shop_order_post_statuses($array)
    {
        if (!AdminSettings::get_status_shipped_field()) {
            return $array;
        }

        if (isset($array['wc-completed'])) {
            /* translators: %s: replace with shipped order count */
            $array['wc-completed']['label_count'] = _n_noop(
                'Shipped <span class="count">(%s)</span>',
                'Shipped <span class="count">(%s)</span>',
                'parcelpanel'
            );
        }
        return $array;
    }

    /**
     * rename bulk action
     */
    public function modify_bulk_actions($bulk_actions)
    {
        if (!AdminSettings::get_status_shipped_field()) {
            return $bulk_actions;
        }

        if (isset($bulk_actions['mark_completed'])) {
            $bulk_actions['mark_completed'] = __('Change status to shipped', 'parcelpanel');
        }
        return $bulk_actions;
    }

    /**
     * Register new status : Partially Shipped
     */
    public function register_partial_shipped_order_status()
    {
        register_post_status('wc-partial-shipped', [
            'label' => __('Partially Shipped', 'parcelpanel'),
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            /* translators: %s: replace with Partially Shipped Count */
            'label_count' => _n_noop(
                'Partially Shipped <span class="count">(%s)</span>',
                'Partially Shipped <span class="count">(%s)</span>',
                'parcelpanel'
            ),
        ]);
    }

    /**
     * add status after completed
     */
    public function add_partial_shipped_to_order_statuses($order_statuses): array
    {
        $new_order_statuses = [];
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-completed' === $key) {
                $new_order_statuses['wc-partial-shipped'] = __('Partially Shipped', 'parcelpanel');
            }
        }
        return $new_order_statuses;
    }

    /**
     * Adding the partial-shipped order status to the default woocommerce order statuses
     */
    public function include_partial_shipped_order_status_to_reports($statuses)
    {
        if ($statuses) {
            $statuses[] = 'partial-shipped';
        }
        return $statuses;
    }

    /**
     * mark status as a paid.
     */
    public function partial_shipped_woocommerce_order_is_paid_statuses($statuses)
    {
        $statuses[] = 'partial-shipped';
        return $statuses;
    }

    /**
     * Give download permission to partial shipped order status
     */
    public function add_partial_shipped_to_download_permission($data, $order)
    {
        if ($order->has_status('partial-shipped')) {
            return true;
        }
        return $data;
    }

    /**
     * add bulk action
     * Change order status to Partially Shipped
     */
    public function add_bulk_actions_partial_shipped($bulk_actions)
    {
        $label = wc_get_order_status_name('partial-shipped');
        /* translators: %s: search order status label */
        $bulk_actions['mark_partial-shipped'] = sprintf(__('Change status to %s', 'parcelpanel'), $label);
        return $bulk_actions;
    }

    /**
     * add order again button for delivered order status
     */
    public function add_reorder_button_partial_shipped($statuses)
    {
        $statuses[] = 'partial-shipped';
        return $statuses;
    }


    /**
     * Register some hooks for debug mode
     */
    private function register_debug_hooks()
    {
        HooksTracker::init_track_hooks(function () {
            (new ParcelPanelFunction)->parcelpanel_log(json_encode([
                'http_path' => strstr(wc_clean($_SERVER['REQUEST_URI']), '?', true),
                'hooks' => HooksTracker::get_hooks(),
            ], 320));
        });
    }

    public function get_category()
    {
        $cateS = (new ParcelPanelFunction)->getCategory();
        (new ParcelPanelFunction)->parcelpanel_json_response($cateS, 'Saved successfully');
    }

    public function pp_get_token()
    {
        $api_key = get_option(\ParcelPanel\OptionName\API_KEY);
        (new ParcelPanelFunction)->parcelpanel_json_response(['token' => $api_key], 'Saved successfully');
    }

    /**
     * 初始化 Ajax 请求处理方法
     */
    private function init_ajax()
    {
        add_action('wp_ajax_pp_get_category', [$this, 'get_category']);
        add_action('wp_ajax_pp_get_token', [$this, 'pp_get_token']);

        // add_action( 'wp_ajax_pp_test', [ $this, 'wp_ajax_pp_test' ] );
        add_action('wp_ajax_pp_feedback_confirm', [$this, 'feedback_ajax']);
        add_action('wp_ajax_pp_deactivate_survey', [$this, 'deactivate_survey_ajax']);

        // add_action( 'wp_ajax_pp_tracking_save_form', [ ShopOrder::instance(), 'save_meta_box_ajax' ] );
        // add_action( 'wp_ajax_pp_tracking_delete_form', [ ShopOrder::instance(), 'delete_meta_box_ajax' ] );
        add_action('wp_ajax_pp_shipment_item_save', [ShopOrder::instance(), 'shipment_item_save_ajax']);
        add_action('wp_ajax_pp_get_tracking_items', [ShopOrder::instance(), 'get_tracking_items_ajax']);
        add_action('wp_ajax_pp_delete_tracking_item', [ShopOrder::instance(), 'shipment_item_delete_ajax']);
        // add_action( 'wp_ajax_pp_save_shipped_label', [ ShopOrder::instance(), 'save_shipped_label_ajax' ] );

        add_action('wp_ajax_pp_resync', [AdminShipments::instance(), 'resync_ajax']);
        add_action('wp_ajax_pp_check_first_sync', [AdminShipments::instance(), 'check_first_sync_ajax']);

        add_action('wp_ajax_pp_enable_dropshipping_mode', [AdminHome::instance(), 'enable_dropshipping_mode_ajax']);

        add_action('wp_ajax_pp_upload_csv', [Upload::instance(), 'csv_handler']);
        add_action('wp_ajax_pp_mapping_items_csv', [TrackingNumber::instance(), 'get_csv_mapping_items_ajax']);
        add_action('wp_ajax_pp_import_csv', [TrackingNumber::instance(), 'csv_importer']);
        add_action('wp_ajax_pp_tracking_number_import_record', [TrackingNumber::instance(), 'get_records_ajax']);
        add_action('wp_ajax_pp_get_current_user', [$this, 'get_current_user']);

        add_action('wp_ajax_pp_export_csv', [AdminShipments::instance(), 'export_csv_ajax']);

        add_action('wp_ajax_pp_tracking_page_save', [AdminTrackingPage::instance(), 'save_settings_ajax']);

        add_action('wp_ajax_pp_settings_save', [AdminSettings::instance(), 'save_settings_ajax']);
        add_action('wp_ajax_pp_courier_matching_save', [AdminSettings::instance(), 'save_courier_matching_ajax']);

        add_action('wp_ajax_pp_get_plan', [AdminAccount::instance(), 'get_plan_ajax']);
        add_action('wp_ajax_pp_drop_free_ajax', [AdminAccount::instance(), 'drop_free_ajax']);
        add_action('wp_ajax_pp_change_plan', [AdminAccount::instance(), 'change_plan_ajax']);
        add_action('wp_ajax_pp_get_plan_link', [AdminAccount::instance(), 'get_plan_link_ajax']);

        add_action('wp_ajax_nopriv_pp_track_info', [UserTrackPage::instance(), 'get_track_info_ajax']);
        add_action('wp_ajax_pp_track_info', [UserTrackPage::instance(), 'get_track_info_ajax']);

        // 更新运输商
        add_action('wp_ajax_pp_couriers_update', [Courier::instance(), 'update_courier_list_ajax']);

        // 连接 ParcelPanel 服务端
        add_action('wp_ajax_pp_connect', [$this, 'connect_endpoint_ajax']);
        add_action('wp_ajax_pp_version_upgrade', [$this, 'version_upgrade_ajax']);
        add_action('wp_ajax_pp_popup_action', [$this, 'popup_action_ajax']);

        // 网站 与 ParcelPanel 账号进行绑定
        add_action('wp_ajax_pp_bind', [$this, 'bind_account_ajax']);

        add_action('wp_ajax_pp_live_chat_connect', [$this, 'live_chat_connect_ajax']);
        add_action('wp_ajax_pp_live_chat_disable', [$this, 'live_chat_disable_ajax']);

        add_action('wp_ajax_pp_change_custom_order_status', [AdminShipments::instance(), 'set_custom_shipment_status_ajax']);
        add_action('wp_ajax_pp_updated_orders_send_email', [AdminShipments::instance(), 'updated_orders_send_email_ajax']);

        add_action('wp_ajax_pp_integration_switch', [AdminIntegration::instance(), 'switch_integration_ajax']);
    }

    function get_current_user()
    {
        $current_user = wp_get_current_user();
        $res = [
            'current_user' => $current_user
        ];
        (new ParcelPanelFunction)->parcelpanel_json_response($res);
    }

    function wp_ajax_pp_test()
    {

        global $wpdb;

        $comment_ID = 10;
        $SQL_UPDATE_TRACKING = <<<SQL
SELECT * FROM wp_comments where comment_ID > {$comment_ID} LIMIT 10
SQL;

        $shipments = (array)$wpdb->get_results($wpdb->prepare(
            $SQL_UPDATE_TRACKING
        ));
        $from = 1003;

        foreach ($shipments as $comment) {

            $comment_type = $comment->comment_type;
            $content = $comment->comment_content;
            $order_id = $comment->comment_post_ID;

            if (!$content || $comment_type !== 'order_note') {
                continue;
            }

            $tracking_number_matches = [];
            $res_match_tracking_number = preg_match('/Tracking number: (.*)/', $content, $tracking_number_matches);
            if (!$res_match_tracking_number) {
                continue;
            }

            $item_id_matches = [];
            $res_match_item_id = preg_match('/Line item ID: (.*)/', $content, $item_id_matches);
            if (!$res_match_item_id) {
                continue;
            }

            $tracking_number = $tracking_number_matches[1];
            $line_item_ids = array_map('intval', explode(',', $item_id_matches[1]));

            foreach ($line_item_ids as $item_id) {
                if (!$item_id) {
                    continue;
                }

                $this->add_list[] = [
                    'tracking_number' => $tracking_number,
                    'order_id' => $order_id,
                    'order_item_id' => $item_id,
                    'from' => $from,
                    'success' => true,
                ];
            }

        }

    }

    function rest_api_init()
    {
        $rest_api = new RestApi();
        $rest_api->register_routes();
    }

    /**
     * 短标签声明
     */
    function init_shortcode()
    {
        add_shortcode('pp-track-page', [UserTrackPage::instance(), 'track_page_function']);
    }

    private function update_tables()
    {
        global $wpdb;

        $db_version = get_option(\ParcelPanel\OptionName\DB_VERSION);

        if (version_compare($db_version, \ParcelPanel\DB_VERSION, '<')) {
            $collate = $wpdb->get_charset_collate();

            $TABLE_COURIER = Table::$courier;
            $TABLE_TRACKING = Table::$tracking;
            $TABLE_LOCATION = Table::$location;
            $TABLE_TRACKING_ITEMS = Table::$tracking_items;

            $TABLE = <<<SQL
CREATE TABLE {$TABLE_COURIER} (
`code` varchar(191) NOT NULL DEFAULT '',
`name` varchar(191) NOT NULL DEFAULT '',
`country_code` char(4) NOT NULL DEFAULT '',
`tel` varchar(50) NOT NULL DEFAULT '',
`logo` varchar(191) NOT NULL DEFAULT '',
`track_url` varchar(1000) NOT NULL DEFAULT '',
`sort` smallint(6) NOT NULL DEFAULT '9999',
`updated_at` int(10) unsigned NOT NULL DEFAULT '0',
UNIQUE KEY `code` (`code`)
) $collate;
CREATE TABLE {$TABLE_TRACKING} (
`id` int(11) NOT NULL AUTO_INCREMENT,
`order_id` bigint(20),
`tracking_number` varchar(50) NOT NULL DEFAULT '',
`courier_code` varchar(191) NOT NULL DEFAULT '',
`shipment_status` tinyint(3) NOT NULL DEFAULT '1',
`last_event` text,
`original_country` varchar(10) NOT NULL DEFAULT '',
`destination_country` varchar(10) NOT NULL DEFAULT '',
`origin_info` text,
`destination_info` text,
`trackinfo` text,
`transit_time` tinyint(4) DEFAULT '0',
`stay_time` tinyint(4) DEFAULT '0',
`sync_times` tinyint(4) NOT NULL DEFAULT '0',
`received_times` tinyint(4) NOT NULL DEFAULT '0',
`fulfilled_at` int(10) unsigned NOT NULL DEFAULT '0',
`updated_at` int(10) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
UNIQUE KEY `tracking_number` (`tracking_number`),
INDEX `order_id` (`order_id`),
INDEX `shipment_status` (`shipment_status`)
) $collate;
CREATE TABLE {$TABLE_LOCATION} (
`id` char(32) NOT NULL,
`data` text,
`expired_at` int(10) unsigned NOT NULL DEFAULT '0',
`updated_at` int(10) unsigned NOT NULL DEFAULT '0',
UNIQUE KEY `id` (`id`)
) $collate;
CREATE TABLE {$TABLE_TRACKING_ITEMS} (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`order_id` bigint(20) unsigned NOT NULL,
`order_item_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`quantity` smallint(5) unsigned NOT NULL DEFAULT '0',
`tracking_id` int(10) unsigned NOT NULL DEFAULT '0',
`shipment_status` tinyint(1) unsigned NOT NULL DEFAULT '1',
`custom_shipment_status` smallint(5) unsigned NOT NULL DEFAULT '0',
`custom_status_time` varchar(191) NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
KEY `tracking_id` (`tracking_id`),
KEY `order_id` (`order_id`),
KEY `shipment_status` (`shipment_status`)
) $collate;
SQL;

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($TABLE);
        }

        if (version_compare($db_version, '1', '<')) {

            // 稍后更新运输商列表
            (new ParcelPanelFunction)->parcelpanel_schedule_single_action('parcelpanel_update_courier_list', 5);

            // initialize default configuration
            update_option(\ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON, 1);
            update_option(\ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION, 1);
            update_option(\ParcelPanel\OptionName\TRACK_BUTTON_ORDER_STATUS, ['wc-processing', 'wc-completed', 'wc-partial-shipped', 'wc-checkout-draft', 'wc-failed', 'wc-refunded', 'wc-cancelled']);
            update_option(\ParcelPanel\OptionName\TRACKING_SECTION_ORDER_STATUS, ['wc-processing', 'wc-completed', 'wc-partial-shipped']);

            // email notifications
            foreach (array_keys(AdminSettings::EMAIL_DEFAULT) as $order_status) {
                $option = get_option("woocommerce_customer_pp_{$order_status}_shipment_settings");
                $option['enabled'] = 'yes';
                update_option("woocommerce_customer_pp_{$order_status}_shipment_settings", $option);
            }

            update_option(\ParcelPanel\OptionName\DB_VERSION, '1');
        }

        if (version_compare($db_version, '1.2.0', '<')) {

            $TABLE_TRACKING = Table::$tracking;

            $wpdb->query("ALTER TABLE {$TABLE_TRACKING} ADD order_item_id bigint(20) unsigned DEFAULT 0 AFTER order_id");

            update_option(\ParcelPanel\OptionName\DB_VERSION, '1.2.0');
        }

        foreach (self::$db_updates as $version => $update_callbacks) {
            if (version_compare($db_version, $version, '<')) {
                foreach ($update_callbacks as $update_callback) {
                    $this->run_update_callback($update_callback);
                }
            }
        }
    }

    private function run_update_callback($update_callback)
    {
        include_once dirname(__FILE__) . '/update-functions.php';

        if (is_callable($update_callback)) {
            call_user_func($update_callback);
        }
    }

    #########################
    #########################

    /**
     * 加载文本域
     *
     * @author: Chuwen
     * @date  : 2021/7/23 14:26
     */
    private function load_plugin_textdomain()
    {

        // pp lang ID
        define('ParcelPanel\OptionName\PP_LANG_NOW', 'parcelpanel_pp_wc_admin_language');
        add_filter('plugin_locale', function ($determined_locale, $domain) {
            if ($domain !== 'parcelpanel') {
                return $determined_locale;
            }
            return Common::instance()->getNowLang();
        }, 10, 2);

        $locale = apply_filters('plugin_locale', get_locale(), 'parcelpanel');

        // 加载系统的文本域
        load_textdomain('parcelpanel', WP_LANG_DIR . '/plugins/parcelpanel-' . $locale . '.mo');

        // 加载插件的文本域
        load_plugin_textdomain('parcelpanel', false, (new ParcelPanelFunction)->parcelpanel_get_plugin_base_path('/l10n/languages'));
    }

    /**
     * Init Admin Page.
     *
     * @return void
     */
    public function ParcelPanel_admin_page()
    {
        require_once plugin_dir_path(plugin_dir_path(__FILE__)) . 'templates/app.php';
    }

    /**
     * 在后台添加菜单
     *
     * @author: Chuwen
     * @date  : 2021/7/21 09:21
     */
    function add_admin_menu()
    {
        global $submenu;

        add_menu_page(
            __('Home - ParcelPanel', 'parcelpanel'),  // 页面标题
            __('ParcelPanel', 'parcelpanel'),  // 菜单标题
            'manage_woocommerce',  // 需要什么权限才能访问
            \ParcelPanel\PP_MENU_SLAG,  // menu_slug
            [$this, 'ParcelPanel_admin_page'],  // function   [$this, 'create_admin_page']
            (new ParcelPanelFunction)->get_dir_path('assets', 'imgs/wp-logo.svg?time=' . time()),
            25 // 菜单位置
        );


        $is_unlimited_plan = get_option(\ParcelPanel\OptionName\IS_UNLIMITED_PLAN);
        $account_menu_title = $is_unlimited_plan ? 'Account' : '<span class="dashicons dashicons-star-filled" style="font-size:17px"></span> ' . __('100% OFF Offer', 'parcelpanel');

        $sub_menu_list = [
            [
                'page_title' => __('Home - ParcelPanel', 'parcelpanel'),
                'menu_title' => __('Home', 'parcelpanel'),
                'menu_slug' => 'pp-home',
                'function' => [$this, 'ParcelPanel_admin_page'],
            ],
            [
                'page_title' => __('Tracking Page - ParcelPanel', 'parcelpanel'),
                'menu_title' => __('Tracking Page', 'parcelpanel'),
                'menu_slug' => 'pp-home#/pp-tracking-page',
                'function' => [$this, 'ParcelPanel_admin_page'],
            ],
            [
                'page_title' => __('Shipments - ParcelPanel', 'parcelpanel'),
                'menu_title' => __('Shipments', 'parcelpanel'),
                'menu_slug' => 'pp-home#/pp-shipments',
                'function' => [$this, 'ParcelPanel_admin_page'],
            ],
            [
                'page_title' => __('Settings - ParcelPanel', 'parcelpanel'),
                'menu_title' => __('Settings', 'parcelpanel'),
                'menu_slug' => 'pp-home#/pp-settings',
                'function' => [$this, 'ParcelPanel_admin_page'],
            ],
            [
                'page_title' => __('Integration - ParcelPanel', 'parcelpanel'),
                'menu_title' => __('Integration', 'parcelpanel') . ' <svg style="vertical-align:top" width="36" height="20" viewBox="0 0 36 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="36" height="20" rx="4" fill="#F36A5A"/><path d="M7.76758 14L7.76758 8.53613H7.8457L11.6689 14H12.5234V6.9541H11.6543V12.4375H11.5762L7.75293 6.9541H6.89844L6.89844 14H7.76758ZM18.6855 13.209H15.1992V10.7871H18.5049V10.0059H15.1992V7.74512H18.6855V6.9541H14.3203V14H18.6855V13.209ZM24.1885 8.43848H24.2471L25.8682 14H26.6982L28.6172 6.9541H27.6992L26.2979 12.6816H26.2393L24.6621 6.9541H23.7734L22.1963 12.6816H22.1377L20.7363 6.9541H19.8184L21.7373 14L22.5674 14L24.1885 8.43848Z" fill="white"/></svg>',
                'menu_slug' => 'pp-home#/pp-integration',
                'function' => [$this, 'ParcelPanel_admin_page'],
            ],
            [
                'page_title' => __('Account - ParcelPanel', 'parcelpanel'),
                'menu_title' => $account_menu_title,
                'menu_slug' => 'pp-home#/pp-account',
                'function' => [$this, 'ParcelPanel_admin_page'],
            ],
        ];

        foreach ($sub_menu_list as $item) {
            // 添加子菜单
            (new ParcelPanelFunction)->parcelpanel_add_submenu_page(
                $item['page_title'],
                $item['menu_title'],
                'manage_woocommerce',  // 需要什么权限才能访问
                $item['menu_slug'],
                $item['function']
            );
        }

        // 6: Account Page
        $submenu['pp-admin'][6][4] = $is_unlimited_plan ? '' : 'pp-wp-submenu-item-strong';

        // 移除"ParcelPanel"子菜单，如果不这么做，子菜单会出现一个同名的"ParcelPanel"
        // 此方法要在 add_pp_menu_page 调用之后才能使用
        remove_submenu_page(\ParcelPanel\PP_MENU_SLAG, \ParcelPanel\PP_MENU_SLAG);

    }

    /**
     * Add action links for the ParcelPanel plugin.
     *
     * @param array $actions Plugin actions.
     *
     * @return array
     */
    public function plugin_action_links($actions): array
    {
        $links = [
            '<a href="https://docs.parcelpanel.com/woocommerce?utm_source=plugin_listing" target="_blank">' . esc_html__('Docs', 'parcelpanel') . '</a>',
            '<a href="https://wordpress.org/support/plugin/parcelpanel/" target="_blank">' . __('Support', 'parcelpanel') . '</a>',
        ];

        return array_merge($links, $actions);
    }

    /**
     * 页面初始化
     *
     * @author: Chuwen
     * @date  : 2021/7/21 09:54
     */
    function admin_init()
    {
        // 注册资源
        $this->admin_register_assets();

        // 载入资源
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    /**
     * 网站初始化
     */
    function site_init()
    {
        // 资源加载
        // $this->site_register_assets();

        // 更新配置信息
        $this->update_settings();
    }

    function update_settings()
    {
        // Check if we are not already running this routine.
        if ('yes' === get_transient('parcelpanel_update_setting')) {
            return;
        }

        // If we made it till here nothing is running yet, lets set the transient now.
        set_transient('parcelpanel_update_setting', 'yes', MINUTE_IN_SECONDS * 60 * 4);

        // 稍后更新配置信息
        (new ParcelPanelFunction)->parcelpanel_update_setting_action();

    }

    function add_id_to_script($tag, $handle, $src)
    {
        if ('parcelpanel-script' === $handle) {
            $tag = '<script type="module" src="' . (new ParcelPanelFunction)->get_dir_path('dist', 'index.js?var=' . VERSION) . '" ></script>';
        }

        return $tag;
    }

    /**
     * 为相应页面加载对应的资源
     */
    function admin_enqueue_scripts()
    {
        global $post;

        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        wp_enqueue_style('parcelpanel-admin');

        if ('parcelpanel_page_pp-home' == $screen_id) {
            // 加载 react 打包文件
            wp_enqueue_style('parcelpanel-style',
                (new ParcelPanelFunction)->get_dir_path('dist', 'index.css'), [], VERSION);

            wp_enqueue_script('parcelpanel-script',
                (new ParcelPanelFunction)->get_dir_path('dist', 'index.js'),
                array('wp-element'), VERSION, true);
            add_filter('script_loader_tag', [$this, 'add_id_to_script'], 10, 3);

            $langMessage = (new ParcelPanelFunction)->getAdminLangList();
            $categoryMessage = (new ParcelPanelFunction)->getCategory();

            // 添加对应语言信息
            $preview_email_url = add_query_arg('_wpnonce', wp_create_nonce('pp-preview-mail'), admin_url('?pp_preview_mail=1'));
            wp_localize_script('parcelpanel-script', 'ppCommonData', [
                'path' => "/wp-content/plugins/parcelpanel/dist",
                'langMessage' => $langMessage,
                'categoryList' => $categoryMessage,
                'token' => get_option(\ParcelPanel\OptionName\API_KEY),
                'preview_email_url' => $preview_email_url,
            ]);

            $pp_param = [
                'import_template_file_link' => (new ParcelPanelFunction)->parcelpanel_get_assets_path('templates/sample-template.csv'),
                'upload_nonce' => wp_create_nonce('pp-upload-csv'),
                'import_nonce' => wp_create_nonce('pp-import-csv-tracking-number'),
                'get_history_nonce' => wp_create_nonce('pp-get-import-tracking-number-records'),
                'export_nonce' => wp_create_nonce('pp-export-csv'),
                'resync_nonce' => wp_create_nonce('pp-resync'),
                'ajax_nonce' => wp_create_nonce('pp-ajax'),
                'shipments_page_link' => (new ParcelPanelFunction)->parcelpanel_get_admin_shipments_url(),
                'pp_bind_account' => wp_create_nonce('pp-bind-account'),
                'pp_update_plan' => wp_create_nonce('pp-get-plan'),
            ];

            wp_localize_script('parcelpanel-script', 'pp_param', $pp_param);

        }

        // 如果是在我们这边的后台页面
        // 就添加个公共头部
        if ((new ParcelPanelFunction)->is_parcelpanel_plugin_page()) {

            // 公共样式
            wp_enqueue_style('pp-admin');

            // 在 body 添加 class
            add_filter('admin_body_class', [__CLASS__, 'add_admin_body_classes']);

            // 引入 Toast 插件
            wp_enqueue_style('pp-toastr');
            wp_enqueue_script('pp-toastr');

            // 加载公共脚本
            wp_enqueue_script('pp-common');

            $plugin_version = get_option(\ParcelPanel\OptionName\PLUGIN_VERSION);

            $free_upgrade_opened_at = intval(get_option('parcelpanel_free_upgrade_opened_at')) ?: time();
            $free_upgrade_last_popup_date = get_user_option('parcelpanel_free_upgrade_last_popup_date') ?: '';
            $is_unlimited_plan = get_option(\ParcelPanel\OptionName\IS_UNLIMITED_PLAN);

            wp_localize_script('pp-common', 'parcelpanel_param', [
                'site_status' => [
                    'is_offline_mode' => (new ParcelPanelFunction)->parcelpanel_is_local_site(),
                    'is_connected' => (new ParcelPanelFunction)->parcelpanel_is_connected(),
                    'is_upgraded' => !version_compare($plugin_version, \ParcelPanel\VERSION),
                ],
                'connect_server_nonce' => wp_create_nonce('pp-connect-parcelpanel'),
                'version_upgrade_nonce' => wp_create_nonce('pp-version-upgrade'),
                'popup' => [
                    'server_time' => time(),
                    'opened_at' => $free_upgrade_opened_at,
                    'last_popup_date' => $free_upgrade_last_popup_date,
                    'is_show' => $is_unlimited_plan != '1',
                    'nonce' => wp_create_nonce('pp-popup'),
                ],
                'live_chat' => [
                    'enabled' => !!get_user_option('parcelpanel_live_chat_enabled_at'),
                    'nonce' => wp_create_nonce('pp-load-live-chat'),
                ],
            ]);
        }

        if ('shop_order' === $screen->post_type) {

            wp_enqueue_style('pp-admin-wc');
            wp_enqueue_script('pp-admin-wc');

            wp_localize_script('pp-admin-wc', 'parcelpanel_admin', [
                'strings' => [
                    'import_tracking_number' => __('Import tracking number', 'parcelpanel'),
                    'import_records' => sprintf(__('%1$s Uploaded %2$s, %3$s tracking numbers, failed to upload %4$s,', 'parcelpanel'), '${date}', '${filename}', '${total}', '${failed}'),
                    'view_details' => __('view details.', 'parcelpanel'),
                ],
                'urls' => [
                    'import_tracking_number' => (new ParcelPanelFunction)->parcelpanel_get_admin_home_url() . '#/pp-import',
                ],
            ]);

            // 引入 Toast 插件
            wp_enqueue_style('pp-toastr');
            wp_enqueue_script('pp-toastr');


            $params = ShopOrder::instance()->get_admin_wc_meta_boxes_params();
            $params['post_id'] = $post->ID ?? '';
            wp_localize_script('pp-admin-wc', 'parcelpanel_admin_wc_meta_boxes', $params);


            $courier_list = array_values(get_object_vars((new ParcelPanelFunction)->parcelpanel_get_courier_list('ASC')));
            wp_localize_script('pp-admin-wc', 'parcelpanel_courier_list', $courier_list);
        }

        if ($screen_id == 'plugins') {
            wp_enqueue_style('pp-admin-plugins');
        }
    }

    /**
     * 添加 Meta Box
     */
    function add_meta_boxes()
    {
        add_meta_box(
            'pp-wc-shop_order-shipment-tracking',
            __('Parcel Panel', 'parcelpanel') . (time() < 1662768000 ? '<span class="parcelpanel-new-badge"></span>' : ''),
            [ShopOrder::instance(), 'meta_box_tracking'],
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * 初始化自定义邮件
     *
     * @author Mark
     * @date   2021/8/2 15:20
     */
    function init_custom_emails($emails)
    {
        $emails['WC_Email_Customer_PP_Partial_Shipped_Order'] = new Emails\WC_Email_Customer_PP_Partial_Shipped_Order();
        $emails['WC_Email_Customer_PP_In_Transit'] = new Emails\WC_Email_Customer_PP_In_Transit();
        $emails['WC_Email_Customer_PP_Out_For_Delivery'] = new Emails\WC_Email_Customer_PP_Out_For_Delivery();
        $emails['WC_Email_Customer_PP_Delivered'] = new Emails\WC_Email_Customer_PP_Delivered();
        $emails['WC_Email_Customer_PP_Exception'] = new Emails\WC_Email_Customer_PP_Exception();
        $emails['WC_Email_Customer_PP_Failed_Attempt'] = new Emails\WC_Email_Customer_PP_Failed_Attempt();

        return $emails;
    }

    public function register_custom_email_actions($actions)
    {
        return array_merge(
            $actions,
            [
                'woocommerce_order_status_partial-shipped',
            ]
        );
    }


    /**
     * 应用激活
     */
    function app_activated($filename)
    {

        $checkArr = [
            'woocommerce-sequential-order-numbers.php',
            'custom-order-numbers-for-woocommerce.php',
        ];
        $check = explode('/', $filename);
        if (!empty($check[1]) && in_array($check[1], $checkArr)) {
            // var_dump($filename, 111);
            // die;
            Api::sync_orders(90, 10);
        }


        if ('/parcelpanel.php' !== substr($filename, -16)) {
            return;
        }

        // 创建 Track 页
        ParcelPanel::create_track_page();

        // 稍后更新运输商列表
        (new ParcelPanelFunction)->parcelpanel_schedule_single_action('parcelpanel_update_courier_list', 5);

        delete_metadata('user', 0, 'parcelpanel_api_key', '', true);
        Api::check_api_key();
        
        $from = function () {
            if (strpos($_SERVER['HTTP_REFERER'] ?? '', 'wp-admin/plugins.php') !== false) {
                return 'plugin';  // 来自已安装的插件  安装
            } elseif (strpos($_SERVER['HTTP_REFERER'] ?? '', 'wp-admin/plugin-install.php') !== false) {
                return 'store';  // 来自插件商店  安装
            }

            return 'unknown';
        };

        // 跳转到插件 Home 页面
        wp_safe_redirect(admin_url('admin.php?' . http_build_query([
                    'page' => 'pp-home',
                    'active' => 'true',
                    'from' => $from(),
                ])
            )
        );
        die;
    }

    /**
     * 应用禁用
     */
    function app_deactivated($filename)
    {


        // woocommerce-sequential-order-numbers/woocommerce-sequential-order-numbers.php // Sequential Order Numbers for WooCommerce   By SkyVerge |
        // wt-woocommerce-sequential-order-numbers/wt-advanced-order-number.php // Sequential Order Numbers for WooCommerce   By WebToffee |
        // custom-order-numbers-for-woocommerce/custom-order-numbers-for-woocommerce.php // Custom Order Numbers for WooCommerce
        // woocommerce-jetpack/woocommerce-jetpack.php // Booster for WooCommerce
        // wp-lister-for-amazon/wp-lister-amazon.php
        // wp-lister-for-ebay/wp-lister.php
        $checkArr = [
            'woocommerce-sequential-order-numbers.php',
            'wt-advanced-order-number.php',
            'custom-order-numbers-for-woocommerce.php',
            'woocommerce-jetpack.php',
            'wp-lister-amazon.php',
            'wp-lister.php',
        ];
        $check = explode('/', $filename);
        if (!empty($check[1]) && in_array($check[1], $checkArr)) {
            // var_dump($filename, 111);
            // die;
            Api::sync_orders(90, 10);
        }

        if ('/parcelpanel.php' !== substr($filename, -16)) {
            return;
        }

        Api::deactivate();
    }

    /**
     * 注册应用
     */
    function register_app()
    {
        if (version_compare(get_option(\ParcelPanel\OptionName\DB_VERSION), \ParcelPanel\DB_VERSION, '<')) {
            // Check if we are not already running this routine.
            if ('yes' === get_transient('parcelpanel_installing')) {
                return;
            }

            // If we made it till here nothing is running yet, lets set the transient now.
            set_transient('parcelpanel_installing', 'yes', MINUTE_IN_SECONDS * 10);

            // 创建数据表
            $this->update_tables();

            delete_transient('parcelpanel_installing');
        }


        /* init admin order actions order status */
        if (!is_array(get_option(\ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS))) {
            $wc_status = ['wc-processing', 'wc-completed', 'wc-partial-shipped', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-checkout-draft'];
            update_option(\ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS, $wc_status);
        }
    }


    /**
     * 连接 ParcelPanel 服务端 ajax
     */
    function connect_endpoint_ajax()
    {
        check_ajax_referer('pp-connect-parcelpanel');

        $last_attempt_connect_at = (int)get_option(\ParcelPanel\OptionName\LAST_ATTEMPT_CONNECT_AT);
        if (time() <= $last_attempt_connect_at + 15) {
            (new ParcelPanelFunction)->parcelpanel_json_response([
                'is_connected' => (new ParcelPanelFunction)->parcelpanel_is_connected(),
            ]);
        }

        update_option(\ParcelPanel\OptionName\LAST_ATTEMPT_CONNECT_AT, time(), false);

        // 连接 ParcelPanel
        $res = $this->connect_endpoint();

        if (is_wp_error($res)) {
            $msg = $res->get_error_message('parcelpanel_connect_error') ?: __('Failed to connect to ParcelPanel.', 'parcelpanel');
            (new ParcelPanelFunction)->parcelpanel_json_response([], $msg, false);
        }

        (new ParcelPanelFunction)->parcelpanel_json_response(['is_connected' => true]);
    }


    /**
     * 连接服务端
     */
    private function connect_endpoint()
    {
        if (!current_user_can('manage_woocommerce')) {
            return new \WP_Error('user_auth_error', 'You are not allowed');
        }

        $user = wp_get_current_user();

        $user_api_key = wc_rand_hash();
        $user_api_key_hash = (new ParcelPanelFunction)->parcelpanel_api_hash($user_api_key);
        update_user_meta($user->ID, 'parcelpanel_api_key', $user_api_key_hash);

        $resp_data = Api::connect($user_api_key);

        if (is_wp_error($resp_data)) {
            // 接口异常

            delete_user_meta($user->ID, 'parcelpanel_api_key', $user_api_key_hash);

            $msg = $resp_data->get_error_message('api_error');

            return new \WP_Error('parcelpanel_connect_error', __('Failed to connect to ParcelPanel.', 'parcelpanel') . ' ' . __($msg, 'parcelpanel'));
        }

        $resp_token = strval($resp_data['token'] ?? '');
        $resp_bid = ArrUtils::get($resp_data, 'bid', '0');
        $resp_uid = ArrUtils::get($resp_data, 'uid', '0');

        $resp_registered_at = ArrUtils::get($resp_data, 'registered_at', 0);

        if (empty($resp_token)) {
            // 认证失败

            delete_user_meta($user->ID, 'parcelpanel_api_key', $user_api_key_hash);

            return new \WP_Error('parcelpanel_connect_error', __('Failed to connect to ParcelPanel.', 'parcelpanel'));
        }

        // 认证成功
        update_option(\ParcelPanel\OptionName\API_KEY, $resp_token);
        // 保存 ParcelPanel 上注册用户的 ID，没有注册会是 0
        update_option(\ParcelPanel\OptionName\API_UID, $resp_uid);
        update_option(\ParcelPanel\OptionName\API_BID, $resp_bid);

        // 更新额度信息
        (new ParcelPanelFunction)->parcelpanel_update_quota_info($resp_data);

        !empty($resp_registered_at) && update_option(\ParcelPanel\OptionName\REGISTERED_AT, $resp_registered_at, false);

        empty(get_option(\ParcelPanel\OptionName\CONNECTED_AT)) && update_option(\ParcelPanel\OptionName\CONNECTED_AT, time(), false);

        update_option(\ParcelPanel\OptionName\PLUGIN_VERSION, \ParcelPanel\VERSION);

        return true;
    }

    /**
     * 插件版本更新啦
     */
    function version_upgrade_ajax()
    {
        check_ajax_referer('pp-version-upgrade');

        $resp_data = Api::site_upgrade();

        if (is_wp_error($resp_data)) {
            // 接口异常

            $api_error_message = $resp_data->get_error_message('api_error');

            $msg = __('Upgrade failed.', 'parcelpanel') . ' ' . __($api_error_message, 'parcelpanel');

            (new ParcelPanelFunction)->parcelpanel_json_response([], $msg, false);
        }

        update_option(\ParcelPanel\OptionName\PLUGIN_VERSION, \ParcelPanel\VERSION);

        (new ParcelPanelFunction)->parcelpanel_json_response([], 'Upgraded successfully');
    }

    public function popup_action_ajax()
    {
        check_ajax_referer('pp-popup');

        $post_data = (new ParcelPanelFunction)->parcelpanel_get_post_data();
        $action = wc_clean($post_data['action'] ?? '');
        $date = wc_clean($post_data['date'] ?? '');
        if (get_option('parcelpanel_free_upgrade_opened_at') <= 0) {
            update_option('parcelpanel_free_upgrade_opened_at', time());
        }
        if ($action == 'open:1' && $date) {
            update_user_option(get_current_user_id(), 'parcelpanel_free_upgrade_last_popup_date', $date, true);
        }
        $post_data['ua'] = wc_clean($_SERVER['HTTP_USER_AGENT'] ?? '');
        Api::popup_action($post_data);

        die;
    }

    /**
     * 更新运输商列表
     */
    private function update_couriers(): bool
    {
        $res = Courier::instance()->update_courier_list();

        if (is_wp_error($res)) {

            $msg = [];

            foreach ($res->get_error_codes() as $code) {
                $msg[] = $res->get_error_message($code);
            }

            add_action('admin_notices', function () use ($msg) {
                echo '<div class="notice notice-error"><p>' . __('Courier providers sync failed.', 'parcelpanel') . ' ' . implode(' ', $msg) . '</p></div>';
            });

            return false;
        }

        return true;
    }


    /**
     * 与 ParcelPanel 账号进行绑定
     */
    function bind_account_ajax()
    {
        check_ajax_referer('pp-bind-account');

        // 绑定 ParcelPanel 账号
        $this->bind_account();
    }

    /**
     * 执行绑定账号操作
     *
     * @param bool $force
     */
    private function bind_account(bool $force = false)
    {
        if (!current_user_can('manage_woocommerce') || (new ParcelPanelFunction)->parcelpanel_is_local_site()) {
            (new ParcelPanelFunction)->parcelpanel_json_response([], __('You are not allowed.', 'parcelpanel'), false);
        }

        $auth_key = !empty($_POST['auth_key']) ? wc_clean($_POST['auth_key']) : '';
        $auth_key = !empty($auth_key) ? $auth_key : wc_clean($_GET['auth_key']);
        $resp_data = Api::bind($auth_key);

        // 接口异常
        if (is_wp_error($resp_data)) {
            $msg = $resp_data->get_error_message('api_error');
            (new ParcelPanelFunction)->parcelpanel_json_response([], __('Failed to connect.', 'parcelpanel') . ' ' . __($msg, 'parcelpanel'), false);
        }

        $resp_already = boolval($resp_data['already'] ?? false);
        $resp_key = strval($resp_data['token'] ?? '');
        $resp_uid = strval($resp_data['uid'] ?? '');

        if ($resp_already) {
            if (!empty($resp_uid)) {
                update_option(\ParcelPanel\OptionName\API_UID, $resp_uid);
            }
            (new ParcelPanelFunction)->parcelpanel_json_response([], __('Already bound.', 'parcelpanel'), true, [
                'redirect' => admin_url('admin.php?page=pp-home'),
            ]);
        }

        if (empty($resp_key) || empty($resp_uid)) {
            (new ParcelPanelFunction)->parcelpanel_json_response([], __('Failed to connect.', 'parcelpanel'), false);
        }

        // 认证成功
        update_option(\ParcelPanel\OptionName\API_KEY, $resp_key);
        // 保存 ParcelPanel 上注册用户的 ID，没有注册会是 0
        update_option(\ParcelPanel\OptionName\API_UID, $resp_uid);

        empty($connected_at) && update_option(\ParcelPanel\OptionName\CONNECTED_AT, time(), false);

        (new ParcelPanelFunction)->parcelpanel_json_response([], __('Connected successfully', 'parcelpanel'), true, [
            'redirect' => admin_url('admin.php?page=pp-home'),
        ]);
    }

    /**
     * Accept to use live chat
     */
    public function live_chat_connect_ajax()
    {
        check_ajax_referer('pp-load-live-chat');

        update_user_option(get_current_user_id(), 'parcelpanel_live_chat_enabled_at', time(), true);
    }

    public function live_chat_disable_ajax()
    {
        check_ajax_referer('pp-load-live-chat');

        delete_user_meta(get_current_user_id(), 'parcelpanel_live_chat_enabled_at');
    }

    ##############独立页面区域##########################

    /**
     * @return string
     *
     * @author: Chuwen
     * @date  : 2021/7/27 10:46
     */
    static function add_admin_body_classes(): string
    {
        return join(' ', [
            'body-parcelpanel-admin',
        ]);
    }

    /**
     * 后台注册相关资源文件
     *
     * @author: Chuwen
     * @date  : 2021/7/27 10:39
     */
    function admin_register_assets()
    {
        wp_register_style('parcelpanel-admin', (new ParcelPanelFunction)->parcelpanel_get_assets_path('css/parcelpanel-admin.css'), [], VERSION);

        wp_register_style('pp-admin', (new ParcelPanelFunction)->parcelpanel_get_assets_path('css/parcelpanel.css'), ['pp-gutenberg'], VERSION);

        wp_register_style('pp-admin-plugins', (new ParcelPanelFunction)->parcelpanel_get_assets_path('css/admin-plugins.css'), ['pp-gutenberg'], VERSION);

        // 古腾堡样式
        $gutenberg_version = '12.8.0';
        wp_register_style('pp-gutenberg', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/gutenberg@{$gutenberg_version}/style.css"), [], null);

        wp_register_script('pp-vue', (new ParcelPanelFunction)->parcelpanel_get_assets_path('plugins/vue.min.js'), [], '2.6.14-1645459200');
        wp_register_script('pp-vue-component', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/component.min.js'), [
            'pp-vue',
            'pp-vue-select',
            'wp-codemirror',
            'pp-vue-slider-component',
        ], VERSION);

        // PP公共JS
        wp_register_script('pp-common', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/common.min.js'), ['jquery'], VERSION);

        wp_register_script('pp-track-page-trans-list', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/track-page-trans-list.min.js'), [], VERSION);

        wp_register_script('pp-home-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/homePage.min.js'), ['pp-vue-component'], VERSION);
        wp_register_script('pp-tracking-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/trackingPage.min.js'), ['pp-vue-component', 'pp-track-page-trans-list'], VERSION);
        wp_register_script('pp-shipments-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/shipmentsPage.min.js'), ['pp-vue-component'], VERSION);
        wp_register_script('pp-settings-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/settingsPage.min.js'), ['pp-vue-component'], VERSION);
        wp_register_script('pp-account-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/accountPage.min.js'), ['pp-vue-component'], VERSION);
        wp_register_script('pp-integration-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/integrationPage.min.js'), ['pp-vue-component'], VERSION);


        // WooCommerce Admin
        wp_register_style('pp-admin-wc', (new ParcelPanelFunction)->parcelpanel_get_assets_path('css/admin-wc.css'), [], VERSION);
        wp_register_script('pp-admin-wc', (new ParcelPanelFunction)->parcelpanel_get_assets_path('js/admin-wc.min.js'), ['jquery', 'selectWoo'], VERSION);


        // vue-select
        $vue_select_version = '3.12.2';
        wp_register_style('pp-vue-select', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/vue-select@{$vue_select_version}/vue-select.css"), [], null);
        wp_register_script('pp-vue-select', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/vue-select@{$vue_select_version}/vue-select.js"), ['pp-vue'], null);

        // Code Mirror
        $codemirror_version = '5.62.3';
        wp_register_style('pp-codemirror-theme-material-darker', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/codemirror@{$codemirror_version}/theme/material-darker.css"), [], null);

        // Vue Range Component
        $vue_slider_component_version = '3.2.15';
        wp_register_style('pp-vue-slider-component', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/vue-slider-component@{$vue_slider_component_version}/parcelpanel.css"), [], null);
        wp_register_script('pp-vue-slider-component', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/vue-slider-component@{$vue_slider_component_version}/vue-slider-component.umd.min.js"), ['pp-vue'], null);

        // Vue Content Placeholders
        $vue_content_placeholders_version = '0.2.1';
        wp_register_style('pp-vue-content-placeholders', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/vue-content-placeholders@{$vue_content_placeholders_version}/vue-content-placeholders.css"), [], null);
        wp_register_script('pp-vue-content-placeholders', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/vue-content-placeholders@{$vue_content_placeholders_version}/vue-content-placeholders.browser.js"), ['pp-vue'], '1645459200');


        // Toastr
        $toastr_version = '2.0';
        wp_register_style('pp-toastr', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/toastr@{$toastr_version}/toastr.min.css"), [], null);
        wp_register_script('pp-toastr-change', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/toastr@{$toastr_version}/toastrChange.js"), ['jquery'], null);
        wp_register_script('pp-toastr', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/toastr@{$toastr_version}/toastr.min.js"), ['jquery', 'pp-toastr-change'], null);


    }

    /**
     * 注册网站公共资源
     */
    private function site_register_assets()
    {
        // User track page
        wp_register_style('pp-user-track-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path('css/user-track-page.css'), [
            'pp-swiper',
        ], VERSION);
        wp_register_script('pp-user-track-page', (new ParcelPanelFunction)->parcelpanel_get_assets_path("js/user-track-page.min.js"), [
            'jquery-core',
            'pp-google-translate',
            'wp-url',
            'pp-swiper',
        ], VERSION, true);

        // Google translate
        wp_register_script('pp-google-translate', (new ParcelPanelFunction)->parcelpanel_get_assets_path('plugins/google-translate/element.js'), [], VERSION, true);

        // Swiper
        $swiper_version = '8.3.2';
        wp_register_style('pp-swiper', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/swiper@{$swiper_version}/swiper-bundle.min.css"), [], null);
        wp_register_script('pp-swiper', (new ParcelPanelFunction)->parcelpanel_get_assets_path("plugins/swiper@{$swiper_version}/swiper-bundle.min.js"), [], null);

    }

    /**
     * 插件 Track 页
     */
    private static function create_track_page()
    {
        global $wpdb;

        $page_title = 'Track Your Order';
        $page_slug = 'parcel-panel';
        $shortcode = '[pp-track-page]';

        $track_page_id = get_option(\ParcelPanel\OptionName\TRACK_PAGE_ID, 0);

        $page_info = get_post($track_page_id);

        if (!empty($page_info)) {
            if (false !== strpos($page_info->post_content, $shortcode)) {
                return;
            }
        }

        $sql = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page'",
            $page_slug
        );

        $page = $wpdb->get_var($sql);
        if ($page) {
            $page_check = get_post($page);
        }

        $page_id = $page_check->ID ?? 0;

        if (empty($page_id)) {

            $new_page = [
                'post_type' => 'page',
                'post_title' => $page_title,
                'post_name' => $page_slug,
                'post_content' => $shortcode,
                'post_status' => 'publish',
                'post_author' => 1,
            ];

            $page_id = wp_insert_post($new_page);
        }

        if ($track_page_id != $page_id) {
            update_option(\ParcelPanel\OptionName\TRACK_PAGE_ID, $page_id);
        }
    }

    function post_updated_track_page($post_ID, $post_after, $post_before)
    {
        if ('page' != $post_after->post_type || $post_after->post_name == $post_before->post_name) {
            return;
        }

        if (get_option(\ParcelPanel\OptionName\TRACK_PAGE_ID) == $post_ID) {
            Api::site_upgrade('tracking-page-url');
        }
    }

    ##############独立页面区域##########################

    /**
     * Change order label style
     */
    public function footer_function()
    {
        if (!is_plugin_active('woocommerce-order-status-manager/woocommerce-order-status-manager.php')) {
            ?>
            <style>
                .order-status.status-partial-shipped {
                    background: #2271b1;
                    color: #fff;
                }
            </style>
            <?php
        }
    }

    private function get_uninstall_reasons(): array
    {
        return [
            [
                'id' => 'temporary_deactivation',
                'text' => __('It is a temporary deactivation, I am just debugging an issue.', 'parcelpanel'),
                'type' => '',
                'placeholder' => '',
                'id_num' => 2,
            ],
            [
                'id' => 'no_longer_need',
                'text' => __('I no longer need the plugin.', 'parcelpanel'),
                'type' => 'text',
                'placeholder' => __('Could you tell us a bit more?', 'parcelpanel'),
                'id_num' => 3,
            ],
            [
                'id' => 'is_not_working',
                'text' => __('I couldn\'t get the plugin to work.', 'parcelpanel'),
                'type' => 'text',
                'placeholder' => __('Would you like us to assist you?', 'parcelpanel'),
                'id_num' => 4,
            ],
            [
                'id' => 'did_not_work_as_expected',
                'text' => __('It didn\'t work as expected.', 'parcelpanel'),
                'type' => 'text',
                'placeholder' => __('What did you expect?', 'parcelpanel'),
                'id_num' => 5,
            ],
            [
                'id' => 'not_have_that_feature',
                'text' => __('It\'s missing a specific feature.', 'parcelpanel'),
                'type' => 'text',
                'placeholder' => __('What specific feature?', 'parcelpanel'),
                'id_num' => 6,
            ],
            [
                'id' => 'found_better_plugin',
                'text' => __('I found a better plugin.', 'parcelpanel'),
                'type' => 'text',
                'placeholder' => __('Which plugin?', 'parcelpanel'),
                'id_num' => 7,
            ],
            [
                'id' => 'other',
                'text' => __('Other', 'parcelpanel'),
                'type' => 'text',
                'placeholder' => __('Could you tell us more to let us know how we can improve.', 'parcelpanel'),
                'id_num' => 8,
            ],
        ];
    }

    public function deactivate_scripts()
    {
        global $pagenow;

        if ($pagenow != 'plugins.php') {
            return;
        }

        static $modal = false;

        $data = [
            'slug' => 'parcelpanel',
        ];

        if (!$modal) :
            $reasons = $this->get_uninstall_reasons();
            ?>
            <div id="parcelpanel-modal-deactivate-survey" class="components-modal__screen-overlay pp-modal"
                 style="display:none">
                <div role="dialog" tabindex="-1" class="components-modal__frame">
                    <div class="components-modal__content">
                        <div class="components-modal__header">
                            <div class="components-modal__header-heading-container"><h1
                                    class="components-modal__header-heading"><?php esc_html_e('Quick feedback', 'parcelpanel') ?></h1>
                            </div>
                            <button type="button" aria-label="Close dialog" class="components-button has-icon btn-close"
                                    style="position:unset;margin-right:-8px">
                                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.414 10l6.293-6.293a1 1 0 10-1.414-1.414L10 8.586 3.707 2.293a1 1 0 00-1.414 1.414L8.586 10l-6.293 6.293a1 1 0 101.414 1.414L10 11.414l6.293 6.293A.998.998 0 0018 17a.999.999 0 00-.293-.707L11.414 10z"
                                        fill="#5C5F62"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="pp-modal-body">
                            <p><?php esc_html_e('May we have a little info about why you are deactivating to see how we can improve?', 'parcelpanel') ?></p>
                            <ul>
                                <?php foreach ($reasons as $reason) : ?>
                                    <li data-type="<?php echo esc_attr($reason['type']); ?>"
                                        data-placeholder="<?php echo esc_attr($reason['placeholder']); ?>">
                                        <div><label><input type="radio" value="<?php echo esc_attr($reason['id']) ?>"
                                                           name="selected-reason"
                                                           class="pp-radio components-radio-control__input"><?php echo esc_html($reason['text']) ?>
                                            </label></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="components-modal__footer">
                            <button type="button" class="components-button pp-button is-secondary btn-skip-deactivate">
                                <span><?php esc_html_e('Skip & Deactivate', 'parcelpanel') ?></span></button>
                            <button type="button" class="components-button pp-button is-primary" disabled>
                                <span><?php esc_html_e('Submit & Deactivate', 'parcelpanel') ?></span></button>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                #parcelpanel-modal-deactivate-survey ul {
                    margin: 16px 0 0;
                    padding: 0
                }

                #parcelpanel-modal-deactivate-survey ul li {
                    margin: 8px 0 0
                }

                .pp-modal .reason-input {
                    margin: 8px 0 10px;
                    padding-left: 26px
                }

                .pp-modal .components-placeholder__input {
                    margin: 0;
                    width: 100%;
                    height: 36px;
                    border-radius: 2px
                }
            </style>
            <script type="text/javascript">
                var pp_deactivate = {
                    deactivateLink: '',
                    survey_nonce: '<?php echo wp_create_nonce('pp-deactivate-survey')?>'
                }

                jQuery(($) => {
                    const $modal = $('#parcelpanel-modal-deactivate-survey')
                    pp_deactivate.$modal = $modal
                    $modal
                        .on('click', '.btn-close', () => {
                            $modal.css({display: 'none'})
                        })
                        .on('click', 'input[type="radio"]', function () {
                            $modal.find('.is-primary').removeAttr('disabled')
                            const parent = $(this).parents('li:first')
                            $modal.find('.reason-input').remove()
                            const inputType = parent.data('type')
                            if (inputType !== '') {
                                const inputPlaceholder = parent.data('placeholder')
                                    ,
                                    reasonInputHtml = `<div class="reason-input">${('text' === inputType) ? '<input type="text" class="components-placeholder__input"/>' : '<textarea rows="5" cols="45"></textarea>'}</div>`
                                parent.append($(reasonInputHtml))
                                parent.find('input,textarea').attr('placeholder', inputPlaceholder).focus()
                            }
                        })
                        .on('click', '.btn-skip-deactivate', function () {
                            const $this = $(this)

                            sendSurvey({reason_id: 'skip'}, $this)
                        })
                        .on('click', '.is-primary', function () {
                            const $this = $(this)
                                , $radio = $('input[type="radio"]:checked', $modal)
                                , $selected_reason = $radio.parents('li:first')
                                , $input = $selected_reason.find('textarea,input[type="text"]')
                                , reason_id = $radio.val()
                                , reason_info = $.trim($input.val())

                            if (!reason_id) return

                            sendSurvey({reason_id, reason_info}, $this)
                        })

                    function sendSurvey({reason_id, reason_info}, $buttonObject) {
                        const data = {
                            reason_id,
                            reason_info,
                            action: 'pp_deactivate_survey',
                            _ajax_nonce: pp_deactivate.survey_nonce,
                        }

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data,
                            beforeSend: function () {
                                doBeforeSend($buttonObject)
                            },
                            complete: function () {
                                toDeactivateLink()
                            }
                        })
                    }

                    function doBeforeSend($buttonObject) {
                        $modal.find('.components-modal__footer button').attr('disabled', 'disabled')
                        $buttonObject.addClass('is-busy').attr('disabled', 'disabled')
                        $modal.find('input[type="radio"],.reason-input input,.reason-input textarea').attr('disabled', 'disabled')
                    }

                    function toDeactivateLink() {
                        window.location.href = pp_deactivate.deactivateLink
                    }
                })
            </script>
            <?php
            $modal = true;
        endif;
        ?>
        <script type="text/javascript">
            jQuery(($) => {
                $(document).on('click', 'a#deactivate-<?php echo esc_html($data['slug']) ?>', function (e) {
                    e.preventDefault()
                    pp_deactivate.$modal.css({display: ''})
                    pp_deactivate.deactivateLink = $(this).attr('href')
                })
            })
        </script>
        <?php
    }

    public function deactivate_survey_ajax()
    {
        check_ajax_referer('pp-deactivate-survey');

        $current_user = wp_get_current_user();

        $reason_flag = wc_clean($_POST['reason_id'] ?? '');
        $reason_info = sanitize_textarea_field($_POST['reason_info'] ?? '');

        $reasons = array_column($this->get_uninstall_reasons(), null, 'id');
        $reason = $reasons[$reason_flag] ?? [];
        $reason_id = $reason['id_num'] ?? 0;
        $reason_text = $reason['text'] ?? '';
        if ($reason_flag === 'skip') {
            $reason_id = 1;
        }

        $name = (new ParcelPanelFunction)->parcelpanel_get_current_user_name();

        Api::uninstall_feedback([
            'reason_id' => $reason_id,
            'reason' => $reason_text,
            'feedback' => $reason_info,
            'name' => $name,
            'email' => $current_user->user_email,
        ]);

        die;
    }

    static function set_screen_option($new_value, $option, $value)
    {
        if (in_array($option, ['parcelpanel_page_pp_shipments_per_page'])) {
            return absint($value);
        }

        return $new_value;
    }

    function feedback_ajax()
    {
        check_ajax_referer('pp-feedback-confirm');

        $msg = sanitize_textarea_field($_REQUEST['msg']);
        $email = sanitize_email($_REQUEST['email']);
        $rating = absint($_REQUEST['rating']);
        $type = absint($_REQUEST['type']);

        if (empty($msg) || empty($email) || empty($rating)) {
            parcelpanel_json_response([], __('Required fields cannot be empty.', 'parcelpanel'), false);
        }

        $current_user = wp_get_current_user();

        $resp = Api::feedback([
            'first_name' => $current_user->first_name,
            'name_name' => $current_user->last_name,
            'msg' => $msg,
            'email' => $email,
            'rating' => $rating,
            'type' => $type,
        ]);

        if (is_wp_error($resp) || !is_array($resp)) {
            parcelpanel_json_response([], 'Save failed. Server error', false);
        }

        $resp_code = $resp['code'] ?? '';
        $resp_msg = $resp['msg'] ?? '';

        if (200 !== $resp_code) {
            parcelpanel_json_response([], "Save failed. {$resp_msg}", false);
        }

        parcelpanel_json_response([], 'Saved successfully');
    }

    function on_order_item_update(\WC_Order_Item $item)
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (is_plugin_active('ali2woo/ali2woo.php') || is_plugin_active('ali2woo-lite/ali2woo-lite.php')) {
            $this->on_order_item_update_ali2woo($item);
        }
    }

    private function on_order_item_update_ali2woo(\WC_Order_Item $item)
    {
        global $wpdb;

        if ('line_item' !== $item->get_type()) {
            return;
        }

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        $order_id = $item->get_order_id();
        $order_item_id = $item->get_id();
        $a2w_tracking_data = $item->get_meta('_a2w_tracking_data');

        if (empty($a2w_tracking_data['tracking_codes']) || !is_array($a2w_tracking_data['tracking_codes'])) {
            /* 清空单号或无操作 */

            // 解除所有商品关联单号
            $this->ali2woo_order_data_reset($order_id, $order_item_id);

            return;
        }


        $tracking_numbers = $a2w_tracking_data['tracking_codes'];
        $tracking_numbers = array_filter($tracking_numbers, function ($b) {
            return strlen($b) >= 4;
        });

        /* tracking numbers 入库 */
        /* 有新单号或需要更新单号 */
        $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_numbers);
        $SQL_RETRIEVE_TRACKINGS_BY_TRACKING_NUMBERS = <<<SQL
SELECT id,tracking_number FROM {$TABLE_TRACKING} WHERE tracking_number IN ({$placeholder_str})
SQL;
        $tracking_data = (array)$wpdb->get_results($wpdb->prepare(
            $SQL_RETRIEVE_TRACKINGS_BY_TRACKING_NUMBERS,
            $tracking_numbers
        ));
        $is_insert_tracking = false;
        // 筛选出不存在数据库的 tracking numbers
        $new_tracking_numbers = array_diff($tracking_numbers, array_column($tracking_data, 'tracking_number'));
        $now = time();
        foreach ($new_tracking_numbers as $tracking_number) {
            $tracking_item_data = ShopOrder::get_tracking_item_data($tracking_number, null, $now);
            $res = $wpdb->insert($TABLE_TRACKING, $tracking_item_data);
            if (!is_wp_error($res)) {
                $_tracking_datum = $tracking_data[] = new \stdClass;
                $_tracking_datum->id = $wpdb->insert_id;
                $_tracking_datum->tracking_number = $tracking_number;

                $is_insert_tracking = true;
            }
        }
        if ($is_insert_tracking) {
            TrackingNumber::schedule_tracking_sync_action(-1);
        }

        // 筛选允许处理的单号
        $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_data, '%d');
        $SQL_RETRIEVE_TRACKINGS_ORDER_IDS = <<<SQL
SELECT order_id,tracking_id FROM {$TABLE_TRACKING_ITEMS} WHERE tracking_id IN({$placeholder_str})
SQL;
        $trackings_order_ids = $wpdb->get_results($wpdb->prepare(
            $SQL_RETRIEVE_TRACKINGS_ORDER_IDS,
            array_column($tracking_data, 'id')
        ));
        if (!empty($trackings_order_ids)) {
            foreach ($trackings_order_ids as $_data) {
                if ($_data->order_id == $order_id) {
                    continue;
                }
                foreach ($tracking_data as $key => $value) {
                    if ($value->id == $_data->tracking_id) {
                        unset($tracking_data[$key]);
                        break;
                    }
                }
            }
        }

        $SQL_RETRIEVE_SHIPMENTS_BY_ORDER_ID = <<<SQL
SELECT * FROM {$TABLE_TRACKING_ITEMS} WHERE order_id=%d
SQL;
        $shipments = $wpdb->get_results($wpdb->prepare(
            $SQL_RETRIEVE_SHIPMENTS_BY_ORDER_ID,
            $order_id
        ));

        if (!empty($tracking_data)) {
            $wpdb->delete($TABLE_TRACKING_ITEMS, ['order_id' => $order_id, 'order_item_id' => $order_item_id, 'quantity' => 0]);

            foreach ($tracking_data as $tracking_datum) {
                $_shipment = null;
                foreach ($shipments as $shipment) {
                    if ($shipment->tracking_id == $tracking_datum->id) {
                        $_shipment = $shipment;
                        break;
                    }
                }
                $_shipment_status = $_shipment->shipment_status ?? 1;
                $_custom_status_time = $_shipment->custom_status_time ?? '';
                $_custom_shipment_status = $_shipment->custom_shipment_status ?? 0;
                $wpdb->insert($TABLE_TRACKING_ITEMS, [
                    'order_id' => $order_id,
                    'order_item_id' => $order_item_id,
                    'tracking_id' => $tracking_datum->id,
                    'shipment_status' => $_shipment_status,
                    'custom_status_time' => $_custom_status_time,
                    'custom_shipment_status' => $_custom_shipment_status,
                ]);
            }
        }

        ShopOrder::adjust_unfulfilled_shipment_items($order_id);
    }

    private function ali2woo_order_data_reset($order_id, $order_item_id)
    {
        global $wpdb;

        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        $wpdb->delete($TABLE_TRACKING_ITEMS, ['order_id' => $order_id, 'order_item_id' => $order_item_id, 'quantity' => 0]);
        ShopOrder::adjust_unfulfilled_shipment_items($order_id);
    }

    private function init_app_1001_integration()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (!AdminIntegration::get_app_integrated(1001)) {
            return;
        }

        add_action('woocommerce_before_order_item_object_save', function (\WC_Order_Item $item) {
            if ($item->get_type() !== 'line_item') return;

            $from = 1001;
            $order_id = $item->get_order_id();
            $order_item_id = $item->get_id();

            $new_tracking_codes = $item->get_meta('_a2w_tracking_data')['tracking_codes'] ?? [];
            $old_tracking_codes = (new \WC_Order_Item_Product($order_item_id))->get_meta('_a2w_tracking_data')['tracking_codes'] ?? [];

            foreach (array_diff($old_tracking_codes, $new_tracking_codes) as $tracking_number) {
                $this->delete_list[] = compact('tracking_number', 'order_id', 'order_item_id', 'from');
            }

            foreach (array_diff($new_tracking_codes, $old_tracking_codes) as $tracking_number) {
                $this->add_list[] = compact('tracking_number', 'order_id', 'order_item_id', 'from');
            }
        });

        add_action('woocommerce_after_order_item_object_save', function (\WC_Order_Item $item) {
            if ($item->get_type() !== 'line_item') return;

            $from = 1001;
            $order_item_id = $item->get_id();

            foreach ($this->delete_list as &$item) {
                if ($item['order_item_id'] === $order_item_id && $item['from'] === $from) {
                    $item['success'] = true;
                }
            }

            foreach ($this->add_list as &$item) {
                if ($item['order_item_id'] === $order_item_id && $item['from'] === $from) {
                    $item['success'] = true;
                }
            }
        });
    }

    private function init_app_1002_integration()
    {
        if (!AdminIntegration::get_app_integrated(1002)) {
            return;
        }

        add_action('added_order_item_meta', function ($mid, $object_id, $meta_key, $_meta_value) {
            if ($meta_key !== '_vi_wot_order_item_tracking_data') {
                return;
            }

            $from = 1002;
            $items = (array)json_decode($_meta_value, true);
            $item = array_pop($items);
            $tracking_number = (string)($item['tracking_number'] ?? '');
            if (!$tracking_number) {
                return;
            }

            $order_id = wc_get_order_id_by_order_item_id($object_id);

            $this->add_list[] = [
                'tracking_number' => $tracking_number,
                'order_id' => $order_id,
                'order_item_id' => $object_id,
                'from' => $from,
                'success' => true,
            ];
        }, 10, 4);

        add_action('update_order_item_meta', function ($meta_id, $object_id, $meta_key, $_meta_value) {
            if ($meta_key !== '_vi_wot_order_item_tracking_data') {
                return;
            }

            $from = 1002;
            $items = (array)json_decode($_meta_value, true);
            $item = array_pop($items);

            $_original_items = (array)json_decode(wc_get_order_item_meta($object_id, $meta_key, true), true);
            $_original_item = array_pop($_original_items);

            $tracking_number = (string)($item['tracking_number'] ?? '');
            $_original_tracking_number = (string)($_original_item['tracking_number'] ?? '');
            if (!$_original_tracking_number || $tracking_number === $_original_tracking_number) {
                return;
            }

            $order_id = wc_get_order_id_by_order_item_id($object_id);

            $this->delete_list[] = [
                'tracking_number' => $_original_tracking_number,
                'order_id' => $order_id,
                'order_item_id' => $object_id,
                'from' => $from,
            ];

            $this->add_list[] = [
                'tracking_number' => $tracking_number,
                'order_id' => $order_id,
                'order_item_id' => $object_id,
                'from' => $from,
            ];
        }, 10, 4);

        add_action('updated_order_item_meta', function ($meta_id, $object_id, $meta_key, $_meta_value) {
            if ($meta_key !== '_vi_wot_order_item_tracking_data') {
                return;
            }

            $from = 1002;

            foreach ($this->delete_list as &$item) {
                if ($item['order_item_id'] === $object_id && $item['from'] === $from) {
                    $item['success'] = true;
                }
            }

            foreach ($this->add_list as &$item) {
                if ($item['order_item_id'] === $object_id && $item['from'] === $from) {
                    $item['success'] = true;
                }
            }
        }, 10, 4);
    }

    private function init_app_1003_integration()
    {
        if (!AdminIntegration::get_app_integrated(1003)) {
            return;
        }

        add_action('wp_insert_comment', function ($id, $comment) {
            /** @var \WP_Comment|null $comment */
            if (!$comment instanceof \WP_Comment) {
                return;
            }

            $from = 1003;
            $comment_type = $comment->comment_type;
            $content = $comment->comment_content;
            $order_id = $comment->comment_post_ID;

            if (!$content || $comment_type !== 'order_note') {
                return;
            }

            $tracking_number_matches = [];
            $res_match_tracking_number = preg_match('/Tracking number: (.*)/', $content, $tracking_number_matches);
            if (!$res_match_tracking_number) {
                return;
            }

            $item_id_matches = [];
            $res_match_item_id = preg_match('/Line item ID: (.*)/', $content, $item_id_matches);
            if (!$res_match_item_id) {
                return;
            }

            $tracking_number = $tracking_number_matches[1];
            $line_item_ids = array_map('intval', explode(',', $item_id_matches[1]));

            foreach ($line_item_ids as $item_id) {
                if (!$item_id) {
                    continue;
                }

                $this->add_list[] = [
                    'tracking_number' => $tracking_number,
                    'order_id' => $order_id,
                    'order_item_id' => $item_id,
                    'from' => $from,
                    'success' => true,
                ];
            }
        }, 10, 2);
    }
}
