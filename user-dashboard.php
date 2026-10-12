<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Account overview: what the member holds, the latest ads, and where to go next.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

// Core hands the dashboard live ads only; the overview shows the newest ads in any state.
$glance_rows = Item::getInstance()->findItemTypesByUserID((int) osc_logged_user_id(), 0, 5, 'all');
View::getInstance()->_exportVariableToView('items', $glance_rows);
glance_prime_rows($glance_rows);

$glance_c     = glance_account_counts();
$glance_allow = glance_account_allowance();
$glance_stats = array(
    array($glance_c['active'], _n('Live ad', 'Live ads', $glance_c['active'], 'glance'), osc_user_list_items_url('', 'active')),
    array($glance_c['pending'], __('Awaiting approval', 'glance'), osc_user_list_items_url('', 'pending')),
    array($glance_c['expired'], __('Expired', 'glance'), osc_user_list_items_url('', 'expired')),
    array($glance_c['alerts'], _n('Search alert', 'Search alerts', $glance_c['alerts'], 'glance'), osc_user_alerts_url()),
);


glance_enqueue('account');
osc_current_web_theme_path('common/header.php');
glance_account_open(__('Dashboard', 'glance'));
osc_run_hook('account_page_before', 'user-dashboard');
?>
<section class="dash-stats" aria-labelledby="dash-stats-title">
    <h2 id="dash-stats-title" class="visually-hidden"><?php _e('Your numbers', 'glance'); ?></h2>
    <ul class="dash-stat-list">
        <?php foreach ($glance_stats as $glance_s) { ?>
            <li>
                <a class="dash-stat<?php echo $glance_s[0] === 0 ? ' is-zero' : ''; ?>" href="<?php echo osc_esc_html($glance_s[2]); ?>">
                    <span class="dash-stat-num tnum"><?php echo (int) $glance_s[0]; ?></span>
                    <span class="dash-stat-label"><?php echo osc_esc_html($glance_s[1]); ?></span>
                </a>
            </li>
        <?php } ?>
    </ul>
    <?php if ($glance_allow !== null) { ?>
        <p class="allowance">
            <span><?php printf(osc_esc_html(__('%1$s of %2$s ad slots in use', 'glance')), '<span class="tnum">' . (int) $glance_allow[0] . '</span>', '<span class="tnum">' . (int) $glance_allow[1] . '</span>'); ?></span>
            <meter min="0" max="<?php echo max(1, (int) $glance_allow[1]); ?>" optimum="0" high="<?php echo max(1, (int) $glance_allow[1]) - 0.5; ?>" value="<?php echo min((int) $glance_allow[0], max(1, (int) $glance_allow[1])); ?>"></meter>
        </p>
    <?php } ?>
</section>

<section class="dash-section" aria-labelledby="dash-latest">
    <div class="dash-head">
        <h2 id="dash-latest"><?php _e('Latest ads', 'glance'); ?></h2>
        <?php if ($glance_c['all'] > 0) { ?>
            <a class="dash-more" href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>"><?php printf(osc_esc_html(__('All %s ads', 'glance')), '<span class="tnum">' . $glance_c['all'] . '</span>'); ?><?php echo glance_icon('chevron-right'); ?></a>
        <?php } ?>
    </div>
    <?php if (osc_count_items() === 0) { ?>
        <div class="acct-empty">
            <p class="acct-empty-title"><?php _e('You have no ads yet', 'glance'); ?></p>
            <p><?php _e('Your ads will show here with their views and end dates.', 'glance'); ?></p>
        </div>
    <?php } else { ?>
        <ul class="ad-list is-compact">
            <?php while (osc_has_items()) {
                glance_ad_row(false, 'dashboard');
            } ?>
        </ul>
    <?php } ?>
</section>

<?php osc_run_hook('user_dashboard'); ?>

<ul class="dash-links">
    <li>
        <a href="<?php echo osc_esc_html(osc_user_public_profile_url(osc_logged_user_id())); ?>">
            <?php echo glance_icon('store'); ?>
            <span><strong><?php _e('See your public page', 'glance'); ?></strong><span><?php _e('What buyers see about you', 'glance'); ?></span></span>
        </a>
    </li>
</ul>
<?php
osc_run_hook('account_page_after', 'user-dashboard');
glance_account_close();
osc_current_web_theme_path('common/footer.php');
