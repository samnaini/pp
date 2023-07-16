<?php

namespace ParcelPanel\Action;

use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\Table;
use ParcelPanel\Models\TrackingItems;
use ParcelPanel\ParcelPanelFunction;

class AdminShipments
{
    use Singleton;

    function load_shipments_table()
    {
        global $wp_list_table;

        $wp_list_table = new \ParcelPanel\Action\AdminShipmentsTableList();

        if (isset($_REQUEST['resync'])) {
            check_admin_referer('bulk-pp-shipments');

            $sync = (int)$_REQUEST['sync'] ?? 0;

            if (in_array($sync, [1, 7, 30, 60, 90])) {
                $this->do_resync($sync);
            }

            // wp_redirect( remove_query_arg( [ 'resync', 'sync' ], wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) );
            wp_redirect('admin.php?page=pp-shipments');
            die;
        }

        if (!empty($_REQUEST['_wp_http_referer'])) {
            wp_redirect(remove_query_arg(['_wp_http_referer', '_wpnonce'], wp_unslash($_SERVER['REQUEST_URI'])));
            exit;
        }

        $wp_list_table->prepare_items();

        add_screen_option('per_page', ['default' => 10]);
    }

    static function get_shipment_counts(): array
    {
        global $wpdb;

        $shipment_statuses = (new ParcelPanelFunction)->parcelpanel_get_shipment_statuses();

        $select_count = [];
        foreach ($shipment_statuses as $item) {
            if (1 == $item['id']) {
                $select_count[] = $wpdb->prepare('COUNT(IF(ppti.shipment_status=%d OR ISNULL(tracking_number),TRUE,NULL))', $item['id']);
                continue;
            }
            $select_count[] = $wpdb->prepare('COUNT(IF(ppti.shipment_status=%d,TRUE,NULL))', $item['id']);
        }
        $select_count = implode(', ', $select_count);

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        // Retrieve data within 60 days
        $date_query = $wpdb->prepare('AND p.post_date >= %s', (new \WC_DateTime('-60 day midnight'))->format('Y-m-d H:i:s'));

        $SQL = <<<SQL
SELECT
{$select_count},
COUNT(*)
FROM (SELECT order_id,tracking_id,shipment_status FROM {$TABLE_TRACKING_ITEMS} GROUP BY order_id,tracking_id) AS ppti
LEFT JOIN {$wpdb->posts} AS p ON ppti.order_id=p.ID
INNER JOIN (SELECT post_id from {$wpdb->postmeta} where meta_key='_parcelpanel_sync_status' AND meta_value='1' group by post_id) AS pm ON p.ID=pm.post_id
LEFT JOIN {$TABLE_TRACKING} AS ppt on ppt.id=ppti.tracking_id
WHERE p.post_type = 'shop_order' AND p.post_status <> 'trash' AND p.post_status <> 'auto-draft' {$date_query}
SQL;

        $row = $wpdb->get_row(
            $SQL,
            ARRAY_N
        );

        $col = 0;
        $ss_count = [];
        foreach ($shipment_statuses as $item) {
            $count = (int)$row[$col++];
            if ($count > 0) {
                $ss_count[$item['id']] = $count;
            }
        }

        $total = (int)$row[$col];

        return [
            'all' => $total,
            'shipment_statuses' => $ss_count,
        ];
    }


    function export_csv_ajax()
    {
        global $wpdb;

        if (!current_user_can('manage_woocommerce')) {
            exit('You are not allowed');
        }

        if (!isset($_POST)) {
            die;
        }

        check_ajax_referer('pp-export-csv');

        if (empty($_REQUEST['filename'])) {
            die;
        }

        $page = absint($_REQUEST['step'] ?? 1) ?: 1;  // PHPCS: input var ok.
        $limit = 200;

        $headers = [
            'Order',
            'Status',
            'Tracking Number',
            'Courier',
            'Created at',
            'Fulfilled at',
            'Transit Time(day)',
            'Residence Time(day)',
            'Last Tracking Info',
            'Last Tracking Time',
            'Destination Country',
            'Customer Name',
            'Customer Email',
            'Customer Phone Number',
            'Product Info',
        ];

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;


        $query_where = "p.post_type = 'shop_order' AND p.post_status <> 'trash' AND p.post_status <> 'auto-draft'";

        if (!empty($_REQUEST['status'])) {
            $status_list = (array)absint($_REQUEST['status']);
            $p_status = [];

            foreach ($status_list as $status) {
                $p_status[] = $wpdb->prepare('ppti.shipment_status=%d', $status);
                if (1 == $status) {
                    $p_status[] = 'ISNULL(tracking_number)';
                }
            }

            $where_status = implode(' OR ', $p_status);

            if (!empty($where_status)) {
                $query_where .= " AND ({$where_status})";
            }
        }

        if (!empty($_REQUEST['courier'])) {
            $query_where .= $wpdb->prepare(' AND ppt.courier_code = %s', wc_clean($_REQUEST['courier']));
        }

        if (!empty($_REQUEST['country'])) {
            $query_where .= $wpdb->prepare(' AND ppt.original_country = %s', wc_clean($_REQUEST['country']));
        }

        if (!empty($_REQUEST['s'])) {
            $p_search = wc_clean(ltrim($_REQUEST['s'], '#'));
            $order_id = absint((new ParcelPanelFunction)->parcelpanel_get_formatted_order_id($p_search));
            $query_where .= $wpdb->prepare(' AND (p.ID = %d OR ppt.tracking_number = %s)', $order_id, $p_search);
        }

        $date_list = [1, 30, 60, 90];

        $date = (int)($_REQUEST['date'] ?? 60);
        $date = in_array($date, $date_list) ? $date : 60;

        $query_where .= $wpdb->prepare(' AND p.post_date >= %s', (new \WC_DateTime("-{$date} day midnight"))->format('Y-m-d H:i:s'));

        // if ( isset( $_REQUEST[ 'm' ] ) ) {
        //     $m    = wc_clean( wp_unslash( $_REQUEST[ 'm' ] ) );
        //     $year = substr( $m, 0, 4 );
        //
        //     if ( ! empty( $year ) ) {
        //         $month = '';
        //         $day   = '';
        //
        //         if ( strlen( $m ) > 5 ) {
        //             $month = substr( $m, 4, 2 );
        //         }
        //
        //         if ( strlen( $m ) > 7 ) {
        //             $day = substr( $m, 6, 2 );
        //         }
        //
        //         $datetime = new \WC_DateTime();
        //         $datetime->setDate( $year, 1, 1 );
        //
        //         if ( ! empty( $month ) ) {
        //             $datetime->setDate( $year, $month, 1 );
        //         }
        //
        //         if ( ! empty( $day ) ) {
        //             $datetime->setDate( $year, $month, $day );
        //         }
        //
        //         $next_month = clone $datetime;
        //         $next_month->modify( '+ 1 month' );
        //         // Make sure to not include next month first day
        //         $next_month->modify( '-1 day' );
        //
        //         $query_where .= $wpdb->prepare( ' AND p.post_date BETWEEN %s AND %s', [ $datetime->format( 'Y-m-d 00:00:00' ), $next_month->format( 'Y-m-d 23:59:59' ) ] );
        //     }
        // }


        $path = wp_upload_dir();
        $filename = sanitize_file_name(str_replace('.csv', '', wp_unslash($_REQUEST['filename'])) . '.csv');
        $savepath = trailingslashit($path['basedir']) . $filename;

        $countries = WC()->countries->get_countries();

        $shipment_statuses = (new ParcelPanelFunction)->parcelpanel_get_shipment_statuses();

        $offset = ($page - 1) * $limit;

        $SQL = <<<SQL
SELECT SQL_CALC_FOUND_ROWS p.ID,ppti.tracking_id,ppti.shipment_status,tracking_number,courier_code,last_event,destination_country,origin_info,destination_info,transit_time,stay_time,fulfilled_at
FROM {$TABLE_TRACKING_ITEMS} AS ppti
LEFT JOIN {$wpdb->posts} AS p ON ppti.order_id = p.ID
INNER JOIN (SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key='_parcelpanel_sync_status' AND meta_value='1') AS pm ON p.ID = pm.post_id
LEFT JOIN {$TABLE_TRACKING} ppt ON ppt.id = ppti.tracking_id
WHERE {$query_where}
GROUP BY ppti.tracking_id,ppti.order_id
ORDER BY p.post_date DESC, p.ID DESC
LIMIT {$offset},{$limit}
SQL;

        $res = (array)$wpdb->get_results($SQL);

        $total_rows = (int)$wpdb->get_var('SELECT FOUND_ROWS()');

        $exported_row_count = 0;

        $csv_data = [];

        $orders = [];

        $items = [];

        if (!empty($res)) {

            $query = new \WC_Order_Query([
                'post__in' => array_column($res, 'ID'),
                'limit' => $limit,
            ]);

            $_orders = $query->get_orders();

            foreach ($_orders as $order) {
                $orders[$order->get_id()] = $order;
            }
        }

        foreach ($res as $item) {

            /* @var \WC_Order $order */

            $order = $orders[$item->ID] ?? null;

            if (empty($order)) {
                continue;
            }

            $transit_time = $item->transit_time;
            $residence_time = 0;

            $order_number = "#{$order->get_order_number()}";

            $shipment_status_label = (new ParcelPanelFunction)->parcelpanel_get_shipment_status($item->shipment_status) ?: 'pending';
            $shipment_status_text = $shipment_statuses[$shipment_status_label]['text'] ?? '';

            $courier_code = $item->courier_code;
            $courier_info = (new ParcelPanelFunction)->parcelpanel_get_courier_info($item->courier_code);
            $courier_name = $courier_info->name ?? $courier_code;

            $created_at = $order->get_date_created()->date_i18n(\DateTimeInterface::ATOM);
            $fulfilled_at = $item->fulfilled_at ? date_i18n(\DateTimeInterface::ATOM, $item->fulfilled_at) : '';

            $origin_info = json_decode($item->origin_info, 1);
            $destination_info = json_decode($item->destination_info, 1);

            $trackinfo = array_merge($origin_info['trackinfo'] ?? [], $destination_info['trackinfo'] ?? []);
            $trackinfo = array_map(array(new ParcelPanelFunction, 'parcelpanel_trackinfo_date_to_time'), $trackinfo);

            // 按时间排序
            !empty($trackinfo) && usort($trackinfo, array(new ParcelPanelFunction, 'parcelpanel_cmp_trackinfo_time'));

            $latest_trackinfo = $trackinfo[0] ?? [];
            $first_trackinfo = end($trackinfo);

            if (!empty($trackinfo)) {

                $time_1 = $latest_trackinfo['time'] ?? 0;
                $time_2 = $first_trackinfo['time'] ?? 0;

                if (4 != $item->shipment_status) {

                    $residence_time = ceil((time() - $time_1) / 86400);

                    $time_1 = time();
                }

                if ($time_2 < $time_1) {
                    $transit_time = ceil(($time_1 - $time_2) / 86400);
                }
            }

            // 目的国
            $dst_country_code = $item->destination_country;
            $destination_country = $countries[$dst_country_code] ?? $dst_country_code;

            // $sku_list        = [];
            $prod_title_list = [];

            foreach ($order->get_items() as $wc_order_item_product) {

                if (is_callable($wc_order_item_product, 'get_product')) {
                    $product = $wc_order_item_product->get_product();
                } else {
                    $product = $order->get_product_from_item($wc_order_item_product);
                }

                if (empty($product)) {
                    continue;
                }

                // $sku_list[] = $product->get_sku();

                $prod_title_list[] = $product->get_title();
            }

            $csv_data[] = [
                "{$order_number}\t",
                $shipment_status_text,
                "{$item->tracking_number}\t",
                $courier_name,
                $created_at,
                $fulfilled_at,
                $transit_time,
                $residence_time,
                    $latest_trackinfo['tracking_detail'] ?? '',
                    $latest_trackinfo['checkpoint_date'] ?? '',
                $destination_country,
                $order->get_formatted_billing_full_name(),
                $order->get_billing_email(),
                "{$order->get_billing_phone()}\t",
                implode(' ; ', $prod_title_list),
            ];

            ++$exported_row_count;
        }

        $download_link = trailingslashit($path['baseurl']) . $filename;

        $f = fopen($savepath, 'a+');
        if (1 == $page) {

            fputcsv($f, $headers);

            $object = [
                'post_title' => basename($savepath),
                'post_content' => $download_link,
                'post_mime_type' => 'text/csv',
                'guid' => $download_link,
                'context' => 'export',
                'post_status' => 'private',
            ];

            // Save the data.
            $id = wp_insert_attachment($object, $filename);

            /*
             * Schedule a cleanup for one day from now in case of failed
             * import or missing wp_import_cleanup() call.
             */
            wp_schedule_single_event(time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', [$id]);
        }
        foreach ($csv_data as $datum) {
            fputcsv($f, $datum);
        }
        fclose($f);


        $total_exported = (($page - 1) * $limit) + $exported_row_count;

        $percent_complete = $total_rows ? floor(($total_exported / $total_rows) * 100) : 100;


        (new ParcelPanelFunction)->parcelpanel_json_response([
            'step' => ++$page,
            'percentage' => $percent_complete,
            'download_link' => $download_link,
        ]);
    }

    function resync_ajax()
    {
        check_ajax_referer('pp-resync');

        $sync = (int)$_REQUEST['sync'] ?? 0;

        if (in_array($sync, [1, 7, 30, 60, 90])) {

            $this->do_resync($sync);

            (new ParcelPanelFunction)->parcelpanel_json_response([], 'The system is syncing your orders and it needs a few minutes.');
        }

        (new ParcelPanelFunction)->parcelpanel_json_response([], 'Sync failed, please try again later.', false);
    }

    private function do_resync($sync_day)
    {
        do_action('parcelpanel_order_sync', "{$sync_day}d");
    }


    function check_first_sync_ajax()
    {
        check_ajax_referer('pp-check-first-sync');

        $first_synced_at = intval(get_option(\ParcelPanel\OptionName\FIRST_SYNCED_AT));

        (new ParcelPanelFunction)->parcelpanel_json_response([
            'first_synced_at' => $first_synced_at,
        ]);
    }


    public function set_custom_shipment_status_ajax()
    {
        check_ajax_referer('pp-ajax');

        $post_data = (new ParcelPanelFunction)->parcelpanel_get_post_data();

        $shipments = (array)($post_data['shipments'] ?? []);
        $update_status = $post_data['update_status'] ?? '';

        $res = $this->handle_change_shipment_status($shipments, $update_status);

        if (is_wp_error($res)) {
            (new ParcelPanelFunction)->parcelpanel_json_response([], $res->get_error_message(), false);
        }
        (new ParcelPanelFunction)->parcelpanel_json_response(['updated_orders' => $res]);
    }

    private function handle_change_shipment_status($shipments, $update_status)
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        $custom_shipment_status = absint($update_status);

        $custom_statuses = (new ParcelPanelFunction)->parcelpanel_get_custom_status();


        if ($update_status != 'auto') {
            // 手动调整状态，检测状态是否合法
            $is_valid_status = false;
            foreach ($custom_statuses as $item) {
                if ($item['status'] === $custom_shipment_status) {
                    $is_valid_status = true;
                    break;
                }
            }
            if (!$is_valid_status) {
                return new \WP_Error('save_error', __('Save failed', 'parcelpanel'));
            }
        }

        $WHERE_BLOCK_ORDER_ID_TRACKING_ID = "OR (ppti.order_id=%d AND ppti.tracking_id=%d)";
        $where_args_order_id_and_tracking_id = [];
        $data_count = 0;

        $tracking_ids = [];

        $dataset = [];

        foreach ($shipments as $shipment) {
            parse_str((string)$shipment, $array);
            $order_id = absint($array['oid'] ?? 0);
            $tracking_id = absint($array['tid'] ?? 0);
            if (empty($order_id)) {
                continue;
            }

            $tracking_ids[] = $tracking_id;

            $where_args_order_id_and_tracking_id[] = $order_id;
            $where_args_order_id_and_tracking_id[] = $tracking_id;
            $dataset[] = [
                $order_id,
                $tracking_id,
            ];
            ++$data_count;
        }

        if (!$data_count) {
            return new \WP_Error('no_data', __('Nothing to do', 'parcelpanel'));
        }

        $updated_orders = [];
        $order_tracking_ids = [];

        $WHERE_ORDER_ID_AND_TRACKING_ID = substr(str_repeat($WHERE_BLOCK_ORDER_ID_TRACKING_ID, $data_count), 2);

        if ($update_status == 'auto') {

            // Retrieve tracking data
            $placeholder = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_ids, '%d');
            $SQL_RETRIEVE_TRACKING_DATA = <<<SQL
SELECT id,shipment_status
FROM {$TABLE_TRACKING}
WHERE id IN ({$placeholder})
SQL;
            $tracking_data = $wpdb->get_results($wpdb->prepare($SQL_RETRIEVE_TRACKING_DATA, $tracking_ids));
            $tracking_data_by_id = array_column($tracking_data, null, 'id');

            $shipment_items = $this->retrieve_shipment_items_by_order_id_and_tracking_id($dataset, ['order_id', 'tracking_id', 'shipment_status']);

            $SQL_UPDATE_ITEM_STATUS = <<<SQL
UPDATE
{$TABLE_TRACKING_ITEMS} AS ppti
SET ppti.shipment_status=%d,ppti.custom_status_time="{}",ppti.custom_shipment_status=0
WHERE ppti.order_id=%d AND ppti.tracking_id=%d
SQL;

            foreach ($shipment_items as $item) {
                $tracking_id = $item->tracking_id;
                $order_id = $item->order_id;

                $tracking_status = 1;
                if (!empty($tracking_data_by_id[$tracking_id])) {
                    $tracking_status = $tracking_data_by_id[$tracking_id]->shipment_status;
                }

                if (!array_key_exists($order_id, $order_tracking_ids)) {
                    $order_tracking_ids[$order_id] = [];
                }
                $order_tracking_ids[$order_id][] = $tracking_id;

                // Update shipment data
                $wpdb->query($wpdb->prepare($SQL_UPDATE_ITEM_STATUS, $tracking_status, $order_id, $tracking_id));

                if ($tracking_status != $item->shipment_status) {
                    // The status of shipment has changed
                    if (!array_key_exists($order_id, $updated_orders)) {
                        $updated_orders[$order_id] = $tracking_status;
                    }
                }
            }

            // Send email notification
            $updated_orders_param_arr = [];
            foreach ($updated_orders as $order_id => $status) {
                $updated_orders_param_arr[] = [
                    'order_id' => $order_id,
                    'status' => $status,
                    'tracking_ids' => $order_tracking_ids[$order_id],
                ];
            }
            return $updated_orders_param_arr;
        }


        $now = time();

        $status_arr = [1, 2, 3, 4, 5, 6, 7, 8];

        // 1001: Ordered, 1002-1004: Custom status, 1100: Order ready
        $status_sorted = [1002, 1003, 1004, 1100, 2, 3, 7, 6, 4];
        $status_sorted_used = [];

        $index = array_search($custom_shipment_status, $status_sorted);
        if ($index !== false) {
            if ($custom_shipment_status == 6) {
                // remove exception status, when failed attempt status is selected
                unset($status_sorted[6]);
                --$index;
            } elseif ($custom_shipment_status == 7) {
                // remove failed attempt status, when exception status is selected
                unset($status_sorted[7]);
            }
            // Used status
            $status_sorted_used = array_slice($status_sorted, 0, $index + 1);
        }

        $shipment_status = 1;
        if (in_array($custom_shipment_status, $status_arr)) {
            $shipment_status = $custom_shipment_status;
        }

        $shipment_items = $this->retrieve_shipment_items_by_order_id_and_tracking_id($dataset, ['id', 'order_id', 'tracking_id', 'shipment_status', 'custom_shipment_status', 'custom_status_time']);

        $SQL_UPDATE_ITEM_STATUS_TIME = <<<SQL
UPDATE
{$TABLE_TRACKING_ITEMS} AS ppti
SET ppti.custom_status_time=%s
WHERE ppti.order_id=%d AND ppti.tracking_id=%d
SQL;

        foreach ($shipment_items as $item) {

            if (!is_object($item->custom_status_time)) {
                $item->custom_status_time = new \stdClass();
            }

            $item->custom_status_time->$custom_shipment_status = $now;
            $custom_status_time_kept = new \stdClass();

            foreach ($status_sorted_used as $status) {
                // Keep some status
                if (!empty($item->custom_status_time->$status)) {
                    $custom_status_time_kept->$status = $item->custom_status_time->$status;
                }
            }

            $wpdb->query($wpdb->prepare($SQL_UPDATE_ITEM_STATUS_TIME
                , [json_encode($custom_status_time_kept), $item->order_id, $item->tracking_id]));

            if ($item->shipment_status !== $custom_shipment_status) {
                // The status of shipment has changed
                if (!array_key_exists($item->order_id, $updated_orders)) {
                    $updated_orders[$item->order_id] = $custom_shipment_status;
                }
            }

            if (!array_key_exists($item->order_id, $order_tracking_ids)) {
                $order_tracking_ids[$item->order_id] = [];
            }
            $order_tracking_ids[$item->order_id][] = $item->tracking_id;
        }

        // Batch update shipment status
        $SQL_UPDATE_ALL_ITEM_STATUS = <<<SQL
UPDATE {$TABLE_TRACKING_ITEMS} AS ppti
SET shipment_status=%d, custom_shipment_status=%d
WHERE {$WHERE_ORDER_ID_AND_TRACKING_ID}
SQL;
        $wpdb->query($wpdb->prepare($SQL_UPDATE_ALL_ITEM_STATUS, array_merge([$shipment_status, $custom_shipment_status], $where_args_order_id_and_tracking_id)));


        // Send email notification
        $updated_orders_param_arr = [];
        foreach ($updated_orders as $order_id => $status) {
            $updated_orders_param_arr[] = [
                'order_id' => $order_id,
                'status' => $status,
                'tracking_ids' => $order_tracking_ids[$order_id],
            ];
        }
        return $updated_orders_param_arr;
    }

    /**
     * @param array $dataset [order_id, tracking_id][]
     */
    public function retrieve_shipment_items_by_order_id_and_tracking_id(array $dataset, $fields = ['id']): array
    {
        global $wpdb;

        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        $unit_count = 0;
        $where_args = [];
        foreach ($dataset as $datum) {
            // Two parameters need
            if (count($datum) != 2) {
                continue;
            }
            foreach ($datum as $arg) {
                $where_args[] = $arg;
            }
            ++$unit_count;
        }

        if (!$unit_count) {
            return [];
        }

        $WHERE_BLOCK_ORDER_ID_TRACKING_ID = "OR (ppti.order_id=%d AND ppti.tracking_id=%d)";
        $WHERE_ORDER_ID_AND_TRACKING_ID = substr(str_repeat($WHERE_BLOCK_ORDER_ID_TRACKING_ID, $unit_count), 2);

        $allowed_fields = ['id', 'order_id', 'order_item_id', 'quantity', 'tracking_id', 'shipment_status', 'custom_status_time', 'custom_shipment_status'];

        $fields_str = join(',', array_intersect($allowed_fields, $fields));
        if (empty($fields_str)) {
            return [];
        }

        $SQL = <<<SQL
SELECT {$fields_str}
FROM {$TABLE_TRACKING_ITEMS} AS ppti
WHERE {$WHERE_ORDER_ID_AND_TRACKING_ID}
GROUP BY ppti.order_id,ppti.tracking_id
SQL;
        $shipment_items = $wpdb->get_results($wpdb->prepare($SQL, $where_args));

        TrackingItems::format_result_data($shipment_items);

        return (array)$shipment_items;
    }

    /**
     * @param array $data {order_id: {status, tracking_ids}}
     */
    private function send_email_notification_by_order_id(array $data)
    {
        if (empty($data)) {
            return;
        }
        \WC()->mailer();
        foreach ($data as $order_id => $datum) {
            $delivery_status = (new ParcelPanelFunction)->parcelpanel_get_shipment_status($datum['status']);
            do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, $datum['tracking_ids']);
        }
    }

    public function updated_orders_send_email_ajax()
    {
        check_ajax_referer('pp-ajax');

        $post_data = (new ParcelPanelFunction)->parcelpanel_get_post_data();

        $updated_orders_arr = (array)$post_data['updated_orders'];

        $updated_orders = [];

        foreach ($updated_orders_arr as $value) {
            if (empty($value['order_id']) || empty($value['status']) || empty($value['tracking_ids'])) {
                continue;
            }

            $updated_orders[$value['order_id']] = $value;
        }

        if (empty($updated_orders)) die;

        $this->send_email_notification_by_order_id($updated_orders);

        die;
    }
}
