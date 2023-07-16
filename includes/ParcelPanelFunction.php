<?php

namespace ParcelPanel;

use MO;
use ParcelPanel\Action\Common;
use ParcelPanel\Action\Lang;
use ParcelPanel\Libs\ArrUtils;
use ParcelPanel\Models\Table;
use ParcelPanel\Models\TrackingSettings;
use stdClass;

final class ParcelPanelFunction
{

    public function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function parcelpanel_log($message, $level = \WC_Log_Levels::DEBUG)
    {
        // return;
        $logger = wc_get_logger();
        $context = ['source' => 'parcelpanel'];
        $logger->log($level, $message, $context);
    }

    /**
     * PP API - Hash.
     *
     * @param string $data Message to be hashed.
     *
     * @return string
     * @since  1.4.0
     */
    public function parcelpanel_api_hash($data): string
    {
        return hash_hmac('sha256', $data, 'pp-api');
    }

    /**
     * 计划一个单一任务
     */
    public function parcelpanel_schedule_single_action(string $hook, int $delay = 1, $args = [])
    {
        $pending_jobs = as_get_scheduled_actions(['per_page' => 1, 'hook' => $hook, 'group' => 'parcelpanel', 'status' => 'pending']);

        if (empty($pending_jobs)) {
            as_schedule_single_action(time() + $delay, $hook, $args, 'parcelpanel');
            return true;
        }

        return false;
    }

    /**
     * 获取 JSON POST DATA
     */
    public function parcelpanel_get_post_data(): array
    {
        if (empty($_POST) && false !== strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {

            $content = file_get_contents('php://input');

            return (array)json_decode($content, 1);
        }

        return $_POST;
    }

    public function parcelpanel_get_prepare_placeholder_str($data, $placeholder = '%s'): string
    {
        return rtrim(str_repeat("{$placeholder},", count($data)), ',');
    }

    /**
     * 判断是否为本地站点
     *
     * @return bool
     */
    public function parcelpanel_is_local_site(): bool
    {
        // Check for localhost and sites using an IP only first.
        $is_local = site_url() && false === strpos(site_url(), '.');

        // Use Core's environment check, if available. Added in 5.5.0 / 5.5.1 (for `local` return value).
        if ('local' === wp_get_environment_type()) {
            $is_local = true;
        }

        // Then check for usual usual domains used by local dev tools.
        $known_local = [
            '#\.local$#i',
            '#\.localhost$#i',
            '#\.test$#i',
            '#\.docksal$#i',      // Docksal.
            '#\.docksal\.site$#i', // Docksal.
            '#\.dev\.cc$#i',       // ServerPress.
            '#\.lndo\.site$#i',    // Lando.
        ];

        if (!$is_local) {
            foreach ($known_local as $url) {
                if (preg_match($url, site_url())) {
                    $is_local = true;
                    break;
                }
            }
        }

        return apply_filters('parcelpanel_is_local_site', $is_local);
    }

    /**
     * 检测 WC 是否激活
     */
    public function parcelpanel_woocommerce_active_check(): bool
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * 是否成功连接了云服务
     */
    public function parcelpanel_is_connected(): bool
    {
        $user = wp_get_current_user();

        $api_key = get_option(\ParcelPanel\OptionName\API_KEY);

        return !empty($api_key) && !empty($user->parcelpanel_api_key);
    }

    public function parcelpanel_get_current_user_name()
    {
        $current_user = wp_get_current_user();

        $user_first_name = $current_user->first_name;
        $user_last_name = $current_user->last_name;
        $user_display_name = $current_user->display_name;

        if ($user_first_name || $user_last_name) {
            return "{$user_first_name} {$user_last_name}";
        }

        return $user_display_name;
    }

    public function parcelpanel_update_quota_info($data)
    {
        $quota = intval(ArrUtils::get($data, 'quota', -1));
        $quota_used = intval(ArrUtils::get($data, 'quota_used', -1));
        $is_free_plan = ArrUtils::get($data, 'is_free_plan');
        $is_unlimited_plan = ArrUtils::get($data, 'is_unlimited_plan');

        // 如果存在额度信息就更新
        if ($quota > -1 && $quota_used > -1) {
            update_option(\ParcelPanel\OptionName\PLAN_QUOTA, $quota);
            update_option(\ParcelPanel\OptionName\PLAN_QUOTA_REMAIN, abs($quota - $quota_used));
        }
        if (!is_null($is_free_plan)) {
            update_option(\ParcelPanel\OptionName\IS_FREE_PLAN, intval($is_free_plan), false);
        }
        if (!is_null($is_unlimited_plan)) {
            update_option(\ParcelPanel\OptionName\IS_UNLIMITED_PLAN, intval(!!$is_unlimited_plan));
        }
    }

    /**
     * 获取资源文件路径
     *
     * @param string $path 相对于 assets 文件夹的资源路径
     * @param false $link 是否生成链接
     *
     * @return string
     *
     * @author: Chuwen
     * @date  : 2021/7/27 09:55
     */
    public function parcelpanel_get_assets_path(string $path = '', bool $link = true): string
    {
        $path = "/assets/{$path}";

        return $link ? plugins_url($path, \ParcelPanel\PLUGIN_FILE) : \ParcelPanel\PLUGIN_PATH . $path;
    }

    /**
     * 获取资源文件路径
     *
     * @param string $dir
     * @param string $path
     * @param bool $link
     *
     * @return string
     *
     * @author: Lijiahao <jiahao.li@trackingmore.org>
     * @date  : 2023/2/25 10:18
     */
    public function get_dir_path(string $dir = '', string $path = '', bool $link = true): string
    {
        $path = "/{$dir}/{$path}";

        return $link ? plugins_url($path, \ParcelPanel\PLUGIN_FILE) : \ParcelPanel\PLUGIN_PATH . $path;
    }

    /**
     * 获取 PP 插件的基础路径
     *
     * @param string $extendPath 需要继续追加的路径
     *
     * @return string
     *
     * @author: Chuwen
     * @date  : 2021/7/27 09:41
     */
    public function parcelpanel_get_plugin_base_path(string $extendPath = ''): string
    {
        $path = basename(dirname(__FILE__, 2));
        if (!empty($extendPath)) $path .= $extendPath;

        return $path;
    }

    /**
     * 判断是否为 PP 的页面
     *
     * @return bool
     *
     * @author: Chuwen
     * @date  : 2021/7/26 17:56
     */
    public function is_parcelpanel_plugin_page(): bool
    {
        return ($GLOBALS['parent_file'] ?? '') === \ParcelPanel\PP_MENU_SLAG;
    }

    /**
     * 引入视图（其实就是引入一个 PHP 文件）
     *
     * @param string $name 文件名字
     *
     * @author: Chuwen
     * @date  : 2021/7/21 10:42
     */
    public function parcelpanel_include_view(string $name)
    {
        include __DIR__ . "/views/{$name}.php";
    }

    /**
     * 在 PP 添加子菜单
     *
     * @param string $page_title 页面标题
     * @param string $menu_title 菜单标题
     * @param string $capability 什么权限可以查看，默认是 manage_options
     * @param string $menu_slug
     * @param callable $function 个人理解，创建菜单成功后的回调，你可以在这里开始创建页面
     * @param int $position 菜单显示位置
     *
     * @author: Chuwen
     * @date  : 2021/7/21 10:48
     */
    public function parcelpanel_add_submenu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null)
    {
        add_submenu_page(
            \ParcelPanel\PP_MENU_SLAG,  // 父级菜单的 slag
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $function,
            $position
        );
    }


    /**
     * 翻译
     */
    public function parcelpanel__($text)
    {
        return $this->parcelpanel_translate($text);
    }

    /**
     * Tracking Settings 翻译
     */
    public function parcelpanel_translate($text)
    {
        $TRANSLATIONS = \ParcelPanel\Models\TrackingSettings::instance()->tracking_page_translations;

        return $TRANSLATIONS[$text] ?? translate($text, 'parcelpanel');
    }


    /**
     * 输出规范的响应数据
     */
    public function parcelpanel_json_response($data = [], $msg = '', $success = true, $other = [])
    {
        $result = [
            'code' => 200,
            'success' => $success,
            'data' => $data,
            'msg' => $msg,
        ];

        $result = array_merge($other, $result);

        wp_send_json($result, null, 320);
    }


    /**
     * Track page url
     */
    public function parcelpanel_get_track_page_url($preview = false, $order_number = '', $email = '', $tracking_number = ''): string
    {
        $track_page_id = get_option(\ParcelPanel\OptionName\TRACK_PAGE_ID);

        $track_page_url = !empty($track_page_id) ? get_page_link($track_page_id) : 'Unknown';

        $separate = strpos($track_page_url, '?') ? '&' : '?';

        if ($preview) {
            return "{$track_page_url}{$separate}nums=1234&preview=parcelpanel";
        }

        if ($order_number) {

            $token = \ParcelPanel\Action\UserTrackPage::encode_email($email);

            $order_number = urlencode($order_number);
            $token = urlencode($token);

            return "{$track_page_url}{$separate}order={$order_number}&token={$token}";
        }

        if ($tracking_number) {

            $tracking_number = urlencode($tracking_number);

            return "{$track_page_url}{$separate}nums={$tracking_number}";
        }

        return $track_page_url;
    }

    public function parcelpanel_get_track_page_url_by_tracking_number($tracking_number): string
    {
        return $this->parcelpanel_get_track_page_url(null, null, null, $tracking_number);
    }

    /**
     * Admin shipment url
     */
    public function parcelpanel_get_admin_home_url()
    {
        return admin_url('admin.php?page=pp-home');
    }

    /**
     * Admin setting url
     */
    public function parcelpanel_get_admin_settings_url()
    {
        return admin_url('admin.php?page=pp-settings');
    }

    /**
     * Admin shipment url
     */
    public function parcelpanel_get_admin_shipments_url()
    {
        return admin_url('admin.php?page=pp-shipments');
    }


    /**
     * Track Your Order 文本
     */
    public function parcelpanel_text_track_your_order(): string
    {
        $page_id = get_option(\Parcelpanel\OptionName\TRACK_PAGE_ID);

        $page = get_post($page_id);

        $page_title = $page->post_title ?? '';

        return $page_title ?: 'Track Your Order';
    }


// function pp_get_date_format(): string
// {
//     $wpDateFormat = get_option( 'date_format' );
//
//     switch ( $wpDateFormat ) {
//         case 'd/m/Y':
//             $date_format = 'd/m';
//             break;
//         case 'm/d/Y':
//             $date_format = 'm/d';
//             break;
//         case 'Y-m-d':
//             $date_format = 'm-d';
//             break;
//         case 'F j, Y':
//             $date_format = 'F j';
//             break;
//         default:
//             $date_format = 'm/d';
//     }
//
//     return $date_format;
// }

// function pp_get_est_delivery( $est_delivery_date )
// {
//     $date_format        = pp_get_date_format();
//     $today_date         = date( $date_format );
//     $est_delivery_date1 = gmdate( $date_format, strtotime( $est_delivery_date ) );
//     if ( $today_date == $est_delivery_date1 ) {
//         return 'Today';
//     } else {
//         return $est_delivery_date1;
//     }
// }

    public function parcelpanel_get_shipment_status($id)
    {
        $status = [
            1 => 'pending',
            2 => 'transit',
            3 => 'pickup',
            4 => 'delivered',
            5 => 'expired',
            6 => 'undelivered',
            7 => 'exception',
            8 => 'info_received',
        ];

        return $status[$id] ?? null;
    }

    /**
     * Shipment Status
     *
     * @return array[]
     *
     * @author Mark
     * @date   2021/7/29 15:40
     */
    public function parcelpanel_get_shipment_statuses($sort = false): array
    {
        $rtn = [
            'pending' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('pending'),
                'sort' => 3,
                'id' => 1,
                'color' => '#6D7175',
                'child_status' => [],
            ],

            'transit' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('in_transit'),
                'sort' => 4,
                'id' => 2,
                'color' => '#1E93EB',
            ],

            'pickup' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('out_for_delivery'),
                'sort' => 5,
                'id' => 3,
                'color' => '#FCAF30',
            ],

            'delivered' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('delivered'),
                'sort' => 1,
                'id' => 4,
                'color' => '#1BBE73',
            ],

            'expired' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('expired'),
                'sort' => 2,
                'id' => 5,
                'color' => '#BABEC3',
            ],

            'undelivered' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('failed_attempt'),
                'sort' => 6,
                'id' => 6,
                'color' => '#8109FF',
            ],

            'exception' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('exception'),
                'sort' => 7,
                'id' => 7,
                'color' => '#FD5749',
            ],

            'info_received' => [
                'keywords' => [],
                'text' => $this->parcelpanel__('info_received'),
                'sort' => 0,
                'id' => 8,
                'color' => '#00A0AC',
                'child_status' => [],
            ],
        ];

        // if ($sort) {
        //     uasort( $rtn, 'pp_sort_shipment_statuses' );
        // }

        return $rtn;
    }

    /**
     * 运输状态升序排序
     */
    public function parcelpanel_sort_shipment_statuses($a, $b)
    {
        return $a['sort'] < $b['sort'] ? -1 : 1;
    }

    public function parcelpanel_get_courier_list($sort = ''): stdClass
    {
        global $wpdb;

        $TABLE_COURIER = Table::$courier;

        $order_sql = '';

        if ($sort && in_array(strtoupper($sort), ['ASC', 'DESC'])) {
            $order_sql = "ORDER BY sort {$sort}";
        }

        $rows = $wpdb->get_results("SELECT * FROM {$TABLE_COURIER} {$order_sql}");

        $data = new stdClass();

        foreach ($rows as $row) {
            $data->{$row->code} = $row;
        }

        return $data;
    }

    public function parcelpanel_get_courier_code_from_name($name): string
    {
        global $wpdb;

        if (empty($name)) {
            return '';
        }

        $TABLE_COURIER = Table::$courier;

        return $wpdb->get_var($wpdb->prepare("SELECT code FROM {$TABLE_COURIER} WHERE `name` = %s", $name)) ?: '';
    }

    public function parcelpanel_get_courier_info($code)
    {
        global $wpdb;

        static $cache;

        $TABLE_COURIER = Table::$courier;

        if (empty($cache[$code])) {

            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$TABLE_COURIER} WHERE `code` = %s", $code));

            $cache[$code] = $row;  // 缓存
        }

        return $cache[$code];
    }

    /**
     * 获取原 Order ID
     */
    public function parcelpanel_get_formatted_order_id($order_id)
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (is_plugin_active('custom-order-numbers-for-woocommerce/custom-order-numbers-for-woocommerce.php')) {
            $alg_wc_custom_order_numbers_enabled = get_option('alg_wc_custom_order_numbers_enabled');
            $alg_wc_custom_order_numbers_prefix = get_option('alg_wc_custom_order_numbers_prefix');
            $new_order_id = str_replace($alg_wc_custom_order_numbers_prefix, '', $order_id);

            if ('yes' == $alg_wc_custom_order_numbers_enabled) {
                $args = [
                    'post_type' => 'shop_order',
                    'posts_per_page' => '1',
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'key' => '_alg_wc_custom_order_number',
                            'value' => $new_order_id,
                        ],
                        [
                            'key' => '_alg_wc_full_custom_order_number',
                            'value' => $order_id,
                        ],
                    ],
                    'post_status' => array_keys(wc_get_order_statuses()),
                ];
                $posts = get_posts($args);
                $my_query = new \WP_Query($args);

                if ($my_query->have_posts()) {
                    while ($my_query->have_posts()) {
                        $my_query->the_post();
                        if (get_the_ID()) {
                            $order_id = get_the_ID();
                        }
                    }
                }
                wp_reset_postdata();
            }
        }

        if (is_plugin_active('woocommerce-sequential-order-numbers/woocommerce-sequential-order-numbers.php')) {

            $s_order_id = wc_sequential_order_numbers()->find_order_by_order_number($order_id);
            if ($s_order_id) {
                $order_id = $s_order_id;
            }
        }

        if (is_plugin_active('woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers-pro.php')) {

            // search for the order by custom order number
            $query_args = [
                'numberposts' => 1,
                'meta_key' => '_order_number_formatted',
                'meta_value' => $order_id,
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'fields' => 'ids',
            ];

            $posts = get_posts($query_args);
            if (!empty($posts)) {
                [$order_id] = $posts;
            }
        }

        if (is_plugin_active('woocommerce-jetpack/woocommerce-jetpack.php')) {

            $wcj_order_numbers_enabled = get_option('wcj_order_numbers_enabled');

            if ('yes' == $wcj_order_numbers_enabled) {
                // Get prefix and suffix options
                $prefix = do_shortcode(get_option('wcj_order_number_prefix', ''));
                $prefix .= date_i18n(get_option('wcj_order_number_date_prefix', ''));
                $suffix = do_shortcode(get_option('wcj_order_number_suffix', ''));
                $suffix .= date_i18n(get_option('wcj_order_number_date_suffix', ''));

                // Ignore suffix and prefix from search input
                $search_no_suffix = preg_replace("/\A{$prefix}/i", '', $order_id);
                $search_no_suffix_and_prefix = preg_replace("/{$suffix}\z/i", '', $search_no_suffix);
                $final_search = $search_no_suffix_and_prefix ?: $order_id;

                $query_args = [
                    'numberposts' => 1,
                    'meta_key' => '_wcj_order_number',
                    'meta_value' => $final_search,
                    'post_type' => 'shop_order',
                    'post_status' => 'any',
                    'fields' => 'ids',
                ];

                $posts = get_posts($query_args);
                if (!empty($posts)) {
                    [$order_id] = $posts;
                }
            }
        }

        if (is_plugin_active('wp-lister-amazon/wp-lister-amazon.php')) {
            $wpla_use_amazon_order_number = get_option('wpla_use_amazon_order_number');
            if (1 == $wpla_use_amazon_order_number) {
                $query_args = [
                    'numberposts' => 1,
                    'meta_key' => '_wpla_amazon_order_id',
                    'meta_value' => $order_id,
                    'post_type' => 'shop_order',
                    'post_status' => 'any',
                    'fields' => 'ids',
                ];

                $posts = get_posts($query_args);
                if (!empty($posts)) {
                    [$order_id] = $posts;
                }
            }
        }

        if (is_plugin_active('wp-lister/wp-lister.php') || is_plugin_active('wp-lister-for-ebay/wp-lister.php')) {
            $args = [
                'post_type' => 'shop_order',
                'posts_per_page' => '1',
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_ebay_extended_order_id',
                        'value' => $order_id,
                    ],
                    [
                        'key' => '_ebay_order_id',
                        'value' => $order_id,
                    ],
                ],
                'post_status' => 'any',
            ];

            $posts = get_posts($args);
            $my_query = new \WP_Query($args);

            if ($my_query->have_posts()) {
                while ($my_query->have_posts()) {
                    $my_query->the_post();
                    if (get_the_ID()) {
                        $order_id = get_the_ID();
                    }
                }
            }
            wp_reset_postdata();
        }

        if (is_plugin_active('yith-woocommerce-sequential-order-number-premium/init.php')) {
            $query_args = [
                'numberposts' => 1,
                'meta_key' => '_ywson_custom_number_order_complete',
                'meta_value' => $order_id,
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'fields' => 'ids',
            ];

            $posts = get_posts($query_args);
            if (!empty($posts)) {
                [$order_id] = $posts;
            }
        }

        if (is_plugin_active('wt-woocommerce-sequential-order-numbers/wt-advanced-order-number.php')) {
            $query_args = [
                'numberposts' => 1,
                'meta_key' => '_order_number',
                'meta_value' => $order_id,
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'fields' => 'ids',
            ];

            $posts = get_posts($query_args);
            if (!empty($posts)) {
                [$order_id] = $posts;
            }
        }

        return $order_id;
    }


    /**
     * 依据用户设置的时间格式 将日期转换为指定格式
     *
     * @param int|null $date
     * @param int|null $time
     */
    public function parcelpanel_handle_time_format($timestamp, int $date = null, int $time = null, $is_hidden_year = false, $is_hidden_time = false, $has_second = false)
    {
        $sec_placeholder = $has_second ? ':s' : '';

        $DEFAULT_DATE_FORMAT = 2;
        $DEFAULT_TIME_FORMAT = 0;

        $DATE_FORMAT_LIST = [
            $is_hidden_year ? 'M d' : 'M d, Y',
            'M d',
            $is_hidden_year ? 'M dS' : 'M dS Y',
            $is_hidden_year ? 'm/d' : 'm/d/Y',
            $is_hidden_year ? 'd/m' : 'd/m/Y',
            $is_hidden_year ? 'd.m' : 'd.m.Y',
            $is_hidden_year ? 'm-d' : 'Y-m-d',
            $is_hidden_year ? 'd M' : 'd M Y',
            $is_hidden_year ? 'd-M' : 'd-M-Y',
        ];

        $TIME_FORMAT_LIST = [
            "h:i{$sec_placeholder} a",
            "H:i{$sec_placeholder}",
        ];

        $format = [
                $DATE_FORMAT_LIST[$date] ?? $DATE_FORMAT_LIST[$DEFAULT_DATE_FORMAT],
        ];

        if (!($is_hidden_year || $is_hidden_time)) {
            // 未隐藏年份与时间

            $format[] = $TIME_FORMAT_LIST[$time] ?? $TIME_FORMAT_LIST[$DEFAULT_TIME_FORMAT];
        }

        $format_str = implode(' ', $format);

        return date($format_str, $timestamp);
    }


    /**
     * 对 $data 数据进行翻译
     */
    public function parcelpanel_tracking_info__($track_info_translation, $data)
    {
        if (empty($track_info_translation)) {
            return $data;
        }

        $track_info = $track_info_translation;

        //!empty($track_info)[不等于空]且$track_info为数组 返回1
        if (!empty($track_info) && is_array($track_info)) {
            if (!empty($data) && !empty($data['tracking'])) {
                $tracking = $data['tracking'];
                //value1为一个单号的所有信息
                foreach ($tracking as $key1 => $value1) {
                    if (!empty($value1['trackinfo'])) {
                        //trackinfo数组   快递信息
                        $trackinfo = $value1['trackinfo'];
                        // 获得快递信息
                        foreach ($trackinfo as $key2 => $value2) {
                            //!empty($value2['status_description'])  --->    55555555555
                            if (!empty($value2['status_description'])) {
                                //details不为空 不是 返回false
                                if (!empty($value2['details'])) {
                                    $statu_tracking = $value2['status_description'] . ', ' . $value2['details'];
                                } else {
                                    $statu_tracking = $value2['status_description'];
                                }
                                // 将连续的空格换成一个空格
                                $statu_tracking = preg_replace('/&rsquo;/', "'", $statu_tracking);
                                $statu_tracking = preg_replace('/\s+/', ' ', $statu_tracking);
                                $statu_tracking = trim($statu_tracking);


                                // 整句翻译
                                $T_key = array_search($statu_tracking, array_column($track_info, 'before'));

                                if ($T_key === 0 || $T_key != false) {
                                    //
                                    if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['status_description']) && !empty($track_info[$T_key]['after'])) {
                                        $data['tracking'][$key1]['trackinfo'][$key2]['status_description'] = $track_info[$T_key]['after'];
                                    }
                                    if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['details']) && !empty($track_info[$T_key]['after'])) {
                                        $data['tracking'][$key1]['trackinfo'][$key2]['details'] = $track_info[$T_key]['after'];
                                    }
                                }

                                // 单个翻译
                                //同时存在status_description和details两个字段
                                if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['status_description']) && isset($data['tracking'][$key1]['trackinfo'][$key2]['details'])) {
                                    //当status_description和details不相等时进行以下循环
                                    if ($data['tracking'][$key1]['trackinfo'][$key2]['status_description'] != $data['tracking'][$key1]['trackinfo'][$key2]['details']) {
                                        //如果Detail不为空
                                        if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['details'])) {
                                            //单句翻译等于status_description和details合并
                                            $statu_tracking_small = $data['tracking'][$key1]['trackinfo'][$key2]['status_description'] . ', ' . $data['tracking'][$key1]['trackinfo'][$key2]['details'];
                                            //否则单句翻译等于status_description
                                        } else {
                                            $statu_tracking_small = $data['tracking'][$key1]['trackinfo'][$key2]['status_description'];
                                        }

                                        // 将连续的空格换成一个空格
                                        $statu_tracking = preg_replace('/&rsquo;/', "'", $statu_tracking);
                                        $statu_tracking_small = preg_replace('/\s+/', ' ', $statu_tracking_small);
                                        $statu_tracking_small = trim($statu_tracking_small);

                                        //$statu_tracking_small 55555   $track_info包含before和after  tracking_info_some_translate对一段字符串进行翻译
                                        //对一段字符串进行翻译
                                        $track_info_after = $this->parcelpanel_tracking_info_some_translate($track_info, $statu_tracking_small);
                                        //$statu_tracking_small需要翻译的

                                        // 替换翻译内容
                                        if ($track_info_after != $statu_tracking_small) {
                                            if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['status_description'])) {
                                                $data['tracking'][$key1]['trackinfo'][$key2]['status_description'] = !empty($track_info_after) ? $track_info_after : '';
                                            }
                                            if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['details'])) {
                                                $data['tracking'][$key1]['trackinfo'][$key2]['details'] = !empty($track_info_after) ? $track_info_after : '';
                                            }
                                        }

                                    }
                                }


                            }
                            if (empty($value2['status_description']) && !empty($value2['details'])) {
                                $statu_tracking = $value2['details'];
                                // 将连续的空格换成一个空格
                                $statu_tracking = preg_replace('/&rsquo;/', "'", $statu_tracking);
                                $statu_tracking = preg_replace('/\s+/', ' ', $statu_tracking);
                                $statu_tracking = trim($statu_tracking);

                                // 整句翻译
                                $T_key = array_search($statu_tracking, array_column($track_info, 'before'));
                                if ($T_key === 0 || $T_key != false) {
                                    if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['status_description']) && !empty($track_info[$T_key]['after'])) {
                                        $data['tracking'][$key1]['trackinfo'][$key2]['status_description'] = $track_info[$T_key]['after'];
                                    }
                                    if (!empty($data['tracking'][$key1]['trackinfo'][$key2]['details']) && !empty($track_info[$T_key]['after'])) {
                                        $data['tracking'][$key1]['trackinfo'][$key2]['details'] = $track_info[$T_key]['after'];
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 多个连续空格只保留一个
     *
     * @param string $str 待转换的字符串
     *
     * @return string $string 转换后的字符串
     */
    public function parcelpanel_merge_spaces(string $str): string
    {
        return preg_replace('/\s+/', ' ', $str);
    }

    /**
     * 符号两边添加空格
     *
     * @param $str
     *
     * @return array|string|string[]|null
     */
    public function parcelpanel_add_spaces_to_punct($str)
    {
        $str = " {$str} ";
        return preg_replace("/(\s*([,.])\s*)/", ' ${1} ', $str);
    }

    /**
     * 对一段字符串进行翻译
     * 翻译对应信息组 $track_info 被翻译信息 $statu_tracking
     *
     * @param  $trans_info
     * @param  $text
     *
     * @return string
     */
    public function parcelpanel_tracking_info_some_translate($trans_info, $text): string
    {
        $statu_tracking_str = $text;

        // ASCII值处理
        $asc = chr(194) . chr(160);
        $statu_tracking_str = str_replace($asc, '', $statu_tracking_str);

        if (!empty($trans_info)) {

            foreach ($trans_info as $value) {

                if (empty($value['before'])) {
                    continue;
                }

                $before = !empty($value['before']) ? trim($value['before']) : '';
                $after = !empty($value['after']) ? trim($value['after']) : '';

                if (!empty($before)) {

                    [$before, $after, $statu_tracking_str] = array_map(array($this, 'parcelpanel_add_spaces_to_punct'), [$before, $after, $statu_tracking_str]);
                    [$before, $after, $statu_tracking_str] = array_map(array($this, 'parcelpanel_merge_spaces'), [$before, $after, $statu_tracking_str]);

                    $statu_tracking_str = trim(str_replace($before, $after, $statu_tracking_str));

                }
            }

            // 特殊空格逗号处理
            $statu_tracking_str = str_replace([' ,', ',,', ' .'], [',', ',', '.'], $statu_tracking_str);
            $statu_tracking_str = preg_replace('/[ ]{2,}/', ' ', $statu_tracking_str);
            // 去掉开头结束特殊字符
            // $statu_tracking_str = ppDelSpeciouWord($statu_tracking_str);
            // 首字母大写
            $statu_tracking_str = ucfirst(trim($statu_tracking_str));
            $statu_tracking_str = trim($statu_tracking_str);
        }

        // 单独翻译单个词
        // $statu_tracking_str = tracking_info_small_translate($track_info,$statu_tracking_str);

        return $statu_tracking_str;
    }

    /**
     * 依据用户设置节点获取状态对应
     */
    public function parcelpanel_get_status_keys(array $custom_status): array
    {
        $status_set = 1001;

        $return = [
            $status_set,
        ];

        foreach ($custom_status as $value) {
            $return[] = ++$status_set;
        }

        $return[] = 1100;

        return $return;
    }

    /**
     * 处理数据库查询出来的 单号数据 组装成指定的格式
     */
    public function parcelpanel_handle_track_page_tracking_data($order, $tracking_list): array
    {
        $tracking_rtn = [];

        foreach ($tracking_list as $tracking) {

            $track_data = $this->parcelpanel_origin_unit_destination_data($order, $tracking);

            $track_data = $this->parcelpanel_add_custom_tracking_info($track_data);

            $tracking_rtn[] = $track_data;
        }

        return $tracking_rtn;
    }

    public function parcelpanel_add_custom_tracking_info($track_data)
    {
        $TRACKING_SETTINGS = \ParcelPanel\Models\TrackingSettings::instance()->get_settings();
        $CUSTOM_TRACKING_INFO = $TRACKING_SETTINGS['custom_tracking_info'];
        $DATE_TIME_FORMAT = $TRACKING_SETTINGS['date_and_time_format'];  // 日期时间格式配置
        $TRACK_INFO_DAYS = $CUSTOM_TRACKING_INFO['days'];
        $TRACK_INFO_INFO = $CUSTOM_TRACKING_INFO['info'];
        $DATE_FORMAT = $DATE_TIME_FORMAT['date_format'];  // 日期格式

        if (empty($TRACK_INFO_DAYS)) {
            return $track_data;
        }

        $custom_info = [];

        $shipment_status = $track_data['status_data_num'] ?? 0;

        if (!in_array($shipment_status, [1, 2, 8]) || $track_data['custom_shipment_status']) {
            return $track_data;
        }

        if (!empty($track_data['trackinfo'])) {
            $last_tracking_time = $track_data['trackinfo'][0]['time'] ?? 0;
            // todo d/m/Y date format
        } else {
            // Shipped date
            $last_tracking_time = $track_data['order_fulfill_time'];
        }

        if (empty($last_tracking_time)) {
            return $track_data;
        }

        if ((time() - $last_tracking_time) >= $TRACK_INFO_DAYS * 86400) {

            $date = $this->parcelpanel_handle_time_format($last_tracking_time + $TRACK_INFO_DAYS * 86400, $DATE_FORMAT, null, false, true);
            $custom_info['date'] = $date;
            $custom_info['status_description'] = $TRACK_INFO_INFO;
            $custom_info['details'] = '';
            $custom_info['checkpoint_status'] = 'blank';
        }

        if (!empty($custom_info)) {
            array_unshift($track_data['trackinfo'], $custom_info);
        }

        return $track_data;
    }

    public function parcelpanel_get_formatted_address($args = [])
    {
        $default_args = [
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'state' => '',
            'country' => '',
        ];

        $args = array_map('trim', wp_parse_args($args, $default_args));
        $state = $args['state'];
        $country = $args['country'];

        // Get format for the address' country.
        $format = "{country}\n{state}\n{city}";

        $countries = WC()->countries->get_countries();

        // Handle full country name.
        $full_country = $countries[$country] ?? $country;

        // Country is not needed if the same as base.
        // if ( $country === WC()->countries->get_base_country() && ! apply_filters( 'woocommerce_formatted_address_force_country_display', false ) ) {
        //     $format = str_replace( '{country}', '', $format );
        // }

        $states = WC()->countries->get_states();

        // Handle full state name.
        $full_state = ($country && $state && isset($states[$country][$state])) ? $states[$country][$state] : $state;

        if (false !== strpos($full_state, '/')) {
            $full_state = trim(explode('/', $full_state)[0]);
        } elseif (false !== strpos($full_state, '(')) {
            $full_state = trim(explode('(', $full_state)[0]);
        }

        // Substitute address parts into the string.
        $replace = [
            '{address_1}' => $args['address_1'],
            '{address_2}' => $args['address_2'],
            '{city}' => $args['city'],
            '{state}' => $full_state,
            '{country}' => $full_country,
        ];

        $formatted_address = str_replace(array_keys($replace), $replace, $format);

        // Clean up white space.
        $formatted_address = preg_replace('/  +/', ' ', trim($formatted_address));
        $formatted_address = preg_replace('/\n\n+/', "\n", $formatted_address);

        // Break newlines apart and remove empty lines/trim commas and white space.
        $formatted_address = array_filter(array_map(array($this, 'parcelpanel_trim_formatted_address_line'), explode("\n", $formatted_address)));

        // Add html breaks.
        $formatted_address = implode('-', $formatted_address);

        // We're done!
        return $formatted_address;
    }

    public function parcelpanel_trim_formatted_address_line($line)
    {
        return trim($line, ', ');
    }

    public function parcelpanel_init_formatted()
    {

        add_filter(
            'woocommerce_localisation_address_formats',
            [$this, 'parcelpanel_tracking_page_address_format'],
            20
        );
    }

    public function parcelpanel_tracking_page_address_format()
    {


        return [
            'default' => "{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
            'AT' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'AU' => "{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
            'BE' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'CA' => "{address_1}\n{address_2}\n{city} {state_code} {postcode}\n{country}",
            'CH' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'CL' => "{address_1}\n{address_2}\n{state}\n{postcode} {city}\n{country}",
            'CN' => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}",
            'CZ' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'DE' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'DK' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'EE' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'ES' => "{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
            'FI' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'FR' => "{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
            'HK' => "{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
            'HU' => "{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
            'IN' => "{address_1}\n{address_2}\n{city} {postcode}\n{state}, {country}",
            'IS' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'IT' => "{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
            'JM' => "{address_1}\n{address_2}\n{city}\n{state}\n{country}",
            'JP' => "{postcode}\n{state} {city} {address_1}\n{address_2}\n{country}",
            'LI' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'NL' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'NO' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'NZ' => "{address_1}\n{address_2}\n{city} {postcode}\n{country}",
            'PL' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'PR' => "{address_1} {address_2}\n{city} \n{country} {postcode}",
            'PT' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'RS' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'SE' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'SI' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'SK' => "{address_1}\n{address_2}\n{postcode} {city}\n{country}",
            'TR' => "{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
            'TW' => "{address_1}\n{address_2}\n{state}, {city} {postcode}\n{country}",
            'UG' => "{address_1}\n{address_2}\n{city}\n{state}, {country}",
            'US' => "{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
            'VN' => "{address_1}\n{city}\n{country}",
        ];
    }


    /**
     * 处理发货信息
     */
    public function parcelpanel_origin_unit_destination_data(\WC_Order $order, $tracking): array
    {
        $TRACKING_SETTINGS = \ParcelPanel\Models\TrackingSettings::instance()->get_settings();

        $HIDE_KEYWORDS = $TRACKING_SETTINGS['display_option']['hide_keywords'] ?? '';  // 敏感词
        $IS_SHOW_MAP = $TRACKING_SETTINGS['display_option']['map_coordinates'];  // 地图开关
        $MAP_POSITION = $TRACKING_SETTINGS['display_option']['map_coordinates_position'];  // 参考位置
        $TRANSLATIONS = $TRACKING_SETTINGS['tracking_page_translations'];  // 翻译信息
        $ESTIMATED_DELIVERY_TIME = $TRACKING_SETTINGS['estimated_delivery_time'];  // 预计到达时间配置
        $DATE_TIME_FORMAT = $TRACKING_SETTINGS['date_and_time_format'];  // 日期时间格式配置
        $DATE_FORMAT = $DATE_TIME_FORMAT['date_format'];  // 日期格式
        $TIME_FORMAT = $DATE_TIME_FORMAT['time_format'];  // 时间格式

        // pickup 状态的 id
        $SHIPMENT_PICKUP_ID = 3;

        // 订单创建时间
        $ordered_at = $order->get_date_created()->getOffsetTimestamp();

        // 收货地址
        $shipping_address = $order->get_address('shipping');

        $tracking_number = $tracking->tracking_number;  // 快递单号
        $courier_code = $tracking->courier_code;  // 运输商简码
        $shipment_status = $tracking->shipment_status;  // 单号状态
        $origin_info = $tracking->origin_info;  // 发件国信息
        $destination_info = $tracking->destination_info;  // 目的国信息
        $fulfilled_at = $tracking->fulfilled_at;  // 订单发货时间
        $product = $tracking->product ?? [];  // 商品信息
        $custom_shipment_status = $tracking->custom_shipment_status;

        $shipment_statuses = $this->parcelpanel_get_shipment_statuses();

        // 读取物流信息
        $origin_trackinfo = (array)($origin_info['trackinfo'] ?? []);
        $destination_trackinfo = (array)($destination_info['trackinfo'] ?? []);


        // 合并物流信息
        $trackinfo = array_merge($destination_trackinfo, $origin_trackinfo);

        // 字段映射
        foreach ($trackinfo as &$track_item) {
            // 兼容旧版
            $track_item['status_description'] = $track_item['tracking_detail'] ?? '';
            $track_item['details'] = $track_item['location'] ?? '';
            $track_item['checkpoint_status'] = $track_item['checkpoint_delivery_status'] ?? '';
            $track_item['date'] = $track_item['checkpoint_date'] ?? '';
        }

        // 敏感词过滤
        $trackinfo = $this->parcelpanel_sensitive_word_filtering($trackinfo, $HIDE_KEYWORDS);

        $trackinfo = array_map(array($this, 'parcelpanel_trackinfo_date_to_time'), $trackinfo);

        // 按时间排序
        !empty($trackinfo) && usort($trackinfo, array($this, 'parcelpanel_cmp_trackinfo_time'));

        // 是否显示时效 快递公司预计到达时间如果存在则显示到页面
        $shipping_all_data = $this->parcelpanel_get_shipping_data($origin_info, $trackinfo, $shipping_address, $ESTIMATED_DELIVERY_TIME, $ordered_at, $fulfilled_at);
        $shipping_time_show = !empty($shipping_all_data);

        # 节点时间和状态
        $status_node = [];

        $trackinfo = $this->change_info_status($trackinfo);

        // 状态处理
        foreach ($trackinfo as $key => &$info) {

            $checkpoint_status = $info['checkpoint_status'] ?? '';

            // 运输途中第二次出现时将前一次出现的状态修改为空白
            if (isset($transit_key) && 'transit' == $checkpoint_status) {
                $trackinfo[$transit_key]['checkpoint_status'] = 'blank';
                unset($transit_key);
            }

            // 记录上一次标记运输途中的位置
            if ('transit' == $checkpoint_status) {
                $transit_key = $key;
            }

            // 翻译状态名称
            $info['name'] = $TRANSLATIONS[$checkpoint_status] ?? '';

            // 取时间最小的节点
            $status = $shipment_statuses[$checkpoint_status]['id'] ?? 0;

            if ($status) {
                $status_node[$status] = $info;
            }
        }
        unset($info);

        if ($shipment_status == 1) {
            unset($status_node[2], $status_node[3], $status_node[4]);
        } elseif ($shipment_status == 2) {
            unset($status_node[3], $status_node[4]);
        } elseif ($shipment_status == 3) {
            unset($status_node[4]);
        }

        // 依据用户全局设置的节点信息、单个单号设置的节点信息、订单创建时间、发货时间 返回节点对应信息和对应时间
        $trackinfo_node_data = $this->parcelpanel_handle_tracking_node($status_node, $order, $tracking);

        $status_node = $trackinfo_node_data['status_node'];  // 提取状态节点
        $custom_status_num = $trackinfo_node_data['status_num'];  // 提取用户定义的状态
        $custom_trackinfo = $trackinfo_node_data['track_info'];  // 提取用户定义的物流信息
        $is_over_info = $trackinfo_node_data['is_over_info'];

        // 如果自定义的状态 超过了发货节点 那么强制替换物流信息
        $trackinfo = $is_over_info && !empty($custom_trackinfo) ? $custom_trackinfo : array_merge($trackinfo, $custom_trackinfo);

        // 地图信息
        $shipping_map = [];

        // 发货地址信息
        if ($IS_SHOW_MAP) {

            $this->parcelpanel_init_formatted();

            if (0 === $MAP_POSITION) {
                $map_address = '';
                foreach (array_slice($trackinfo, 0, 3) as $info) {
                    if (!empty($info['location'])) {
                        $map_address = $info['location'];
                        break;
                    }
                }
            } else {
                $map_address = WC()->countries->get_formatted_address($shipping_address, ', ');
            }
        }

        if (!empty($map_address)) {
            $shipping_map['ship'] = $MAP_POSITION;
            $shipping_map['location'] = $map_address;
        }


        # 预计到达时间（时效）
        $scheduled_delivery_date = $origin_data['scheduled_delivery_date'] ?? '';

        $delivery_status = $this->parcelpanel_get_shipment_status($shipment_status);
        $status_str = !empty($delivery_status) ? $shipment_statuses[$delivery_status]['text'] : '';

        if (
            !empty($status_node[2]['name']) &&
            in_array($shipment_status, [5, 6, 7, 8])
        ) {
            // 异常状态进度条文本调整

            if (empty($status_node[3])) {
                $status_node[2]['name'] = $status_str;
                // 替换时间
                if (!empty($status_node[$shipment_status]['time'])) {
                    $status_node[2]['time'] = $status_node[$shipment_status]['time'];
                }
            } elseif (in_array($shipment_status, [5, 6, 7])) {
                $status_node[3]['name'] = $status_str;
                // 替换时间
                if (!empty($status_node[$shipment_status]['time'])) {
                    $status_node[3]['time'] = $status_node[$shipment_status]['time'];
                }
            }
        } elseif (!empty($custom_status_num)) {
            // 根据用户自定义状态来修改用户实际订单状态

            $custom_status = intval($custom_status_num['status']);
            if (in_array($custom_status, [1001, 1002, 1003, 1004, 1100])) {
                $custom_status = 1;
            }
            $delivery_status = $this->parcelpanel_get_shipment_status($custom_status);
            $status_str = empty($delivery_status) ? $status_str : $shipment_statuses[$delivery_status]['text'];
        }


        // + 格式化日期时间 +
        foreach ($status_node as &$value) {
            $value['date'] = !empty($value['time']) ? $this->parcelpanel_handle_time_format($value['time'], $DATE_FORMAT, null, true, true) : '';
        }

        foreach ($trackinfo as &$value) {
            $_is_hidden_time = isset($value['only_show_day']);
            $value['date'] = !empty($value['time']) ? $this->parcelpanel_handle_time_format($value['time'], $DATE_FORMAT, $TIME_FORMAT, false, $_is_hidden_time) : '';
        }

        if (is_array($shipping_all_data)) {

            foreach ($shipping_all_data as &$t) {
                $t = $this->parcelpanel_handle_time_format($t, $DATE_FORMAT, null, false, true);
            }

            // 预计到达时间
            $shipping_time_con = implode(' - ', $shipping_all_data);
        }
        // - 格式化日期时间 -


        // 发件国家电话
        $phone = $origin_data['courier_phone'] ?? '';

        // 发件国家查询链接
        $web_link = $origin_data['weblink'] ?? '';

        if ('global' === $courier_code) {
            $courier_code = 'cainiao';
        }

        // 根据简码读取运输商基本信息
        $express_info = $this->parcelpanel_get_courier_info($courier_code);

        // 发件国简码
        $carrier_code = $express_info->code ?? $origin_data['courier_code'] ?? '';

        // Track Url
        $track_url = $express_info->track_url ?? $web_link;
        if (!empty($tracking_number) && !empty($track_url)) {
            $track_url = str_replace('******', $tracking_number, $track_url);
        }

        $courier_url = 'https://cdn.parcelpanel.com/assets/common/images/express/';
        // 运输商信息
        $carrier_info = [
            'code' => $carrier_code,
            'name' => $express_info->name ?? $carrier_code,
            // 'img'  => $express_info->logo ?? '',
            // 'img'  => parcelpanel_get_assets_path( "imgs/express/{$carrier_code}.png" ),
            'img' => $courier_url . "{$carrier_code}.png",
            'tel' => $express_info->tel ?? $phone,
            'url' => $track_url,
        ];

        return [
            'tracking_number' => $tracking_number,  // 快递单号
            'shipping_map' => $shipping_map,
            'scheduled_delivery_date' => $scheduled_delivery_date,
            'order_create_time' => $ordered_at,  // 订单创建时间
            'order_fulfill_time' => $fulfilled_at,  // 订单发货时间
            'status_num' => $custom_status_num,  // 状态节点信息
            'shipping_time_show' => $shipping_time_show,
            'shipping_time_con' => $shipping_time_con ?? '',
            'status' => $status_str,
            'status_data_num' => $shipment_status,
            'custom_shipment_status' => $custom_shipment_status,
            'status_node' => $status_node,
            'carrier' => $carrier_info,
            'trackinfo' => $trackinfo,  // 物流信息
            'product' => $product,
        ];
    }

    /**
     * 时间转换
     */
    public function parcelpanel_trackinfo_date_to_time($item)
    {

        $date = $item['checkpoint_date'] ?? $item['date'] ?? '';

        $item['time'] = strtotime($date) ?: 0;

        return $item;
    }

// function pp_trackinfo_trans_status_name( $item )
// {
//     $info[ 'name' ] = $TRANSLATIONS[ $item[ 'checkpoint_status' ] ?? '' ] ?? '';
// }


    /**
     * 依据用户全局设置的节点信息、单个单号设置的节点信息、订单创建时间、发货时间 返回节点对应信息和对应时间
     */
    public function parcelpanel_handle_tracking_node($status_node, $order, $tracking): array
    {
        $TRACKING_SETTINGS = \ParcelPanel\Models\TrackingSettings::instance()->get_settings();

        $TRANSLATIONS = $TRACKING_SETTINGS['tracking_page_translations'];  // 翻译信息
        $CUSTOM_STATUSES = $TRACKING_SETTINGS['custom_order_status'];  // 自定义状态

        // 订单创建时间
        $ordered_at = $order->get_date_created()->getOffsetTimestamp();

        $shipment_status = $tracking->shipment_status;  // 单号状态
        $fulfilled_at = $tracking->fulfilled_at;  // 订单发货时间
        $custom_status_time = (array)$tracking->custom_status_time;
        $custom_shipment_status = $tracking->custom_shipment_status;

        if (!empty($custom_shipment_status)) {
            $status_node = [];
        }

        $rtn = [
            'status_node' => $status_node,
            'status_num' => [],
            'track_info' => [],
            'is_over_info' => false,
        ];

        # 记录物流信息中的状态
        $old_status_node = $status_node;

        $status_num = [];

        // 自定义状态 与 准备就绪 的 tracking 信息
        $track_info = [];

        $is_over_info = false;

        ### 获取所有的状态节点 开始 ###

        # 如果订单创建时间为空
        if (empty($ordered_at) || !is_array($status_node)) {
            return $rtn;
        }

        $TEXT_ORDERED = $TRANSLATIONS['ordered'] ?? '';
        $TEXT_ORDER_READY = $TRANSLATIONS['order_ready'] ?? '';
        $TEXT_DELIVERED = $TRANSLATIONS['delivered'] ?? '';
        $TEXT_NOT_YET_SHIPPED = $TRANSLATIONS['not_yet_shipped'] ?? '';
        $TEXT_WAITING_UPDATED = $TRANSLATIONS['waiting_updated'] ?? '';

        $status_keys = $this->parcelpanel_get_status_keys($CUSTOM_STATUSES);

        $status_set = $status_set_start = current($status_keys);

        $status_set_end = end($status_keys);

        // Ordered 订单创建节点
        $status_node[$status_set_start] = [
            'time' => $ordered_at,
            'name' => $TEXT_ORDERED,
        ];

        // Order Ready 订单发货节点
        $status_node[$status_set_end] = [
            'time' => empty($custom_shipment_status) ? $fulfilled_at : 0,
            'name' => $TEXT_ORDER_READY,
        ];

        // 订单创建和发货之间的节点 用户自定义的节点
        if (!empty($CUSTOM_STATUSES)) {

            $start_time = $ordered_at;

            # 有些单号可能还没有同步到发货时间 但是实际上已经发货了 避免时间出现 1970
            ## 如果设置了发货节点 那么发货时间修改为设置的时间
            $end_time = ($custom_status_time[$status_set_end] ?? $fulfilled_at) ?: time();

            foreach ($CUSTOM_STATUSES as $value) {

                $status_set++;

                $days = intval($value['days'] ?? 0);   // 时间间隔
                $status = trim($value['status'] ?? '');  // 节点名称
                $info = trim($value['info'] ?? '');    // 节点物流信息

                // 将创建时间 加上设置的时间
                $start_time += $days * DAY_IN_SECONDS;

                # 如果设置的时间超过了发货时间 那么取发货时间
                $start_time = min($start_time, $end_time);

                // 如果设置了时间节点 那么以设置的为准
                $start_time = $custom_status_time[$status_set] ?? $start_time;

                $time = $start_time >= time() ? 0 : $start_time;

                $status_node[$status_set] = [
                    'time' => $time,
                    'name' => $status,
                    'status_description' => $info,
                ];

                ### 如果是未发货的订单号 或者是发货了但是没有单号 或者没有物流信息的单号 获取用户设置的当前状态 开始 ###

                if (empty($old_status_node) && (empty($custom_shipment_status) || empty($custom_status_time))) {
                    # 只有没有物流信息的时候处理 //并且没有对此单号做单独的状态设置

                    if (empty($fulfilled_at)) {
                        # 如果还没有发货

                        # 从用户设置的节点信息中提取时间 检查节点时间是否已经超过了当前时间 未超过则修改单号状态
                        if ($start_time < time()) {
                            $status_num = $this->parcelpanel_set_custom_status($status_set, $status, $info);
                        }
                    } elseif (empty($status_num)) {
                        # 如果已经发货了 只设置一次

                        $status_num = $this->parcelpanel_set_custom_status($status_set_end, $TEXT_ORDER_READY, $TEXT_WAITING_UPDATED);
                    }
                }

                ### 如果是未发货的订单号 或者是发货了但是没有单号 或者没有物流信息的单号 获取用户设置的当前状态 结束 ###

                ### 在没有自定义物流信息的时候 新加 物流信息 开始 ###

                if (!empty($time) && (empty($custom_shipment_status) || empty($custom_status_time))) {

                    $info = $info ?: $status;

                    $track_info[] = $this->parcelpanel_set_custom_trackinfo($time, $info, 'blank');
                }

                ### 在没有自定义物流信息的时候 新加 物流信息 结束 ###

                // 未选择的自定义状态，时间取消
                if (!empty($custom_shipment_status)) {
                    if (empty($custom_status_time[$status_set]) && !empty($status_node[$status_set]['time'])) {
                        $status_node[$status_set]['time'] = 0;
                    }
                }
            }

            # 如果已发货 那么添加发货节点 （有设置的以设置为准）
            if (!empty($fulfilled_at) && empty($custom_shipment_status)) {

                // $date = empty( $order_fulfill_time ) ? "" : pp_handle_time_format( $order_fulfill_time, $format_date, null );
                $time = $fulfilled_at;

                $track_info[] = $this->parcelpanel_set_custom_trackinfo($time, $TEXT_ORDER_READY, 'blank');
            }
        }

        # 如果没有物流信息 并且没有发货时间 也没有超过设置的时间 设置状态
        if (empty($status_num) && empty($fulfilled_at) && empty($old_status_node)) {
            $status_num = $this->parcelpanel_set_custom_status($status_set_start, $TEXT_ORDERED, $TEXT_NOT_YET_SHIPPED);
        }

        ### 获取所有的状态节点 结束 ###

        ### 检查用户是否自定义了单号状态 开始 ###

        // 如果定义了此单号的状态信息 那么以当前定义的状态为准
        if (!empty($custom_shipment_status)) {
            $is_over_info = true;
            // 获取状态列表
            $custom_status_node = $this->parcelpanel_get_custom_status();
            $custom_status_node_by_status = array_column($custom_status_node, null, 'status');

            $checkpoint_status_config = [
                2 => 'transit',
                3 => 'pickup',
                4 => 'delivered',
                6 => 'undelivered',
                7 => 'exception',
            ];

            // When 2 and 3 are empty, set the time of 6 or 7 to 3
            foreach ([6, 7] as $status_id) {
                if (!empty($custom_status_time[$status_id])) {
                    if (empty($custom_status_time[2]) && empty($custom_status_time[3])) {
                        $custom_status_time[3] = $custom_status_time[$status_id];
                    }
                    break;
                }
            }

            if ($custom_shipment_status == 1001) {
                // When the status is set to the `ordered`, only `ordered` tracking info is displayed.
                $tp_status_progress_bar_all_node_ids = [1001];
                $custom_status_time = [1001 => $ordered_at];
            } else {
                $tp_status_progress_bar_all_node_ids = [1002, 1003, 1004, 1100, 2, 3, 4];

                // Add 6 or 7 to 2 or 3 according to the time order
                $status_4_index = array_search(4, $tp_status_progress_bar_all_node_ids);
                $status_3_index = array_search(3, $tp_status_progress_bar_all_node_ids);
                if (!empty($custom_status_time[6])) {
                    if (!empty($custom_status_time[3]) && $custom_status_time[6] >= $custom_status_time[3]) {
                        // 插入到3后面，也就是4的位置
                        array_splice($tp_status_progress_bar_all_node_ids, $status_4_index, 0, 6);
                    } elseif (!empty($custom_status_time[2])) {
                        // 插入到2后面
                        array_splice($tp_status_progress_bar_all_node_ids, $status_3_index, 0, 6);
                    }
                } elseif (!empty($custom_status_time[7])) {
                    if (!empty($custom_status_time[3]) && $custom_status_time[7] >= $custom_status_time[3]) {
                        // 插入到3后面，也就是4的位置
                        array_splice($tp_status_progress_bar_all_node_ids, $status_4_index, 0, 7);
                    } elseif (!empty($custom_status_time[2])) {
                        // 插入到2后面
                        array_splice($tp_status_progress_bar_all_node_ids, $status_3_index, 0, 7);
                    }
                }
            }
            $tp_status_progress_bar_all_node_ids = array_intersect($tp_status_progress_bar_all_node_ids, array_merge(array_keys($custom_status_node_by_status), array_keys($custom_status_time)));

            // Fill in the node with the time of using the large node
            $last_time = 0;
            foreach (array_reverse($tp_status_progress_bar_all_node_ids) as $status_id) {
                if (!empty($custom_status_time[$status_id])) {
                    $last_time = $custom_status_time[$status_id];
                    continue;
                }
                if (!empty($last_time)) {
                    $custom_status_time[$status_id] = $last_time;
                }
            }

            // Sorting custom status.
            $_custom_status_time = [];
            foreach ($tp_status_progress_bar_all_node_ids as $status_id) {
                if (!empty($custom_status_time[$status_id])) {
                    $_custom_status_time[$status_id] = $custom_status_time[$status_id];
                }
            }
            $custom_status_time = $_custom_status_time;

            $i = 0;
            foreach ($custom_status_time as $_status_id_1 => $time) {
                /** @var string $_status_id_1 status id */
                /** @var int $time timestamp */
                $i++;
                $name = $custom_status_node_by_status[$_status_id_1]['name'] ?? '';
                $info = $custom_status_node_by_status[$_status_id_1]['info'] ?? '';
                $info = $info ?: $name;

                $status_node[$_status_id_1] = [
                    'time' => $time,
                    'name' => $name,
                ];

                if (array_key_exists($_status_id_1, $checkpoint_status_config)) {
                    $checkpoint_status = $checkpoint_status_config[$_status_id_1];
                } else {
                    $checkpoint_status = 'blank';
                }

                // 组装物流信息
                if (!empty($info)) {
                    $track_info[] = $this->parcelpanel_set_custom_trackinfo($time, $info, $checkpoint_status, $detail = '', $only_show_day = 1);
                }

                if ($custom_shipment_status == 1100) {
                    // 如果是发货
                    $info = $TEXT_WAITING_UPDATED;
                } elseif ($custom_shipment_status < 1000) {
                    // 如果状态超过了运输途中
                    $info = '';
                }

                // 当查询不到的时候 并且状态吻合 或者子自定义的状态超过了运输途中 设置状态
                if (($_status_id_1 == $custom_shipment_status && $shipment_status <= 1) || $custom_shipment_status < 1000) {
                    $status_num = $this->parcelpanel_set_custom_status($_status_id_1, $name, $info);
                }

                // 状态修改为用户自定义状态
                if (count($custom_status_time) == $i) {
                    $shipment_status = $_status_id_1;
                }
            }

            // 如果设置的状态 超过了运输途中 那么覆盖原有的物流信息
            // $status_arr = ['1','2','3','4','5','6','7','8'];
            foreach ($status_node as $key => $value) {
                if ($key == 1001) {
                    continue;
                }
                if (empty($custom_status_time[$key]) && !empty($value['time'])) {
                    $status_node[$key]['time'] = 0;
                }
            }
        }

        # 如果有发货时间 并且没有设置时间 设置状态 （不管是否发货，以设置的为准）
        if (!empty($fulfilled_at) && empty($old_status_node) && (empty($custom_shipment_status) || ($custom_shipment_status > 1000 && $custom_shipment_status < 1100))) {
            $status_num = $this->parcelpanel_set_custom_status($status_set_end, $TEXT_ORDER_READY, $TEXT_WAITING_UPDATED);
        }


        // 获取当前节点信息
        $status_num_arr = [4, 3, 2, 1100, 1004, 1003, 1002, 1001];
        foreach ($status_num_arr as $value) {

            if (!empty($status_node[$value]['time'])) {

                $name = $status_node[$value]['name'] ?? '';
                $status_description = trim($status_node[$value]['status_description'] ?? '');

                if (!empty($status_num['status']) && $value == $status_num['status']) {
                    $status_description = $status_num['status_description'];
                }

                $status_num = $this->parcelpanel_set_custom_status($value, $name, $status_description);
                break;
            }
        }

        if (count($track_info) > 1) {
            $track_info = array_reverse($track_info);
        }

        // 节点状态时间调整 transit < order_ready 时 transit 取 order_ready
        // 如果发货时间 $order_fulfill_time 大于签收时间 不改
        if (is_array($status_node)) {

            if (!empty($status_node[2]) && !empty($status_node[1100])) {

                $order_time = $status_node[1100]['time'];
                $transit_time = $status_node[2]['time'];
                $delivered_time = $status_node[4]['time'] ?? 0;
                $change_time = false;

                if (!empty($delivered_time)) {
                    if ($delivered_time < $order_time) {
                        $change_time = true;
                    }
                }

                if (!empty($order_time) && !empty($transit_time) && !$change_time) {
                    if ($transit_time < $order_time) {
                        $status_node[2]['time'] = $status_node[1100]['time'];
                    }
                }
            }
        }

        // 订单状态为签收状态 但 status_node 进度条没有签收  (获取 shopify 状态进行的判断)
        if (4 == $shipment_status && (empty($status_num) || 4 != $status_num['status'])) {
            $status_num = [
                'status' => 4,
                'name' => $TEXT_DELIVERED,
                'status_description' => '',
            ];
        }

        ### 检查用户是否自定义了单号状态 结束 ###

        return [
            'status_node' => $status_node,
            'status_num' => $status_num,
            'track_info' => $track_info,
            'is_over_info' => $is_over_info,
        ];
    }

    public function parcelpanel_sensitive_word_sort($a, $b)
    {
        return mb_strlen($a) > mb_strlen($b) ? -1 : 1;
    }

    /**
     * 物流信息过滤敏感词
     * $info array 指定的物流信息格式
     * $sensitive_world array or string 需要过滤的词
     * 物流信息格式优化
     */
    public function parcelpanel_sensitive_word_filtering($info, string $sensitive_world = '')
    {
        if (empty($info) || !is_array($info)) {
            return $info;
        }

        // 针对 postman :tees  情况隐藏 postman 去掉 postman :tees
        $sensitive_word_new = [];

        $sensitive_word_list = [];

        if (!empty($sensitive_world)) {

            // 字符串都转换为小写
            $sensitive_world = strtolower($sensitive_world);

            // 分割数组
            $sensitive_word_list = array_map('trim', array_filter(explode(',', $sensitive_world)));

            // 如果用户输入的配置中有 Chinese cities，表示要隐藏中国所有城市名称
            $key_chinese_cities = array_search('chinese cities', $sensitive_word_list);
            if ($key_chinese_cities !== false) {
                unset($sensitive_word_list[$key_chinese_cities]);  // 删除 chinese cities 这一项
                $sensitive_word_list = array_merge($sensitive_word_list, $this->parcelpanel_get_chinese_cities_list());
            }

            usort($sensitive_word_list, array($this, 'parcelpanel_sensitive_word_sort'));

            // 针对 postman :tees  情况隐藏 postman 去掉 postman :tees
            if (in_array('postman', $sensitive_word_list)) {
                $sensitive_word_new[] = "/postman :[A-Za-z0-9,]*/";
            }
            if (in_array('postmana', $sensitive_word_list)) {
                $sensitive_word_new[] = "/postman :.*/";
            }
        }

        $details_start_arr = ['-', ',', '，'];

        foreach ($info as &$value) {

            // 对 info 信息进行处理 （符号问题）
            $status_description = trim($value['status_description'] ?? '');
            $details = trim($value['details'] ?? '');

            // 优化文本显示 status_description
            if (!empty($status_description)) {

                // 去掉开头结束特殊字符 首字母大写
                $status_description = ucfirst($this->parcelpanel_del_speciou_word($status_description));

                $value['status_description'] = $status_description;
            }

            // 优化文本显示 details
            if (!empty($details)) {
                foreach ($details_start_arr as $value2) {
                    $search2 = "/^{$value2}.*/";
                    $isstart2 = preg_match($search2, $details);

                    // 去掉开头的特殊符号
                    if (!empty($isstart2)) {
                        $details = preg_replace("#^{$value2}#i", '', $details);
                    }
                }

                $value['details'] = $details;
            }

            // 循环过滤指定字符
            if (!empty($sensitive_word_list)) {

                if (!empty($details)) {
                    $str_replace = "{$status_description}, {$details}";
                } else {
                    $str_replace = $status_description;
                }

                $str_replace = $this->parcelpanel_merge_spaces($str_replace);

                if (!empty($sensitive_word_new)) {
                    $str_replace = preg_replace($sensitive_word_new, '', $str_replace);
                }

                // 隐藏关键词
                $str_replace = trim(str_ireplace($sensitive_word_list, '', $str_replace));

                // 去掉开头结束特殊字符
                $str_replace = $this->parcelpanel_del_speciou_word($str_replace);


                if ($str_replace == ',') {
                    $str_replace = '';
                }

                if (!empty($str_replace)) {
                    $search4 = "/.*?,$/";
                    $isend4 = preg_match($search4, $str_replace);
                    // 去掉结尾的逗号
                    if (!empty($isend4)) {
                        $str_replace = preg_replace('#,$#i', '', $str_replace);
                    }
                }

                // 获取开头第一个字符，使用 mb_substr 是考虑到可能有中文字符的情况也能符合预期的正确截取
                $startChar = mb_substr($str_replace, 0, 1);

                //如果开头第一个字符是这个数组里面的，那么输出的字符就从第一位开始输出
                if (in_array($startChar, [',', '/'])) {
                    $str_replace = substr($str_replace, 1);
                }

                $info_status_description = ucfirst(trim($str_replace));


                if (preg_match("/【(\s*,)+|(\s*,){2,}|(,\s*)+】/", $info_status_description)) {
                    $info_status_description = preg_replace(["/(\s*,){2,}/", "/【(\s*,)+/", "/(,\s*)+】/"], [", ", '【', '】'], $info_status_description);
                }

                // 特殊符号中间不带内容去掉符号
                $info_status_description = preg_replace(
                    ["/\[\s*\]/", "/\(\s*\)/", "/【\s*】/"],
                    "", $info_status_description);

                $info_status_description = preg_replace(
                    ["/\s{2,}/", "/\s+,/"], [" ", ","],
                    $info_status_description);


                // 优化文本显示 status_description
                if (!empty($info_status_description)) {
                    // 去掉开头结束特殊字符
                    $info_status_description = $this->parcelpanel_del_speciou_word($info_status_description);
                    // 首字母大写
                    $info_status_description = ucfirst($info_status_description);
                }

                $value['status_description'] = $info_status_description;

                // 针对地图添加地址
                $value['details_map'] = $value['details'] ?: '';
                $value['details'] = '';
            }
        }

        return $info;
    }

    /**
     * 去掉开头结束特殊字符
     */
    public function parcelpanel_del_speciou_word($status_description): string
    {

        $des_start_arr = [',', '，'];
        $des_end_arr = ['\.', ',', '，'];

        foreach ($des_start_arr as $key3 => $value3) {
            $start3 = $value3;
            $search3 = "/^{$start3}.*/";
            $isstart3 = preg_match($search3, $status_description);

            // 去掉开头的特殊符号
            if (!empty($isstart3)) {
                $status_description = preg_replace("#^{$start3}#i", '', $status_description);
            }
        }

        foreach ($des_end_arr as $key1 => $value1) {
            $end1 = $value1;
            $search1 = "/.*?{$end1}$/";
            $isend1 = preg_match($search1, $status_description);

            // 去掉结尾的句号
            if (!empty($isend1)) {
                $status_description = preg_replace("#{$end1}\$#i", '', $status_description);
            }
        }

        return trim($status_description);
    }

    public function parcelpanel_get_shipping_data($original_info, $trackinfo, $shipping_address, $shipping_time, $order_create_time, $order_fulfill_time)
    {
        // 快递信息有预计到达时间
        $scheduled_delivery_date = strtotime($original_info['scheduled_delivery_date'] ?? '') ?: 0;

        // 获取最后一条物流信息  shipping_time  用户设置时效
        $info_last_time = 0;
        $info_last_status = '';

        if (!empty($scheduled_delivery_date) || !empty($shipping_time)) {
            if (!empty($trackinfo)) {
                $info_last = $trackinfo[0] ?? [];
                $info_last_time = strtotime($info_last['date'] ?? '') ?: 0;
                $info_last_status = $info_last['checkpoint_status'] ?? '';
            }
        }

        // 时效数据获取 是否设置时效
        if ('delivered' != $info_last_status) {

            if (!empty($scheduled_delivery_date) && $info_last_time < $scheduled_delivery_date) {

                return [$scheduled_delivery_date];

            } elseif (!empty($shipping_time)) {

                $shipping_enabled = $shipping_time['enabled'] ?? false;
                $shipping_calc_from = $shipping_time['calc_from'] ?? 0;
                $shipping_all_set = $shipping_time['e_d_t'] ?? [10, 20];
                $shipping_bod_items = $shipping_time['bod_items'] ?? [];
                [$first_ship, $last_ship] = $shipping_all_set;

                if (!$shipping_enabled) {
                    return null;
                }

                foreach ($shipping_bod_items as $bod_item) {
                    if ($bod_item['to'] == $shipping_address['country']) {
                        [$first_ship, $last_ship] = $bod_item['edt'];
                        break;
                    }
                }

                /* 0: Order created time, 1: Order fulfilled time */
                $ref_time = $shipping_calc_from === 1 ? $order_fulfill_time : $order_create_time;
                if ($ref_time <= 0) {
                    return null;
                }

                $start_show = $ref_time + ($first_ship ?? 10) * DAY_IN_SECONDS;
                $end_show = $ref_time + ($last_ship ?? 20) * DAY_IN_SECONDS;

                if ($info_last_time < $end_show && $start_show != $end_show) {
                    return [$start_show, $end_show];
                }
            }
        }

        return 0;
    }

    /**
     * 时间排序规则
     */
    public function parcelpanel_cmp_trackinfo_time($a, $b): int
    {
        $a = $a['time'] ?? 0;
        $b = $b['time'] ?? 0;
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }

    /**
     * 设置状态
     */
    public function parcelpanel_set_custom_status($code, $name, $info): array
    {
        return [
            'status' => $code,
            'name' => $name,
            'status_description' => $info,
        ];
    }

    /**
     * 设置物流信息
     */
    public function parcelpanel_set_custom_trackinfo($time, $info, $checkpoint_status, $detail = "", $only_show_day = 0): array
    {
        return [
            'time' => $time,
            'date' => '',
            'status_description' => $info,
            'details' => $detail,
            'checkpoint_status' => $checkpoint_status,
            'only_show_day' => $only_show_day,
        ];
    }

    public function change_info_status($info)
    {
        if (empty($info)) {
            return $info;
        }

        // 对应签收与到达代取状态只保留一条
        foreach ($info as $key => $value) {
            $checkpoint_status = !empty($value["checkpoint_status"]) ? trim($value["checkpoint_status"]) : '';
            // 已有 delivered ，后面的 delivered 状态改为 transit
            if (!empty($firstDelivered)) {
                if ($checkpoint_status == 'delivered') {
                    $info[$key]['checkpoint_status'] = 'transit';
                    $info[$key]['substatus'] = 'transit001';
                }
            }
            if ($checkpoint_status == 'delivered') {
                $firstDelivered = 1;
            }

            // 已有 pickup ，后面的 pickup 状态改为 transit
            if (!empty($firstPickup)) {
                if ($checkpoint_status == 'pickup') {
                    $info[$key]['checkpoint_status'] = 'transit';
                    $info[$key]['substatus'] = 'transit001';
                }
            }
            if ($checkpoint_status == 'pickup') {
                $firstPickup = 1;
            }
        }

        return $info;
    }

    public function parcelpanel_get_datetime_base_on_connected_time($duration = 'P30D')
    {
        $connected_at = get_option(\ParcelPanel\OptionName\CONNECTED_AT);

        if (empty($connected_at)) {
            return null;
        }

        try {
            $connected_datetime = new \DateTime("@{$connected_at}", new \DateTimeZone('UTC'));
            // -30天
            $connected_datetime->sub(new \DateInterval($duration));

            return $connected_datetime;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function parcelpanel_get_custom_status(): array
    {
        $TRANSLATIONS = TrackingSettings::instance()->tracking_page_translations;
        $CUSTOM_ORDER_STATUS = TrackingSettings::instance()->custom_order_status;

        $status_config = $this->parcelpanel_get_shipment_statuses();

        $status_list_key = ['order_ready', 'transit', 'pickup', 'delivered', 'exception'];

        $status_arr = [];

        foreach ($status_list_key as $value) {

            $name = $TRANSLATIONS[$value] ?? '';

            if ($value === 'order_ready' && !empty($CUSTOM_ORDER_STATUS)) {

                $status_set = 1002;

                # 将自定义节点放到 ordered 和 order_ready 之间
                foreach ($CUSTOM_ORDER_STATUS as $val) {

                    $status = empty($val['status']) ? '' : $val['status'];
                    $days = empty($val['days']) ? 0 : $val['days'];
                    $info = empty($val['info']) ? '' : $val['info'];
                    if (empty($status)) continue;

                    $status_arr[] = ['name' => $status, 'time' => $days, 'status' => $status_set, 'info' => $info];

                    $status_set++;

                }

            } elseif (empty($name)) {

                $name = empty($status_config[$value]['text']) ? '' : $status_config[$value]['text'];
            }

            $status = empty($status_config[$value]['id']) ? 0 : $status_config[$value]['id'];

            switch ($value) {
                case 'ordered':
                    $status = 1001;
                    break;
                case 'order_ready':
                    $status = 1100;
                    break;
            }

            $status_arr[] = ['name' => $name, 'key' => $value, 'status' => $status];
        }

        return $status_arr;
    }


    /**
     * Post ShopOrder 查询参数
     *
     * @param array $query_vars
     * @param int $after_date default: 30 day ago
     * @param int $sync_status 0: 未同步成功, 1: 同步成功, false: 不过滤
     *
     * @return array|\WP_Error 异常：未安装异常
     */
    public function parcelpanel_get_shop_order_query_args($query_vars = [], $after_date = 30, $sync_status = 0, $limit = 100)
    {
        $wp_query_args = [
            'fields' => 'ids',
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'orderby' => 'post_date',
            'order' => 'ASC',
            'posts_per_page' => $limit,
        ];

        // $wp_query_args = wp_parse_args( $query_vars, $wp_query_args );

        if (is_numeric($after_date)) {

            // $connected_datetime = pp_get_datetime_base_on_connected_time();
            //
            // if ( empty( $connected_datetime ) ) {
            //     return new \WP_Error( 'no_install' );
            // }
            //
            // $wp_query_args[ 'date_query' ] = [
            //     'column'    => 'post_date_gmt',
            //     'after'     => $connected_datetime->format( 'Y-m-d H:i:s' ),
            //     'inclusive' => true,
            // ];

            $wp_query_args['date_query'] = [
                'column' => 'post_date',
                'after' => "{$after_date} day ago",
                'inclusive' => true,
            ];
        }

        if (0 === $sync_status) {
            $wp_query_args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_parcelpanel_sync_status',
                    'compare_key' => 'NOT EXISTS',
                ],
                [
                    'key' => '_parcelpanel_sync_status',
                    'value' => '1',
                    'compare' => '!=',
                ],
            ];
        } elseif (1 === $sync_status) {
            $wp_query_args['meta_query'] = [
                [
                    'key' => '_parcelpanel_sync_status',
                    'value' => '1',
                ],
            ];
        }

        return $wp_query_args;
    }

    public function parcelpanel_get_chinese_cities_list(): array
    {
        return [
            "-&gt;中国-华东分拣中心",
            "[4PX]",
            "[China]",
            "[Chinese City ]",
            "[CN,HKG]",
            "[CN]STOPCRAWLER",
            "[HeFei City ]",
            "【CN ->中国-华东分拣中心】",
            "【CN.Yiwu -&gt;中国-华东分拣中心】",
            "【CN.Yiwu -&gt;中国-华东分拣中心】, CN.Yiwu",
            "【GUANGZHOU",
            "4px",
            "4PX /",
            "amp;",
            "Anhui",
            "Ankang",
            "Anqing",
            "Anshun",
            "Anyang",
            "Baicheng",
            "Baise",
            "Baishan",
            "Baiyin",
            "Baiyun",
            "Baoding",
            "Baoji",
            "Baoshan",
            "Baotou",
            "Beihai",
            "Beijing",
            "Bengbu",
            "Binzhou",
            "Boertala",
            "Bozhou",
            "Changsha",
            "chaoshan",
            "Chaozhou",
            "Chenzhou",
            "Chifeng",
            "CHINA MAINLAND",
            "CHINE",
            "Chongming",
            "Chongqing",
            "ChuKou ExchangeJuLiuCun",
            "Chuzhou",
            "cn",
            "CN.East",
            "CN.Shanghai",
            "CN.South",
            "CN.Yiwu",
            "Dali",
            "Dalian",
            "Dandong",
            "Danzhou",
            "Daqing",
            "Datong",
            "Daxinganling",
            "Dazhou",
            "Dingxi",
            "Diqing",
            "Dongguan",
            "Dongying",
            "EMS",
            "Ems-Wuxi",
            "Ezhou",
            "fenggang",
            "Foshan",
            "Fuzhou",
            "Gannan",
            "Ganzhou",
            "Ganzi",
            "GuangDongSheng",
            "guangzhou",
            "Guankou Town",
            "Guigang",
            "Guiyang",
            "GuoJi",
            "Guyuan",
            "Haibei",
            "Haikou",
            "Hainan",
            "HaiWai",
            "Haixi",
            "Hami",
            "Handan",
            "HangZhou",
            "Harbin",
            "Hefei",
            "HeFei City",
            "Hengshui",
            "Hengyang",
            "Hetian",
            "Heze",
            "Hezhou",
            "Hohhot",
            "Hong Kong",
            "Honghe",
            "hongkong",
            "Hongqiao",
            "Huaibei",
            "Huainan",
            "Huainan",
            "huizhou",
            "Huludao",
            "Hulunbuir",
            "HuMen",
            "Huzhou",
            "in SHENZHEN",
            "Ji'an",
            "Jiamusi",
            "Jiaozuo",
            "Jiaxing",
            "Jiayuguan",
            "Jilin",
            "Jinchang",
            "jingjiang",
            "Jingmen",
            "Jingzhou",
            "Jinhua",
            "Jiujiang",
            "Jiuquan",
            "Karamay",
            "Kashen",
            "Kezilesukeerkezi",
            "Kunn",
            "Laiwu",
            "LanTou",
            "Lanzhou",
            "Leshan",
            "Liangshan",
            "Lianyungang",
            "Liaocheng",
            "Liaoyang",
            "Liaoyuan",
            "Lijiang",
            "Lincang",
            "Linfen",
            "Linyi",
            "Lishui",
            "Liupanshui",
            "Loudi",
            "Lu'an",
            "Lüliang",
            "Luohe",
            "Maoming",
            "Meishan",
            "Mianyang",
            "Mudanjiang",
            "Nanchang",
            "Nanchong",
            "Nanjing",
            "Nanning",
            "Nanping",
            "Nantong",
            "Nanyang",
            "Naqu",
            "Neijiang",
            "nghai",
            "Ningbo",
            "Ningde",
            "Ordos",
            "Panjin",
            "Pingxiang",
            "postman",
            "Pudong",
            "Pudong International",
            "Putian",
            "Puyang",
            "Qingyuan",
            "Qinhuangdao",
            "Qitaihe",
            "QUANZHOU",
            "Quzhou",
            "Sanmenxia",
            "Sanming",
            "Sanya",
            "SHA",
            "Shanghai",
            "Shangluo",
            "Shangqiu",
            "Shangrao",
            "Shaoxing",
            "Shaoyang",
            "Shatian Town",
            "Shenzen",
            "Shenzhen",
            "SHFBD",
            "Shijiazhuang",
            "Shiyan",
            "Shizuishan",
            "Shuangyashan",
            "Siping",
            "Suihua",
            "Suining",
            "Suzhou",
            "Tacheng",
            "Taiwan",
            "Taiyuan",
            "Taizhou",
            "Tangshan",
            "tian Town",
            "Tianjin",
            "Tianshui",
            "Tonghua",
            "Tongliao",
            "Tulufan",
            "Turpan",
            "TW",
            "Ulanqab",
            "Urumqi",
            "Weifang",
            "Weihai",
            "Weinan",
            "Wenzhou",
            "Wuhai",
            "Wuhu",
            "Wuxi",
            "Wuzhong",
            "Xi'an",
            "Xiamen",
            "Xiangfan",
            "Xiangtan",
            "Xiangxi",
            "Xianning",
            "Xianyang",
            "Xiaogan",
            "Xiaon",
            "Xiaon District",
            "Xiaoshan District",
            "Xinganmeng",
            "Xingtai",
            "Xining",
            "Xinyang",
            "Xishuangbanna",
            "Xuchang",
            "Yancheng",
            "Yantai",
            "yanwen",
            "Yibin",
            "Yichang",
            "Yichun",
            "Yichun",
            "Yili",
            "Yingkou",
            "Yiwu",
            "Yiyang",
            "YouJian",
            "Yulin",
            "Yuncheng",
            "Yunfu",
            "Yushu",
            "Yuxi",
            "Zhangzhou",
            "Zhanjiang",
            "Zhaoqing",
            "ZHEJIANG",
            "ZheJiangShengHangZhouShi",
            "Zhengzhou",
            "Zhongshan",
            "Zhongwei",
            "Zhoukou",
            "Zhoushan",
            "Zhuhai",
            "Zibo",
            "Zigong",
            "Ziyang",
            "Zunyi",
            "义乌",
            "国际",
            "宁波分公司",
            "常州分公司",
            "广东省",
            "广州",
            "杭州分公司",
            "深东凤岗仓",
            "深东凤岗仓 /",
            "深圳",
            "清关完成,已放行",
        ];
    }

    public function parcelpanel_get_a2w_tracking_data()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (is_plugin_active('ali2woo/ali2woo.php') || is_plugin_active('ali2woo-lite/ali2woo-lite.php')) {

        }
    }

    public function parcelpanel_get_client_ip()
    {
        $fields = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        $result = [];

        foreach ($fields as $field) {
            if (!empty($_SERVER[$field])) {
                $result[$field] = wc_clean($_SERVER[$field]);
            }
        }

        return $result;
    }

    public function parcelpanel_update_setting_action()
    {
        \ParcelPanel\Api\Configs::get_pp_setting_config();
    }

    public function get_translated_text($text, $domain = 'parcelpanel', $the_locale = 'en_US')
    {
        // get the global
        global $locale;
        // save the current value
        $old_locale = $locale;
        // override it with our desired locale
        $locale = $the_locale;
        // get the translated text (note that the 'locale' filter will be applied)
        $translated = __($text, $domain);
        // reset the locale
        $locale = $old_locale;
        // return the translated text
        return $translated;
    }

    // 获取用户后台对应语言集合
    public function getAdminLangList()
    {
        // 获取 pp 设置当前语言版本 (后台页面语言已PP后台语言类型为准)
        $langRes = \ParcelPanel\Action\Common::getCommonSetting();
        $lang = $langRes['lang'] ?? '';
        $langList = $langRes['langList'] ?? [];
        update_option(\ParcelPanel\OptionName\PP_LANG_NOW, $lang);
        return Lang::instance()->langToWordpress($langList);
    }

    public function getCategory()
    {
        $category_names = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'pad_counts' => false,
                'hide_empty' => false,
                // 'include'  => $category_ids,
                // 'fields'   => 'names',
            )
        );
        return $this->getCategoryData($category_names);
    }

    private function getCategoryData($list, $res = [], $pid = 0, $deep = 0)
    {
        foreach ($list as $v) {
            $term_id = $v->term_id ?? 0;
            $name = $v->name ?? '';
            $parent = $v->parent ?? 0;
            if (empty($term_id) || empty($name)) {
                continue;
            }
            if ($parent === $pid) {
                if (empty($parent)) {
                    $deep = 0;
                }
                $str = $deep ? str_repeat(' ', $deep) : '';
                $res[] = [
                    'value' => $term_id,
                    'label' => $str . $name,
                ];
                $deep++;
                // 获取对应子集
                $res = $this->getCategoryData($list, $res, $term_id, $deep);
            }
        }
        return $res;
    }

}
