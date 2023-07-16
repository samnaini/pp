<?php

namespace ParcelPanel\Action;

use ParcelPanel\Models\Table;
use ParcelPanel\Models\TrackingSettings;
use ParcelPanel\ParcelPanelFunction;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class AdminShipmentsTableList extends \WP_List_Table
{
    protected $counts = [];

    protected $total_shipments = '';

    public function __construct( $args = [] )
    {
        add_filter( 'default_hidden_columns', [ $this, 'default_hidden_columns' ], 10, 2 );

        parent::__construct( [
            'plural'   => 'pp-shipments',
            'singular' => 'pp-shipment',
            'ajax'     => true,
        ] );
    }

    public function default_hidden_columns( $columns, $screen )
    {
        if ( $this->screen->id === $screen->id ) {
            // $columns = array_merge( $columns, $this->get_default_hidden_columns() );
        }

        return $columns;
    }

    public function prepare_items()
    {
        $args = [];

        $per_page = $this->get_items_per_page( 'parcelpanel_page_pp_shipments_per_page', 10 );

        $args[ 'limit' ] = $per_page;

        if ( ! empty( $_REQUEST[ 'paged' ] ) ) {
            $args[ 'page' ] = absint( $_REQUEST[ 'paged' ] );
        }

        if ( ! empty( $_REQUEST[ 'status' ] ) ) {
            $args[ 'status' ] = absint( $_REQUEST[ 'status' ] );
        }

        if ( ! empty( $_REQUEST[ 'courier' ] ) ) {
            $args[ 'courier' ] = wc_clean( $_REQUEST[ 'courier' ] );
        }

        if ( ! empty( $_REQUEST[ 's' ] ) ) {
            $args[ 'search' ] = wc_clean( ltrim( $_REQUEST[ 's' ], '#' ) );
        }

        $date_list = [ 1, 30, 60, 90 ];

        $date = (int)( $_REQUEST[ 'date' ] ?? 60 );
        $date = in_array( $date, $date_list ) ? $date : 60;

        $args[ 'date_created' ] = [
            ( new \WC_DateTime( "-{$date} day midnight" ) )->format( 'Y-m-d H:i:s' ),
            current_time( 'Y-m-d H:i:s' ),
        ];

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
        //         $args[ 'date_created' ] = [ $datetime->format( 'Y-m-d 00:00:00' ), $next_month->format( 'Y-m-d 23:59:59' ) ];
        //     }
        // }

        $data = $this->query( $args );

        $this->items = $data;

        $this->counts = AdminShipments::get_shipment_counts();

        $this->set_pagination_args(
            [
                'total_items' => $this->total_shipments,
                'per_page'    => $per_page,
            ]
        );
    }

    /**
     * Displays the pagination.
     *
     * @param string $which
     *
     * @since 3.1.0
     *
     */
    protected function pagination( $which )
    {
        if ( empty( $this->_pagination_args ) ) {
            return;
        }

        $total_items     = $this->_pagination_args[ 'total_items' ];
        $total_pages     = $this->_pagination_args[ 'total_pages' ];
        $infinite_scroll = false;
        if ( isset( $this->_pagination_args[ 'infinite_scroll' ] ) ) {
            $infinite_scroll = $this->_pagination_args[ 'infinite_scroll' ];
        }

        if ( 'top' === $which && $total_pages > 1 ) {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }

        $output = '<span class="displaying-num">' . sprintf(
            /* translators: %s: Number of items. */
                _n( 'Showing %s shipment', 'Showing %s shipments', $total_items, 'parcelpanel' ),
                number_format_i18n( $total_items )
            ) . '</span>';

        $current              = $this->get_pagenum();
        $removable_query_args = wp_removable_query_args();

        $current_url = set_url_scheme( 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );

        $current_url = remove_query_arg( $removable_query_args, $current_url );

        $page_links = [];

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = false;
        $disable_last  = false;
        $disable_prev  = false;
        $disable_next  = false;

        if ( 1 == $current ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( 2 == $current ) {
            $disable_first = true;
        }
        if ( $total_pages == $current ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $total_pages - 1 == $current ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( remove_query_arg( 'paged', $current_url ) ),
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        $page_links[]     = $total_pages_before . sprintf(
            /* translators: 1: Current page, 2: Total pages. */
                _x( '%1$s of %2$s', 'paging' ),
                $html_current_page,
                $html_total_pages
            ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }

        echo "<div class='tablenav-pages{$page_class}'>$output</div>";
    }

    public function get_columns(): array
    {
        return [
            // 若要修改这个按钮的样式，需要重写 print_column_headers() 方法
            'cb' => '<input type="checkbox" />',  // Render a checkbox
            'order'           => 'Order',
            'tracking_number' => 'Tracking number',
            'courier'         => 'Courier',
            'last_event'      => 'Last check point',
            'order_date'      => 'Order date',
            'shipment_status' => 'Shipment status',
        ];
    }

    public function column_default( $item, $column_name )
    {
        $content = $item->$column_name ?? '';
        return $content ? "<span title='" . esc_attr( $content ) . "'>{$content}</span>" : '';
    }

    /**
     * Table list views.
     *
     * @return array
     */
    protected function get_views()
    {
        $total_users    = $this->counts[ 'all' ];
        $shipment_count = $this->counts[ 'shipment_statuses' ];

        $status_links = [];

        $class = '';

        if ( empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST[ 'all_shipments' ] ) ) ) {
            $class = ' class="current"';
        }

        $url = 'admin.php?page=pp-shipments';

        /* translators: %s: count */
        $status_links[ 'all' ] = "<a href='{$url}'$class>" . sprintf(
                _nx( 'All <span class="count">(%s)</span>',
                    'All <span class="count">(%s)</span>',
                    $total_users,
                    'shipments',
                    'woocommerce'
                ),
                number_format_i18n( $total_users )
            ) . '</a>';

        // sorted shipment statuses
        $shipment_statuses = [
            'pending' => [
                'text' => 'Pending',
                'id'   => 1,
            ],

            'transit' => [
                'text' => 'In transit',
                'id'   => 2,
            ],

            'delivered' => [
                'text' => 'Delivered',
                'id'   => 4,
            ],

            'pickup' => [
                'text' => 'Out for delivery',
                'id'   => 3,
            ],

            'info_received' => [
                'text' => 'Info received',
                'id'   => 8,
            ],

            'exception' => [
                'text' => 'Exception',
                'id'   => 7,
            ],

            'undelivered' => [
                'text' => 'Failed attempt',
                'id'   => 6,
            ],

            'expired' => [
                'text' => 'Expired',
                'id'   => 5,
            ],
        ];

        foreach ( $shipment_statuses as $item ) {

            $class = '';

            $_count = $shipment_count[ $item[ 'id' ] ] ?? 0;

            if ( isset( $_REQUEST[ 'status' ] ) && sanitize_key( wp_unslash( $_REQUEST[ 'status' ] ) ) == $item[ 'id' ] ) {  // WPCS: input var okay, CSRF ok.
                $class = ' class="current"';
            }

            $text = sprintf(
            /* translators: 1: User role name, 2: Number of users. */
                '%1$s <span class="count">(%2$s)</span>',
                $item[ 'text' ],
                number_format_i18n( $_count )
            );

            $status_links[ $item[ 'id' ] ] = "<a href='{$url}&amp;status={$item[ 'id' ]}'$class>{$text}</a>";
        }

        return $status_links;
    }

    function no_items()
    {
        echo '<div class="pp-empty-state__wrapper pp-m-y-4" style=""><svg width="60" height="60" viewBox="0 0 46 42" xmlns="http://www.w3.org/2000/svg"><path d="M27 0C17 0 8.66671 8.33333 8.66671 18.3333C8.66671 22 9.66671 25.3333 11.6667 28.3333L0.333374 38.3333L3.66671 42L15 32.3333C18.3334 35.3333 22.3334 37 27 37C37 37 45.3334 28.6667 45.3334 18.6667C45.3334 8.33333 37 0 27 0ZM27 31.6667C19.6667 31.6667 13.6667 25.6667 13.6667 18.3333C13.6667 11 19.6667 5 27 5C34.3334 5 40.3334 11 40.3334 18.3333C40.3334 25.6667 34.3334 31.6667 27 31.6667Z" fill="#d9d9d9"></path></svg><p class="pp-m-t-5 pp-m-b-0 pp-empty-state__title">';
        _e( 'No orders yet', 'parcelpanel' );
        echo '</p><p class="pp-m-t-2 pp-m-b-0 pp-text-subdued">';
        _e( 'Try changing the filters or search term', 'parcelpanel' );
        echo '</p></div>';
    }

    /**
     * Determine if the current view is the "All" view.
     *
     * @return bool Whether the current view is the "All" view.
     * @since 4.2.0
     *
     */
    protected function is_base_request()
    {
        $vars = $_GET;
        unset( $vars[ 'paged' ] );

        if ( empty( $vars ) ) {
            return true;
        }

        return 1 === count( $vars );
    }

    private function query( $args )
    {
        global $wpdb;

        $args = wp_parse_args( $args, [
            'limit' => 10,
            'page'  => 1,
        ] );

        $TABLE_TRACKING       = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;

        $query_where = "p.post_type = 'shop_order' AND p.post_status <> 'trash' AND p.post_status <> 'auto-draft'";

        $query_limit = '';

        // status
        if ( isset( $args[ 'status' ] ) ) {
            $status_list = array_map( 'intval', (array)$args[ 'status' ] );
            $p_status    = [];

            foreach ( $status_list as $status ) {
                $p_status[] = $wpdb->prepare( 'ppti.shipment_status=%d', $status );
                if ( 1 == $status ) {
                    $p_status[] = 'ISNULL(tracking_number)';
                }
            }

            $where_status = implode( ' OR ', $p_status );

            if ( ! empty( $where_status ) ) {
                $query_where .= " AND ({$where_status})";
            }
        }

        // courier
        if ( isset( $args[ 'courier' ] ) ) {
            $query_where .= $wpdb->prepare( ' AND ppt.courier_code = %s', $args[ 'courier' ] );
        }

        // search
        if ( isset( $args[ 'search' ] ) ) {
            $order_id    = absint( (new ParcelPanelFunction)->parcelpanel_get_formatted_order_id( $args[ 'search' ] ) );
            $query_where .= $wpdb->prepare( ' AND (p.ID = %d OR ppt.tracking_number = %s)', $order_id, $args[ 'search' ] );
        }

        // date; e.g. [ 'Y-m-d H:i:s', 'Y-m-d H:i:s' ]
        if ( isset( $args[ 'date_created' ] ) ) {
            $query_where .= $wpdb->prepare( ' AND p.post_date BETWEEN %s AND %s', $args[ 'date_created' ] );
        }

        // limit
        if ( isset( $args[ 'limit' ] ) && $args[ 'limit' ] > 0 ) {
            if ( isset( $args[ 'offset' ] ) ) {
                $query_limit = $wpdb->prepare( 'LIMIT %d, %d', $args[ 'offset' ], $args[ 'limit' ] );
            } else {
                $query_limit = $wpdb->prepare( 'LIMIT %d, %d', $args[ 'limit' ] * ( $args[ 'page' ] - 1 ), $args[ 'limit' ] );
            }
        }

        $SQL = <<<SQL
SELECT SQL_CALC_FOUND_ROWS p.ID,ppti.tracking_id,ppti.shipment_status,tracking_number,courier_code,last_event FROM {$TABLE_TRACKING_ITEMS} AS ppti
LEFT JOIN {$wpdb->posts} p ON ppti.order_id = p.ID
INNER JOIN (SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key='_parcelpanel_sync_status' AND meta_value='1') AS pm ON p.ID = pm.post_id
LEFT JOIN {$TABLE_TRACKING} ppt ON ppt.id = ppti.tracking_id
WHERE {$query_where}
GROUP BY ppti.tracking_id,ppti.order_id
ORDER BY p.post_date DESC
{$query_limit}
SQL;

        $results = $wpdb->get_results( $SQL );

        $this->total_shipments = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );

        return $results;
    }

    /**
     * Column cb.
     *
     * @return string
     */
    public function column_cb( $item )
    {
//       return <<<HTML
// <label class="PP-Choice components-checkbox-control__label">
// <span class="components-checkbox-control__input-container">
//   <input type="checkbox" class="components-checkbox-control__input"/>
//   <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img"
//   class="components-checkbox-control__checked" aria-hidden="true" focusable="false">
//   <path d="M18.3 5.6L9.9 16.9l-4.6-3.4-.9 1.2 5.8 4.3 9.3-12.6z"></path>
//   </svg>
// </span>
// </label>
// HTML;

        return sprintf( '<input type="checkbox" name="shipments[]" value="oid=%1$d&tid=%2$d" />', $item->ID, $item->tracking_id );
    }

    function column_tracking_number( $item )
    {
        $tracking_number = $item->tracking_number ?? '';

        $order = ( $item->ID > 0 ? wc_get_order( $item->ID ) : false );

        $order_number = ! empty( $order ) ? $order->get_order_number() : '';
        $email        = ! empty( $order ) ? $order->get_billing_email() : '';

        if ( empty( $tracking_number ) ) {
            $track_link = (new ParcelPanelFunction)->parcelpanel_get_track_page_url( false, $order_number, $email );
        } else {
            $track_link = (new ParcelPanelFunction)->parcelpanel_get_track_page_url_by_tracking_number( $tracking_number );
        }

        $text = $tracking_number ?: 'Not added yet';

        echo '<a href="' . esc_url( $track_link ) . '" target="_blank" title="' . esc_attr( $text ) . '">' . esc_html( $text ) . '</a>';
    }

    function column_courier( $item )
    {
        if ( empty( $item->tracking_number ) ) {
            return;
        }

        $courier_code = $item->courier_code;

        if ( empty( $courier_code ) ) {
            $courier_name = 'Unknown';
        } else {
            $courier_info = (new ParcelPanelFunction)->parcelpanel_get_courier_info( $courier_code );
            $courier_name = $courier_info->name ?? $courier_code;
        }

        echo '<span title="' . esc_attr( $courier_name ) . '">' . esc_html( $courier_name ) . '</span>';
    }

    function column_shipment_status( $item )
    {
        $no_quota = false;

        $shipment_statuses = (new ParcelPanelFunction)->parcelpanel_get_shipment_statuses();

        if ( $no_quota ) {
            $shipment_status = 'noquota';
            $shipment_text   = '0 Quota Available';
        } else {
            $shipment_status = (new ParcelPanelFunction)->parcelpanel_get_shipment_status( $item->shipment_status ) ?: 'pending';
            $shipment_text   = $shipment_statuses[ $shipment_status ][ 'text' ] ?? '';
        }

        return "<span class=\"pp-tracking-icon icon-default icon-{$shipment_status} pp-shipment-tracking-status\">{$shipment_text}</span><span class=\"pp-spinner\"></span>";
    }

    function column_order( $item )
    {
        $order = ( $item->ID > 0 ? wc_get_order( $item->ID ) : false );

        if ( is_callable( [ $order, 'get_edit_order_url' ] ) ) {
            $show_content = $order->get_order_number();
            $href         = $order->get_edit_order_url();
            echo '<a href="' . esc_attr( $href ) . '" title="#' . esc_attr( $show_content ) . '" target="_blank">#' . esc_html( $show_content ) . '</a>';
        } else {
            $show_content = $item->ID;
            echo '<span title="#' . esc_attr( $show_content ) . '">#' . esc_html( $show_content ) . '</span>';
        }
    }

    function column_order_date( $item )
    {
        $order = ( $item->ID > 0 ? wc_get_order( $item->ID ) : false );

        $timestamp = $order->get_date_created()->getTimestamp() ?? '';

        if ( empty( $timestamp ) ) {
            return;
        }

        // 时间在 1 天内
        if ( $timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $timestamp <= current_time( 'timestamp', true ) ) {
            $show_date = sprintf(
            /* translators: %s: human-readable time difference */
                _x( '%s ago', '%s = human-readable time difference', 'parcelpanel' ),
                human_time_diff( $order->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
            );
        } else {
            $show_date = $order->get_date_created()->date_i18n( _x( 'M j, Y', 'shipments', 'parcelpanel' ) );
        }

        printf(
            '<time datetime="%1$s" title="%2$s">%3$s</time>',
            esc_attr( $order->get_date_created()->date( 'c' ) ),
            esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
            esc_html( $show_date )
        );
    }

    function display()
    {
        extract( $this->_args );

        $this->display_tablenav( 'top' );

        ?>
      <table class="wp-list-table pp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
        <thead>
        <tr>
            <?php $this->print_column_headers(); ?>
        </tr>
        </thead>

        <tbody <?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
        <?php $this->display_rows_or_placeholder(); ?>
        </tbody>
      </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }

    /**
     * Gets a list of CSS classes for the WP_List_Table table tag.
     *
     * @return string[] Array of CSS classes for the table tag.
     * @since 3.1.0
     *
     */
    protected function get_table_classes()
    {
        $mode = get_user_setting( 'posts_list_mode', 'list' );

        $mode_class = esc_attr( 'table-view-' . $mode );

        return [ 'fixed', $mode_class, $this->_args[ 'plural' ] ];
    }

    public function extra_tablenav( $which )
    {
        if ( 'top' == $which ) {

            echo '<div class="alignleft actions" style="font-size:0">';

            echo '<div id="box-update-status" style="display:none;margin-right:5px;">';
            $this->update_status_dropdown();
            submit_button( __( 'Apply' ), '', 'update_status_action', false, [ 'id' => 'update-status-submit' ] );
            echo '</div>';

            echo '<input class="pp-ipt" type="search" name="s" placeholder="Filter order number or Tracking number" value="' . esc_attr( $_REQUEST[ 's' ] ?? '' ) . '" style="margin-right:6px;width:300px">';
            $this->date_dropdown( 1 );
            $this->courier_dropdown( 1 );
            submit_button( __( 'Filter' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );

            echo '</div>';

        } elseif ( 'bottom' == $which ) {

            echo '<div class="alignleft actions">';

            $sync_time_list = [
                1  => 'Today',
                7  => 'Last 7 days',
                30 => 'Last 30 days',
                60 => 'Last 60 days',
                90 => 'Last 90 days',
            ];

            echo '<select class="pp-slc" name="sync" class="first" id="resync-time">';

            echo '<option value="">Select sync time</option>';

            foreach ( $sync_time_list as $value => $label ) {
                // echo '<option value="' . esc_attr( $value ) . '" ' . esc_html( $value == $default ? 'selected' : '' ) . '>'
                echo '<option value="' . esc_attr( $value ) . '">'
                    . esc_html( $label )
                    . '</option>';
            }

            echo '</select>';

            submit_button( __( 'Re-sync', 'parcelpanel' ), '', 'resync', false, [ 'id' => 're-sync-submit' ] );

            echo '</div>';
        }
    }

    protected function date_dropdown( $type )
    {
        global $wpdb, $wp_locale;

        $date_list = [
            1  => 'Today',
            7  => 'Last 7 days',
            30 => 'Last 30 days',
            60 => 'Last 60 days',
            90 => 'Last 90 days',
        ];

        $date = (int)( $_GET[ 'date' ] ?? 60 );

        $date = array_key_exists( $date, $date_list ) ? $date : 60;
        ?>
      <label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ) ?></label><select class="pp-slc" name="date" id="filter-by-date" style="float:none;">
      <option disabled>Filter by date</option>
        <?php
        foreach ( $date_list as $k => $itm ) {
            printf(
                "<option %s value='%s'>%s</option>\n",
                selected( $date, $k, false ),
                esc_attr( $k ),
                $itm
            );
        }
        ?>
    </select>
        <?php
    }

    protected function courier_dropdown( $type )
    {
        global $wpdb;

        $courier = wc_clean( $_GET[ 'courier' ] ?? '' );

        $TABLE_TRACKING       = Table::$tracking;
        $TABLE_TRACKING_ITEMS = Table::$tracking_items;
        $TABLE_COURIER        = Table::$courier;

        $QUERY = <<<SQL
SELECT DISTINCT `code`,`name`
FROM {$TABLE_TRACKING_ITEMS} AS ppti
INNER JOIN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_parcelpanel_sync_status' AND meta_value='1') AS pm ON ppti.order_id=pm.post_id
INNER JOIN (SELECT ID FROM {$wpdb->posts} WHERE post_date >= %s) AS p ON p.ID=pm.post_id
INNER JOIN {$TABLE_TRACKING} AS ppt ON ppt.id=ppti.tracking_id
INNER JOIN {$TABLE_COURIER} AS ppc ON ppc.code=ppt.courier_code
ORDER BY `sort` ASC
SQL;

        $post_date = ( new \WC_DateTime( "-90 day midnight" ) )->format( 'Y-m-d H:i:s' );

        $couriers = $wpdb->get_results( $wpdb->prepare( $QUERY, $post_date ) );

        echo '<select class="pp-slc" name="courier" class="first" id="filter-by-courier" style="float:none;">';

        echo '<option' . selected( $courier, '' ) . ' value="">Filter by courier</option>';

        foreach ( $couriers as $item ) {
            echo '<option' . selected( $courier, $item->code ) . ' value="' . esc_attr( $item->code ) . '">'
                . esc_html( $item->name )
                . '</option>';
        }

        echo '</select>';
    }

    private function update_status_dropdown()
    {
        $shipment_statuses = (new ParcelPanelFunction)->parcelpanel_get_custom_status();

        echo '<select class="pp-slc" name="update_status" id="select-update-status" style="float:none;">';
        echo '<option value="">Update Status</option>';

        foreach ( $shipment_statuses as $item ) {
            echo '<option value="' . esc_attr( $item['status'] ) . '">'
                . esc_html( $item['name'] )
                . '</option>';
        }

        echo '<option disabled>--------------</option>';
        echo '<option value="auto">Revert to Automatic Update</option>';
        echo '</select>';
    }
}
