<?php

namespace ParcelPanel\Action;

use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\TrackingSettings;
use ParcelPanel\ParcelPanelFunction;

class AdminHome
{
    use Singleton;

    function enqueue_scripts()
    {
        $params = [
            'track_page_link'           => (new ParcelPanelFunction)->parcelpanel_get_track_page_url(),
            'preview_track_link'        => (new ParcelPanelFunction)->parcelpanel_get_track_page_url( true ),
            'settings_page_link'        => (new ParcelPanelFunction)->parcelpanel_get_admin_settings_url(),
            'import_template_file_link' => (new ParcelPanelFunction)->parcelpanel_get_assets_path( 'templates/sample-template.csv' ),
            'upload_nonce'              => wp_create_nonce( 'pp-upload-csv' ),
            'import_nonce'              => wp_create_nonce( 'pp-import-csv-tracking-number' ),
            'get_history_nonce'         => wp_create_nonce( 'pp-get-import-tracking-number-records' ),
            'shipments_page_link' => (new ParcelPanelFunction)->parcelpanel_get_admin_shipments_url(),
        ];

        wp_localize_script( 'pp-home-page', 'pp_param', $params );
    }

    /**
     * 启用 Dropshipping 配置
     */
    function enable_dropshipping_mode_ajax()
    {
        check_ajax_referer( 'pp-enable-dropshipping-mode' );

        $display_option                               = TrackingSettings::instance()->display_option;
        $display_option[ 'map_coordinates' ]          = true;
        $display_option[ 'map_coordinates_position' ] = 1;
        $display_option[ 'carrier_details' ]          = false;
        $display_option[ 'hide_keywords' ]            = "{$display_option[ 'hide_keywords' ]},China,Aliexpress,Chinese cities";

        $res = TrackingSettings::instance()->save_settings( [ 'display_option' => $display_option ] );

        /* 更新运输商列表 */
        $enabled_list = [ 'cainiao' ];

        $selected_courier = (array)get_option( \ParcelPanel\OptionName\SELECTED_COURIER, [] );

        $additional_couriers = array_diff( $enabled_list, $selected_courier );

        if ( ! empty( $additional_couriers ) ) {
            // 追加启用的运输商
            $selected_courier = array_merge( $selected_courier, $additional_couriers );
            update_option( \ParcelPanel\OptionName\SELECTED_COURIER, $selected_courier, false );
        }

        (new ParcelPanelFunction)->parcelpanel_json_response( [], 'Enabled successfully' );
    }
}
