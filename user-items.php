<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The seller's ads as a ledger. Every action is a real link or form post; account.js turns
 * delete into an API call with a confirm dialog when it can.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

require_once ABS_PATH . 'oc-includes/osclass/gui/page-fn.php';

$glance_type  = (string) View::getInstance()->_get('items_type');
$glance_type  = $glance_type === '' ? 'all' : $glance_type;
$glance_c     = glance_account_counts();
$glance_allow = glance_account_allowance();
$glance_tabs  = array(
    'all'     => array(__('All', 'glance'), $glance_c['all']),
    'active'  => array(__('Live', 'glance'), $glance_c['active']),
    'pending' => array(__('Awaiting approval', 'glance'), $glance_c['pending']),
    'expired' => array(__('Expired', 'glance'), $glance_c['expired']),
);
$glance_rows = (array) View::getInstance()->_get('items');
glance_prime_rows($glance_rows);

glance_enqueue('account');
osc_current_web_theme_path('common/header.php');
glance_account_open(__('Your ads', 'glance'));
osc_run_hook('account_page_before', 'user-items');
?>
<div class="ads-bar">
    <nav class="oe-tabs" aria-label="<?php echo osc_esc_html(__('Show ads by state', 'glance')); ?>">
        <?php foreach ($glance_tabs as $glance_key => $glance_tab) { ?>
            <a href="<?php echo osc_esc_html(osc_user_list_items_url('', $glance_key === 'all' ? '' : $glance_key)); ?>"<?php echo $glance_type === $glance_key ? ' aria-current="page"' : ''; ?>>
                <?php echo osc_esc_html($glance_tab[0]); ?> <span class="tab-count tnum" data-count="<?php echo $glance_key; ?>"><?php echo (int) $glance_tab[1]; ?></span>
            </a>
        <?php } ?>
    </nav>
    <?php if ($glance_allow !== null) { ?>
        <p class="allowance">
            <span><?php printf(osc_esc_html(__('%1$s of %2$s ad slots in use', 'glance')), '<span class="tnum">' . (int) $glance_allow[0] . '</span>', '<span class="tnum">' . (int) $glance_allow[1] . '</span>'); ?></span>
            <meter min="0" max="<?php echo max(1, (int) $glance_allow[1]); ?>" optimum="0" high="<?php echo max(1, (int) $glance_allow[1]) - 0.5; ?>" value="<?php echo min((int) $glance_allow[0], max(1, (int) $glance_allow[1])); ?>"></meter>
        </p>
    <?php } ?>
</div>

<?php
// The limit, the next ad's price and paused bumps as one notice; core 7.1 prints the price lines.
$glance_note = '';
if ($glance_allow !== null && !osc_user_can_publish()) {
    if (function_exists('osc_item_pay_url')) {
        $slotInline = true;
        ob_start();
        require ABS_PATH . 'oc-includes/osclass/gui/billing/slot-prompt.php';
        $glance_note = trim((string) ob_get_clean());
    }
    if ($glance_note === '') {
        $glance_note = '<p>' . osc_esc_html(osc_listing_limit_message()) . '</p>';
    }
}
$glance_paused = function_exists('osc_billing_bump_paused_message') ? osc_billing_bump_paused_message() : '';
if ($glance_paused !== '') {
    $glance_note .= '<p>' . osc_esc_html($glance_paused) . '</p>';
}
if ($glance_note !== '') { ?>
    <div class="acct-note acct-note--box" role="note"><?php echo $glance_note; ?></div>
<?php } ?>

<div class="acct-empty" data-ads-empty<?php echo osc_count_items() > 0 ? ' hidden' : ''; ?>>
    <p class="acct-empty-title"><?php echo $glance_type === 'all' ? osc_esc_html(__('You have no ads yet', 'glance')) : osc_esc_html(__('No ads in this state', 'glance')); ?></p>
    <p><?php echo $glance_type === 'all'
        ? osc_esc_html(__('Post one and it shows here with its views and end date.', 'glance'))
        : osc_esc_html(__('Ads move here as their state changes.', 'glance')); ?></p>
    <?php if ($glance_type !== 'all') { ?>
        <a class="btn" href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>"><?php _e('Show all ads', 'glance'); ?></a>
    <?php } ?>
</div>

<?php if (osc_count_items() > 0) { ?>
    <div class="ad-ledger" data-ads
         data-confirm-title="<?php echo osc_esc_html(__('Delete this ad?', 'glance')); ?>"
         data-confirm-text="<?php echo osc_esc_html(__('“%s” and its photos are removed for good. Buyers can no longer open it.', 'glance')); ?>"
         data-confirm-ok="<?php echo osc_esc_html(__('Delete ad', 'glance')); ?>"<?php echo glance_account_i18n(); ?>>
        <div class="ad-head" aria-hidden="true">
            <span class="ad-head-ad"><?php _e('Ad', 'glance'); ?></span>
            <span class="ad-head-views"><?php _e('Views', 'glance'); ?></span>
            <span class="ad-head-ends"><?php _e('Ends', 'glance'); ?></span>
            <span class="ad-head-price"><?php _e('Price', 'glance'); ?></span>
        </div>
        <ul class="ad-list">
            <?php while (osc_has_items()) {
                glance_ad_row(true, 'user_items');
            } ?>
        </ul>
    </div>
    <?php osc_gui_print_pager(); ?>
    <?php glance_account_dialog(); ?>
<?php } ?>
<?php
osc_run_hook('account_page_after', 'user-items');
glance_account_close();
osc_current_web_theme_path('common/footer.php');
