<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * One ledger row for the current item of any item loop. Call glance_prime_rows() on the loop's
 * rows first so the cover comes from one batched query.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$row_id     = (int) osc_item_id();
$row_url    = osc_item_url();
$row_title  = osc_item_title();
$row_n      = glance_row_index();
$row_img    = osc_images_enabled_at_items() ? glance_row_cover($row_id) : '';
$row_size   = glance_thumb_size();
$row_place  = explode(', ', glance_item_place(), 2);
$row_cat    = (int) osc_item_category_id() === glance_search_category_id() ? '' : (string) osc_item_category();
$row_facts  = glance_row_facts($row_id, (string) $row_title);
$row_age    = glance_time_ago(osc_item_pub_date());
$row_price  = glance_price_html();
$row_dist   = osc_item_distance();
$row_prem   = osc_item_is_premium();
$row_urgent = function_exists('osc_item_is_urgent') && osc_item_is_urgent();
$row_hl     = function_exists('osc_item_is_highlighted') && osc_item_is_highlighted();
?>
<li class="row<?php echo $row_hl ? ' row-hl' : ''; ?>" data-row data-id="<?php echo $row_id; ?>">
    <a class="row-link" href="<?php echo osc_esc_html($row_url); ?>" data-item-link="<?php echo $row_id; ?>">
        <span class="row-thumb">
            <?php if ($row_img !== '') { ?>
                <img src="<?php echo osc_esc_html($row_img); ?>" alt="" width="<?php echo $row_size[0]; ?>" height="<?php echo $row_size[1]; ?>"
                     decoding="async"<?php echo $row_n < 4 ? '' : ' loading="lazy"'; ?><?php echo $row_n === 0 ? ' fetchpriority="high"' : ''; ?>>
            <?php } else { ?>
                <?php echo glance_icon('image'); ?>
            <?php } ?>
        </span>
        <span class="row-main">
            <span class="row-title"><?php echo osc_esc_html($row_title); ?></span>
            <span class="row-facts"><?php
                if ($row_prem) { ?><span class="row-badge"><?php _e('Featured', 'glance'); ?></span><?php }
                if ($row_urgent) { ?><span class="row-badge row-badge-warn"><?php _e('Urgent', 'glance'); ?></span><?php }
                foreach ($row_facts as $row_f) { ?><span class="row-fact row-spec"><?php echo osc_esc_html($row_f); ?></span><?php }
                if ($row_cat !== '') { ?><span class="row-fact row-fact-cat"><?php echo osc_esc_html($row_cat); ?></span><?php }
                if ($row_place[0] !== '') { ?><span class="row-fact"><?php echo osc_esc_html($row_place[0]);
                    if (isset($row_place[1])) { ?><span class="row-place-more">, <?php echo osc_esc_html($row_place[1]); ?></span><?php } ?></span><?php }
                if ($row_dist !== null) { ?><span class="row-fact tnum"><?php echo osc_esc_html(osc_distance_label($row_dist)); ?></span><?php }
            ?></span>
        </span>
        <span class="row-end">
            <span class="row-price tnum"><?php echo $row_price; ?></span>
            <span class="row-when">
                <span class="row-seen" data-seen-mark hidden><?php echo glance_icon('eye'); ?><span class="row-seen-text"><?php _e('Seen', 'glance'); ?></span></span>
                <?php if ($row_age !== '') { ?>
                    <time class="tnum" datetime="<?php echo osc_esc_html(glance_iso_date(osc_item_pub_date())); ?>"><?php echo osc_esc_html($row_age); ?></time>
                <?php } ?>
            </span>
        </span>
    </a>
    <button type="button" class="btn btn-quiet btn-icon btn-save row-save" data-save="<?php echo $row_id; ?>"
            data-title="<?php echo osc_esc_html($row_title); ?>" data-url="<?php echo osc_esc_html($row_url); ?>"
            data-price="<?php echo osc_esc_html(osc_item_formatted_price()); ?>" data-img="<?php echo osc_esc_html($row_img); ?>"
            data-label-on="<?php echo osc_esc_html(__('Saved', 'glance')); ?>" data-label-off="<?php echo osc_esc_html(__('Save', 'glance')); ?>"
            aria-pressed="false"><?php echo glance_icon('heart'); ?><span class="visually-hidden"><span data-save-label><?php _e('Save', 'glance'); ?></span>: <?php echo osc_esc_html($row_title); ?></span></button>
</li>
