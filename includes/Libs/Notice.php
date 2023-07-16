<?php

namespace ParcelPanel\Libs;

use ParcelPanel\Api\Api;
use ParcelPanel\Api\Configs;
use ParcelPanel\ParcelPanelFunction;

/**
 * 部分代码来自：/wp-content/plugins/woocommerce/includes/admin/class-wc-admin-notices.php
 */
class Notice
{
    // 里面存储类似于 notice_quota_remain
    private static $notices = [];
    private static $core_notices = [
        'notice_quota_remain',
        'feedback_notice',
        'plugins_feedback_notice',
        'free_upgrade_notice',
        'free_sync_orders_notice',
        'remove_pp_branding_notice',
        // 'question_banner_notice',
        'nps_banner_notice',
    ];

    /**
     * 初始化需要发出的提醒
     */
    public static function init()
    {

        // TODO 获取 pp notice 配置信息进行设置
        Configs::get_pp_notice_config();

        $core_notices = self::$core_notices;
        if (!is_array($core_notices) || empty($core_notices)) return;

        foreach ($core_notices as $notice) {
            self::$notice();
        }
    }

    /**
     * 额度不足提醒 Banner
     * 这里写逻辑，是否显示额度提醒
     */
    public static function notice_quota_remain()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\CLOSE_QUOTA_NOTICE, strtotime('tomorrow midnight'));
        }, 'notice_quota_remain');
        if ($hide_notices) return;

        // new
        if (get_option(\ParcelPanel\OptionName\CLOSE_QUOTA_NOTICE)) {
            return;
        }

        $WP_Screen = get_current_screen();

        $true_list = [
            // $WP_Screen->id != 'parcelpanel_page_pp-account' && $WP_Screen->parent_base === 'pp-admin',  // ParcelPanel Page
            $WP_Screen->id === 'edit-shop_order' && $WP_Screen->parent_base === 'woocommerce',  // WC Orders
            $WP_Screen->id === 'dashboard' && $WP_Screen->parent_base === 'index',  // Dashboard 首页
        ];

        // if (in_array(true, $true_list, true)) {
        //     (new ParcelPanelFunction)->parcelpanel_include_view('notices/quota-remain');
        // }
    }

    /**
     * Feedback Notice
     */
    public static function feedback_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FEEDBACK, time());
        }, 'feedback_notice');
        if ($hide_notices) return;

        $WP_Screen = get_current_screen();

        $true_list = [
            // Home Page（关闭后不再出现）
            // $WP_Screen->id === 'parcelpanel_page_pp-home' && $WP_Screen->parent_base === 'pp-admin'
            // && ! get_option( \ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FEEDBACK ),

            // Account Page
            // $WP_Screen->id === 'parcelpanel_page_pp-account' && $WP_Screen->parent_base === 'pp-admin',
        ];

        if (in_array(true, $true_list, true)) {
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/feedback-admin');
        }
    }

    /**
     * Question Notice
     */
    public static function question_banner_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_QUESTION, time());
        }, 'question_banner_notice');

        if ($hide_notices) return;

        $WP_Screen = get_current_screen();

        if (get_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_QUESTION)) {
            return;
        }

        $true_list = [
            // Home Page（关闭后不再出现）
            // $WP_Screen->id === 'parcelpanel_page_pp-home' && $WP_Screen->parent_base === 'pp-admin',
            $WP_Screen->id === 'edit-product_cat' && $WP_Screen->parent_base === 'edit',  // WC Categories 页面
            $WP_Screen->id === 'edit-shop_order' && $WP_Screen->parent_base === 'woocommerce',  // WC Orders
            $WP_Screen->id === 'dashboard' && $WP_Screen->parent_base === 'index',  // Dashboard 首页
            $WP_Screen->id === 'update-core' && $WP_Screen->parent_base === 'index',  // Dashboard update 首页
        ];

        if (in_array(true, $true_list, true)) {
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/question-banner-admin');
        }
    }

    /**
     * NPS Notice
     */
    public static function nps_banner_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_NPS, time());
        }, 'nps_banner_notice');

        if ($hide_notices) return;

        $WP_Screen = get_current_screen();

        if (get_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_NPS)) {
            return;
        }
        // var_dump($WP_Screen->id, $WP_Screen->parent_base);
        // die;
        $true_list = [
            // Home Page（关闭后不再出现）
            // // $WP_Screen->id === 'parcelpanel_page_pp-home' && $WP_Screen->parent_base === 'pp-admin',
            // $WP_Screen->id === 'plugins' && $WP_Screen->parent_base === 'plugins',
            // $WP_Screen->id === 'woocommerce_page_wc-admin' && $WP_Screen->parent_base === 'woocommerce',  // WC Home 页面
            // $WP_Screen->id === 'edit-shop_order' && $WP_Screen->parent_base === 'woocommerce',  // WC Orders
            // $WP_Screen->id === 'dashboard' && $WP_Screen->parent_base === 'index',  // Dashboard 首页
            // $WP_Screen->id === 'update-core' && $WP_Screen->parent_base === 'index',  // Dashboard update 首页
        ];

        if (in_array(true, $true_list, true)) {
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/nps-banner-admin');
        }
    }

    /**
     * Feedback Notice
     */
    public static function plugins_feedback_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_PLUGINS_FEEDBACK, time());
        }, 'plugins_feedback_notice');
        if ($hide_notices) return;

        $screen = get_current_screen();

        $true_list = [
            // Plugins
            $screen->id === 'plugins'
            && get_option(\ParcelPanel\OptionName\CONNECTED_AT) + 259200 < time()
            && !get_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_PLUGINS_FEEDBACK)
        ];

        if (in_array(true, $true_list, true)) {
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/admin-plugins-feedback');
        }
    }

    /**
     * Free upgrade Notice
     */
    static function free_upgrade_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FREE_UPGRADE, time());
        }, 'free_upgrade_notice');
        if ($hide_notices) return;

        $WP_Screen = get_current_screen();

        $is_free_plan = get_option(\ParcelPanel\OptionName\IS_FREE_PLAN);

        $true_list = [
            // Home Page
            // $WP_Screen->id === 'parcelpanel_page_pp-home' && $WP_Screen->parent_base === 'pp-admin'
            // && ! get_option( \ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FREE_UPGRADE )
            // && ( $is_free_plan === false || ( $is_free_plan === '1' && ! get_option( \ParcelPanel\OptionName\IS_UNLIMITED_PLAN ) ) ),
        ];

        if (in_array(true, $true_list, true)) {
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/free-upgrade-admin');
        }
    }

    /**
     * Free sync orders Notice
     */
    static function free_sync_orders_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FREE_SYNC_ORDERS, time());
        }, 'free_sync_orders_notice');
        if ($hide_notices) return;

        $WP_Screen = get_current_screen();

        $true_list = [
            // Shipments Page
            // $WP_Screen->id === 'parcelpanel_page_pp-shipments' && $WP_Screen->parent_base === 'pp-admin'
            // && ! get_option( \ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_FREE_SYNC_ORDERS ),
        ];

        if (in_array(true, $true_list, true)) {
            $registered_at = get_option(\ParcelPanel\OptionName\REGISTERED_AT);
            if (empty($registered_at)) {
                return;
            }
            // 注册时间超过一天
            if ($registered_at + 86400 < time()) {
                return;
            }
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/free-sync-orders-admin');
        }
    }

    /**
     * Remove ParcelPanel branding Notice
     */
    static function remove_pp_branding_notice()
    {
        $hide_notices = self::hide_notices(function () {
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_REMOVE_BRANDING, time());
        }, 'remove_pp_branding_notice');
        if ($hide_notices) return;

        $WP_Screen = get_current_screen();

        $true_list = [
            // Tracking Page
            // $WP_Screen->id === 'parcelpanel_page_pp-tracking-page' && $WP_Screen->parent_base === 'pp-admin'
            // && ! get_option( \ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_REMOVE_BRANDING )
            // && ! get_option( \ParcelPanel\OptionName\REMOVE_BRANDING ),
        ];

        if (in_array(true, $true_list, true)) {
            (new ParcelPanelFunction)->parcelpanel_include_view('notices/remove-pp-branding-admin');
        }
    }

    #####################################
    ################操作类################
    #####################################

    /**
     * Hide a notice if the GET variable is set.
     *
     * @param callable|null $callback 回调，会传回 $hide_notice 参数，请务必接收下
     */
    public static function hide_notices($callback = null, $notice = '')
    {
        if (isset($_GET['pp-hide-notice']) && isset($_GET['_pp_notice_nonce']) && isset($_GET['_expired_at'])) { // WPCS: input var ok, CSRF ok.
            if (!wp_verify_nonce(sanitize_key(wp_unslash($_GET['_pp_notice_nonce'])), 'parcelpanel_hide_notices_nonce')) { // WPCS: input var ok, CSRF ok.
                wp_die(esc_html__('Action failed. Please refresh the page and retry.', 'parcelpanel'));
            }

            if ($_GET['_expired_at'] < time()) {
                return false;
            }

            // if (!current_user_can('manage_woocommerce')) {
            //     wp_die(esc_html__('You don&#8217;t have permission to do this.', 'woocommerce'));
            // }

            $hide_notice = sanitize_text_field(wp_unslash($_GET['pp-hide-notice'])); // WPCS: input var ok, CSRF ok.

            if ($notice == $hide_notice && is_callable($callback)) {
                $callback($hide_notice);
                return true;
            }

            self::remove_notice($hide_notice);
        }

        return false;
    }

    /**
     * 移除提醒
     */
    public static function remove_notice(string $hide_notice)
    {
        // TODO feature 未来功能
    }

    /**
     * 存储 提醒
     */
    public static function store_notices()
    {
        update_option('parcelpanel_admin_notices', self::get_notices());
    }

    /**
     * 获取 提醒
     *
     * @return mixed
     */
    public static function get_notices()
    {
        return self::$notices;
    }
}
