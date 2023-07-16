<?php

namespace ParcelPanel\Api;

use ParcelPanel\Libs\Singleton;

class Configs
{
    use Singleton;

    function update(\WP_REST_Request $request)
    {
        $p_settings = $request['update'];

        if (empty($p_settings)) {
            return rest_ensure_response(['code' => RestApi::CODE_BAD_REQUEST]);
        }

        // update configs
        self::get_pp_setting_config();

        // update notice
        self::get_pp_notice_config();

        $resp_data = [
            'code' => RestApi::CODE_SUCCESS,
            'data' => [],
        ];

        return rest_ensure_response($resp_data);
    }

    /**
     * get shop setting page configs
     */
    public static function get_pp_setting_config(): void
    {
        // 获取 setting page 相关 配置
        $user_setting_configs = Api::userSettingConfigs();
        if (is_wp_error($user_setting_configs)) {
            return;
        }

        $req_data = $user_setting_configs['data'] ?? [];
        // return $req_data;
        if (isset($req_data['tracking_section_order_status'])) {
            $tracking_section_order_status = array_filter(wc_clean((array)($req_data['tracking_section_order_status'])), function ($var) {
                if (!in_array($var, ['wc-checkout-draft', 'wc-pending'])) {
                    return $var;
                }
            });

            $array_keys = array_keys(wc_get_order_statuses());

            $tracking_section_order_status = array_values(array_intersect($array_keys, $tracking_section_order_status));

            update_option(\ParcelPanel\OptionName\TRACKING_SECTION_ORDER_STATUS, $tracking_section_order_status);

            // 无选项时，自动关闭开关
            if (empty($tracking_section_order_status)) {
                update_option(\ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION, filter_var(!empty($tracking_section_order_status), FILTER_VALIDATE_BOOLEAN));
            }
        }

        if (isset($req_data['track_button_order_status'])) {

            $track_button_order_status = array_filter(wc_clean((array)($req_data['track_button_order_status'])));

            $array_keys = array_keys(wc_get_order_statuses());

            $track_button_order_status = array_values(array_intersect($array_keys, $track_button_order_status));

            update_option(\ParcelPanel\OptionName\TRACK_BUTTON_ORDER_STATUS, $track_button_order_status);

            // 无选项时，自动关闭开关
            if (empty($track_button_order_status)) {
                update_option(\ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON, filter_var(!empty($track_button_order_status), FILTER_VALIDATE_BOOLEAN));
            }

        }

        if (isset($req_data['admin_order_actions_add_track_order_status'])) {

            $track_button_order_status = array_filter(wc_clean((array)($req_data['admin_order_actions_add_track_order_status'])));

            $array_keys = array_keys(wc_get_order_statuses());

            $track_button_order_status = array_values(array_intersect($array_keys, $track_button_order_status));

            update_option(\ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS, $track_button_order_status);

            // 无选项时，自动关闭开关
            if (empty($track_button_order_status)) {
                update_option(\ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK, filter_var(!empty($track_button_order_status), FILTER_VALIDATE_BOOLEAN));
            }
        }

        if (isset($req_data['email_notification'])) {
            $order_status = [
                'in_transit',
                'out_for_delivery',
                'delivered',
                'exception',
                'failed_attempt',
            ];
            // foreach ($req_data[ 'email_notification' ] as $k=>$v) {
            //     $option              = get_option( "woocommerce_customer_pp_{$k}_shipment_settings" );
            //     $option[ 'enabled' ] = filter_var( $v, FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
            //     update_option( "woocommerce_customer_pp_{$k}_shipment_settings", $option );
            // }
            foreach ($order_status as $value) {
                if (isset($req_data['email_notification'][$value])) {
                    $option = get_option("woocommerce_customer_pp_{$value}_shipment_settings");
                    $option['enabled'] = filter_var($req_data['email_notification'][$value], FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no';
                    update_option("woocommerce_customer_pp_{$value}_shipment_settings", $option);
                }
            }
        }

        if (isset($req_data['email_notification_add_tracking_section'])) {
            update_option(\ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION, filter_var($req_data['email_notification_add_tracking_section'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($req_data['orders_page_add_track_button'])) {
            update_option(\ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON, filter_var($req_data['orders_page_add_track_button'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($req_data['status_shipped'])) {
            update_option(\ParcelPanel\OptionName\STATUS_SHIPPED, filter_var($req_data['status_shipped'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($req_data['admin_order_actions_add_track'])) {
            update_option(\ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK, filter_var($req_data['admin_order_actions_add_track'], FILTER_VALIDATE_BOOLEAN));
        }

    }

    public static function get_pp_notice_config(): void
    {
        $user_common_configs = Api::userOtherConfigs();
        if (is_wp_error($user_common_configs)) {
            return;
        }

        $req_data = $user_common_configs['data'] ?? [];

        if (isset($req_data['upgradeReminder'])) {
            $upgradeReminder = empty($req_data['upgradeReminder']) ? 0 : time();
            update_option(\ParcelPanel\OptionName\PLAN_QUOTA_REMAIN, $upgradeReminder);
        }

        if (isset($req_data['question'])) {
            $question = empty($req_data['question']) ? 0 : strtotime('tomorrow midnight');
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_QUESTION, $question);
        }

        if (isset($req_data['npsBtn'])) {
            $npsBtn = !empty($req_data['npsBtn']) ? 0 : strtotime('tomorrow midnight');
            update_option(\ParcelPanel\OptionName\ADMIN_NOTICE_IGNORE_NPS, $npsBtn);
        }

        if (isset($req_data['wc_app_drop_shipping_aliExpress'])) {
            $k_1001 = sprintf(\ParcelPanel\OptionName\INTEGRATION_APP_ENABLED, 1001);
            update_option($k_1001, $req_data['wc_app_drop_shipping_aliExpress']);
        }

        if (isset($req_data['wc_app_drop_shipping_ALD'])) {
            $k_1001 = sprintf(\ParcelPanel\OptionName\INTEGRATION_APP_ENABLED, 1002);
            update_option($k_1001, $req_data['wc_app_drop_shipping_ALD']);
        }

        if (isset($req_data['wc_app_drop_shipping_DSer'])) {
            $k_1001 = sprintf(\ParcelPanel\OptionName\INTEGRATION_APP_ENABLED, 1003);
            update_option($k_1001, $req_data['wc_app_drop_shipping_DSer']);
        }

    }

    // update plugin to pp
    public static function update_plugin_complete()
    {
        Api::updatePluginComplete();
    }
}
