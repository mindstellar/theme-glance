<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The ad page. Everything a reader needs sits in the one [data-glance-panel] article, which the
 * search page also loads into its side panel. Breadcrumbs, neighbours and related ads stay outside it.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

glance_enqueue('item');
osc_current_web_theme_path('common/header.php');

$ad_id       = (int) osc_item_id();
$ad_title    = (string) osc_item_title();
$ad_url      = (string) osc_item_url();
$ad_photos   = glance_item_photos();
$ad_trail    = glance_category_trail((int) osc_item_category_id());
$ad_seller   = glance_item_seller();
$ad_block    = glance_contact_block();
$ad_owner    = glance_item_is_owner();
$ad_expired  = osc_item_is_expired();
$ad_inactive = osc_item_is_inactive();
$ad_unpaid   = function_exists('osc_item_is_unpaid') && osc_item_is_unpaid();
$ad_pay_url  = $ad_owner && function_exists('osc_item_pay_url') ? osc_item_pay_url() : '';
$ad_fields   = glance_item_fields();
$ad_place    = glance_item_location();
$ad_posted   = (string) osc_item_pub_date();
$ad_prev     = osc_item_adjacent_url('prev');
$ad_next     = osc_item_adjacent_url('next');
$ad_views    = (int) osc_item_views();
$ad_price    = glance_price_html();
$ad_desc     = trim((string) osc_item_description());
list($ad_w, $ad_h)   = glance_dims((string) osc_normal_dimensions());
list($ad_tw, $ad_th) = glance_dims((string) osc_thumbnail_dimensions(), 240, 200);
$ad_count    = count($ad_photos);
$ad_cat_url  = $ad_trail ? end($ad_trail)['url'] : '';
$ad_map      = glance_item_map();

ob_start();
osc_show_item_comments();
$ad_comments = glance_fold_comment_form(trim((string) ob_get_clean()));

/** Contact, Save and Share, or Edit and Delete for the owner. Printed twice: in the side column and as the bottom bar. */
$ad_bar = static function (string $mod) use ($ad_id, $ad_title, $ad_url, $ad_photos, $ad_block, $ad_owner, $ad_comments, $ad_unpaid, $ad_pay_url): void {
    ?>
    <div class="ad-bar ad-bar--<?php echo $mod; ?>" role="group" aria-label="<?php echo osc_esc_html(__('Actions', 'glance')); ?>">
        <?php if ($ad_owner) { ?>
            <?php if ($ad_pay_url !== '') { ?>
                <a class="btn btn-primary" href="<?php echo osc_esc_html($ad_pay_url); ?>"><?php _e('Pay to publish', 'glance'); ?></a>
            <?php } ?>
            <a class="btn<?php echo $ad_pay_url === '' ? ' btn-primary' : ''; ?>" href="<?php echo osc_esc_html(osc_item_edit_url()); ?>"><?php echo glance_icon('pencil'); ?><?php _e('Edit ad', 'glance'); ?></a>
            <a class="btn btn-danger" href="<?php echo osc_esc_html(osc_item_delete_url()); ?>" data-confirm="<?php echo osc_esc_html(__('Delete this ad?', 'glance')); ?>"><?php _e('Delete', 'glance'); ?></a>
        <?php } else { ?>
            <?php if ($ad_block === '') { ?>
                <a class="btn btn-primary ad-bar-main" href="#ad-contact" data-contact><?php echo glance_icon('message-circle'); ?><?php _e('Contact seller', 'glance'); ?></a>
            <?php } elseif ($ad_block === 'login') { ?>
                <a class="btn btn-primary ad-bar-main" href="<?php echo osc_esc_html(osc_user_login_url()); ?>"><?php echo glance_icon('log-in'); ?><?php _e('Sign in to contact', 'glance'); ?></a>
            <?php } ?>
            <button type="button" class="btn btn-save" data-save="<?php echo $ad_id; ?>"
                data-title="<?php echo osc_esc_html($ad_title); ?>" data-url="<?php echo osc_esc_html($ad_url); ?>"
                data-price="<?php echo osc_esc_html(osc_item_formatted_price()); ?>" data-img="<?php echo osc_esc_html($ad_photos ? $ad_photos[0]['thumb'] : ''); ?>"
                data-label-on="<?php echo osc_esc_html(__('Saved', 'glance')); ?>" data-label-off="<?php echo osc_esc_html(__('Save', 'glance')); ?>"
                aria-pressed="false"><?php echo glance_icon('heart'); ?><span data-save-label><?php _e('Save', 'glance'); ?></span></button>
            <?php if ($ad_comments !== '') { ?>
                <a class="btn ad-bar-comment" href="#comments" data-comment-bar aria-label="<?php echo osc_esc_html(__('Leave a comment', 'glance')); ?>"
                   title="<?php echo osc_esc_html(__('Leave a comment', 'glance')); ?>"><?php echo glance_icon('message-circle'); ?><span class="ad-bar-label" aria-hidden="true"><?php _e('Comment', 'glance'); ?></span></a>
            <?php } ?>
            <?php if (!$ad_unpaid) { ?>
            <button type="button" class="btn btn-icon" data-share data-title="<?php echo osc_esc_html($ad_title); ?>" data-url="<?php echo osc_esc_html($ad_url); ?>"
                data-copied="<?php echo osc_esc_html(__('Link copied', 'glance')); ?>" hidden aria-label="<?php echo osc_esc_html(__('Share this ad', 'glance')); ?>"><?php echo glance_icon('share-2'); ?></button>
            <?php } ?>
        <?php } ?>
    </div>
    <?php
};

$ad_sidebar = '';
ob_start();
osc_show_widgets('item_sidebar');
osc_run_hook('item_sidebar', osc_item());
$ad_sidebar = trim((string) ob_get_clean());
?>
<div class="wrap item-page">
    <?php echo glance_item_jsonld($ad_photos, $ad_trail); ?>

    <div class="item-top">
        <nav aria-label="<?php echo osc_esc_html(__('Breadcrumb', 'glance')); ?>">
            <ol class="crumbs">
                <li><a href="<?php echo osc_esc_html(osc_base_url()); ?>"><?php _e('Home', 'glance'); ?></a></li>
                <?php foreach ($ad_trail as $crumb) { ?>
                    <li><a href="<?php echo osc_esc_html($crumb['url']); ?>"><?php echo osc_esc_html($crumb['name']); ?></a></li>
                <?php } ?>
                <li><span aria-current="page"><?php echo osc_esc_html($ad_title); ?></span></li>
            </ol>
        </nav>
        <?php if ($ad_prev !== '' || $ad_next !== '') { ?>
            <nav class="item-neighbours" aria-label="<?php echo osc_esc_html(__('Other ads', 'glance')); ?>">
                <?php if ($ad_prev !== '') { ?>
                    <a class="btn btn-quiet" rel="prev" href="<?php echo osc_esc_html($ad_prev); ?>"><?php echo glance_icon('chevron-left'); ?><?php _e('Previous ad', 'glance'); ?></a>
                <?php } ?>
                <?php if ($ad_next !== '') { ?>
                    <a class="btn btn-quiet" rel="next" href="<?php echo osc_esc_html($ad_next); ?>"><?php _e('Next ad', 'glance'); ?><?php echo glance_icon('chevron-right'); ?></a>
                <?php } ?>
            </nav>
        <?php } ?>
    </div>

    <article class="ad<?php echo $ad_photos ? '' : ' ad--nophoto'; ?>" data-glance-panel data-item-id="<?php echo $ad_id; ?>"
             data-view-beacon="<?php echo osc_esc_html(osc_item_view_beacon_url($ad_id)); ?>" aria-labelledby="ad-title"
             data-i18n="<?php echo glance_item_i18n(); ?>">

        <?php if ($ad_expired || ($ad_inactive && !$ad_unpaid)) { ?>
            <p class="ad-notice" role="status"><?php echo glance_icon('triangle-alert'); ?><span><?php
                echo osc_esc_html($ad_expired
                    ? __('This ad has expired. You can no longer contact the seller.', 'glance')
                    : __('This ad is not live yet.', 'glance')); ?></span></p>
        <?php } ?>

        <div class="ad-grid">
            <?php if ($ad_photos) { ?>
            <div class="ad-media">
                <div class="gal" data-gallery data-count="<?php echo $ad_count; ?>">
                    <div class="gal-stage">
                        <div class="gal-track" data-gal-track tabindex="0" role="group" aria-roledescription="<?php echo osc_esc_html(__('carousel', 'glance')); ?>" aria-label="<?php echo osc_esc_html(sprintf(_n('Photos of %1$s, %2$d item', 'Photos of %1$s, %2$d items', $ad_count, 'glance'), $ad_title, $ad_count)); ?>">
                            <?php foreach ($ad_photos as $i => $photo) { ?>
                                <figure class="gal-slide">
                                    <a href="<?php echo osc_esc_html($photo['src']); ?>" data-gal-open="<?php echo $i; ?>" target="_blank" rel="noopener"
                                       aria-label="<?php echo osc_esc_html(sprintf(__('Photo %1$d of %2$d, open full size', 'glance'), $i + 1, $ad_count)); ?>">
                                        <img src="<?php echo osc_esc_html($photo['src']); ?>" alt="<?php echo osc_esc_html($photo['alt']); ?>"
                                             width="<?php echo $ad_w; ?>" height="<?php echo $ad_h; ?>" decoding="async"
                                             <?php echo $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
                                    </a>
                                </figure>
                            <?php } ?>
                        </div>
                        <?php if ($ad_count > 1) { ?>
                            <button type="button" class="gal-nav gal-prev" data-gal-prev hidden aria-label="<?php echo osc_esc_html(__('Previous photo', 'glance')); ?>"><?php echo glance_icon('chevron-left'); ?></button>
                            <button type="button" class="gal-nav gal-next" data-gal-next hidden aria-label="<?php echo osc_esc_html(__('Next photo', 'glance')); ?>"><?php echo glance_icon('chevron-right'); ?></button>
                            <p class="gal-count tnum" aria-live="polite"><span data-gal-current>1</span> / <?php echo $ad_count; ?></p>
                        <?php } ?>
                    </div>
                    <?php if ($ad_count > 1) { ?>
                        <ol class="gal-thumbs" data-gal-thumbs hidden>
                            <?php foreach ($ad_photos as $i => $photo) { ?>
                                <li><button type="button" data-gal-thumb="<?php echo $i; ?>" aria-label="<?php echo osc_esc_html(sprintf(__('Show photo %d', 'glance'), $i + 1)); ?>"<?php echo $i === 0 ? ' aria-current="true"' : ''; ?>>
                                    <img src="<?php echo osc_esc_html($photo['thumb']); ?>" alt="" width="<?php echo $ad_tw; ?>" height="<?php echo $ad_th; ?>" loading="lazy" decoding="async">
                                </button></li>
                            <?php } ?>
                        </ol>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <div class="ad-aside">
                <header class="ad-head">
                    <h1 id="ad-title"><?php echo glance_title_html($ad_title); ?></h1>
                    <?php if ($ad_price !== '') { ?>
                        <p class="ad-price tnum"><?php echo $ad_price; ?></p>
                    <?php } ?>
                    <?php if (!$ad_photos) { ?>
                        <p class="ad-nophoto"><?php echo glance_icon('image'); ?><span><?php _e('No photos', 'glance'); ?></span></p>
                    <?php } ?>
                </header>

                <?php $ad_bar('inline'); ?>

                <div class="ad-facts">
                    <h2 class="visually-hidden"><?php _e('Details', 'glance'); ?></h2>
                    <dl class="facts">
                        <?php if (osc_item_category() !== '') { ?>
                            <div><dt><?php _e('Category', 'glance'); ?></dt><dd><?php
                                if ($ad_cat_url !== '') {
                                    echo '<a href="' . osc_esc_html($ad_cat_url) . '">' . osc_esc_html(osc_item_category()) . '</a>';
                                } else {
                                    echo osc_esc_html(osc_item_category());
                                } ?></dd></div>
                        <?php } ?>
                        <?php if ($ad_place !== '') { ?>
                            <div<?php echo mb_strlen($ad_place) > 32 ? ' class="is-wide"' : ''; ?>><dt><?php _e('Location', 'glance'); ?></dt><dd><?php echo osc_esc_html($ad_place); ?></dd></div>
                        <?php } ?>
                        <?php if (glance_iso_date($ad_posted) !== '') { ?>
                            <div><dt><?php _e('Posted', 'glance'); ?></dt><dd><time datetime="<?php echo osc_esc_html(glance_iso_date($ad_posted)); ?>"><?php
                                echo osc_esc_html(osc_format_date($ad_posted)); ?></time> <span class="muted"><?php echo osc_esc_html(glance_time_ago($ad_posted)); ?></span></dd></div>
                        <?php } ?>
                        <?php foreach ($ad_fields as $field) { ?>
                            <div<?php echo mb_strlen(trim(strip_tags($field['html']))) > 32 ? ' class="is-wide"' : ''; ?>><dt><?php echo osc_esc_html($field['name']); ?></dt><dd><?php echo $field['html']; ?></dd></div>
                        <?php } ?>
                    </dl>
                    <?php osc_run_hook('item_meta', osc_item()); ?>
                </div>

                <ul class="trust">
                    <?php if ($ad_seller['active'] !== '') { ?>
                        <li><?php echo glance_icon('clock'); ?><span><?php echo osc_esc_html($ad_seller['active']); ?></span></li>
                    <?php } ?>
                    <?php if ($ad_seller['since'] !== '') { ?>
                        <li><?php echo glance_icon('calendar'); ?><span><?php echo osc_esc_html(__('Member since', 'glance')); ?> <time datetime="<?php echo osc_esc_html(glance_iso_date($ad_seller['since'])); ?>"><?php echo osc_esc_html(osc_format_date($ad_seller['since'])); ?></time></span></li>
                    <?php } ?>
                    <?php if ($ad_views > 0) { ?>
                        <li><?php echo glance_icon('eye'); ?><span class="tnum"><?php echo osc_esc_html($ad_views === 1 ? __('1 view', 'glance') : sprintf(__('%s views', 'glance'), number_format($ad_views))); ?></span></li>
                    <?php } ?>
                    <?php if ($ad_seller['ads'] > 0) { ?>
                        <li><?php echo glance_icon('layers'); ?><span class="tnum"><a href="<?php echo osc_esc_html($ad_seller['ads_url']); ?>"><?php
                            echo osc_esc_html($ad_seller['ads'] === 1 ? __('1 other ad', 'glance') : sprintf(__('%d other ads', 'glance'), $ad_seller['ads'])); ?></a></span></li>
                    <?php } ?>
                </ul>

                <?php if ($ad_seller['name'] !== '') { ?>
                    <section class="seller" aria-labelledby="ad-seller-h">
                        <div class="seller-id">
                            <span class="seller-mark" aria-hidden="true"><?php echo osc_esc_html(mb_strtoupper(mb_substr($ad_seller['name'], 0, 1))); ?></span>
                            <div>
                                <h2 id="ad-seller-h" class="seller-name"><?php
                                    if ($ad_seller['profile'] !== '') {
                                        echo '<a href="' . osc_esc_html($ad_seller['profile']) . '">' . osc_esc_html($ad_seller['name']) . '</a>';
                                    } else {
                                        echo osc_esc_html($ad_seller['name']);
                                    } ?></h2>
                                <?php if ($ad_seller['company']) { ?><p class="seller-note"><?php _e('Business seller', 'glance'); ?></p><?php } ?>
                            </div>
                        </div>
                        <?php if ($ad_seller['phone'] !== '' && !$ad_owner) { ?>
                            <a class="btn seller-phone" href="tel:<?php echo osc_esc_html(preg_replace('/[^0-9+]/', '', $ad_seller['phone'])); ?>"
                               data-phone-reveal data-phone="<?php echo osc_esc_html($ad_seller['phone']); ?>"><?php echo glance_icon('phone'); ?><span data-phone-label><?php _e('Show phone number', 'glance'); ?></span></a>
                        <?php } ?>
                    </section>
                <?php } ?>

                <?php if ($ad_sidebar !== '') { ?>
                    <div class="ad-extra"><?php echo $ad_sidebar; ?></div>
                <?php } ?>
            </div>

            <div class="ad-body">
                <?php if ($ad_desc !== '') { ?>
                    <section class="ad-desc" aria-labelledby="ad-desc-h">
                        <h2 id="ad-desc-h"><?php _e('Description', 'glance'); ?></h2>
                        <div class="ad-text"><?php echo $ad_desc; ?></div>
                    </section>
                <?php } ?>

                <?php
                ob_start();
                osc_run_hook('location');
                $ad_loc_hook = trim((string) ob_get_clean());
                if ($ad_place !== '' || $ad_loc_hook !== '' || $ad_map['show']) { ?>
                    <section class="ad-loc" aria-labelledby="ad-loc-h">
                        <h2 id="ad-loc-h"><?php _e('Location', 'glance'); ?></h2>
                        <?php if ($ad_place !== '') { ?>
                            <p class="ad-loc-place"><?php echo glance_icon('map-pin'); ?><span><?php echo osc_esc_html($ad_place); ?></span></p>
                        <?php } ?>
                        <?php if ($ad_loc_hook !== '') { ?>
                            <div class="ad-extra"><?php echo $ad_loc_hook; ?></div>
                        <?php } elseif ($ad_map['show']) { ?>
                            <div class="ad-map" data-map data-map-provider="<?php echo osc_esc_html($ad_map['type']); ?>"
                                 data-map-key="<?php echo osc_esc_html($ad_map['key']); ?>" data-map-lat="<?php echo osc_esc_html($ad_map['lat']); ?>"
                                 data-map-lng="<?php echo osc_esc_html($ad_map['lng']); ?>" data-map-address="<?php echo osc_esc_html($ad_map['addr']); ?>">
                                <button type="button" class="ad-map-load" data-map-load>
                                    <span class="ad-map-pin" aria-hidden="true"><?php echo glance_icon('map-pin'); ?></span>
                                    <span class="ad-map-cta"><?php _e('Show map', 'glance'); ?></span>
                                    <span class="ad-map-note"><?php echo osc_esc_html($ad_map['type'] === 'google'
                                        ? __('Loads Google Maps', 'glance')
                                        : __('Loads OpenStreetMap', 'glance')); ?></span>
                                </button>
                                <div class="ad-map-canvas" data-map-canvas hidden></div>
                            </div>
                            <p class="ad-map-link"><a href="<?php echo osc_esc_html($ad_map['link']); ?>" target="_blank" rel="noopener nofollow"><?php
                                echo osc_esc_html($ad_map['type'] === 'google' ? __('Open in Google Maps', 'glance') : __('Open in OpenStreetMap', 'glance'));
                                echo glance_icon('external-link'); ?></a></p>
                        <?php } ?>
                    </section>
                <?php } ?>

                <?php
                ob_start();
                osc_run_hook('item_detail', osc_item());
                $ad_detail = trim((string) ob_get_clean());
                if ($ad_detail !== '') { ?>
                    <div class="ad-extra"><?php echo $ad_detail; ?></div>
                <?php }

                ob_start();
                osc_run_hook('glance_item_booking');
                $ad_booking = trim((string) ob_get_clean());
                if ($ad_booking !== '') { ?>
                    <div class="ad-booking" data-booking-slot><?php echo $ad_booking; ?></div>
                <?php } ?>

                <div class="ad-tools">
                    <?php if (osc_enable_send_friend() && (!osc_reg_user_can_send_friend() || osc_is_web_user_logged_in())) { ?>
                        <a class="btn btn-quiet" href="<?php echo osc_esc_html(osc_item_send_friend_url()); ?>"><?php echo glance_icon('mail'); ?><?php _e('Send to a friend', 'glance'); ?></a>
                    <?php } ?>
                    <?php if (!$ad_owner) { ?>
                        <details class="ad-report">
                            <summary class="btn btn-quiet"><?php echo glance_icon('flag'); ?><?php _e('Report this ad', 'glance'); ?></summary>
                            <form method="post" action="<?php echo osc_esc_html(osc_base_url(true)); ?>">
                                <input type="hidden" name="page" value="item">
                                <input type="hidden" name="action" value="mark">
                                <input type="hidden" name="id" value="<?php echo $ad_id; ?>">
                                <div class="oe-field">
                                    <label class="oe-label" for="ad-report-as"><?php _e('What is wrong with it?', 'glance'); ?></label>
                                    <select id="ad-report-as" name="as">
                                        <option value="spam"><?php _e('It is spam or a scam', 'glance'); ?></option>
                                        <option value="offensive"><?php _e('It is offensive', 'glance'); ?></option>
                                        <option value="badcat"><?php _e('It is in the wrong category', 'glance'); ?></option>
                                        <option value="repeated"><?php _e('It is posted more than once', 'glance'); ?></option>
                                        <option value="expired"><?php _e('It is no longer available', 'glance'); ?></option>
                                    </select>
                                </div>
                                <?php if (osc_recaptcha_reports_enabled() && osc_captcha_enabled()) { ?>
                                    <div class="oe-field"><?php osc_show_captcha('reports'); ?></div>
                                <?php } ?>
                                <button type="submit" class="btn"><?php _e('Send report', 'glance'); ?></button>
                            </form>
                        </details>
                    <?php } ?>
                </div>

                <?php if ($ad_block === '') { ?>
                    <section class="ad-contact" id="ad-contact" data-contact-form aria-labelledby="ad-contact-h">
                        <p class="ad-contact-line"><?php _e('Questions about this ad?', 'glance'); ?>
                            <a href="#ad-contact" data-contact><?php _e('Contact seller', 'glance'); ?></a></p>
                        <div class="ad-contact-body" data-contact-body>
                            <h2 id="ad-contact-h" class="ad-contact-title"><?php _e('Contact the seller', 'glance'); ?></h2>
                            <p class="ad-safe"><?php _e('Never pay in advance. Meet in a public place and check the item first.', 'glance'); ?></p>
                            <?php glance_contact_form(); ?>
                        </div>
                        <template data-contact-head>
                            <header class="cdlg-head">
                                <div class="cdlg-heading">
                                    <h2 id="ad-contact-dlg-h" class="cdlg-title"><?php echo osc_esc_html($ad_seller['name'] !== ''
                                        ? sprintf(__('Write to %s', 'glance'), $ad_seller['name'])
                                        : __('Write to the seller', 'glance')); ?></h2>
                                    <p class="cdlg-sub"><?php echo osc_esc_html($ad_title); ?></p>
                                </div>
                                <button type="button" class="btn btn-quiet btn-icon cdlg-close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'glance')); ?>"><?php echo glance_icon('x'); ?></button>
                            </header>
                        </template>
                    </section>
                <?php } elseif ($ad_block === 'login') { ?>
                    <section class="ad-contact" id="ad-contact" aria-labelledby="ad-contact-h">
                        <h2 id="ad-contact-h"><?php _e('Contact the seller', 'glance'); ?></h2>
                        <p><a href="<?php echo osc_esc_html(osc_user_login_url()); ?>"><?php _e('Sign in', 'glance'); ?></a> <?php _e('to write to the seller.', 'glance'); ?></p>
                    </section>
                <?php } ?>

                <?php if ($ad_comments !== '') { ?>
                    <?php $ad_ccount = preg_match('/oe-comments-count">\s*(\d+)/', $ad_comments, $m) ? (int) $m[1] : 0; ?>
                    <details class="ad-comments" data-collapse-in-panel open>
                        <summary><?php echo osc_esc_html(sprintf(__('Comments (%d)', 'glance'), $ad_ccount)); ?></summary>
                        <?php echo $ad_comments; ?>
                    </details>
                    <template data-comment-head>
                        <header class="cdlg-head">
                            <div class="cdlg-heading">
                                <h2 id="ad-comment-dlg-h" class="cdlg-title"><?php _e('Leave a comment', 'glance'); ?></h2>
                                <p class="cdlg-sub"><?php echo osc_esc_html($ad_title); ?></p>
                            </div>
                            <button type="button" class="btn btn-quiet btn-icon cdlg-close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'glance')); ?>"><?php echo glance_icon('x'); ?></button>
                        </header>
                    </template>
                <?php } ?>

                <p class="ad-id tnum"><?php echo osc_esc_html(sprintf(__('Ad ID %d', 'glance'), $ad_id)); ?></p>
            </div>
        </div>

        <?php $ad_bar('dock'); ?>
    </article>

    <?php
    $ad_related = glance_related_items(6);
    if ($ad_related) {
        $ad_covers = osc_item_cover_urls(array_map('intval', array_column($ad_related, 'pk_i_id')));
        ?>
        <section class="related" aria-labelledby="related-h">
            <h2 id="related-h"><?php _e('Similar ads', 'glance'); ?></h2>
            <?php osc_run_hook('item_before_related', osc_item()); ?>
            <?php View::getInstance()->_exportVariableToView('items', $ad_related); ?>
            <ol class="rel-list">
                <?php while (osc_has_items()) { ?>
                    <li>
                        <a class="rel-link" href="<?php echo osc_esc_html(osc_item_url()); ?>">
                            <?php if (!empty($ad_covers[(int) osc_item_id()])) { ?>
                                <span class="rel-img rel-noimg rel-plate" aria-hidden="true"><?php echo glance_icon('image'); ?><img src="<?php echo osc_esc_html($ad_covers[(int) osc_item_id()]); ?>" alt="" width="<?php echo $ad_tw; ?>" height="<?php echo $ad_th; ?>" loading="lazy" decoding="async"></span>
                            <?php } else { ?>
                                <span class="rel-img rel-noimg" aria-hidden="true"><?php echo glance_icon('image'); ?></span>
                            <?php } ?>
                            <span class="rel-text">
                                <strong><?php echo osc_esc_html(osc_item_title()); ?></strong>
                                <span><?php echo osc_esc_html(glance_item_place()); ?></span>
                            </span>
                            <?php if (osc_price_enabled_at_items()) { ?>
                                <span class="rel-price tnum"><?php echo glance_price_html(); ?></span>
                            <?php } ?>
                        </a>
                    </li>
                <?php } ?>
            </ol>
            <?php osc_reset_items(); ?>
            <?php osc_run_hook('item_after_related', osc_item()); ?>
        </section>
    <?php } ?>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
