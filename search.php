<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Search results: filters above, the ledger of ads on the left, the reading panel on the right.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

glance_enqueue('list');

$sr_total   = (int) osc_search_total_items();
$sr_active  = glance_active_filters();
$sr_cat     = glance_search_category_id();
$sr_alerts  = glance_alerts_state();
$sr_heading = glance_search_heading();

View::getInstance()->_exportVariableToView('glance_filter_count', glance_filter_count($sr_active));
View::getInstance()->_exportVariableToView('glance_list_pos', array(
    'offset' => max(0, (int) osc_search_start()),
    'total'  => $sr_total,
    'next'   => (int) osc_search_page() + 1 < (int) osc_search_total_pages() ? glance_page_url((int) osc_search_page() + 2) : '',
));
glance_prime_rows((array) View::getInstance()->_get('items'));
glance_search_noindex();

osc_current_web_theme_path('common/header.php');
?>
<div class="wrap results-top">
    <?php if ($sr_cat > 0) { ?>
        <nav aria-label="<?php echo osc_esc_html(__('Breadcrumb', 'glance')); ?>">
            <ol class="crumbs">
                <li><a href="<?php echo osc_esc_html(osc_base_url()); ?>"><?php _e('Home', 'glance'); ?></a></li>
                <?php $sr_trail = glance_category_trail($sr_cat);
                foreach ($sr_trail as $sr_i => $sr_step) { ?>
                    <?php if ($sr_i === count($sr_trail) - 1) { ?>
                        <li aria-current="page"><?php echo osc_esc_html($sr_step['name']); ?></li>
                    <?php } else { ?>
                        <li><a href="<?php echo osc_esc_html($sr_step['url']); ?>"><?php echo osc_esc_html($sr_step['name']); ?></a></li>
                    <?php } ?>
                <?php } ?>
            </ol>
        </nav>
    <?php } ?>

    <div class="results-head">
        <div class="results-title">
            <h1 id="results-title"><?php echo osc_esc_html($sr_heading); ?></h1>
            <p class="results-count tnum"><?php printf(
                osc_esc_html(_n('%s ad', '%s ads', $sr_total, 'glance')),
                osc_esc_html(function_exists('osc_search_total_text') ? osc_search_total_text() : number_format($sr_total))
            ); ?></p>
        </div>

        <div class="results-tools">
            <?php osc_current_web_theme_path('common/filters.php'); ?>
            <?php if ($sr_total > 0) { ?>
                <form class="sort" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="get">
                    <input type="hidden" name="page" value="search">
                    <label for="glance-sort"><?php _e('Sort', 'glance'); ?></label>
                    <select id="glance-sort" name="glance_sort" data-sort>
                        <?php foreach (glance_sort_options() as $sr_o) { ?>
                            <option value="<?php echo osc_esc_html($sr_o['url']); ?>"<?php echo $sr_o['selected'] ? ' selected' : ''; ?>><?php echo osc_esc_html($sr_o['label']); ?></option>
                        <?php } ?>
                    </select>
                    <noscript><button type="submit" class="btn"><?php _e('Sort', 'glance'); ?></button></noscript>
                </form>
                <?php if ($sr_alerts !== 'off') { ?>
                    <button type="button" class="btn btn-quiet results-alert" popovertarget="glance-alert">
                        <?php echo glance_icon('bell'); ?><span class="results-alert-label"><?php _e('Email me new ads', 'glance'); ?></span>
                    </button>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <?php if ($sr_active && $sr_total > 0) { ?>
        <ul class="chips" aria-label="<?php echo osc_esc_html(__('Active filters', 'glance')); ?>">
            <?php foreach ($sr_active as $sr_f) { ?>
                <li><a class="chip" href="<?php echo osc_esc_html($sr_f['url']); ?>">
                    <span><?php echo osc_esc_html($sr_f['label']); ?></span><?php echo glance_icon('x'); ?><span class="visually-hidden"><?php _e('(remove)', 'glance'); ?></span>
                </a></li>
            <?php } ?>
            <?php if (count($sr_active) > 1) { ?>
                <li><a class="chip chip-clear" href="<?php echo osc_esc_html($sr_cat > 0 ? osc_search_url(array('sCategory' => $sr_cat)) : glance_browse_all_url()); ?>"><?php _e('Clear all', 'glance'); ?></a></li>
            <?php } ?>
        </ul>
    <?php } ?>
</div>

<div class="wrap ledger"<?php echo $sr_total === 0 || osc_count_items() === 0 ? ' data-empty' : ''; ?> data-ledger>
    <section class="ledger-list" aria-labelledby="results-title">
        <?php $sr_top = glance_hook_html('search_ads_listing_top');
        if ($sr_top !== '') { ?><div class="list-slot"><?php echo $sr_top; ?></div><?php } ?>

        <?php if ($sr_total === 0 || osc_count_items() === 0) { ?>
            <div class="list-empty">
                <h2><?php _e('No ads match', 'glance'); ?></h2>
                <?php if ($sr_active) { ?>
                    <p><?php _e('Remove a filter to widen the search:', 'glance'); ?></p>
                    <ul class="chips">
                        <?php foreach ($sr_active as $sr_f) { ?>
                            <li><a class="chip" href="<?php echo osc_esc_html($sr_f['url']); ?>">
                                <span><?php echo osc_esc_html($sr_f['label']); ?></span><?php echo glance_icon('x'); ?><span class="visually-hidden"><?php _e('(remove)', 'glance'); ?></span>
                            </a></li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p><?php _e('Nothing is listed here yet.', 'glance'); ?></p>
                <?php } ?>

                <?php if ($sr_alerts === 'open') { ?>
                    <div class="list-empty-alert">
                        <h3><?php _e('Hear about it first', 'glance'); ?></h3>
                        <p><?php _e('We email you when a new ad matches this search.', 'glance'); ?></p>
                        <?php osc_alert_form(); ?>
                    </div>
                <?php } elseif ($sr_alerts === 'sign-in') { ?>
                    <p><?php printf(
                        osc_esc_html(__('%s to get an email when a new ad matches this search.', 'glance')),
                        '<a href="' . osc_esc_html(osc_user_login_url()) . '">' . osc_esc_html(__('Sign in', 'glance')) . '</a>'
                    ); ?></p>
                <?php } ?>

                <p class="list-empty-actions">
                    <a class="btn" href="<?php echo osc_esc_html(glance_browse_all_url()); ?>"><?php _e('See all ads', 'glance'); ?></a>
                    <?php if (osc_item_post_url_in_category() !== '') { ?>
                        <a class="btn btn-quiet" href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>"><?php _e('Post an ad', 'glance'); ?></a>
                    <?php } ?>
                </p>
            </div>
        <?php } else { ?>
            <ol class="rows" data-rows data-density="<?php echo glance_list_density(); ?>">
                <?php $sr_n = 0;
                while (osc_has_items()) {
                    osc_current_web_theme_path('common/row.php');
                    if (++$sr_n % 8 === 0 && $sr_n < osc_count_items()) {
                        $sr_mid = glance_hook_html('search_ads_listing_medium');
                        if ($sr_mid !== '') { ?><li class="list-slot"><?php echo $sr_mid; ?></li><?php }
                    }
                } ?>
            </ol>
            <?php osc_current_web_theme_path('common/pager.php'); ?>
        <?php } ?>
    </section>

    <?php osc_current_web_theme_path('common/reader.php'); ?>
</div>

<?php if ($sr_total > 0 && $sr_alerts !== 'off') { ?>
    <div id="glance-alert" class="alert-pop" popover>
        <div class="saved-head">
            <h2><?php _e('Email me new ads', 'glance'); ?></h2>
            <button class="btn btn-quiet btn-icon" type="button" popovertarget="glance-alert" popovertargetaction="hide">
                <?php echo glance_icon('x', __('Close', 'glance')); ?>
            </button>
        </div>
        <?php if ($sr_alerts === 'open') { ?>
            <p class="alert-note"><?php printf(
                osc_esc_html(__('We email you when a new ad matches “%s”.', 'glance')),
                osc_esc_html($sr_heading)
            ); ?></p>
            <?php osc_alert_form(); ?>
        <?php } else { ?>
            <p class="alert-note"><?php printf(
                osc_esc_html(__('%s to get an email when a new ad matches this search.', 'glance')),
                '<a href="' . osc_esc_html(osc_user_login_url()) . '">' . osc_esc_html(__('Sign in', 'glance')) . '</a>'
            ); ?></p>
        <?php } ?>
    </div>
<?php } ?>
<?php osc_current_web_theme_path('common/footer.php'); ?>
