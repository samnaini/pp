<?php

namespace ParcelPanel\Action;

use ParcelPanel\Libs\Singleton;
use ParcelPanel\ParcelPanelFunction;

class AdminSettings
{
    use Singleton;

    const EMAIL_DEFAULT = [
        'in_transit'       => false,
        'out_for_delivery' => false,
        'delivered'        => false,
        'exception'        => false,
        'failed_attempt'   => false,
    ];

    function enqueue_scripts()
    {
        $setting_config = $this->get_setting_config();

        $courier_config = $this->get_courier_config();

        $pp_param = [
            'preview_email_url' => add_query_arg( '_wpnonce', wp_create_nonce( 'pp-preview-mail' ), admin_url( '?pp_preview_mail=1' ) ),
        ];

        $js = 'const pp_setting_config = ' . json_encode( $setting_config ) . ';';
        $js .= "\nconst pp_courier_config = " . json_encode( $courier_config ) . ';';
        $js .= "\nconst pp_param = " . json_encode( $pp_param ) . ';';

        wp_add_inline_script( 'pp-settings-page', $js, 'before' );
    }

    function get_setting_config()
    {

        $email_notification_add_tracking_section = $this->get_email_notification_add_tracking_section_field();
        $tracking_section_order_status = $this->get_tracking_section_order_status_field();
        $orders_page_add_track_button = $this->get_orders_page_add_track_button_field();
        $track_button_order_status = $this->get_track_button_order_status_field();
        $admin_order_actions_add_track = self::get_admin_order_actions_add_track_field();
        $admin_order_actions_add_track_order_status = self::get_admin_order_actions_add_track_order_status_field();
        // 无选项时，自动关闭开关
        if (empty($tracking_section_order_status)) {
            $email_notification_add_tracking_section = false;
            // update_option( \ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION, filter_var( $email_notification_add_tracking_section, FILTER_VALIDATE_BOOLEAN ) );
        }

        // 无选项时，自动关闭开关
        if (empty($track_button_order_status)) {
            $orders_page_add_track_button = false;
            // update_option( \ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON, filter_var( $orders_page_add_track_button, FILTER_VALIDATE_BOOLEAN ) );
        }

        // 无选项时，自动关闭开关
        if (empty($admin_order_actions_add_track_order_status)) {
            $admin_order_actions_add_track = false;
            // update_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK, filter_var( $admin_order_actions_add_track, FILTER_VALIDATE_BOOLEAN ) );
        }

        return [
            'order_status'                            => wc_get_order_statuses(),
            'email_notification_add_tracking_section' => $email_notification_add_tracking_section,
            'tracking_section_order_status'           => $tracking_section_order_status,
            'email_notification'                      => $this->get_email_notification_field(),
            'orders_page_add_track_button'            => $orders_page_add_track_button,
            'track_button_order_status'               => $track_button_order_status,
            'status_shipped' => self::get_status_shipped_field(),
            'admin_order_actions_add_track' => $admin_order_actions_add_track,
            'admin_order_actions_add_track_order_status' => $admin_order_actions_add_track_order_status,
        ];
    }

    /**
     * 保存设置项 API
     */
    function save_settings_ajax()
    {
        check_ajax_referer( 'pp-settings-save' );

        $req_data = json_decode( file_get_contents( 'php://input' ), 1 );
        // var_dump($req_data);die;
        if ( isset( $req_data[ 'tracking_section_order_status' ] ) ) {
            $tracking_section_order_status = array_filter( wc_clean( (array)( $req_data[ 'tracking_section_order_status' ] ) ),function($var){
                if(!in_array($var,['wc-checkout-draft','wc-pending'])){
                    return $var;
                }
            } );

            $array_keys = array_keys( wc_get_order_statuses() );

            $tracking_section_order_status = array_values( array_intersect( $array_keys, $tracking_section_order_status ) );

            update_option( \ParcelPanel\OptionName\TRACKING_SECTION_ORDER_STATUS, $tracking_section_order_status );

            // 无选项时，自动关闭开关
            if (empty($tracking_section_order_status)) {
                update_option( \ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION, filter_var( !empty($tracking_section_order_status), FILTER_VALIDATE_BOOLEAN ) );
            }
        }

        if ( isset( $req_data[ 'track_button_order_status' ] ) ) {

            $track_button_order_status = array_filter( wc_clean( (array)( $req_data[ 'track_button_order_status' ] ) ) );

            $array_keys = array_keys( wc_get_order_statuses() );

            $track_button_order_status = array_values( array_intersect( $array_keys, $track_button_order_status ) );

            update_option( \ParcelPanel\OptionName\TRACK_BUTTON_ORDER_STATUS, $track_button_order_status );

            // 无选项时，自动关闭开关
            if (empty($track_button_order_status)) {
                update_option( \ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON, filter_var( !empty($track_button_order_status), FILTER_VALIDATE_BOOLEAN ) );
            }

        }

        if ( isset( $req_data[ 'admin_order_actions_add_track_order_status' ] ) ) {

            $track_button_order_status = array_filter( wc_clean( (array)( $req_data[ 'admin_order_actions_add_track_order_status' ] ) ) );

            $array_keys = array_keys( wc_get_order_statuses() );

            $track_button_order_status = array_values( array_intersect( $array_keys, $track_button_order_status ) );

            update_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS, $track_button_order_status );

            // 无选项时，自动关闭开关
            if (empty($track_button_order_status)) {
                update_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK, filter_var( !empty($track_button_order_status), FILTER_VALIDATE_BOOLEAN ) );
            }
        }

        if ( isset( $req_data[ 'email_notification' ] ) ) {
            $order_status = [
                'in_transit',
                'out_for_delivery',
                'delivered',
                'exception',
                'failed_attempt',
            ];
            foreach ( $order_status as $value ) {
                if ( isset( $req_data[ 'email_notification' ][ $value ] ) ) {
                    $option              = get_option( "woocommerce_customer_pp_{$value}_shipment_settings" );
                    $option[ 'enabled' ] = filter_var( $req_data[ 'email_notification' ][ $value ], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
                    update_option( "woocommerce_customer_pp_{$value}_shipment_settings", $option );
                }
            }
        }

        if ( isset( $req_data[ 'email_notification_add_tracking_section' ] ) ) {
            update_option( \ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION, filter_var( $req_data[ 'email_notification_add_tracking_section' ], FILTER_VALIDATE_BOOLEAN ) );
        }

        if ( isset( $req_data[ 'orders_page_add_track_button' ] ) ) {
            update_option( \ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON, filter_var( $req_data[ 'orders_page_add_track_button' ], FILTER_VALIDATE_BOOLEAN ) );
        }

        if ( isset( $req_data[ 'status_shipped' ] ) ) {
            update_option( \ParcelPanel\OptionName\STATUS_SHIPPED, filter_var( $req_data[ 'status_shipped' ], FILTER_VALIDATE_BOOLEAN ) );
        }

        if ( isset( $req_data[ 'admin_order_actions_add_track' ] ) ) {
            update_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK, filter_var( $req_data[ 'admin_order_actions_add_track' ], FILTER_VALIDATE_BOOLEAN ) );
        }

        (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'Saved successfully', 'parcelpanel' ) );
    }


    function get_courier_config(): array
    {
        $selected_courier = (array)get_option( \ParcelPanel\OptionName\SELECTED_COURIER, [] );

        $rtn = [
            'enable'  => [],
            'disable' => [],
        ];

        $courier_list = (new ParcelPanelFunction)->parcelpanel_get_courier_list( 'ASC' );

        $courier_url = 'https://cdn.parcelpanel.com/assets/common/images/express/';

        foreach ( $selected_courier as $courier_code ) {

            if ( isset( $courier_list->$courier_code ) ) {

                $rtn[ 'enable' ][] = [
                    'express' => $courier_code,
                    'name'    => $courier_list->$courier_code->name,
                    // 'logo'    => $courier_list->$express->logo,
                    // 'logo'    => parcelpanel_get_assets_path( "imgs/express/{$courier_code}.png" ),
                    'logo'    => $courier_url."{$courier_code}.png",
                    'sort'    => $courier_list->$courier_code->sort,
                ];

                unset( $courier_list->$courier_code );
            }
        }

        foreach ( $courier_list as $courier_code => $courier_item ) {

            $courier = [
                'express' => $courier_code,
                'name'    => $courier_item->name,
                // 'logo'    => $name->logo,
                // 'logo'    => parcelpanel_get_assets_path( "imgs/express/{$courier_code}.png" ),
                'logo'    => $courier_url."{$courier_code}.png",
                'sort'    => $courier_item->sort,
            ];

            $rtn[ 'disable' ][] = $courier;
        }

        return $rtn;
    }

    /**
     * 保存 Courier matching 配置
     */
    function save_courier_matching_ajax()
    {
        check_ajax_referer( 'pp-courier-matching-save' );

        $enabled_list  = array_unique( array_filter( wc_clean( (array)( $_POST[ 'enable' ] ?? [] ) ) ) );
        $disabled_list = array_filter( wc_clean( (array)( $_POST[ 'disable' ] ?? [] ) ) );

        if ( empty( $enabled_list ) && empty( $disabled_list ) ) {
            (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'Invalid parameter', 'parcelpanel' ), false );
        }

        $selected_courier = (array)get_option( \ParcelPanel\OptionName\SELECTED_COURIER, [] );

        $courier_code_list = array_keys( (array)(new ParcelPanelFunction)->parcelpanel_get_courier_list() );

        // 过滤输入：保留合法的运输商
        // 为保留用户输入顺序，参数不能填反
        $enabled_list = array_intersect( $enabled_list, $courier_code_list );

        // 移除启用（防止存在重复值）与禁用列表中的运输商
        $selected_courier = array_diff( $selected_courier, $enabled_list, $disabled_list );

        // 追加启用的运输商
        $selected_courier = array_merge( $selected_courier, $enabled_list );

        update_option( \ParcelPanel\OptionName\SELECTED_COURIER, $selected_courier, false );

        (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'Saved successfully', 'parcelpanel' ) );
    }


    public static function get_email_notification_add_tracking_section_field(): bool
    {
        return (int)get_option( \ParcelPanel\OptionName\EMAIL_NOTIFICATION_ADD_TRACKING_SECTION ) === 1;
    }

    public static function get_tracking_section_order_status_field(): array
    {
        $IGNORE_ORDER_STATUSES = [ 'wc-pending', 'wc-on-hold', 'wc-checkout-draft' ];
        $list = (array)get_option( \ParcelPanel\OptionName\TRACKING_SECTION_ORDER_STATUS, [] );
        return array_values( array_diff( $list, $IGNORE_ORDER_STATUSES ) );
    }

    private function get_email_notification_field(): array
    {
        $rtn = self::EMAIL_DEFAULT;

        foreach ( $rtn as $order_status => &$value ) {
            $data = get_option( "woocommerce_customer_pp_{$order_status}_shipment_settings" );
            if ( $data ) {
                $value = 'yes' == ( get_option( "woocommerce_customer_pp_{$order_status}_shipment_settings" )[ 'enabled' ] ?? 'no' );
            }
        }

        return $rtn;
    }

    public static function get_orders_page_add_track_button_field(): bool
    {
        return (int)get_option( \ParcelPanel\OptionName\ORDERS_PAGE_ADD_TRACK_BUTTON ) === 1;
    }

    public static function get_track_button_order_status_field(): array
    {
        $IGNORE_ORDER_STATUSES = [ 'wc-pending', 'wc-on-hold' ];
        $list = (array)get_option( \ParcelPanel\OptionName\TRACK_BUTTON_ORDER_STATUS, [] );
        return array_values( array_diff( $list, $IGNORE_ORDER_STATUSES ) );
    }

    public static function get_status_shipped_field(): bool
    {
        return (int)get_option( \ParcelPanel\OptionName\STATUS_SHIPPED ) === 1;
    }

    public static function get_admin_order_actions_add_track_field(): bool
    {
        return (int)get_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK ) === 1;
    }

    public static function get_admin_order_actions_add_track_order_status_field(): array
    {
        $IGNORE_ORDER_STATUSES = [ 'wc-pending', 'wc-on-hold' ];
        $list = (array)get_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS, [] );
        return array_values( array_diff( $list, $IGNORE_ORDER_STATUSES ) );
    }
}
