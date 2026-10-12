<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The search filters: a bar above the list on wide screens, a bottom sheet on small ones
 * (a plain disclosure without scripts). Field names are the ones core's search reads; the
 * keyword comes from the header search and rides along as a hidden field.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$fl_types   = Search::getAllowedTypesForSorting();
$fl_cat     = glance_search_category_id();
$fl_extra   = glance_search_form_extra();
$fl_meta    = Params::getParam('meta');
$fl_more_on = (is_array($fl_meta) && array_filter($fl_meta)) || osc_search_radius() > 0;
$fl_count   = (int) View::getInstance()->_get('glance_filter_count');
$fl_hidden  = array(
    'page'       => 'search',
    'sPattern'   => trim((string) osc_search_pattern()),
    'sOrder'     => (string) osc_search_order(),
    'iOrderType' => (string) ($fl_types[osc_search_order_type()] ?? ''),
    'sCountry'   => (string) Params::getParam('sCountry'),
    'sRegion'    => (string) Params::getParam('sRegion'),
    'bPremium'   => osc_search_only_premium() ? '1' : '',
);
?>
<details class="filters" data-filters>
    <summary class="btn filters-toggle" data-filters-toggle>
        <?php echo glance_icon('sliders-horizontal'); ?>
        <span><?php _e('Filters', 'glance'); ?></span>
        <?php if ($fl_count > 0) { ?><span class="filters-count tnum"><?php echo $fl_count; ?></span><?php } ?>
    </summary>
    <form class="filters-form nocsrf" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="get" data-filters-form
          aria-label="<?php echo osc_esc_html(__('Filter ads', 'glance')); ?>">
        <div class="filters-head">
            <h2><?php _e('Filters', 'glance'); ?></h2>
            <button type="button" class="btn btn-quiet btn-icon" data-filters-close>
                <?php echo glance_icon('x', __('Close filters', 'glance')); ?>
            </button>
        </div>
        <?php foreach ($fl_hidden as $fl_name => $fl_value) {
            if ($fl_value !== '') { ?>
            <input type="hidden" name="<?php echo $fl_name; ?>" value="<?php echo osc_esc_html($fl_value); ?>">
        <?php }
        } ?>
        <?php foreach (osc_search_user() as $fl_uid) { ?>
            <input type="hidden" name="sUser[]" value="<?php echo osc_esc_html($fl_uid); ?>">
        <?php } ?>

        <div class="filters-row">
            <div class="ff ff-cat">
                <label for="glance-f-cat"><?php _e('Category', 'glance'); ?></label>
                <select id="glance-f-cat" name="sCategory">
                    <option value=""><?php _e('All categories', 'glance'); ?></option>
                    <?php foreach (glance_category_options() as $fl_c) { ?>
                        <option value="<?php echo $fl_c['id']; ?>"<?php echo $fl_c['id'] === $fl_cat ? ' selected' : ''; ?>><?php
                            echo str_repeat('&emsp;', $fl_c['depth']) . osc_esc_html($fl_c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="ff ff-city">
                <label for="glance-f-city"><?php _e('City', 'glance'); ?></label>
                <input id="glance-f-city" type="text" name="sCity" autocomplete="off"
                       value="<?php echo osc_esc_html((string) osc_search_city()); ?>"
                       placeholder="<?php echo osc_esc_html(__('Anywhere', 'glance')); ?>"
                       data-ac="location_cities" data-ac-url="<?php echo osc_esc_html(osc_base_url(true)); ?>">
            </div>

            <?php if (osc_price_enabled_at_items()) { ?>
                <fieldset class="ff ff-price">
                    <legend><?php _e('Price', 'glance'); ?></legend>
                    <div class="ff-range">
                        <label class="visually-hidden" for="glance-f-min"><?php _e('Lowest price', 'glance'); ?></label>
                        <input id="glance-f-min" class="tnum" type="text" name="sPriceMin" inputmode="numeric" maxlength="12"
                               value="<?php echo osc_esc_html((string) osc_search_price_min()); ?>" placeholder="<?php echo osc_esc_html(__('Min', 'glance')); ?>">
                        <span aria-hidden="true">–</span>
                        <label class="visually-hidden" for="glance-f-max"><?php _e('Highest price', 'glance'); ?></label>
                        <input id="glance-f-max" class="tnum" type="text" name="sPriceMax" inputmode="numeric" maxlength="12"
                               value="<?php echo osc_esc_html((string) osc_search_price_max()); ?>" placeholder="<?php echo osc_esc_html(__('Max', 'glance')); ?>">
                    </div>
                </fieldset>
            <?php } ?>

            <?php if (osc_images_enabled_at_items()) { ?>
                <div class="ff ff-check">
                    <label for="glance-f-pic">
                        <input id="glance-f-pic" type="checkbox" name="bPic" value="1"<?php echo osc_search_has_pic() ? ' checked' : ''; ?>>
                        <?php _e('With photos', 'glance'); ?>
                    </label>
                </div>
            <?php } ?>

            <div class="ff ff-go">
                <button type="submit" class="btn btn-primary"><?php _e('Show ads', 'glance'); ?></button>
            </div>
        </div>

        <?php if ($fl_extra !== '') { ?>
            <details class="filters-more"<?php echo $fl_more_on ? ' open' : ''; ?>>
                <summary>
                    <?php echo glance_icon('chevron-down'); ?>
                    <?php _e('More filters', 'glance'); ?>
                </summary>
                <div class="filters-extra"><?php echo $fl_extra; ?></div>
            </details>
        <?php } ?>
    </form>
</details>
<dialog class="sheet filter-sheet" data-filter-sheet aria-label="<?php echo osc_esc_html(__('Filters', 'glance')); ?>"></dialog>
