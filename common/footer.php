<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_note = trim((string) glance_setting('footer_note'));
// A link the saved footer menu already shows is not repeated in the other columns.
$glance_footer_menu = glance_saved_menu('footer');
$glance_taken = array();
foreach ($glance_footer_menu['items'] ?? array() as $glance_link) {
    $glance_taken[glance_url_key((string) $glance_link['url'])] = true;
}
$glance_link_shown = static fn(string $url): bool => !isset($glance_taken[glance_url_key($url)]);
osc_reset_static_pages();
$glance_pages = array();
while (osc_has_static_pages()) {
    if ($glance_link_shown(osc_static_page_url())) {
        $glance_pages[] = array(osc_static_page_url(), osc_static_page_title());
    }
}
$glance_contact_shown = $glance_link_shown(osc_contact_url());
?>
</main>

<footer class="site-footer">
    <?php glance_widget_zone('footer', 'footer-widgets'); ?>
    <div class="wrap cols">
        <div class="about">
            <h2><?php echo osc_esc_html(osc_page_title()); ?></h2>
            <?php if ($glance_note !== '') { ?>
                <p><?php echo osc_esc_html($glance_note); ?></p>
            <?php } elseif (osc_page_description() !== '') { ?>
                <p><?php echo osc_esc_html(osc_page_description()); ?></p>
            <?php } ?>
        </div>

        <nav aria-labelledby="glance-f-ads">
            <h2 id="glance-f-ads"><?php _e('Ads', 'glance'); ?></h2>
            <ul>
                <li><a href="<?php echo osc_esc_html(glance_browse_all_url()); ?>"><?php _e('All ads', 'glance'); ?></a></li>
                <?php if (osc_item_post_url_in_category() !== '' && $glance_link_shown(osc_item_post_url_in_category())) { ?>
                    <li><a href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>"><?php _e('Post an ad', 'glance'); ?></a></li>
                <?php } ?>
                <li><a href="<?php echo osc_esc_html(osc_search_url(array('sFeed' => 'rss'))); ?>"><?php _e('RSS feed', 'glance'); ?></a></li>
            </ul>
        </nav>

        <?php if ($glance_footer_menu !== null) { ?>
            <nav aria-labelledby="glance-f-menu">
                <h2 id="glance-f-menu"><?php echo osc_esc_html($glance_footer_menu['label'] !== '' ? $glance_footer_menu['label'] : __('Links', 'glance')); ?></h2>
                <ul>
                    <?php foreach ($glance_footer_menu['items'] as $glance_link) { ?>
                        <li><a href="<?php echo osc_esc_html((string) $glance_link['url']); ?>"><?php echo osc_esc_html((string) $glance_link['label']); ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>

        <?php if (osc_users_enabled()) { ?>
            <nav aria-labelledby="glance-f-account">
                <h2 id="glance-f-account"><?php _e('Account', 'glance'); ?></h2>
                <ul>
                    <?php if (osc_is_web_user_logged_in()) { ?>
                        <li><a href="<?php echo osc_esc_html(osc_user_dashboard_url()); ?>"><?php _e('Dashboard', 'glance'); ?></a></li>
                        <li><a href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>"><?php _e('Your ads', 'glance'); ?></a></li>
                        <li><a href="<?php echo osc_esc_html(osc_user_logout_url()); ?>"><?php _e('Log out', 'glance'); ?></a></li>
                    <?php } else { ?>
                        <li><a href="<?php echo osc_esc_html(osc_user_login_url()); ?>"><?php _e('Sign in', 'glance'); ?></a></li>
                        <?php if (osc_user_registration_enabled()) { ?>
                            <li><a href="<?php echo osc_esc_html(osc_register_account_url()); ?>"><?php _e('Create an account', 'glance'); ?></a></li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>

        <?php if ($glance_pages !== array() || $glance_contact_shown) { ?>
            <nav aria-labelledby="glance-f-site">
                <h2 id="glance-f-site"><?php _e('About', 'glance'); ?></h2>
                <ul>
                    <?php foreach ($glance_pages as $glance_page) { ?>
                        <li><a href="<?php echo osc_esc_html($glance_page[0]); ?>"><?php echo osc_esc_html($glance_page[1]); ?></a></li>
                    <?php } ?>
                    <?php if ($glance_contact_shown) { ?>
                        <li><a href="<?php echo osc_esc_html(osc_contact_url()); ?>"><?php _e('Contact us', 'glance'); ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>
    </div>

    <div class="wrap legal">
        <span>&copy; <?php echo date('Y'); ?> <?php echo osc_esc_html(osc_page_title()); ?></span>
        <?php if (function_exists('osc_count_web_enabled_locales') && osc_count_web_enabled_locales() > 1) { ?>
            <form class="lang-switch" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="get">
                <input type="hidden" name="page" value="language">
                <label class="visually-hidden" for="glance-lang"><?php _e('Language', 'glance'); ?></label>
                <select id="glance-lang" name="locale" data-autosubmit>
                    <?php osc_goto_first_locale(); ?>
                    <?php while (osc_has_web_enabled_locales()) { ?>
                        <option value="<?php echo osc_esc_html(osc_locale_code()); ?>" <?php echo osc_locale_code() === osc_current_user_locale() ? 'selected' : ''; ?>><?php echo osc_esc_html(osc_locale_name()); ?></option>
                    <?php } ?>
                </select>
                <noscript><button class="btn" type="submit"><?php _e('Go', 'glance'); ?></button></noscript>
            </form>
        <?php } ?>
        <div class="theme-switch" role="group" aria-label="<?php echo osc_esc_html(__('Colour theme', 'glance')); ?>" data-theme-switch hidden>
            <button type="button" data-theme-set="light" aria-pressed="false"><?php echo glance_icon('sun'); ?><?php _e('Light', 'glance'); ?></button>
            <button type="button" data-theme-set="auto" aria-pressed="true"><?php echo glance_icon('sun-moon'); ?><?php _e('Auto', 'glance'); ?></button>
            <button type="button" data-theme-set="dark" aria-pressed="false"><?php echo glance_icon('moon'); ?><?php _e('Dark', 'glance'); ?></button>
        </div>
    </div>
</footer>
<?php osc_run_hook('footer'); ?>
</body>
</html>
