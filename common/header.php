<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Opens the document through the site header. Declared as the theme's chrome, so core renders
 * the pages it owns between this file and common/footer.php.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_logo     = glance_logo_url();
$glance_post_url = osc_item_post_url_in_category();
$glance_cat      = glance_search_category_id();
$glance_messages = glance_messaging_link();
// On account pages the account header carries Post an ad, so the bar does not repeat it.
$glance_band     = function_exists('glance_is_account_page') && glance_is_account_page();
?><!doctype html>
<html <?php osc_language_attributes(); ?>>
<head>
<?php osc_current_web_theme_path('common/head.php'); ?>
</head>
<body <?php osc_body_class(); ?>>
<a class="skip" href="#content"><?php _e('Skip to content', 'glance'); ?></a>

<header class="site-header">
    <div class="wrap bar">
        <a class="brand" href="<?php echo osc_esc_html(osc_base_url()); ?>">
            <?php if ($glance_logo !== '') { ?>
                <img src="<?php echo osc_esc_html($glance_logo); ?>" alt="<?php echo osc_esc_html(osc_page_title()); ?>" height="36">
            <?php } else { ?>
                <?php echo glance_mark(); ?>
                <span><?php echo osc_esc_html(osc_page_title()); ?></span>
            <?php } ?>
        </a>

        <search class="header-search">
            <form action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="get" role="search">
                <input type="hidden" name="page" value="search">
                <?php if ($glance_cat > 0) { ?>
                    <input type="hidden" name="sCategory" value="<?php echo $glance_cat; ?>">
                <?php } ?>
                <label class="visually-hidden" for="glance-q"><?php _e('Search ads', 'glance'); ?></label>
                <?php echo glance_icon('search'); ?>
                <input id="glance-q" type="search" name="sPattern" enterkeyhint="search" autocomplete="off"
                       value="<?php echo osc_esc_html(osc_search_pattern()); ?>"
                       placeholder="<?php echo osc_esc_html($glance_cat > 0 ? sprintf(__('Search in %s', 'glance'), glance_category_name($glance_cat)) : __('Search ads', 'glance')); ?>">
                <button type="submit"><?php _e('Search', 'glance'); ?></button>
            </form>
        </search>

        <nav class="nav-actions" aria-label="<?php echo osc_esc_html(__('Your site menu', 'glance')); ?>">
            <button class="nav-link nav-saved" type="button" popovertarget="glance-saved" data-saved-toggle>
                <?php echo glance_icon('heart'); ?>
                <span class="nav-label"><?php _e('Saved', 'glance'); ?></span>
                <span class="nav-count" data-saved-count hidden>0</span>
            </button>

            <?php echo $glance_messages; ?>

            <?php if (osc_users_enabled()) { ?>
                <?php if (osc_is_web_user_logged_in()) { ?>
                    <details class="account-menu">
                        <summary class="nav-link" aria-label="<?php echo osc_esc_html(__('Your account', 'glance')); ?>">
                            <?php echo glance_icon('user'); ?>
                            <span class="nav-label"><?php _e('Account', 'glance'); ?></span>
                        </summary>
                        <div class="menu-pop">
                            <div class="menu-head">
                                <strong><?php echo osc_esc_html(osc_logged_user_name()); ?></strong>
                                <span><?php echo osc_esc_html(osc_logged_user_email()); ?></span>
                            </div>
                            <a href="<?php echo osc_esc_html(osc_user_dashboard_url()); ?>"><?php echo glance_icon('layout-grid'); ?><?php _e('Dashboard', 'glance'); ?></a>
                            <a href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>"><?php echo glance_icon('list'); ?><?php _e('Your ads', 'glance'); ?></a>
                            <a href="<?php echo osc_esc_html(osc_user_alerts_url()); ?>"><?php echo glance_icon('bell'); ?><?php _e('Search alerts', 'glance'); ?></a>
                            <a href="<?php echo osc_esc_html(osc_user_profile_url()); ?>"><?php echo glance_icon('user'); ?><?php _e('Profile', 'glance'); ?></a>
                            <hr>
                            <a href="<?php echo osc_esc_html(osc_user_logout_url()); ?>"><?php echo glance_icon('log-out'); ?><?php _e('Log out', 'glance'); ?></a>
                        </div>
                    </details>
                <?php } else { ?>
                    <a class="nav-link" href="<?php echo osc_esc_html(osc_user_login_url()); ?>">
                        <?php echo glance_icon('user'); ?>
                        <span class="nav-label"><?php _e('Sign in', 'glance'); ?></span>
                    </a>
                <?php } ?>
            <?php } ?>

            <?php if ($glance_post_url !== '' && !$glance_band) { ?>
                <a class="btn btn-primary nav-post" href="<?php echo osc_esc_html($glance_post_url); ?>" aria-label="<?php echo osc_esc_html(__('Post an ad', 'glance')); ?>">
                    <?php echo glance_icon('plus'); ?><span class="nav-label"><?php _e('Post an ad', 'glance'); ?></span>
                </a>
            <?php } ?>
        </nav>
    </div>
    <?php $glance_main_menu = glance_saved_menu('main'); ?>
    <?php if ($glance_main_menu !== null) { ?>
        <nav class="glance-menu" aria-label="<?php echo osc_esc_html($glance_main_menu['label'] !== '' ? $glance_main_menu['label'] : __('Primary', 'glance')); ?>">
            <div class="wrap">
                <?php foreach ($glance_main_menu['items'] as $glance_link) { ?>
                    <a class="nav-link" href="<?php echo osc_esc_html((string) $glance_link['url']); ?>"<?php echo glance_menu_current((string) $glance_link['url']) ? ' aria-current="page"' : ''; ?>><?php echo osc_esc_html((string) $glance_link['label']); ?></a>
                <?php } ?>
            </div>
        </nav>
    <?php } ?>
    <?php glance_widget_zone('header', 'header-widgets'); ?>
</header>

<div id="glance-saved" class="saved-pop" popover>
    <div class="saved-head">
        <h2><?php _e('Saved ads', 'glance'); ?></h2>
        <button class="btn btn-quiet btn-icon" type="button" popovertarget="glance-saved" popovertargetaction="hide">
            <?php echo glance_icon('x', __('Close', 'glance')); ?>
        </button>
    </div>
    <p class="saved-note"><?php _e('Saved on this device only.', 'glance'); ?></p>
    <ul class="saved-list" data-saved-list data-remove-label="<?php echo osc_esc_html(__('Remove from saved', 'glance')); ?>" data-remove-icon="<?php echo osc_esc_html(glance_icon('x')); ?>"></ul>
    <p class="saved-empty" data-saved-empty><?php _e('Nothing saved yet. Tap the heart on any ad to keep it here.', 'glance'); ?></p>
</div>

<main id="content">
<?php
if ($glance_band) {
    glance_account_band();
} elseif (function_exists('glance_own_profile_note')) {
    glance_own_profile_note();
}
// A panel fetch must not consume a flash message meant for the next real page.
if (!glance_is_panel_request()) {
ob_start();
osc_show_flash_message();
$glance_flash = trim((string) ob_get_clean());
if ($glance_flash !== '') {
    echo '<div class="wrap flash-wrap" role="status">' . $glance_flash . '</div>';
}
}
