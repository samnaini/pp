<?php

namespace ParcelPanel\Action;

use ParcelPanel\Libs\Singleton;
use ParcelPanel\ParcelPanelFunction;

class Lang
{
    use Singleton;

    public static function langToWordpress($langList, $appName = 'parcelpanel')
    {
        if (empty($langList)) {
            $langList = self::defaultLangList();
        }
        
        $langListNew = [];
        $locale = apply_filters('plugin_locale', get_locale(), 'parcelpanel');
        // $checkLang = str_contains($locale, 'en');
        $checkLang = strpos($locale, 'en') !== false;
        foreach ($langList as $k => $v) {
            foreach ($v as $kk => $vv) {
                if ($checkLang) {
                    break;
                } else {
                    $langListNew[$k][$kk] = __($vv, $appName);
                }

            }
        }
        if (empty($langListNew)) {
            $langListNew = $langList;
        }

        return $langListNew;
    }

    private static function defaultLangList()
    {
        return [
            'pageTip' => [
                'common_sync_error' => 'Sync unsuccessfully',
                'common_sync_success' => 'The system is syncing your orders and it needs a few minutes.',

                'track_page_save_error' => 'Saved unsuccessfully',
                'track_page_save_success' => 'Saved successfully',

                'update_order_status_fail' => 'Saved unsuccessfully',
                'update_order_status_success' => 'Saved successfully',

                'account_free_error' => 'Change unsuccessfully',
                'account_free_success' => 'Change successfully',

                'common_config_save_error' => 'Saved unsuccessfully',
                'common_config_save_success' => 'Saved successfully',

                'toggle_success' => 'Enabled successfully',
                'toggle_fail' => 'Disabled successfully',

                'export_error' => 'Export unsuccessfully',
                'export_success' => 'Export successfully',

                'setting_save_success' => 'Saved successfully',

                'import_step_choose_file' => 'Please choose a CSV file',
                'import_step_choose_file_type' => 'Sorry, this file type is not permitted!',
                'import_step_file_select_order' => 'Order number',
                'import_step_file_select_number' => 'Tracking number',
                'import_step_file_select' => 'These fields are required to map:',

                'email_tip_empty' => 'Required fields can\'t be empty.',
                'email_tip_valid' => 'Please enter a valid email.',

                'email_send_success' => 'Sent successfully',
                'email_send_fail' => 'Sent unsuccessfully',

                'copy_success' => 'Copied successfully',
                'copy_fail' => 'Copied unsuccessfully',
            ],
            'tips' => [
                'feedback_title' => 'We want to provide the best experience for you 👋',
                'feedback_con' => 'Your feedback means a lot to us! Taking a minute to leave your review will inspire us to keep going.',

                'free_upgrade_title' => 'Special offer - Contact us to Free upgrade 🥳',
                'free_upgrade_con' => 'We\'ve so far provided service for over 120,000 Shopify & WooCommerce stores. This is our way of giving back (20 → Unlimited free quota) 🙌',
                'free_upgrade_btn' => 'Free upgrade now',

                'remove_branding_title' => 'Remove ParcelPanel branding for Free 😘',
                'remove_branding_con' => 'Contact support to remove the branding (worth $49/month) from your tracking page.',
                'remove_branding_btn' => 'Contact us',

                'upgrade_reminder_title' => 'Upgrade reminder',
                'upgrade_reminder_con' => 'Howdy partner, there are only <0> quota available in your account, upgrade to sync & track more orders.',
                'upgrade_reminder_btn' => 'Upgrade now',

                'first_sync_title' => 'Even better - free sync & track your last-30-day orders 🎉',
                'first_sync_con' => 'This will help you know how ParcelPanel performs and provide your old customers with a seamless order tracking experience.',
                'first_sync_tip' => 'Completed on <0>',

                'nps_title' => 'A Quick Word on your ParcelPanel Experience (Only 2 questions ) 🌻',
                'nps_con' => 'We value your opinion! It is highly appreciated if you could take <0>10 seconds<1> to rate your experience with us by participating in our brief Net Promoter Score (NPS) survey.',
                'nps_btn' => 'Take the survey →',
            ],
            'common' => [
                'dismiss' => 'Dismiss',
                'cancel' => 'Cancel',
                'apply' => 'Apply',
                'close' => 'Close',
                'upgrade' => 'Upgrade',
                'remaining_total' => 'Remaining / Total',
                'upgrade_note' => 'Note: Remaining means you have <0> available quota.',
                'export' => 'Export',
                'country' => 'Country',
                'courier' => 'Courier',
                'quantity' => 'Quantity',
                'day' => 'Day',
                'destination' => 'Destination',
                'filter' => 'Filter',

                'pending' => 'Pending',
                'in_transit' => 'In transit',
                'delivered' => 'Delivered',
                'out_for_delivery' => 'Out for delivery',
                'info_received' => 'Info received',
                'exception' => 'Exception',
                'failed_attempt' => 'Failed attempt',
                'expired' => 'Expired',

                'enabled' => 'Enabled',
                'disabled' => 'Disabled',
                'no_couriers_yet' => 'No couriers yet',
                'no_couriers_yet_note' => 'You have not set up a frequently-used courier',

                'need_any_help' => 'Need any help?',

                'feedback_title' => 'Send your feedback',
                'feedback_con' => 'Please tell us more, we will try the best to get better',
                'feedback_con_eg' => 'Edit your message here...',
                'feedback_email' => 'Your contact email',
                'feedback_email_eg' => 'e.g. parcelpanel100@gmail.com',
                'send' => 'Send',
                'contact_us' => 'Contact us',
                'view_example' => 'View example',
                'learn_more_about_parcel_panel' => 'Learn more about <0>Parcel Panel<1>',
                'new' => 'NEW',
                'hot' => 'HOT',

                'intercom_title' => 'Start Live Chat',
                'intercom_con' => 'When you click the confirm button, we will open our live chat widget, where you can chat with us in real-time to solve your questions, and also potentially collect some personal data.',
                'intercom_con_s' => 'Learn more about our <0>Privacy Policy<1>.',
                'intercom_con_t' => 'Don\'t want to use live chat? Contact us via email: <0>support@parcelpanel.org<1>',
                'intercom_con_b_cancel' => 'Cancel',
                'intercom_con_b_confirm' => 'Confirm',
                'intercom_alert_user' => '<0>Jessie<1> from Parcel Panel',
                'intercom_alert_status' => 'Active',
                'intercom_alert_title' => 'Welcome to ParcelPanel',
                'intercom_alert_hello' => 'Hello',
                'intercom_alert_con' => 'We’re so glad you’re here, let us know if you have any questions.',
                'intercom_alert_input' => 'Live chat with us',
                'intercom_alert_con_shipment' => 'Any questions about orders sync, courier matching or tracking update?',


                'help' => 'Help',
                'help_con' => 'Select a direction.',
                'live_chat_support' => 'Live Chat Support',
                'get_email_support' => 'Get Email Support',
                'parcel_panel_help_docs' => 'ParcelPanel Help Docs',

                'alert_title_other' => 'Special Gift',
                'alert_title' => 'Free upgrade plan',
                'alert_con' => 'Offer Will Expire Soon',
                'alert_btn' => 'Free Upgrade Now',
                'alert_con_t1' => '<0><1><2>Get<3> <4>unlimited quota worth $999<5>',
                'alert_con_t2' => '<0><1><2>The best for<3> <4>dropshipping<5>',
                'alert_con_t3' => '<0><1><2>Access to<3> <4>1000+ couriers<5>',
                'alert_con_t4' => '<0><1><2>Import widget &<3> <4>CSV import<5>',
                'alert_con_t5' => '<0><1><2>Branded<3> <4>tracking page<5>',
                'alert_con_t6' => '<0><1><2><3><4>Remove ParcelPanel branding<5>',
                'alert_con_t7' => '<0><1><2>Shipping<3> <4>notifications<5>',
                'alert_con_t8' => '<0>24/7<1> <2>live chat<3> <4>support<5>',
            ],
            'home' => [
                'welcome' => 'Welcome to Parcel Panel 👋',
                'welcome_card_title' => 'Welcome!! Get started with ParcelPanel',

                'add_tracking_page' => 'Add tracking page',
                'add_tracking_page_title' => 'Add tracking page to your storefront',
                'add_tracking_page_con' => 'Your branded tracking page is ready, the URL is: <0>, add it to your store menus so your customers can track orders there.',
                'add_tracking_page_con_s' => 'Don’t know how to do this? Please follow <0>this instruction<1>.',
                'add_tracking_page_btn' => 'Preview tracking page',
                'add_tracking_page_foot' => 'Looks not so good?',

                'drop_shipping_optional' => 'Dropshipping (optional)',
                'drop_shipping_optional_title' => 'Enable dropshipping mode',
                'drop_shipping_optional_con' => 'ParcelPanel is the best for dropshipping, and supports Aliexpress Standard Shipping, Yunexpress, CJ packet & ePacket (China EMS, China Post), and all commonly used couriers for dropshipping merchants.',
                'drop_shipping_optional_con_s' => 'This feature will allow you to hide all Chinese origins easily, bringing your customers an all-around brand shopping experience. <0>Learn more<1>.',

                'how_parcel_panel_works' => 'How ParcelPanel works',
                'how_parcel_panel_works_title' => 'Out-of-box user experience',
                'how_parcel_panel_works_con' => 'Tracking numbers are synced from the Orders section of your WooCommerce admin, please don\'t forget to add them.',
                'how_parcel_panel_works_con_s' => 'For new users, ParcelPanel will automatically sync their last-30-day orders for Free.',
                'how_parcel_panel_works_con_t' => 'New coming orders will be automatically synced in real time.',
                'how_parcel_panel_works_con_f' => 'We fetch the tracking info from couriers\' websites based on the tracking numbers.',

                'sync_tracking_numbers' => 'Sync tracking numbers',
                'sync_tracking_numbers_con' => 'ParcelPanel will automatically sync tracking numbers from Orders section in your WooCommerce admin once you add there, then track them.',
                'sync_tracking_numbers_t' => 'Orders section in WooCommerce admin',
                'sync_tracking_numbers_foot' => 'Additionally, how to get the tracking number, it\'s not about ParcelPanel. As we know, some merchants fulfilled orders via the 3rd party fulfillment service, some use dropshipping apps, some fulfilled by themselves etc. To know the tracking number of your order, we kindly suggest you asking suppliers or carriers support directly for some help.',

                'import_number_title' => 'Import tracking number of your orders',
                'import_con' => 'Import tracking numbers of your orders in bulk with a CSV file, or manually add one by one in the Edit order page.',
                'import_tracking_number' => 'Import tracking number',
                'import_help_link_con' => 'How to import tracking number with a CSV file?',

                'exception_shipments_to_check' => '<0><1> exception<2> shipments to check',
                'failed_attempt_shipments_to_check' => '<0><1> failed attempt<2> shipments to check',
                'expired_shipments_to_check' => '<0><1> expired<2> shipments to check',

                'shipments_lookups' => 'Shipments / Lookups',
                'delivery_performance' => 'Delivery performance',
                'valid_tracking' => 'Valid tracking',
                'shipments_lookups_over_time' => 'SHIPMENT / LOOKUPS OVER TIME',
                'shipment_status' => 'SHIPMENT STATUS',

                'delivery_days_by_destinations' => 'Delivery days by destinations',
                'delivery_days_by_couriers' => 'Delivery days by couriers',

                'upcoming_features' => 'Upcoming features 👏',
                'upcoming_features_t_1' => 'Integration with WPML...',
                'upcoming_features_c_1' => 'Will be launched in July',
                'upcoming_features_t_2' => 'Customized email templates',
                'upcoming_features_c_2' => 'Will be launched in August',
                'upcoming_features_t_3' => 'Notification / Product Recommendation analysis',
                'upcoming_features_c_3' => 'Will be launched in August',

                'get_email_support' => 'Get Email Support',
                'get_email_support_con' => 'Email us and we\'ll get back to you as soon as possible.',
                'start_live_chat' => 'Start Live Chat',
                'start_live_chat_con' => 'Talk to us directly via live chat to get help with your question.',
                'parcel_panel_help_docs' => 'ParcelPanel Help Docs',
                'parcel_panel_help_docs_con' => 'Find a solution for your problem with ParcelPanel documents and tutorials.',

                'Last_days' => 'Last <0> days',
                'day_range' => 'Day range',
                'starting_date' => 'Starting Date',
                'ending_date' => 'Ending Date',
            ],
            'trackPage' => [
                'tracking_page' => 'Tracking page',
                'preview' => 'Preview',
                'save_changes' => 'Save changes',

                'appearance' => 'Appearance',
                'languages' => 'Languages',
                'custom_shipment_status' => 'Custom shipment status',
                'estimated_delivery_time' => 'Estimated delivery time',
                'product_recommendation' => 'Product recommendation',
                'manual_translations' => 'Manual translations',
                'css_html' => 'CSS & HTML',

                'foot_con' => 'How to add tracking page to your store? <0>Click here<1>',

                'style' => 'Style',
                'theme_container_width' => 'Theme container width',
                'progress_bar_color' => 'Progress bar color',
                'theme_mode' => 'Theme mode',
                'light_mode' => 'Light mode',
                'light_mode_con' => 'Choose Light mode if it is dark-colored text on your store theme.',
                'dark_mode' => 'Dark mode',
                'dark_mode_con' => 'Choose Dark mode if it is light-colored text on your store theme.',
                'order_lookup_widget' => 'Order lookup widget',
                'lookup_options' => 'Lookup options',
                'by_order_email' => 'By order number and email',
                'by_number' => 'By tracking number',
                'parcel_panel_branding' => 'ParcelPanel branding',
                'parcel_panel_branding_con' => 'Remove "Powered by ParcelPanel"',
                'parcel_panel_branding_help' => 'to remove ParcelPanel branding for Free 😘',
                'additional_text' => 'Additional text',
                'text_above_title' => 'Text above the order lookup widget',
                'text_above_eg' => 'e.g. Curious about where your package is? Click the button to track!',
                'text_below_title' => 'Text below the order lookup widget',
                'text_below_eg' => 'e.g. Any questions or concerns? Please feel free to contact us!',
                'tracking_results' => 'Tracking results',
                'shipment_display_options' => 'Shipment display options',
                'carrier_name_and_logo' => 'Carrier name and logo',
                'tracking_number' => 'Tracking number',
                'product_info' => 'Product info',
                'tracking_details' => 'Tracking details',
                'google_translate_widget' => 'Google translate widget',
                'map_coordinates' => 'Map coordinates',
                'map_coordinates_con' => 'Show map on your tracking page',
                'current_location' => 'Current location',
                'destination_address' => 'Destination address',
                'hide_keywords' => 'Hide keywords',
                'hide_keywords_eg' => 'e.g. China,Aliexpress,Chinese cities. Separate with comma.',
                'date_and_time_format' => 'Date and time format',
                'date_format' => 'Date format',
                'time_format' => 'Time format',

                'theme_language' => 'Theme language',

                'hot' => 'HOT',
                'custom_shipment_status_con' => 'Add custom status (up to 3) with time interval and description, to timely inform customers about the progress of their orders before you fulfill them. <0>Learn more<1>.',
                'custom_shipment_status_add' => 'Add custom shipment status',
                'custom_shipment_status_con_s' => 'Create additional steps to inform customers of your process prior to shipping',
                'ordered' => 'Ordered',
                'order_ready' => 'Order ready',
                'in_transit' => 'In transit',
                'out_for_delivery' => 'Out for delivery',
                'delivered' => 'Delivered',
                'custom_tracking_info' => 'Custom tracking info',
                'custom_tracking_info_con' => 'Add one custom tracking info with time interval to reduce customer anxiety when the package was stuck in shipping, and it will be shown if the tracking info hasn\'t updated for the days you set.',
                'custom_tracking_info_day' => 'Day(s) since last tracking info',
                'custom_tracking_info_day_eg' => 'eg. 7',
                'custom_tracking_info_eg' => 'e.g. In Transit to Next Facility',

                'estimated_delivery_time_con' => 'Set an estimated time period that will be displayed on your tracking page, to show your customers when they will receive their orders. <0>Learn more<1>.',
                'enable_this_feature' => 'Enable this feature',
                'calculate_from' => 'Calculate from',
                'order_created_time' => 'Order created time',
                'order_shipped_time' => 'Order shipped time',
                'estimated_delivery_time_common' => 'Estimated delivery time: <0> - <1>(d)',
                'advanced_settings_based_destinations' => 'Advanced settings based on destinations',
                'shipping_to' => 'Shipping to',
                'add_another' => 'Add another',

                'product_recommendation_con_f' => 'Turn your tracking page into a marketing channel to make more sales.',
                'product_recommendation_con_s' => '<0>Add categories<1> in your WordPress Products section to recommend. By default the recommendations are based on the customer’s order items, you can also select a category to recommend. <2>Learn more.<3>',
                'display_at_on_the' => 'Display at/on the',
                'top' => 'Top',
                'right' => 'Right',
                'bottom' => 'Bottom',
                'advanced_settings_based_category' => 'Advanced settings based on a category',
                'select_a_category' => 'Select a category',
                'category' => 'Category',

                'manual_translations_con' => 'Here you can manually translate the tracking detailed info by yourself.',
                'manual_translations_con_s' => 'Note: this feature distinguishes the strings based on Space, please don\'t forget the comma and period when you do the translation.',
                'translation_before' => 'Tracking info (before translation)',
                'translation_after' => 'Tracking info (after translation)',
                'translation_before_eg' => 'e.g. Shanghai,CN',
                'translation_after_eg' => 'Sorting Center',

                'css' => 'CSS',
                'contact_us_track' => 'contact us',
                'css_con' => 'View the CSS codes here you used to do the custom change of your tracking page, if you would like to change codes, please',
                'css_con_s' => 'or follow <0>this instruction<1>.',
                'html_top' => 'HTML top of page',
                'html_bottom' => 'HTML bottom of page',
            ],
            'shipment' => [
                'shipments' => 'Shipments',
                'columns' => 'Columns',
                'pagination' => 'Pagination',
                'item_per_page' => 'Number of items per page:',
                'screen_options' => 'Screen Options',

                'all' => 'All',
                'import_tracking_number' => 'Import tracking number',
                'search_eg' => 'Filter order number or Tracking number',
                'filter_by_date' => 'Filter by date',
                'filter_by_courier' => 'Courier',
                'filter_by_country' => 'Destination',
                'showing_shipments' => 'Showing <0> shipments',
                'page_of' => 'of <0>',
                'select_sync_time' => 'Select sync time',
                're_sync' => 'Re-sync',
                'shipment_foot' => 'Why are my orders on black Pending status? <0>Click here<1>',
                'no_date_yet' => 'No date yet',
                'no_orders_yet' => 'No orders yet',
                'table_empty_tip' => 'Try changing the filters or search term',

                'order' => 'Order',
                'tracking_number' => 'Tracking number',
                'courier' => 'Courier',
                'last_check_point' => 'Last check point',
                'transit' => 'Transit',
                'order_date' => 'Order date',
                'shipment_status' => 'Shipment status',

                'export_shipments' => 'Export shipments',
                'export_con' => '<0> shipments data will be exported a CSV table,',
                'export_learn_more' => 'Learn more about <0>CSV table headers<1>.',
                'exporting' => 'Exporting:',
                'exporting_con' => 'Please DO NOT close or refresh this page before it was completed.',

                'import_back' => 'back',
                'import_title' => 'Import tracking number',
                'import_step_1_title' => 'Upload CSV file',
                'import_step_2_title' => 'Column mapping',
                'import_step_3_title' => 'Import',
                'import_step_4_title' => 'Done!',
                'import_step_1_c_title' => 'Select CSV file to import',
                'import_step_1_c_f' => 'Your CSV file should have following columns:',
                'import_step_1_c_required' => '<0>Required:<1> Order number, Tracking number',
                'import_step_1_c_optional' => '<0>Optional:<1> Courier, SKU, Qty, Date shipped, Mark order as Completed',
                'import_step_1_note' => 'Note:',
                'import_step_1_note_f' => 'Product sku and quantity are required if you need to ship the items in one order into multiple packages. <0>Learn more<1>.',
                'import_step_1_note_s' => 'If the total quantity shipped exceeds the total quantity of items in an order, the system will automatically adjust to the maximum shippable quantity of the items.',
                'import_step_1_f_file' => 'Choose a local file',
                'import_step_1_f_file_name' => 'No file chosen',
                'import_step_1_f_choose' => 'Choose File',
                'import_step_1_s_map' => 'Mapping preferences',
                'import_step_1_s_con' => 'Enable this feature to save previous column mapping settings automatically',
                'import_step_1_t_csv' => 'CSV Delimiter',
                'import_step_1_t_con' => 'This sets the separator of your CSV files to separates the data in your file into distinct fields',
                'import_step_1_btn_hid' => 'Hide advanced options',
                'import_step_1_btn_show' => 'Show advanced options',
                'import_step_1_btn_continue' => 'Continue',
                'import_step_2_c_title' => 'Map CSV fields to orders',
                'import_step_2_c_con' => 'Select fields from your CSV file to map against orders fields.',
                'import_step_2_c_top_f' => 'Column name',
                'import_step_2_c_top_s' => 'Map to field',
                'import_step_2_btn' => 'Import',
                'import_step_2_select' => 'Select a column title',
                'import_step_2_show_required' => ' (*Required)',
                'import_step_2_show_optional' => ' (Optional)',
                'import_step_2_order_number' => 'Order number',
                'import_step_2_tracking_number' => 'Tracking number',
                'import_step_2_courier' => 'Courier',
                'import_step_2_sku' => 'SKU',
                'import_step_2_qty' => 'Qty',
                'import_step_2_fulfilled_date' => 'Date shipped',
                'import_step_2_mark_order_as_completed' => 'Mark order as Completed',
                'import_step_3_c_title' => 'Importing',
                'import_step_3_c_con' => 'Your orders are now being imported…',
                'import_step_3_uploading' => 'Uploading:',
                'import_step_3_uploading_con' => 'Please DO NOT close or refresh this page before it was completed.',
                'import_step_4_c_title' => 'Import Completed',
                'import_step_4_c_tip_success' => '<0> tracking numbers imported successfully',
                'import_step_4_c_tip_fail' => '<0> tracking numbers imported failed,',
                'import_step_4_c_tip_model' => 'view details',
                'import_step_4_c_shipments' => 'View shipments',
                'import_step_4_c_again' => 'Upload again',
                'import_step_4_c_records' => 'Import records',
                'import_step_4_mod_title' => 'View details',
                'import_step_4_mod_file' => 'Import file name:',
                'import_step_4_mod_number' => 'Total tracking numbers:',
                'import_step_4_mod_success' => 'Succeeded:',
                'import_step_4_mod_failed' => 'Failed:',
                'import_step_4_mod_details' => 'Details:',
                'import_step_4_mod_close' => 'Close',
                'import_step_4_c_records_list' => '<0> Uploaded <1>, <2> tracking numbers, failed to upload <3>,',

                'manually_update_status' => 'Manually update status',
                'save_changes' => 'Save changes',
                'updating_shipment_title' => 'Updating the status for <0> shipment.',
                'automatic_shipment_status_update' => 'Automatic shipment status update',

                'import_step_1_model' => 'You can also copy or download a sample template to import tracking numbers.',
                'import_step_1_model_btn' => 'View instruction',
                'import_step_1_model_title' => 'Import tracking number',
                'import_step_1_model_con_1' => 'Step 1: Copy the <0>sample template<1> on Google Sheets (strongly recommend) or download <2>this CSV file<3>.',
                'import_step_1_model_con_2' => 'Step 2: Fill in the data following the <0>Import Template Instructions<1>. Tracking number that do not comply with the instructions will not be imported.',
                'import_step_1_model_con_3' => 'Step 3: Download the file in a CSV format and upload it.',
                'import_step_1_model_b_import' => 'Import',
                'import_step_1_model_b_close' => 'Close',

            ],
            'setting' => [
                'settings' => 'Settings',
                'delivery_notifications' => 'Delivery notifications',
                'preview' => 'Preview',
                'add_an_order_tracking_switch' => 'Add an order tracking section to WooCommerce email notifications',
                'add_an_order_tracking_select' => 'Select order status to show the tracking section',
                'shipping_email_title' => 'ParcelPanel shipping email notifications',
                'shipping_email_note' => 'This feature will allow you to send email notifications based on ParcelPanel shipment status.',
                'manage_template' => 'Manage template',
                'account_page_tracking' => 'Account page tracking',
                'add_a_order_track_btn' => 'Add a track button to orders history page (Actions column)',
                'add_a_order_track_note' => 'This feature will allow you to add a track button to the orders history page (Actions column) so your customers can track their orders with one click there.',
                'add_a_order_track_model' => 'View example',
                'add_a_order_track_select' => 'Select order status to show the track button',
                'configuring_wooCommerce_orders' => 'Configuring WooCommerce Orders',
                'rename_order_status_switch' => 'Rename order status "Completed" to "Shipped"',
                'rename_order_status_note' => 'This feature will allow you to change the order status label name from "Completed" to "Shipped".',
                'add_a_order_track_wc_btn' => 'Add a track button to WooCommerce orders (Actions column)',
                'add_a_order_track_wc_note' => 'This feature will allow you to add a shortcut button to WooCommerce orders (Actions column), which will make it easier to add tracking information.',
                'drop_shipping_mode' => 'Dropshipping mode',
                'enable_drop_shipping_mode' => 'Enable dropshipping mode',
                'enable_drop_shipping_mode_note' => 'This feature will allow you to hide all Chinese origin to bring your customers an all-around brand shopping experience. <0>Learn more<1>.',
                'courier_matching' => 'Courier matching',
                'courier_matching_note' => 'Enable frequently-used couriers if you exactly know which couriers you are using. If not, please just leave this empty, the system will automatically recognize suitable couriers for you.',
                'search_couriers' => 'Search couriers',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'completed' => 'Completed',
                'partial_shipped' => 'Partially Shipped',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded',
                'failed' => 'Failed',
                'draft' => 'Draft',
                'save_changes' => 'Save changes',
                'couriers_selected' => '<0> selected',
                'couriers_selected_Showing' => 'Showing <0> of <1> couriers',

                'model_account_btn_title' => 'How it works?',
                'model_account_btn_con' => 'After enabled, ParcelPanel will add a track button to the orders history page (Actions column), so your customers can track their orders with one click directed to your store tracking page.',
                'model_account_btn_con_s' => 'After enabled, ParcelPanel will add a track button to orders admin (Actions column), you can add tracking info easily without clicking into order details.',

            ],
            'integration' => [
                'integration' => 'Integration',
                'featured' => 'Featured',
                'drop_shipping' => 'Dropshipping',
                'custom_order_number' => 'Custom order number',
                'email_customizer' => 'Email customizer',
                'plugins_integrated_title' => 'Will there be more plugins integrated?',
                'plugins_integrated_con' => 'Yes, ParcelPanel will continue to integrate with more plugins to improve the user experience. Stay tuned!',
                'using_list_title' => 'Is it possible to integrate a plugin that I using but is not on your list?',
                'using_list_con' => 'Let us know which plugin you would like us to integrate with.',
                'contact_us' => 'Contact us',
                'custom_order_number_con' => 'You do not have to do anything, ParcelPanel works with the below plugins by default.  <0>Learn more<1>.',
                'email_customizer_con' => 'You do not have to do anything, ParcelPanel works with the below plugins by default.',

                'api_key' => 'API Key',
                'api_key_note' => 'Developers can use the ParcelPanel API to effectively manage shipments by creating, viewing, and deleting tracking information. Learn <0>API doc<1> for details.',
                'api_key_title' => 'Your API Key',
                'api_key_btn_show' => 'Show',
                'api_key_btn_hidden' => 'Hide',
                'api_key_btn_copy' => 'Copy',

            ],
            'account' => [
                'account' => 'Account',
                'account_info' => 'Account info',
                'current_plan' => 'Current plan',
                'quota_limit' => 'Quota limit',
                'next_quota_reset_date' => 'Next quota reset date',
                'quota_usage' => 'Quota usage',
                'plan_quota' => 'quota',
                'plan_note' => 'Avg $<0> per order',
                'month' => 'month',
                'choose' => 'Choose',
                'unlimited_tip' => 'Limited time offer specially for WooCommerce stores✨',
                'unlimited' => 'Unlimited',
                'unlimited_note' => 'Unlimited quota Avg $0.0000 per order',
                'unlimited_btn' => 'Free Upgrade Now 🥳',
                'charge_based_on_title' => 'Does ParcelPanel charge based on order lookups?',
                'charge_based_on_con' => 'No, ParcelPanel counts the quota based on the number of your orders synced to ParcelPanel, and provides unlimited order lookups.',
                'billing_cycle_title' => 'Can I change my plan in the middle of a billing cycle?',
                'billing_cycle_con' => 'Yes, you can change your plan at any time based on your needs. If you want to change the plan, the remaining quotas of current plan will be added to the new one automatically.',
                'why_unlimited_title' => 'Why is ParcelPanel launching an unlimited plan? When will ParcelPanel charge?',
                'why_unlimited_con' => 'We\'ve provided service for over 120,000 Shopify and WooCommerce stores, but we are still pretty new to WooCommerce for now. We want to give more special offers for WooCommerce merchants.',
                'why_unlimited_con_s' => 'ParcelPanel will not charge for a short time. But please rest assured that if we decide to charge, we will definitely inform you in advance, and there will be no hidden charges.',

                'free_plan_model_title' => 'Downgrade to Starter Free plan',
                'free_plan_model_con' => 'This can\'t be undone!',
                'free_plan_model_btn' => 'Downgrade',
            ],
        ];
    }


    private static function langNew_pageTip()
    {
        // pageTip ----------
        __('Sync unsuccessfully', 'parcelpanel');
        __('The system is syncing your orders and it needs a few minutes.', 'parcelpanel');

        __('Saved unsuccessfully', 'parcelpanel');
        __('Saved successfully', 'parcelpanel');

        __('Change unsuccessfully', 'parcelpanel');
        __('Change successfully', 'parcelpanel');

        __('Enabled successfully', 'parcelpanel');
        __('Disabled successfully', 'parcelpanel');

        __('Export unsuccessfully', 'parcelpanel');
        __('Export successfully', 'parcelpanel');

        __('Please choose a CSV file', 'parcelpanel');
        __('Sorry, this file type is not permitted!', 'parcelpanel');
        __('These fields are required to map:', 'parcelpanel');
        __('Order number', 'parcelpanel');
        __('Tracking number', 'parcelpanel');
        __('Required fields can\'t be empty.', 'parcelpanel');
        __('Please enter a valid email.', 'parcelpanel');
        __('Sent successfully', 'parcelpanel');
        __('Sent unsuccessfully', 'parcelpanel');
        __('Copied successfully', 'parcelpanel');
        __('Copied unsuccessfully', 'parcelpanel');
        // pageTip ----------
    }

    private static function langNew_tips()
    {
        // tips ----------
        __('We want to provide the best experience for you 👋', 'parcelpanel');
        __('Your feedback means a lot to us! Taking a minute to leave your review will inspire us to keep going.', 'parcelpanel');
        __('Special offer - Contact us to Free upgrade 🥳', 'parcelpanel');
        __('We\'ve so far provided service for over 120,000 Shopify & WooCommerce stores. This is our way of giving back (20 → Unlimited free quota) 🙌', 'parcelpanel');
        __('Free upgrade now', 'parcelpanel');
        __('Remove ParcelPanel branding for Free 😘', 'parcelpanel');
        __('Contact support to remove the branding (worth $49/month) from your tracking page.', 'parcelpanel');
        __('Contact us', 'parcelpanel');
        __('Upgrade reminder', 'parcelpanel');
        __('Howdy partner, there are only <0> quota available in your account, upgrade to sync & track more orders.', 'parcelpanel');
        __('Upgrade now', 'parcelpanel');
        __('Even better - free sync & track your last-30-day orders 🎉', 'parcelpanel');
        __('This will help you know how ParcelPanel performs and provide your old customers with a seamless order tracking experience.', 'parcelpanel');
        __('Completed on <0>', 'parcelpanel');
        __('A Quick Word on your ParcelPanel Experience (Only 2 questions ) 🌻', 'parcelpanel');
        __('We value your opinion! It is highly appreciated if you could take <0>10 seconds<1> to rate your experience with us by participating in our brief Net Promoter Score (NPS) survey.', 'parcelpanel');
        __('Take the survey →', 'parcelpanel');
        // tips ----------
    }

    private static function langNew_common()
    {
        // common ----------
        __('Dismiss', 'parcelpanel');
        __('Cancel', 'parcelpanel');
        __('Apply', 'parcelpanel');
        __('Close', 'parcelpanel');
        __('Upgrade', 'parcelpanel');
        __('Remaining / Total', 'parcelpanel');
        __('Note: Remaining means you have <0> available quota.', 'parcelpanel');
        __('Export', 'parcelpanel');
        __('Country', 'parcelpanel');
        __('Courier', 'parcelpanel');
        __('Quantity', 'parcelpanel');
        __('Day', 'parcelpanel');
        __('Destination', 'parcelpanel');
        __('Filter', 'parcelpanel');

        __('Pending', 'parcelpanel');
        __('In transit', 'parcelpanel');
        __('Delivered', 'parcelpanel');
        __('Out for delivery', 'parcelpanel');
        __('Info received', 'parcelpanel');
        __('Exception', 'parcelpanel');
        __('Failed attempt', 'parcelpanel');
        __('Expired', 'parcelpanel');

        __('Enabled', 'parcelpanel');
        __('Disabled', 'parcelpanel');
        __('No couriers yet', 'parcelpanel');
        __('You have not set up a frequently-used courier', 'parcelpanel');

        __('Need any help?', 'parcelpanel');

        __('Send your feedback', 'parcelpanel');
        __('Please tell us more, we will try the best to get better', 'parcelpanel');
        __('Edit your message here...', 'parcelpanel');
        __('Your contact email', 'parcelpanel');
        __('e.g. parcelpanel100@gmail.com', 'parcelpanel');
        __('Send', 'parcelpanel');
        __('Contact us', 'parcelpanel');
        __('View example', 'parcelpanel');
        __('Learn more about <0>Parcel Panel<1>', 'parcelpanel');
        __('NEW', 'parcelpanel');
        __('HOT', 'parcelpanel');

        __('Start Live Chat', 'parcelpanel');
        __('When you click the confirm button, we will open our live chat widget, where you can chat with us in real-time to solve your questions, and also potentially collect some personal data.', 'parcelpanel');
        __('Learn more about our <0>Privacy Policy<1>.', 'parcelpanel');
        __('Don\'t want to use live chat? Contact us via email: <0>support@parcelpanel.org<1>', 'parcelpanel');
        __('Cancel', 'parcelpanel');
        __('Confirm', 'parcelpanel');
        __('<0>Jessie<1> from Parcel Panel', 'parcelpanel');
        __('Active', 'parcelpanel');
        __('Welcome to ParcelPanel', 'parcelpanel');
        __('Hello', 'parcelpanel');
        __('We’re so glad you’re here, let us know if you have any questions.', 'parcelpanel');
        __('Live chat with us', 'parcelpanel');
        __('Any questions about orders sync, courier matching or tracking update?', 'parcelpanel');

        __('Help', 'parcelpanel');
        __('Select a direction.', 'parcelpanel');
        __('Live Chat Support', 'parcelpanel');
        __('Get Email Support', 'parcelpanel');
        __('ParcelPanel Help Docs', 'parcelpanel');
        
        __('Special Gift', 'parcelpanel');
        __('Free upgrade plan', 'parcelpanel');
        __('Offer Will Expire Soon', 'parcelpanel');
        __('Free Upgrade Now', 'parcelpanel');
        __('<0><1><2>Get<3> <4>unlimited quota worth $999<5>', 'parcelpanel');
        __('<0><1><2>The best for<3> <4>dropshipping<5>', 'parcelpanel');
        __('<0><1><2>Access to<3> <4>1000+ couriers<5>', 'parcelpanel');
        __('<0><1><2>Import widget &<3> <4>CSV import<5>', 'parcelpanel');
        __('<0><1><2>Branded<3> <4>tracking page<5>', 'parcelpanel');
        __('<0><1><2><3><4>Remove ParcelPanel branding<5>', 'parcelpanel');
        __('<0><1><2>Shipping<3> <4>notifications<5>', 'parcelpanel');
        __('<0>24/7<1> <2>live chat<3> <4>support<5>', 'parcelpanel');
        // common ----------
    }

    private static function langNew_home()
    {
        // home ----------
        __('Welcome to Parcel Panel 👋', 'parcelpanel');
        __('Welcome!! Get started with ParcelPanel', 'parcelpanel');

        __('Add tracking page', 'parcelpanel');
        __('Add tracking page to your storefront', 'parcelpanel');
        __('Your branded tracking page is ready, the URL is: <0>, add it to your store menus so your customers can track orders there.', 'parcelpanel');
        __('Don’t know how to do this? Please follow <0>this instruction<1>.', 'parcelpanel');
        __('Preview tracking page', 'parcelpanel');
        __('Looks not so good?', 'parcelpanel');

        __('Dropshipping (optional)', 'parcelpanel');
        __('Enable dropshipping mode', 'parcelpanel');
        __('ParcelPanel is the best for dropshipping, and supports Aliexpress Standard Shipping, Yunexpress, CJ packet & ePacket (China EMS, China Post), and all commonly used couriers for dropshipping merchants.', 'parcelpanel');
        __('This feature will allow you to hide all Chinese origins easily, bringing your customers an all-around brand shopping experience. <0>Learn more<1>.', 'parcelpanel');

        __('How ParcelPanel works', 'parcelpanel');
        __('Out-of-box user experience', 'parcelpanel');
        __('Tracking numbers are synced from the Orders section of your WooCommerce admin, please don\'t forget to add them.', 'parcelpanel');
        __('For new users, ParcelPanel will automatically sync their last-30-day orders for Free.', 'parcelpanel');
        __('New coming orders will be automatically synced in real time.', 'parcelpanel');
        __('We fetch the tracking info from couriers\' websites based on the tracking numbers.', 'parcelpanel');

        __('Sync tracking numbers', 'parcelpanel');
        __('ParcelPanel will automatically sync tracking numbers from Orders section in your WooCommerce admin once you add there, then track them.', 'parcelpanel');
        __('Orders section in WooCommerce admin', 'parcelpanel');
        __('Additionally, how to get the tracking number, it\'s not about ParcelPanel. As we know, some merchants fulfilled orders via the 3rd party fulfillment service, some use dropshipping apps, some fulfilled by themselves etc. To know the tracking number of your order, we kindly suggest you asking suppliers or carriers support directly for some help.', 'parcelpanel');

        __('Import tracking number of your orders', 'parcelpanel');
        __('Import tracking numbers of your orders in bulk with a CSV file, or manually add one by one in the Edit order page.', 'parcelpanel');
        __('Import tracking number', 'parcelpanel');
        __('How to import tracking number with a CSV file?', 'parcelpanel');

        __('<0><1> exception<2> shipments to check', 'parcelpanel');
        __('<0><1> failed attempt<2> shipments to check', 'parcelpanel');
        __('<0><1> expired<2> shipments to check', 'parcelpanel');

        __('Shipments / Lookups', 'parcelpanel');
        __('Delivery performance', 'parcelpanel');
        __('Valid tracking', 'parcelpanel');
        __('SHIPMENT / LOOKUPS OVER TIME', 'parcelpanel');
        __('SHIPMENT STATUS', 'parcelpanel');

        __('Delivery days by destinations', 'parcelpanel');
        __('Delivery days by couriers', 'parcelpanel');

        __('Upcoming features 👏', 'parcelpanel');
        __('Integration with WPML...', 'parcelpanel');
        __('Will be launched in July', 'parcelpanel');
        __('Customized email templates', 'parcelpanel');
        __('Will be launched in August', 'parcelpanel');
        __('Notification / Product Recommendation analysis', 'parcelpanel');
        __('Will be launched in August', 'parcelpanel');

        __('Get Email Support', 'parcelpanel');
        __('Email us and we\'ll get back to you as soon as possible.', 'parcelpanel');
        __('Start Live Chat', 'parcelpanel');
        __('Talk to us directly via live chat to get help with your question.', 'parcelpanel');
        __('ParcelPanel Help Docs', 'parcelpanel');
        __('Find a solution for your problem with ParcelPanel documents and tutorials.', 'parcelpanel');

        __('Last <0> days', 'parcelpanel');
        __('Day range', 'parcelpanel');
        __('Starting Date', 'parcelpanel');
        __('Ending Date', 'parcelpanel');
        // home ----------
    }

    private static function langNew_trackPage()
    {

        // trackPage ----------
        __('Tracking page', 'parcelpanel');
        __('Preview', 'parcelpanel');
        __('Save changes', 'parcelpanel');

        __('Appearance', 'parcelpanel');
        __('Languages', 'parcelpanel');
        __('Custom shipment status', 'parcelpanel');
        __('Estimated delivery time', 'parcelpanel');
        __('Product recommendation', 'parcelpanel');
        __('Manual translations', 'parcelpanel');
        __('CSS & HTML', 'parcelpanel');

        __('How to add tracking page to your store? <0>Click here<1>', 'parcelpanel');

        __('Style', 'parcelpanel');
        __('Theme container width', 'parcelpanel');
        __('Progress bar color', 'parcelpanel');
        __('Theme mode', 'parcelpanel');
        __('Light mode', 'parcelpanel');
        __('Choose Light mode if it is dark-colored text on your store theme.', 'parcelpanel');
        __('Dark mode', 'parcelpanel');
        __('Choose Dark mode if it is light-colored text on your store theme.', 'parcelpanel');
        __('Order lookup widget', 'parcelpanel');
        __('Lookup options', 'parcelpanel');
        __('By order number and email', 'parcelpanel');
        __('By tracking number', 'parcelpanel');
        __('ParcelPanel branding', 'parcelpanel');
        __('Remove "Powered by ParcelPanel"', 'parcelpanel');
        __('to remove ParcelPanel branding for Free 😘', 'parcelpanel');
        __('Additional text', 'parcelpanel');
        __('Text above the order lookup widget', 'parcelpanel');
        __('e.g. Curious about where your package is? Click the button to track!', 'parcelpanel');
        __('Text below the order lookup widget', 'parcelpanel');
        __('e.g. Any questions or concerns? Please feel free to contact us!', 'parcelpanel');
        __('Tracking results', 'parcelpanel');
        __('Shipment display options', 'parcelpanel');
        __('Carrier name and logo', 'parcelpanel');
        __('Tracking number', 'parcelpanel');
        __('Product info', 'parcelpanel');
        __('Tracking details', 'parcelpanel');
        __('Google translate widget', 'parcelpanel');
        __('Map coordinates', 'parcelpanel');
        __('Show map on your tracking page', 'parcelpanel');
        __('Current location', 'parcelpanel');
        __('Destination address', 'parcelpanel');
        __('Hide keywords', 'parcelpanel');
        __('e.g. China,Aliexpress,Chinese cities. Separate with comma.', 'parcelpanel');
        __('Date and time format', 'parcelpanel');
        __('Date format', 'parcelpanel');
        __('Time format', 'parcelpanel');

        __('Theme language', 'parcelpanel');

        __('HOT', 'parcelpanel');
        __('Add custom status (up to 3) with time interval and description, to timely inform customers about the progress of their orders before you fulfill them. <0>Learn more<1>.', 'parcelpanel');
        __('Add custom shipment status', 'parcelpanel');
        __('Create additional steps to inform customers of your process prior to shipping', 'parcelpanel');
        __('Ordered', 'parcelpanel');
        __('Order ready', 'parcelpanel');
        __('In transit', 'parcelpanel');
        __('Out for delivery', 'parcelpanel');
        __('Delivered', 'parcelpanel');
        __('Custom tracking info', 'parcelpanel');
        __('Add one custom tracking info with time interval to reduce customer anxiety when the package was stuck in shipping, and it will be shown if the tracking info hasn\'t updated for the days you set.', 'parcelpanel');
        __('Day(s) since last tracking info', 'parcelpanel');
        __('eg. 7', 'parcelpanel');
        __('e.g. In Transit to Next Facility', 'parcelpanel');

        __('Set an estimated time period that will be displayed on your tracking page, to show your customers when they will receive their orders. <0>Learn more<1>.', 'parcelpanel');
        __('Enable this feature', 'parcelpanel');
        __('Calculate from', 'parcelpanel');
        __('Order created time', 'parcelpanel');
        __('Order shipped time', 'parcelpanel');
        __('Estimated delivery time: <0> - <1>(d)', 'parcelpanel');
        __('Advanced settings based on destinations', 'parcelpanel');
        __('Shipping to', 'parcelpanel');
        __('Add another', 'parcelpanel');

        __('Turn your tracking page into a marketing channel to make more sales.', 'parcelpanel');
        __('<0>Add categories<1> in your WordPress Products section to recommend. By default the recommendations are based on the customer’s order items, you can also select a category to recommend. <2>Learn more.<3>', 'parcelpanel');
        __('Display at/on the', 'parcelpanel');
        __('Top', 'parcelpanel');
        __('Right', 'parcelpanel');
        __('Bottom', 'parcelpanel');
        __('Advanced settings based on a category', 'parcelpanel');
        __('Select a category', 'parcelpanel');
        __('Category', 'parcelpanel');

        __('Here you can manually translate the tracking detailed info by yourself.', 'parcelpanel');
        __('Note: this feature distinguishes the strings based on Space, please don\'t forget the comma and period when you do the translation.', 'parcelpanel');
        __('Tracking info (before translation)', 'parcelpanel');
        __('Tracking info (after translation)', 'parcelpanel');
        __('e.g. Shanghai,CN', 'parcelpanel');
        __('Sorting Center', 'parcelpanel');

        __('CSS', 'parcelpanel');
        __('contact us', 'parcelpanel');
        __('View the CSS codes here you used to do the custom change of your tracking page, if you would like to change codes, please', 'parcelpanel');
        __('or follow <0>this instruction<1>.', 'parcelpanel');
        __('HTML top of page', 'parcelpanel');
        __('HTML bottom of page', 'parcelpanel');
        // trackPage ----------
    }

    private static function langNew_shipment()
    {
        // shipment ----------
        __('Shipments', 'parcelpanel');
        __('Columns', 'parcelpanel');
        __('Pagination', 'parcelpanel');
        __('Number of items per page:', 'parcelpanel');
        __('Screen Options', 'parcelpanel');

        __('All', 'parcelpanel');
        __('Import tracking number', 'parcelpanel');
        __('Filter order number or Tracking number', 'parcelpanel');
        __('Filter by date', 'parcelpanel');
        __('Courier', 'parcelpanel');
        __('Destination', 'parcelpanel');
        __('Showing <0> shipments', 'parcelpanel');
        __('of <0>', 'parcelpanel');
        __('Select sync time', 'parcelpanel');
        __('Re-sync', 'parcelpanel');
        __('Why are my orders on black Pending status? <0>Click here<1>', 'parcelpanel');
        __('No date yet', 'parcelpanel');
        __('No orders yet', 'parcelpanel');
        __('Try changing the filters or search term', 'parcelpanel');

        __('Order', 'parcelpanel');
        __('Tracking number', 'parcelpanel');
        __('Courier', 'parcelpanel');
        __('Last check point', 'parcelpanel');
        __('Transit', 'parcelpanel');
        __('Order date', 'parcelpanel');
        __('Shipment status', 'parcelpanel');

        __('Export shipments', 'parcelpanel');
        __('<0> shipments data will be exported a CSV table,', 'parcelpanel');
        __('Learn more about <0>CSV table headers<1>.', 'parcelpanel');
        __('Exporting:', 'parcelpanel');
        __('Please DO NOT close or refresh this page before it was completed.', 'parcelpanel');

        __('back', 'parcelpanel');
        __('Import tracking number', 'parcelpanel');
        __('Upload CSV file', 'parcelpanel');
        __('Column mapping', 'parcelpanel');
        __('Import', 'parcelpanel');
        __('Done!', 'parcelpanel');
        __('Select CSV file to import', 'parcelpanel');
        __('Your CSV file should have following columns:', 'parcelpanel');
        __('<0>Required:<1> Order number, Tracking number', 'parcelpanel');
        __('<0>Optional:<1> Courier, SKU, Qty, Date shipped, Mark order as Completed', 'parcelpanel');
        __('Note:', 'parcelpanel');
        __('Product sku and quantity are required if you need to ship the items in one order into multiple packages. <0>Learn more<1>.', 'parcelpanel');
        __('If the total quantity shipped exceeds the total quantity of items in an order, the system will automatically adjust to the maximum shippable quantity of the items.', 'parcelpanel');
        __('Choose a local file', 'parcelpanel');
        __('No file chosen', 'parcelpanel');
        __('Choose File', 'parcelpanel');
        __('Mapping preferences', 'parcelpanel');
        __('Enable this feature to save previous column mapping settings automatically', 'parcelpanel');
        __('CSV Delimiter', 'parcelpanel');
        __('This sets the separator of your CSV files to separates the data in your file into distinct fields', 'parcelpanel');
        __('Hide advanced options', 'parcelpanel');
        __('Show advanced options', 'parcelpanel');
        __('Continue', 'parcelpanel');
        __('Map CSV fields to orders', 'parcelpanel');
        __('Select fields from your CSV file to map against orders fields.', 'parcelpanel');
        __('Column name', 'parcelpanel');
        __('Map to field', 'parcelpanel');
        __('Import', 'parcelpanel');
        __('Select a column title', 'parcelpanel');
        __(' (*Required)', 'parcelpanel');
        __(' (Optional)', 'parcelpanel');
        __('Order number', 'parcelpanel');
        __('Tracking number', 'parcelpanel');
        __('Courier', 'parcelpanel');
        __('SKU', 'parcelpanel');
        __('Qty', 'parcelpanel');
        __('Date shipped', 'parcelpanel');
        __('Mark order as Completed', 'parcelpanel');
        __('Importing', 'parcelpanel');
        __('Your orders are now being imported…', 'parcelpanel');
        __('Uploading:', 'parcelpanel');
        __('Please DO NOT close or refresh this page before it was completed.', 'parcelpanel');
        __('Import Completed', 'parcelpanel');
        __('<0> tracking numbers imported successfully', 'parcelpanel');
        __('<0> tracking numbers imported failed,', 'parcelpanel');
        __('view details', 'parcelpanel');
        __('View shipments', 'parcelpanel');
        __('Upload again', 'parcelpanel');
        __('Import records', 'parcelpanel');
        __('View details', 'parcelpanel');
        __('Import file name:', 'parcelpanel');
        __('Total tracking numbers:', 'parcelpanel');
        __('Succeeded:', 'parcelpanel');
        __('Failed:', 'parcelpanel');
        __('Details:', 'parcelpanel');
        __('Close', 'parcelpanel');
        __('<0> Uploaded <1>, <2> tracking numbers, failed to upload <3>,', 'parcelpanel');

        __('Manually update status', 'parcelpanel');
        __('Save changes', 'parcelpanel');
        __('Updating the status for <0> shipment.', 'parcelpanel');
        __('Automatic shipment status update', 'parcelpanel');

        __('You can also copy or download a sample template to import tracking numbers.', 'parcelpanel');
        __('View instruction', 'parcelpanel');
        __('Import tracking number', 'parcelpanel');
        __('Step 1: Copy the <0>sample template<1> on Google Sheets (strongly recommend) or download <2>this CSV file<3>.', 'parcelpanel');
        __('Step 2: Fill in the data following the <0>Import Template Instructions<1>. Tracking number that do not comply with the instructions will not be imported.', 'parcelpanel');
        __('Step 3: Download the file in a CSV format and upload it.', 'parcelpanel');
        __('Import', 'parcelpanel');
        __('Close', 'parcelpanel');
        // shipment ----------
    }

    private static function langNew_setting()
    {
        // setting ----------
        __('Settings', 'parcelpanel');
        __('Delivery notifications', 'parcelpanel');
        __('Preview', 'parcelpanel');
        __('Add an order tracking section to WooCommerce email notifications', 'parcelpanel');
        __('Select order status to show the tracking section', 'parcelpanel');
        __('ParcelPanel shipping email notifications', 'parcelpanel');
        __('This feature will allow you to send email notifications based on ParcelPanel shipment status.', 'parcelpanel');
        __('Manage template', 'parcelpanel');
        __('Account page tracking', 'parcelpanel');
        __('Add a track button to orders history page (Actions column)', 'parcelpanel');
        __('This feature will allow you to add a track button to the orders history page (Actions column) so your customers can track their orders with one click there.', 'parcelpanel');
        __('View example', 'parcelpanel');
        __('Select order status to show the track button', 'parcelpanel');
        __('Configuring WooCommerce Orders', 'parcelpanel');
        __('Rename order status "Completed" to "Shipped"', 'parcelpanel');
        __('This feature will allow you to change the order status label name from "Completed" to "Shipped".', 'parcelpanel');
        __('Add a track button to WooCommerce orders (Actions column)', 'parcelpanel');
        __('This feature will allow you to add a shortcut button to WooCommerce orders (Actions column), which will make it easier to add tracking information.', 'parcelpanel');
        __('Dropshipping mode', 'parcelpanel');
        __('Enable dropshipping mode', 'parcelpanel');
        __('This feature will allow you to hide all Chinese origin to bring your customers an all-around brand shopping experience. <0>Learn more<1>.', 'parcelpanel');
        __('Courier matching', 'parcelpanel');
        __('Enable frequently-used couriers if you exactly know which couriers you are using. If not, please just leave this empty, the system will automatically recognize suitable couriers for you.', 'parcelpanel');
        __('Search couriers', 'parcelpanel');
        __('Processing', 'parcelpanel');
        __('Shipped', 'parcelpanel');
        __('Completed', 'parcelpanel');
        __('Partially Shipped', 'parcelpanel');
        __('Cancelled', 'parcelpanel');
        __('Refunded', 'parcelpanel');
        __('Failed', 'parcelpanel');
        __('Draft', 'parcelpanel');
        __('Save changes', 'parcelpanel');
        __('<0> selected', 'parcelpanel');
        __('Showing <0> of <1> couriers', 'parcelpanel');

        __('How it works?', 'parcelpanel');
        __('After enabled, ParcelPanel will add a track button to the orders history page (Actions column), so your customers can track their orders with one click directed to your store tracking page.', 'parcelpanel');
        __('After enabled, ParcelPanel will add a track button to orders admin (Actions column), you can add tracking info easily without clicking into order details.', 'parcelpanel');
        // setting ----------
    }

    private static function langNew_integration()
    {
        // integration ----------
        __('Integration', 'parcelpanel');
        __('Featured', 'parcelpanel');
        __('Dropshipping', 'parcelpanel');
        __('Custom order number', 'parcelpanel');
        __('Email customizer', 'parcelpanel');
        __('Will there be more plugins integrated?', 'parcelpanel');
        __('Yes, ParcelPanel will continue to integrate with more plugins to improve the user experience. Stay tuned!', 'parcelpanel');
        __('Is it possible to integrate a plugin that I using but is not on your list?', 'parcelpanel');
        __('Let us know which plugin you would like us to integrate with.', 'parcelpanel');
        __('Contact us', 'parcelpanel');
        __('You do not have to do anything, ParcelPanel works with the below plugins by default.  <0>Learn more<1>.', 'parcelpanel');
        __('You do not have to do anything, ParcelPanel works with the below plugins by default.', 'parcelpanel');
        __('API Key', 'parcelpanel');
        __('Developers can use the ParcelPanel API to effectively manage shipments by creating, viewing, and deleting tracking information. Learn <0>API doc<1> for details.', 'parcelpanel');
        __('Your API Key', 'parcelpanel');
        __('Show', 'parcelpanel');
        __('Hide', 'parcelpanel');
        __('Copy', 'parcelpanel');
        // integration ----------
    }

    private static function langNew_account()
    {
        // account ----------
        __('Account', 'parcelpanel');
        __('Account info', 'parcelpanel');
        __('Current plan', 'parcelpanel');
        __('Quota limit', 'parcelpanel');
        __('Next quota reset date', 'parcelpanel');
        __('Quota usage', 'parcelpanel');
        __('quota', 'parcelpanel');
        __('Avg $<0> per order', 'parcelpanel');
        __('month', 'parcelpanel');
        __('Choose', 'parcelpanel');
        __('Limited time offer specially for WooCommerce stores✨', 'parcelpanel');
        __('Unlimited', 'parcelpanel');
        __('Unlimited quota Avg $0.0000 per order', 'parcelpanel');
        __('Free Upgrade Now 🥳', 'parcelpanel');
        __('Does ParcelPanel charge based on order lookups?', 'parcelpanel');
        __('No, ParcelPanel counts the quota based on the number of your orders synced to ParcelPanel, and provides unlimited order lookups.', 'parcelpanel');
        __('Can I change my plan in the middle of a billing cycle?', 'parcelpanel');
        __('Yes, you can change your plan at any time based on your needs. If you want to change the plan, the remaining quotas of current plan will be added to the new one automatically.', 'parcelpanel');
        __('Why is ParcelPanel launching an unlimited plan? When will ParcelPanel charge?', 'parcelpanel');
        __('We\'ve provided service for over 120,000 Shopify and WooCommerce stores, but we are still pretty new to WooCommerce for now. We want to give more special offers for WooCommerce merchants.', 'parcelpanel');
        __('ParcelPanel will not charge for a short time. But please rest assured that if we decide to charge, we will definitely inform you in advance, and there will be no hidden charges.', 'parcelpanel');

        __('Downgrade to Starter Free plan', 'parcelpanel');
        __('This can\'t be undone!', 'parcelpanel');
        __('Downgrade', 'parcelpanel');
        // account ----------
    }

}
