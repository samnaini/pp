<?php
/**
 * The template for displaying Tracking Page
 *
 * @var array $display_option
 * @var array $tracking_page_translations
 * @var array $custom_order_status
 * @var array $estimated_delivery_time
 * @var array $additional_text_setting
 * @var array $date_and_time_format
 * @var array $translate_tracking_detailed_info
 * @var array $custom_css_and_html
 * @var boolean $show_branding
 */

$is_single_tracking_form = !($display_option['b_od_nb_a_em'] && $display_option['b_tk_nb']);
$classname_theme = 1 == $display_option['ui_style'] ? 'theme-dark' : 'theme-light';

// line \n bad
$additional_text_setting['text_above'] = str_replace("\n", "<br/>", $additional_text_setting['text_above']);
$additional_text_setting['text_below'] = str_replace("\n", "<br/>", $additional_text_setting['text_below']);
?>
<div class="pp-tracking-section woocommerce <?php echo $classname_theme ?>"
     style="max-width:<?php echo esc_attr($display_option['_width']) ?>;margin:24px auto;min-height:1000px">
    <div class="tracking-form <?php echo $is_single_tracking_form ? 'single-form' : '' ?>">
        <div class="above-section"><?php echo wp_kses_post($additional_text_setting['text_above']) ?></div>
        <div class="box-main">
            <?php if ($display_option['b_od_nb_a_em']) { ?>
                <div class="box-form box-form-od-em">
                    <div class="form-field">
                        <label
                            for="pp-tracking-ipt-on"><?php echo esc_html($tracking_page_translations['order_number']) ?></label><input
                            type="text" name="order" value="<?php echo esc_attr($order) ?>"
                            id="pp-tracking-ipt-on"><span
                            class="tip"><?php echo esc_html($tracking_page_translations['enter_your_order']) ?></span>
                    </div>
                    <div class="form-field">
                        <label
                            for="pp-tracking-ipt-em"><?php echo esc_html($tracking_page_translations['email']) ?></label><input
                            type="text" name="email" value="<?php echo esc_attr($email) ?>"
                            id="pp-tracking-ipt-em"><span
                            class="tip"><?php echo esc_html($tracking_page_translations['enter_your_email']) ?></span>
                    </div>
                    <div class="form-button">
                        <button type="submit" id="pp-btn-trk-1"
                                class="btn-enter button btn btn-primary alt"><?php echo esc_html($tracking_page_translations['track']) ?></button>
                    </div>
                    <?php wp_nonce_field('pp-track-page-form', 'pp-track-form-nonce') ?>
                </div>
            <?php }
            if (!$is_single_tracking_form) { ?>
                <div class="line-center">
                    <div class="word"><?php echo esc_html($tracking_page_translations['or']) ?></div>
                </div>
            <?php }
            if ($display_option['b_tk_nb']) { ?>
                <div class="box-form box-form-tn">
                    <div class="form-field">
                        <label
                            for="pp-ipt-track-tn"><?php echo esc_html($tracking_page_translations['tracking_number']) ?></label><input
                            type="text" name="nums" value="<?php echo esc_attr($tracking_number) ?>"
                            id="pp-tracking-ipt-tn"><span
                            class="tip"><?php echo esc_html($tracking_page_translations['enter_your_tracking_number']) ?></span>
                    </div>
                    <div class="form-button">
                        <button type="submit" id="pp-btn-trk-2"
                                class="btn-enter button btn btn-primary alt"><?php echo esc_html($tracking_page_translations['track']) ?></button>
                    </div>
                    <?php wp_nonce_field('pp-track-page-form', 'pp-track-form-nonce') ?>
                </div>
            <?php } ?>
        </div>
        <?php echo $show_branding ? '<div style="text-align:right;font-size:12px!important;color:#111;opacity:.5;line-height:16px!important;padding-top:4px"><span>Powered by <a href="https://www.parcelpanel.com/" style="text-decoration:none;color:#111" target="_blank">ParcelPanel</a></span></div>' : '' ?>
        <div class="below-section"><?php echo wp_kses_post($additional_text_setting['text_below']) ?></div>
    </div>
    <h2 class="tracking-result-title" style="display:none"><span
            class="title"><?php echo esc_html($tracking_page_translations['order']) ?></span>&nbsp;<span
            class="order-nums"></span></h2>
    <div class="loading-container" style="display:none">
        <div class="loading"><span></span><span></span><span></span><span></span><span></span><span></span></div>
    </div>
    <div id="pp-google-translate-element" style="display:none"></div>
</div>
