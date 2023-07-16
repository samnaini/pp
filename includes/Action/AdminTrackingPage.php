<?php

namespace ParcelPanel\Action;

use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\TrackingSettings;
use ParcelPanel\ParcelPanelFunction;

class AdminTrackingPage
{
    use Singleton;

    function enqueue_scripts()
    {
        $settings = TrackingSettings::instance()->get_settings();

        $settings[ 'tracking_page_translations_default' ] = TrackingSettings::DEFAULT_SETTINGS[ 'trk_pg_trans' ];

        $settings[ 'trackurl' ] = (new ParcelPanelFunction)->parcelpanel_get_track_page_url( true );


        $category_names = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'pad_counts'   => false,
                'hide_empty'   => false,
                // 'include'  => $category_ids,
                // 'fields'   => 'names',
            )
        );

        $settings[ 'product_categories' ] = $category_names;

        wp_localize_script( 'pp-tracking-page', 'pp_tracking_page_settings', $settings );
    }

    /**
     * 保存设置项 API
     */
    function save_settings_ajax()
    {
        check_ajax_referer( 'pp-tracking-page-save' );

        $settings = (new ParcelPanelFunction)->parcelpanel_get_post_data();

        $res = TrackingSettings::instance()->save_settings( $settings );

        if ( $res ) {
            (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'Saved successfully' ) );
        }

        (new ParcelPanelFunction)->parcelpanel_json_response( [], __( 'No changes' ) );
    }
}
