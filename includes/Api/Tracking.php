<?php

namespace ParcelPanel\Api;

use ParcelPanel\Libs\HooksTracker;
use ParcelPanel\Libs\Singleton;
use ParcelPanel\Models\Table;
use ParcelPanel\Models\TrackingItems;
use ParcelPanel\ParcelPanelFunction;

class Tracking
{
    use Singleton;

    /**
     * 获取所有单号
     */
    function get_trackings(\WP_REST_Request $request)
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;

        $page = absint($request['page']) ?: 1;
        $limit = absint($request['limit']) ?: 200;
        $sort_id = 'ASC' == $request['sort_id'] ? 'ASC' : 'DESC';

        $offset = ($page - 1) * $limit;

        $where = '';

        if (isset($request['is_synced'])) {
            $is_synced = wc_string_to_bool($request['is_synced']);
            if ($is_synced) {
                $where .= ' AND sync_times = -1';
            } else {
                $where .= ' AND sync_times > -1';
            }
        }

        $SQL = <<<SQL
SELECT SQL_CALC_FOUND_ROWS * FROM {$TABLE_TRACKING} AS ppt
WHERE 1=1 {$where}
ORDER BY ppt.id {$sort_id}
LIMIT {$offset},{$limit}
SQL;

        $trackings = $wpdb->get_results($wpdb->prepare($SQL));
        $total_rows = (int)$wpdb->get_var('SELECT FOUND_ROWS()');

        $this->retrieve_tracking_items($trackings);

        foreach ($trackings as $tracking) {
            $tracking->fulfilled_at = $tracking->fulfilled_at ? date_i18n(\DateTimeInterface::ATOM, $tracking->fulfilled_at) : '';
        }

        return ['total' => $total_rows, 'per_page' => $limit, 'trackings' => $trackings];
    }

    /**
     * 单号更新 webhook
     */
    function update(\WP_REST_Request $request)
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        // 异常单号
        $errors = [];

        // 当前时间
        $NOW_TIME = time();

        // 运输状态
        $shipment_statuses = (new ParcelPanelFunction)->parcelpanel_get_shipment_statuses();

        // 状态映射
        $DELIVERY_STATUS_MAP = [
            'InfoReceived' => 'info_received',
        ];

        $NOTFOUND_SUBSTATUS_MAP = [
            'notfound001' => 'info_received',
            'notfound002' => 'pending',
        ];

        // SQL for updating tracking info
        $SQL_UPDATE_TRACKING = <<<SQL
UPDATE `{$TABLE_TRACKING}`
SET `courier_code` = %s
, `shipment_status` = %d
, `last_event` = %s
, `original_country` = %s
, `destination_country` = %s
, `origin_info` = %s
, `destination_info` = %s
, `received_times` = `received_times` + 1
, `transit_time` = %d
, `stay_time` = %d
, `updated_at` = %d
WHERE `id` = %d
SQL;
        $SQL_UPDATE_SHIPMENT = <<<SQL
UPDATE {$TABLE_TRACKING_ITEMS} AS ppti
SET ppti.shipment_status = %d
WHERE ppti.tracking_id=%d
SQL;


        // 物流信息
        $tracks = (array)($request['data'] ?? []);

        // 兼容单身数据
        if (!isset($tracks[0])) {
            $tracks = [$tracks];
        }


        // 过滤输入
        $tracking_numbers = array_filter(array_column($tracks, 'tracking_number'));
        if (empty($tracking_numbers)) {
            return rest_ensure_response(['code' => RestApi::CODE_BAD_REQUEST]);
        }

        // 生成 SQL 占位符
        $placeholder = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_numbers);
        $RETRIEVE_TRACKING_SQL = <<<SQL
SELECT
ppt.id,ppti.order_id,ppt.tracking_number,ppt.shipment_status AS tracking_status,ppti.shipment_status,ppti.custom_shipment_status,ppti.order_item_id,ppti.quantity
FROM {$TABLE_TRACKING} AS ppt
LEFT JOIN {$TABLE_TRACKING_ITEMS} AS ppti ON ppt.id=ppti.tracking_id
WHERE tracking_number IN ({$placeholder})
SQL;
        $tracking_items = (array)$wpdb->get_results($wpdb->prepare($RETRIEVE_TRACKING_SQL, $tracking_numbers));

        // 缓存数据
        $db_cache = array_column($tracking_items, null, 'tracking_number');


        // 2天前
        $before_two_day = strtotime('-2 day midnight');


        // 开启事务
        wc_transaction_query();

        // 处理单号更新数据
        foreach ($tracks as $track) {

            $tracking_number = wc_clean($track['tracking_number'] ?? '');
            $courier_code = wc_clean($track['courier_code'] ?? '');
            $delivery_status = wc_clean($track['delivery_status'] ?? '');
            $substatus = wc_clean($track['sub_status'] ?? '');
            $destination_country = wc_clean($track['destination'] ?? '');
            $original_country = wc_clean($track['original'] ?? '');
            $origin_info = $track['origin_info'] ?? [];
            $destination_info = $track['destination_info'] ?? [];
            $last_event = wc_clean($track['latest_event'] ?? '');
            $transit_time = (int)($track['transit_time'] ?? 0);
            $stay_time = (int)($track['stay_time'] ?? 0);
            // $updated_at          = (array)( $track[ 'updated_at' ] ?? [] );


            // 状态转换
            $delivery_status = $DELIVERY_STATUS_MAP[$delivery_status] ?? $delivery_status;

            if (isset($NOTFOUND_SUBSTATUS_MAP[$substatus])) {
                $delivery_status = $NOTFOUND_SUBSTATUS_MAP[$substatus];
            }

            $tracking_status = $shipment_statuses[$delivery_status]['id'] ?? 1;

            // 取数据缓存
            $key = $tracking_number;

            if (!array_key_exists($key, $db_cache)) {
                // 查无此单
                $errors[] = $tracking_number;
                continue;
            }

            $tracking_item = $db_cache[$key];
            $tracking_id = $tracking_item->id ?? 0;
            $order_id = $tracking_item->order_id ?? 0;
            $previous_status = $tracking_item->shipment_status ?? 1;
            $custom_shipment_status = $tracking_item->custom_shipment_status ?? 0;
            $shipment_status = empty($custom_shipment_status) ? $tracking_status : $previous_status;

            $origin_info_str = json_encode($origin_info, 320);
            $destination_info_str = json_encode($destination_info, 320);

            try {

                if (empty($custom_shipment_status)) {
                    // automatic update shipment status
                    $wpdb->query($wpdb->prepare($SQL_UPDATE_SHIPMENT, [
                        $tracking_status,  // shipment_status
                        $tracking_id,  // id
                    ]));
                }

                // 更改SQL语句
                $res = $wpdb->query($wpdb->prepare($SQL_UPDATE_TRACKING, [
                    $courier_code,  // `courier_code`
                    $tracking_status,  // `shipment_status`
                    $last_event,  // `last_event`
                    $original_country,  // `original_country`
                    $destination_country,  // `destination_country`
                    $origin_info_str,  // `origin_info`
                    $destination_info_str,  // `destination_info`
                    $transit_time,  // `transit_time`
                    $stay_time,  // `stay_time`
                    $NOW_TIME,  // `updated_at`
                    $tracking_id,  // `id`
                ]));

                if (false === $res) {

                    $errors[] = $tracking_number;

                } else {

                    if (empty($custom_shipment_status) && $previous_status != $shipment_status) {

                        if (4 == $shipment_status) {
                            /* 已到达状态 */

                            $org_trackinfo = (array)$origin_info['trackinfo'] ?? [];
                            $dst_trackinfo = (array)$destination_info['trackinfo'] ?? [];
                            $trackinfo = array_merge($org_trackinfo, $dst_trackinfo);

                            foreach ($trackinfo as $item) {

                                $checkpoint_delivery_status = $item['checkpoint_delivery_status'] ?? '';

                                if ('delivered' == $checkpoint_delivery_status) {

                                    $checkpoint_time = strtotime($item['checkpoint_date'] ?? '') ?: 0;

                                    if ($before_two_day <= $checkpoint_time) {
                                        /* 在时效内 */

                                        // 发送邮件
                                        do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [$tracking_id]);
                                    }

                                    break;
                                }
                            }
                        } else {

                            // 发送邮件
                            do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [$tracking_id]);
                        }
                    }
                }
            } catch (\Exception $e) {

                $errors[] = $tracking_number;
            }
        }

        // 提交事务
        wc_transaction_query('commit');

        $resp_data = [
            'code' => RestApi::CODE_SUCCESS,
            'errors' => $errors,
        ];

        return rest_ensure_response($resp_data);
    }

    function updateNew(\WP_REST_Request $request)
    {
        global $wpdb;

        $TABLE_TRACKING = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        // 异常单号
        $errors = [];

        // 当前时间
        $NOW_TIME = time();

        // 运输状态
        $shipment_statuses = (new ParcelPanelFunction)->parcelpanel_get_shipment_statuses();

        // 状态映射
        $DELIVERY_STATUS_MAP = [
            'InfoReceived' => 'info_received',
        ];

        $NOTFOUND_SUBSTATUS_MAP = [
            'notfound001' => 'info_received',
            'notfound002' => 'pending',
        ];

        // SQL for updating tracking info
        $SQL_UPDATE_TRACKING = <<<SQL
UPDATE `{$TABLE_TRACKING}`
SET `courier_code` = %s
, `shipment_status` = %d
, `last_event` = %s
, `original_country` = %s
, `destination_country` = %s
, `origin_info` = %s
, `destination_info` = %s
, `received_times` = `received_times` + 1
, `transit_time` = %d
, `stay_time` = %d
, `updated_at` = %d
WHERE `id` = %d
SQL;
        $SQL_UPDATE_SHIPMENT = <<<SQL
UPDATE {$TABLE_TRACKING_ITEMS} AS ppti
SET ppti.shipment_status = %d
WHERE ppti.tracking_id=%d
SQL;


        // 物流信息
        $tracks = (array)($request['data'] ?? []);

        // 兼容单身数据
        if (!isset($tracks[0])) {
            $tracks = [$tracks];
        }


        // 过滤输入
        $tracking_numbers = array_filter(array_column($tracks, 'tracking_number'));
        if (empty($tracking_numbers)) {
            // return rest_ensure_response(['code' => RestApi::CODE_BAD_REQUEST]);
        }

        // 生成 SQL 占位符
        $placeholder = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($tracking_numbers);
        $RETRIEVE_TRACKING_SQL = <<<SQL
SELECT
ppt.id,ppti.order_id,ppt.tracking_number,ppt.shipment_status AS tracking_status,ppti.shipment_status,ppti.custom_shipment_status,ppti.order_item_id,ppti.quantity
FROM {$TABLE_TRACKING} AS ppt
LEFT JOIN {$TABLE_TRACKING_ITEMS} AS ppti ON ppt.id=ppti.tracking_id
WHERE tracking_number IN ({$placeholder})
SQL;
        $tracking_items = (array)$wpdb->get_results($wpdb->prepare($RETRIEVE_TRACKING_SQL, $tracking_numbers));

        // 缓存数据
        $db_cache = array_column($tracking_items, null, 'tracking_number');


        // 2天前
        $before_two_day = strtotime('-2 day midnight');

        $status_arr = [1, 2, 3, 4, 5, 6, 7, 8];

        // 开启事务
        wc_transaction_query();

        // 处理单号更新数据
        foreach ($tracks as $track) {

            $order_id = wc_clean($track['order_id'] ?? '');
            $tracking_number = wc_clean($track['tracking_number'] ?? '');
            $courier_code = wc_clean($track['courier_code'] ?? '');
            $delivery_status = wc_clean($track['delivery_status'] ?? '');
            // $substatus           = wc_clean( $track[ 'sub_status' ] ?? '' );
            // $destination_country = wc_clean($track['destination'] ?? '');
            $destination_country = wc_clean($track['destination'] ?? '');
            $original_country = wc_clean($track['original'] ?? '');
            $origin_info = $track['origin_info'] ?? [];
            $destination_info = $track['destination_info'] ?? [];
            $last_event = wc_clean($track['latest_event'] ?? '');
            $transit_time = (int)($track['transit_time'] ?? 0);
            $stay_time = (int)($track['stay_time'] ?? 0);
            // $updated_at          = (array)( $track[ 'updated_at' ] ?? [] );
            $custom_track_status = $track['custom_track_status'] ?? 0;
            $custom_track_time = $track['custom_track_time'] ?? [];
            if (!empty($custom_track_time) && !empty($custom_track_status)) {
                $custom_track_time = is_array($custom_track_time) ? $custom_track_time : json_decode($custom_track_time, true);
            }


            // 状态转换
            $tracking_status = $shipment_statuses[$delivery_status]['id'] ?? 1;

            // 取数据缓存
            $key = $tracking_number;

            if (empty($tracking_number) && !empty($order_id)) {
                $shipment_status = 1;
                if (in_array($custom_track_status, $status_arr)) {
                    $shipment_status = $custom_track_status;
                }

                $SQL_RETRIEVE_SHIPMENTS_BY_ORDER_ID = <<<SQL
SELECT *
FROM {$TABLE_TRACKING_ITEMS}
WHERE order_id=%d
SQL;
                $_shipments = $wpdb->get_results($wpdb->prepare(
                    $SQL_RETRIEVE_SHIPMENTS_BY_ORDER_ID,
                    $order_id
                ));
                if (empty($_shipments)) {
                    // 不存在，初始化 item
                    $wpdb->insert($TABLE_TRACKING_ITEMS, ['order_id' => $order_id]);
                }

                // Batch update shipment status
                $SQL_UPDATE_ALL_ITEM_STATUS = <<<SQL
UPDATE {$TABLE_TRACKING_ITEMS} AS ppti
SET shipment_status=%d, custom_shipment_status=%d
WHERE order_id=%d AND tracking_id=%d
SQL;
                $wpdb->query($wpdb->prepare($SQL_UPDATE_ALL_ITEM_STATUS, [$shipment_status, $custom_track_status, $order_id, 0]));

                $delivery_status = (new ParcelPanelFunction)->parcelpanel_get_shipment_status($shipment_status);
                do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [0]);

                continue;
            }

            if (!array_key_exists($key, $db_cache)) {
                // 查无此单
                $errors[] = $tracking_number;
                continue;
            }

            $tracking_item = $db_cache[$key];
            $tracking_id = $tracking_item->id ?? 0;
            $order_id = $tracking_item->order_id ?? 0;
            $previous_status = $tracking_item->shipment_status ?? 1;
            $custom_shipment_status = $tracking_item->custom_shipment_status ?? 0;
            $shipment_status = empty($custom_shipment_status) ? $tracking_status : $previous_status;

            $origin_info_str = json_encode($origin_info, 320);
            $destination_info_str = json_encode($destination_info, 320);

            try {
                if (empty($custom_shipment_status)) {
                    // automatic update shipment status
                    $wpdb->query($wpdb->prepare($SQL_UPDATE_SHIPMENT, [
                        $tracking_status,  // shipment_status
                        $tracking_id,  // id
                    ]));
                }

                // 更改SQL语句
                $res = $wpdb->query($wpdb->prepare($SQL_UPDATE_TRACKING, [
                    $courier_code,  // `courier_code`
                    $tracking_status,  // `shipment_status`
                    $last_event,  // `last_event`
                    $original_country,  // `original_country`
                    $destination_country,  // `destination_country`
                    $origin_info_str,  // `origin_info`
                    $destination_info_str,  // `destination_info`
                    $transit_time,  // `transit_time`
                    $stay_time,  // `stay_time`
                    $NOW_TIME,  // `updated_at`
                    $tracking_id,  // `id`
                ]));
            } catch (\Exception $e) {
                $errors[] = $tracking_number;

            }

            // 提交事务
            wc_transaction_query('commit');

            try {
                // email send
                if (false === $res) {

                    $errors[] = $tracking_number;

                } else {

                    if (!empty($custom_track_status)) {
                        // 自定义状态发送邮件

                        if (!empty($custom_track_time['4'])) {
                            $checkpoint_time = $custom_track_time['4'];
                            if ($before_two_day <= $checkpoint_time) {
                                /* 在时效内 */

                                // 发送邮件
                                do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [$tracking_id]);
                            }
                        } else {
                            // 发送邮件
                            do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [$tracking_id]);
                        }

                    } else {
                        if (empty($custom_shipment_status) && $previous_status != $shipment_status) {

                            if (4 == $shipment_status) {
                                /* 已到达状态 */

                                $org_trackinfo = (array)$origin_info['trackinfo'] ?? [];
                                $dst_trackinfo = (array)$destination_info['trackinfo'] ?? [];
                                $trackinfo = array_merge($org_trackinfo, $dst_trackinfo);

                                foreach ($trackinfo as $item) {

                                    $checkpoint_delivery_status = $item['checkpoint_delivery_status'] ?? '';

                                    if ('delivered' == $checkpoint_delivery_status) {

                                        $checkpoint_time = strtotime($item['checkpoint_date'] ?? '') ?: 0;

                                        if ($before_two_day <= $checkpoint_time) {
                                            /* 在时效内 */

                                            // 发送邮件
                                            do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [$tracking_id]);
                                        }

                                        break;
                                    }
                                }

                            } else {

                                // 发送邮件
                                do_action("parcelpanel_shipment_status_{$delivery_status}_notification", $order_id, false, [$tracking_id]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = $tracking_number;

            }
        }

        $resp_data = [
            'code' => RestApi::CODE_SUCCESS,
            'errors' => $errors,
            'res' => $res ?? '',
            'delivery_status' => $delivery_status ?? '',
            'order_id' => $order_id ?? '',
            'tracking_id' => $tracking_id ?? '',
            'custom_track_status' => $custom_track_status ?? '',
            'custom_track_time' => $custom_track_time ?? '',
        ];

        return rest_ensure_response($resp_data);
    }

    /**
     * @param \stdClass[] $trackings
     */
    private function retrieve_tracking_items(array $trackings)
    {
        global $wpdb;

        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        foreach ($trackings as $tracking) {
            // Set the default value
            $tracking->tracking_items = [];
        }

        $trackings_by_id = array_column($trackings, null, 'id');

        if (!empty($trackings_by_id)) {

            $placeholder_str = (new ParcelPanelFunction)->parcelpanel_get_prepare_placeholder_str($trackings_by_id);

            $SQL2 = <<<SQL
SELECT * FROM {$TABLE_TRACKING_ITEMS}
WHERE tracking_id IN ({$placeholder_str})
SQL;

            $tracking_item_results = $wpdb->get_results($wpdb->prepare($SQL2, array_keys($trackings_by_id)));
            TrackingItems::format_result_data($tracking_item_results);

            foreach ($tracking_item_results as $item) {

                $tracking_id = $item->tracking_id;

                if (array_key_exists($tracking_id, $trackings_by_id)) {
                    $trackings_by_id[$tracking_id]->tracking_items[] = $item;
                }
            }
        }
    }
}
