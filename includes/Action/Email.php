<?php

namespace ParcelPanel\Action;

use ParcelPanel\Emails\WC_Email_Shipping_Notice;
use ParcelPanel\Libs\Singleton;
use ParcelPanel\ParcelPanelFunction;
use const ParcelPanel\TEMPLATE_PATH;

class Email
{
    use Singleton;

    /**
     * Display shipment info in customer emails.
     *
     * @param \WC_Order $order Order object.
     * @param bool $sent_to_admin Whether the email is being sent to admin or not.
     * @param bool $plain_text Whether email is in plain text or not.
     * @param \WC_Email $email Email object.
     */
    public function order_shipment_info($order, $sent_to_admin, $plain_text = null, $email = null)
    {
        $TRACKING_SECTION_ORDER_STATUS = AdminSettings::get_tracking_section_order_status_field();

        $order_id = $order->get_id();
        $_sync_status = $order->get_meta('_parcelpanel_sync_status');

        if ('1' !== $_sync_status) {
            return;
        }

        // $stylesheet_directory = get_stylesheet_directory();
        // $local_template       = "{$stylesheet_directory}/woocommerce/emails/tracking-info.php";

        // $order = wc_get_order( $order_id );

        if (!is_a($email, WC_Email_Shipping_Notice::class)) {

            $order_status = $order->get_status();

            // 启用状态
            $is_enable_email_notice = AdminSettings::get_email_notification_add_tracking_section_field();

            if (!$is_enable_email_notice || !(in_array($order_status, $TRACKING_SECTION_ORDER_STATUS, true) || in_array("wc-{$order_status}", $TRACKING_SECTION_ORDER_STATUS, true))) {
                return;
            }
        }

        $tracking_items = ShopOrder::instance()->retrieve_shipments_info_by_order_id($order_id);

        if (empty($tracking_items)) {
            $tracking_item = TrackingNumber::get_empty_tracking();

            $tracking_item->shipment_status = 1;

            $tracking_items = [$tracking_item];
        }


        foreach ($tracking_items as $key => $item) {
            if ($item->id == 0) {
                unset($tracking_items[$key]);
                $tracking_items[] = $item;
                break;
            }
        }

        $order_number = $order->get_order_number();
        $order_billing_email = $order->get_billing_email();
        $is_multi_shipment = count($tracking_items) > 1;  // 标记是否多个单号

        $f = 0; // 单号序列
        foreach ($tracking_items as $item) {
            ++$f;
            $order_number_suffix = $is_multi_shipment ? "-F{$f}" : '';
            $item->order_number = "#{$order_number}{$order_number_suffix}";

            if ('global' === $item->courier_code) {
                $item->courier_code = 'cainiao';
            }

            if (empty($item->tracking_number)) {
                $item->track_link = (new ParcelPanelFunction)->parcelpanel_get_track_page_url(false, $order_number, $order_billing_email);
            } else {
                $item->track_link = (new ParcelPanelFunction)->parcelpanel_get_track_page_url_by_tracking_number($item->tracking_number);
            }
        }

        if (true === $plain_text) {
            // wc_get_template( 'emails/plain/tracking-info.php', [ 'tracking_items' => $tracking_items, 'order_id' => $order_id ], 'parcelpanel-woocommerce/', "{$stylesheet_directory}/woocommerce/" );
        } else {
            // if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
            //     wc_get_template( 'emails/tracking-info.php', [ 'tracking_items' => $tracking_items, 'order_id' => $order_id ], 'parcelpanel-woocommerce/', "{$stylesheet_directory}/woocommerce/" );
            // } else {
            wc_get_template('emails/tracking-info.php', ['shipment_items' => $tracking_items, 'order_id' => $order_id], 'parcelpanel-woocommerce/', \ParcelPanel\TEMPLATE_PATH);
            // }
        }
    }

    public function shipment_email_order_details($order, $shipment_items, $sent_to_admin = null, $plain_text = null, $email = null)
    {
        $order_id = $order->get_id();
        $_sync_status = $order->get_meta('_parcelpanel_sync_status');
        if ('1' !== $_sync_status) {
            return;
        }

        if (true === $plain_text) {
        } else {
            wc_get_template(
                'emails/tracking-info.php',
                [
                    'order' => $order,
                    'sent_to_admin' => $sent_to_admin,
                    'plain_text' => $plain_text,
                    'email' => $email,
                    'order_id' => $order_id,
                    'shipment_items' => $shipment_items,
                ],
                'parcelpanel-woocommerce/',
                \ParcelPanel\TEMPLATE_PATH
            );
            wc_get_template(
                'emails/email-order-details.php',
                [
                    'order' => $order,
                    'sent_to_admin' => $sent_to_admin,
                    'plain_text' => $plain_text,
                    'email' => $email,
                    'order_id' => $order_id,
                    'shipment_items' => $shipment_items,
                ],
                'parcelpanel-woocommerce/',
                \ParcelPanel\TEMPLATE_PATH
            );
        }
    }


    /**
     * Preview email template.
     */
    public function preview_emails()
    {
        if (isset($_GET['pp_preview_mail'])) {
            if (!check_ajax_referer('pp-preview-mail', false, false)) {
                die('Security check');
            }

            // load the mailer class.
            $mailer = \WC()->mailer();

            // get the preview email subject.
            $email_heading = __('Your order is in transit', 'parcelpanel');

            $preview = true;

            $tracking_item = new \stdClass();
            $tracking_item->tracking_number = '92055901755477000271990251';
            $tracking_item->courier_code = 'usps';
            $tracking_item->shipment_status = 2;
            $tracking_item->order_number = '#1234';
            $tracking_item->track_link = (new ParcelPanelFunction)->parcelpanel_get_track_page_url(true);

            $shipment_items = [$tracking_item];

            ob_start();
            include TEMPLATE_PATH . '/emails/tracking-info.php';
            $order_shipment_table = ob_get_clean();

            // get the preview email content.
            ob_start();
            include TEMPLATE_PATH . '/emails/shipment-notice-preview.php';
            $message = ob_get_clean();

            // create a new email.
            $email = new \WC_Email();

            // wrap the content with the email template and then add styles.
            $message = apply_filters('woocommerce_mail_content', $email->style_inline($mailer->wrap_message($email_heading, $message)));

            // print the preview email.
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $message;
            // phpcs:enable
            exit;
        }
    }
}
