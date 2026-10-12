<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_cats = array();
osc_goto_first_category();
while (osc_has_categories() && count($glance_cats) < 12) {
    $glance_cats[] = array('name' => osc_category_name(), 'url' => osc_search_category_url());
}

View::getInstance()->_exportVariableToView('meta_noindex', true);
osc_add_filter('meta_title_filter', static fn() => sprintf(__('Page not found - %s', 'glance'), osc_page_title()));
glance_enqueue('account');
osc_current_web_theme_path('common/header.php');
?>
<div class="wrap">
    <section class="glance-notfound" aria-labelledby="glance-404-title">
        <h1 id="glance-404-title"><?php _e('We could not find that page', 'glance'); ?></h1>
        <p class="glance-lead"><?php _e('The ad may have sold, expired or been removed, or the link is mistyped. Search for what you were after.', 'glance'); ?></p>

        <form class="glance-notfound-search" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="get" role="search">
            <input type="hidden" name="page" value="search">
            <label class="visually-hidden" for="glance-404-q"><?php _e('Search ads', 'glance'); ?></label>
            <input id="glance-404-q" type="search" name="sPattern" enterkeyhint="search" autocomplete="off" placeholder="<?php echo osc_esc_html(__('Search ads', 'glance')); ?>">
            <button class="btn btn-primary" type="submit"><?php _e('Search', 'glance'); ?></button>
        </form>

        <p class="glance-links">
            <a class="btn" href="<?php echo osc_esc_html(glance_browse_all_url()); ?>"><?php _e('Browse all ads', 'glance'); ?></a>
            <a class="btn btn-quiet" href="<?php echo osc_esc_html(osc_base_url()); ?>"><?php _e('Go to the home page', 'glance'); ?></a>
        </p>

        <?php if ($glance_cats) { ?>
            <h2><?php _e('Browse by category', 'glance'); ?></h2>
            <ul class="glance-cats">
                <?php foreach ($glance_cats as $glance_cat) { ?>
                    <li><a href="<?php echo osc_esc_html($glance_cat['url']); ?>"><?php echo osc_esc_html($glance_cat['name']); ?></a></li>
                <?php } ?>
            </ul>
        <?php } ?>
    </section>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
