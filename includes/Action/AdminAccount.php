<?php

namespace ParcelPanel\Action;

use ParcelPanel\Api\Api;
use ParcelPanel\Libs\ApiSign;
use ParcelPanel\Libs\ArrUtils;
use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\TrackingSettings;
use ParcelPanel\ParcelPanelFunction;

class AdminAccount
{
    use Singleton;

    function enqueue_scripts()
    {
        $current_user = wp_get_current_user();

        $plans_list = [
            'read_nonce' => wp_create_nonce('pp-get-plan'),
            'link_nonce' => wp_create_nonce('pp-get-plan-link'),
            'feedback_confirm_nonce' => wp_create_nonce('pp-feedback-confirm'),
            'user_email' => $current_user->user_email,
            'i18n' => [
                'choose_plan' => __('Choose plan', 'parcelpanel'),
                'get_100off' => __('$0 Get 100% OFF Offer Now', 'parcelpanel'),
                'login_dashboard' => __('Log in Dashboard', 'parcelpanel'),
                'remaining' => __('Remaining', 'parcelpanel'),
                'total' => __('Total', 'parcelpanel'),
            ],
        ];
        wp_localize_script('pp-account-page', 'pp_plans', $plans_list);
    }

    /**
     * 获取当前套餐
     */
    function get_plan_ajax()
    {
        check_ajax_referer('pp-get-plan');

        $plan_info = Api::plan_info();

        if (is_wp_error($plan_info)) {
            (new ParcelPanelFunction)->parcelpanel_json_response([], $plan_info->get_error_message('api_error'), false);
        }

        $current_plan = $plan_info['current_plan'] ?? [];

        if (!empty($current_plan)) {
            $DATE_TIME_FORMAT = TrackingSettings::instance()->date_and_time_format;
            $DATE_FORMAT = $DATE_TIME_FORMAT['date_format'];

            $expired_at = strtotime($current_plan['expired_at'] ?? '') ?: 0;

            $plan_info['current_plan']['expired_date'] = (new ParcelPanelFunction)->parcelpanel_handle_time_format($expired_at, $DATE_FORMAT, null, false, true);
        }

        $current_plan = ArrUtils::get($plan_info, 'current_plan');
        if (is_array($current_plan)) {
            // 更新额度信息
            (new ParcelPanelFunction)->parcelpanel_update_quota_info($current_plan);
        }

        (new ParcelPanelFunction)->parcelpanel_json_response($plan_info);
    }

    /**
     * 降级到免费套餐操作
     */
    public function drop_free_ajax()
    {
        check_ajax_referer('pp-get-plan');

        $drop_info = Api::drop_free_plan();

        if (is_wp_error($drop_info)) {
            (new ParcelPanelFunction)->parcelpanel_json_response([], $drop_info->get_error_message('api_error'), false);
        } else {
            update_option(\ParcelPanel\OptionName\IS_UNLIMITED_PLAN, 0);
        }

        (new ParcelPanelFunction)->parcelpanel_json_response([], 'Updated successfully');
    }

    /**
     * 变更计划 ajax
     */
    function change_plan_ajax()
    {

    }

    function get_plan_link_ajax()
    {
        check_ajax_referer('pp-get-plan-link');

        $planId = intval($_GET['pid'] ?? 0);

        if (empty($planId)) {
            (new ParcelPanelFunction)->parcelpanel_json_response([], __('Invalid parameter', 'parcelpanel'), false);
        }

        $parse_url = parse_url(home_url());

        $domain = $parse_url['host'] ?? '';

        // 购买套餐跳转到注册页面的签名
        $data = [
            'domain' => $domain,  // 是哪个域名注册的
            'expire_date' => date('c', time() + 86400),  // 有效日期
            'pid' => $planId,
            'version' => \ParcelPanel\VERSION,
        ];

        // 这个东西就是那个保存的 Token
        $token = get_option(\ParcelPanel\OptionName\API_KEY, '');

        // 执行签名
        $sign = ApiSign::doSign($data, $token);

        // 输出类似    domain=test.qq.com&expire_date=xxxxxxx&signature=xxxxxxxxxx
        $link = http_build_query(array_merge($data, [
            'signature' => $sign,
        ]));

        $URL = Api::get_url(Api::USER_WC);

        (new ParcelPanelFunction)->parcelpanel_json_response(['link' => "{$URL}?{$link}"]);
    }
}
