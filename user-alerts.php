<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Saved searches that email the member about new ads. Stopping one is core's own link;
 * account.js stops it through the API when it can.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

glance_enqueue('account');
osc_current_web_theme_path('common/header.php');
glance_account_open(__('Search alerts', 'glance'));
osc_run_hook('account_page_before', 'user-alerts');
?>
<p class="acct-lede"><?php _e('Each alert is a saved search. You get an email when a new ad matches it.', 'glance'); ?></p>

<div class="acct-empty" data-alerts-empty<?php echo osc_count_alerts() > 0 ? ' hidden' : ''; ?>>
    <p class="acct-empty-title"><?php _e('No search alerts yet', 'glance'); ?></p>
    <p><?php _e('Run a search, then choose “Email me new ads”.', 'glance'); ?></p>
    <a class="btn" href="<?php echo osc_esc_html(glance_browse_all_url()); ?>"><?php echo glance_icon('search'); ?><span><?php _e('Browse ads', 'glance'); ?></span></a>
</div>

<?php if (osc_count_alerts() > 0) { ?>
    <ul class="alert-list" data-alerts
        data-confirm-title="<?php echo osc_esc_html(__('Stop this alert?', 'glance')); ?>"
        data-confirm-text="<?php echo osc_esc_html(__('You will get no more emails for %s. You can save the search again at any time.', 'glance')); ?>"
        data-confirm-ok="<?php echo osc_esc_html(__('Stop alert', 'glance')); ?>"<?php echo glance_account_i18n(); ?>>
        <?php while (osc_has_alerts()) {
            $glance_phrases = glance_alert_phrases();
            $glance_summary = implode(' · ', $glance_phrases);
            $glance_search  = glance_alert_search_url();
            $glance_active  = osc_alert_is_active();
            $glance_weekly  = strtoupper(osc_alert_type()) === 'WEEKLY';
            $glance_actions = array();
            if ($glance_active) {
                $glance_actions['unsubscribe'] = array(
                    'label' => __('Stop alert', 'glance'),
                    'url'   => osc_user_unsubscribe_alert_url('', (string) osc_alert_field('s_email')),
                );
            }
            $glance_actions = (array) osc_apply_filter('alert_row_actions', $glance_actions, osc_alert());
            ?>
            <li class="alert-row" data-alert data-id="<?php echo (int) osc_alert_id(); ?>" data-title="<?php echo osc_esc_html($glance_summary); ?>">
                <span class="alert-icon"><?php echo glance_icon('bell'); ?></span>
                <div class="alert-main">
                    <h3 class="alert-title">
                        <?php foreach ($glance_phrases as $glance_p) { ?>
                            <span class="alert-term"><?php echo osc_esc_html($glance_p); ?></span>
                        <?php } ?>
                    </h3>
                    <p class="alert-facts">
                        <?php if (!$glance_active) { ?>
                            <span class="ad-status is-expired"><?php _e('Stopped', 'glance'); ?></span>
                        <?php } ?>
                        <span><?php echo $glance_weekly ? osc_esc_html(__('Weekly email', 'glance')) : osc_esc_html(__('Daily email', 'glance')); ?></span>
                        <span><?php printf(osc_esc_html(__('Saved %s', 'glance')), '<time datetime="' . osc_esc_html(glance_iso_date(osc_alert_date())) . '">' . osc_esc_html(osc_format_date(osc_alert_date())) . '</time>'); ?></span>
                    </p>
                    <?php if (osc_count_items() > 0) { ?>
                        <ul class="alert-matches" aria-label="<?php echo osc_esc_html(__('Newest matches', 'glance')); ?>">
                            <?php while (osc_has_items()) { ?>
                                <li>
                                    <a href="<?php echo osc_esc_html(osc_item_url()); ?>">
                                        <span class="alert-match-title"><?php echo osc_esc_html(osc_item_title()); ?></span>
                                        <span class="alert-match-price tnum"><?php echo glance_price_html(); ?></span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <p class="alert-none"><?php _e('No ads match it right now.', 'glance'); ?></p>
                    <?php } ?>
                </div>
                <div class="alert-actions">
                    <?php if ($glance_search !== '') { ?>
                        <a class="btn btn-quiet ad-act" href="<?php echo osc_esc_html($glance_search); ?>"><?php echo glance_icon('search'); ?><span><?php _e('See results', 'glance'); ?></span></a>
                    <?php }
                    foreach ($glance_actions as $glance_key => $glance_action) {
                        if (!is_array($glance_action) || !isset($glance_action['label'], $glance_action['url'])) {
                            continue;
                        }
                        if ($glance_key === 'unsubscribe') {
                            echo str_replace('<a class=', '<a data-alert-stop class=', glance_ad_action($glance_action, 'btn btn-quiet ad-act ad-act-danger', 'x'));
                        } else {
                            echo glance_ad_action($glance_action, 'btn btn-quiet ad-act');
                        }
                    } ?>
                </div>
            </li>
        <?php } ?>
    </ul>
    <?php glance_account_dialog(); ?>
<?php } ?>
<?php
osc_run_hook('account_page_after', 'user-alerts');
glance_account_close();
osc_current_web_theme_path('common/footer.php');
