<?php

namespace ParcelPanel\Api;

use ParcelPanel\ParcelPanelFunction;

class Api
{

    const USER_LANG = '/wordpress/userinfo/lang';
    const USER_CONFIGS = '/wordpress/userinfo/config';
    const USER_SET_CONFIGS = '/wordpress/settings/configs';
    const USER_TRACK_CONFIGS = '/wordpress/track-page/configs-wc';
    const USER_CONFIGS_OTHER = '/wordpress/user/configsUpToWC';
    const USER_TRACKING_ORDERS = '/wordpress/tracking/orders';
    const PLUGIN_UPDATE_NOW = '/wordpress/plugin/update';
    const ORDER_SYNC = '/wordpress/user/sync';
    const USER_API_KEY = '/wordpress/user/api-key';


    const REGISTER_SITE = '/wp/register-site';
    const SITE_DEACTIVATE = '/wp/site/deactivate';
    const SITE_UPGRADE = '/wp/site/upgrade';
    const POPUP_ACTION = '/wp/popup/action';

    const BIND_SITE = '/wp/bind-site';

    const ORDER_DEDUCTION = '/wp/order/deduction';
    const ORDER_UPDATED = '/wp/order/updated';
    const ORDER_TRACKED = '/wp/order/tracked';

    const TRACKING = '/wp/tracking';

    const TRACKING_COURIERS = '/wp/tracking/couriers';

    const COURIER = '/wp/courier';

    const PLAN = '/wp/plan';
    const DROP_FREE_PLAN = '/wp/plan/drop-to-free';

    const USER_WC = '/user/wc';

    const GEO = '/wp/geo';

    const FEEDBACK = '/wp/feedback';
    const UNINSTALL_FEEDBACK = '/wp/uninstall/feedback';

    public static function get_url($path): string
    {
        $server_api = apply_filters('parcelpanel_server_api_url', 'https://wp.parcelpanel.com/api/v1');

        return $server_api . $path;
    }

    private static function get_api_key()
    {
        return get_option(\ParcelPanel\OptionName\API_KEY, '');
    }

    private static function request($method, $api, $payload = null, $args = [])
    {
        $now_time = time();

        $home_url = home_url();

        $request_url = Api::get_url($api);

        $headers = [
            'Content-Type' => 'application/json',
            'X-WCPP-Source' => $home_url,
            'X-WCPP-Timestamp' => $now_time,
            'PP-Token' => self::get_api_key(),
        ];

        $ip_values = (new ParcelPanelFunction)->parcelpanel_get_client_ip();
        foreach ($ip_values as $field => $value) {
            $field = str_replace('_', '-', $field);
            $headers["X-WCPP-{$field}"] = $value;
        }

        $http_args = [
            'method' => $method,
            'timeout' => $args['timeout'] ?? MINUTE_IN_SECONDS,
            'redirection' => 0,
            'httpversion' => '1.1',
            'blocking' => true,
            'user-agent' => sprintf('ParcelPanel/%s WooCommerce/%s WordPress/%s', \ParcelPanel\VERSION, WC()->version, $GLOBALS['wp_version']),
            'sslverify' => false,
            'headers' => $headers,
        ];

        if (!is_null($payload)) {
            $http_args['body'] = trim(wp_json_encode($payload));
        }

        $content = strtolower($method) . "\n" . $request_url . "\n" . $home_url . "\n" . $now_time . "\n" . ($http_args['body'] ?? '');

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        $http_args['headers']['X-WCPP-Signature'] = base64_encode(hash_hmac('sha256', $content, self::get_api_key(), true));

        return self::parse_api_response(wp_remote_request($request_url, $http_args));
    }

    static function get($api, $payload = null, $args = [])
    {
        return self::request('GET', $api, $payload, $args);
    }

    static function post($api, $payload, $args = [])
    {
        return self::request('POST', $api, $payload, $args);
    }

    static function patch($api, $payload, $args = [])
    {
        return self::request('PATCH', $api, $payload, $args);
    }

    static function delete($api, $payload = null, $args = [])
    {
        return self::request('DELETE', $api, $payload, $args);
    }


    public static function parse_api_response($resp)
    {
        if (is_wp_error($resp)) {
            return $resp;
        }

        $body = json_decode(wp_remote_retrieve_body($resp), 1);

        $error = $body['error'] ?? '';

        if (isset($error['message'])) {
            $message = strval(is_array($error['message']) ? current($error['message']) : $error['message']);
            $error_code = intval($error['error_code'] ?? 0);
            return new \WP_Error('api_error', $message, ['error_code' => $error_code]);
        }

        return $body;
    }


    /**
     * 请求分配 api key
     */
    public static function connect($api_key)
    {
        $payload = ['api_key' => $api_key];

        return self::post(Api::REGISTER_SITE, array_merge($payload, self::getSiteInfo()), ['timeout' => 15]);
    }

    /**
     * 插件卸载事件
     */
    public static function deactivate()
    {
        return self::post(Api::SITE_DEACTIVATE, null);
    }

    /**
     * 绑定账号
     *
     * @param string $authKey 授权密钥，由 ParcelPanel 提供，有时间限制
     *
     * @return mixed|\WP_Error
     */
    public static function bind(string $authKey = '')
    {
        $payload = [
            'token' => get_option(\ParcelPanel\OptionName\API_KEY),
            'auth_key' => $authKey,
        ];

        return self::post(Api::BIND_SITE, array_merge($payload, self::getSiteInfo()));
    }

    /**
     * 版本升级消息
     */
    public static function site_upgrade($action = 'site-info')
    {
        switch ($action) {
            case 'site-info':
                return self::post(Api::SITE_UPGRADE, self::getSiteInfo());
            case 'tracking-page-url';
                return self::post(Api::SITE_UPGRADE, ['action' => 'update-tracking-page-url', 'tracking-page-url' => (new ParcelPanelFunction)->parcelpanel_get_track_page_url()]);
        }

        return [];
    }


    public static function getSiteInfo(): array
    {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $email = empty($user_id) ? '' : $current_user->user_email;

        return [
            'user_id' => $user_id,
            'email' => $email,
            'nickname' => $current_user->display_name ?: $current_user->nickname,
            'firstname' => $current_user->user_firstname,
            'lastname' => $current_user->user_lastname,
            'locale' => $current_user->locale ?: get_locale(),
            'roles' => $current_user->roles,
            'title' => get_bloginfo('title', 'display') ?? '',
            'version' => \ParcelPanel\VERSION ?? '0.0.0',
            'urls' => [
                'base' => rest_url('parcelpanel/v1/'),
                'track_page' => (new ParcelPanelFunction)->parcelpanel_get_track_page_url(),
            ],
            'site' => [
                'hash' => '',
                'multisite' => is_multisite(),
                'lang' => get_locale(),
            ],
        ];
    }

    public static function popup_action($data)
    {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        $data['user_id'] = $user_id;

        return self::post(Api::POPUP_ACTION, $data);
    }

    /**
     * 同步订单
     */
    public static function sync_orders($day = 90, $sleep = 0)
    {
        $payload = [
            'day' => $day,
            'sleep' => $sleep,
        ];;
        return self::post(Api::ORDER_SYNC, $payload);;
    }

    /**
     * 检测 wp api key 是否正常
     */
    public static function check_api_key()
    {
        $payload = [];
        return self::post(Api::USER_API_KEY, $payload);
    }


    /**
     * 同步订单
     */
    public static function add_orders(array $order_ids, array $orders = [])
    {
        $payload = [
            'order_id' => $order_ids,
            'orders' => $orders,
        ];

        return self::post(Api::ORDER_DEDUCTION, $payload);
    }

    public static function order_updated(array $order = [])
    {
        return self::post(Api::ORDER_UPDATED, $order);
    }


    public static function add_tracking($data, $courier_code = [])
    {
        if (empty($courier_code)) {
            $courier_code = (array)get_option(\ParcelPanel\OptionName\SELECTED_COURIER, []);
        }

        $payload = [
            'data' => $data,
            'courier_code' => $courier_code,
        ];

        return self::post(Api::TRACKING, $payload);
    }

    /**
     * 更新单号
     */
    public static function update_tracking($tracking_number, $data, $courier_list = [])
    {
        if (!empty($data['tracking_number'])) {
            // 修改单号
            $payload = [
                'tracking_number' => $data['tracking_number'],
                'courier_code' => $data['courier_code'] ?? '',
            ];
        } elseif (isset($data['courier_code'])) {
            // 修改运输商
            $payload = [
                'new_courier_code' => $data['courier_code'],
            ];
        } else {
            return false;
        }

        if (empty($courier_list)) {
            $courier_list = (array)get_option(\ParcelPanel\OptionName\SELECTED_COURIER, []);
        }

        $payload['courier_code_list'] = $courier_list;

        return self::patch(Api::TRACKING . "/{$tracking_number}", $payload);
    }

    /**
     * 删除单号
     */
    public static function delete_tracking($tracking_number)
    {
        return self::delete(Api::TRACKING . "/{$tracking_number}");
    }

    /**
     * 运输商识别
     */
    public static function tracking_couriers($tracking_number_list)
    {
        $payload = [
            'tracking_numbers' => $tracking_number_list,
        ];

        return self::post(Api::TRACKING_COURIERS, $payload);
    }

    /**
     * 运输商列表
     */
    public static function couriers()
    {
        return self::get(Api::COURIER);
    }

    /**
     * user common lang
     */
    public static function userCommonLangList($params)
    {
        return self::post(Api::USER_LANG, $params);
    }

    /**
     * user common configs
     */
    public static function userConfigs()
    {
        return self::post(Api::USER_CONFIGS, null);
    }

    /**
     * user other configs
     */
    public static function userOtherConfigs()
    {
        return self::post(Api::USER_CONFIGS_OTHER, null);
    }

    /**
     * user track configs
     */
    public static function userTrackConfigs()
    {
        return self::post(Api::USER_TRACK_CONFIGS, null);
    }

    /**
     * user tracking message
     */
    public static function userTrackingPage($params)
    {
        return self::post(Api::USER_TRACKING_ORDERS, $params);
    }

    /**
     * user setting configs
     */
    public static function userSettingConfigs()
    {
        return self::post(Api::USER_SET_CONFIGS, null);
    }

    /**
     * Plugin update
     */
    public static function updatePluginComplete()
    {
        return self::post(Api::PLUGIN_UPDATE_NOW, null);
    }


    /**
     * 当前套餐信息
     */
    public static function plan_info()
    {
        return self::get(Api::PLAN);
    }

    /**
     * 降级到免费套餐
     *
     * @return mixed|\WP_Error
     */
    public static function drop_free_plan()
    {
        return self::post(Api::DROP_FREE_PLAN, []);
    }

    /**
     * 地理位置信息
     * @deprecated 2.2.0
     */
    public static function geo_info($address, $country_code = '')
    {
        return self::post(Api::GEO, ['q' => $address]);
    }

    /**
     * Feedback
     */
    public static function feedback($data)
    {
        return self::post(Api::FEEDBACK, $data);
    }

    /**
     * Deactivate survey
     */
    public static function uninstall_feedback($data)
    {
        return self::post(Api::UNINSTALL_FEEDBACK, $data);
    }

    public static function order_tracked($data)
    {
        return self::post(Api::ORDER_TRACKED, $data, ['timeout' => 5]);
    }

}
