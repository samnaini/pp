<?php

namespace ParcelPanel\Action;

use ParcelPanel\Libs\Singleton;
use ParcelPanel\ParcelPanelFunction;

class AdminIntegration
{
    use Singleton;

    public const APP_IDS = [
        1001,
        1002,
        1003,
    ];

    public function enqueue_scripts()
    {
        $integration_enabled_config = $this->get_integration_enabled_config();

        $js = 'const pp_integration_enabled_config = ' . json_encode( $integration_enabled_config ) . ';';

        wp_add_inline_script( 'pp-integration-page', $js, 'before' );
    }

    public function get_integration_enabled_config()
    {
        $result = [];

        foreach ( self::APP_IDS as $app_id ) {
            $result[ $app_id ] = self::get_app_integrated( $app_id );
        }

        return $result;
    }

    public function switch_integration_ajax()
    {
        check_ajax_referer( 'pp-integration-switch' );

        $req_data = (new ParcelPanelFunction)->parcelpanel_get_post_data();

        $app_id = (int)( $req_data[ 'app_id' ] ?? 0 );
        $enabled = $req_data[ 'enabled' ] ?? false;

        if ( ! in_array( $app_id, self::APP_IDS ) ) {
            (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'App not found', 'parcelpanel' ), false );
        }

        self::set_integration_enabled( $app_id, $enabled );

        (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'Saved successfully', 'parcelpanel' ) );
    }

    public static function get_app_integrated( $app_id ): bool
    {
        return (int)get_option( sprintf( \ParcelPanel\OptionName\INTEGRATION_APP_ENABLED, $app_id ) ) === 1;
    }

    public static function get_admin_order_actions_add_track_order_status_field(): array
    {
        return (array)get_option( \ParcelPanel\OptionName\ADMIN_ORDER_ACTIONS_ADD_TRACK_ORDER_STATUS, [] );
    }

    public static function set_integration_enabled( $app_id, $enabled ): bool
    {
        $option_key = sprintf( \ParcelPanel\OptionName\INTEGRATION_APP_ENABLED, $app_id );
        $option_value = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );
        return update_option( $option_key, $option_value );
    }
}
