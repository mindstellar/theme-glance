<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The signed-in account area. Dashboard, your ads and alerts are Glance views; the other
 * account, sign-in, contact and credits pages are drawn by core inside the theme chrome.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/** Whether core renders this request itself (account, auth, contact, credits, plugin pages). */
function glance_is_core_page(): bool
{
    $location = (string) osc_get_osclass_location();
    $section  = (string) osc_get_osclass_section();

    if (in_array($location, array('user', 'login', 'register', 'custom', 'contact', 'billing', 'messages'), true)) {
        return true;
    }

    return $location === 'item' && in_array($section, array('contact', 'send_friend', 'contact_post'), true);
}

function glance_account_surface(): void
{
    if (!OC_ADMIN && glance_is_core_page()) {
        glance_enqueue('account');
    }
}

osc_add_hook('header', 'glance_account_surface', 0);

/** True on the private pages of the signed-in member, where the account header shows. */
function glance_is_account_page(): bool
{
    if (!osc_is_web_user_logged_in() || glance_is_panel_request()) {
        return false;
    }

    $location = (string) osc_get_osclass_location();
    $section  = (string) osc_get_osclass_section();

    if ($location === 'billing' || $location === 'messages') {
        return true;
    }
    if ($location === 'custom') {
        return (bool) Params::getParam('in_user_menu');
    }

    return $location === 'user' && in_array($section, array(
        'dashboard', 'items', 'alerts', 'profile', 'change_email', 'change_username', 'change_password',
        'delete', 'api_access', 'api_access_post',
    ), true);
}

/** Glance's names for core's account nav entries, so the nav and the header menu agree. */
function glance_account_menu(array $items): array
{
    $names = array(
        'opt_items'  => __('Your ads', 'glance'),
        'opt_alerts' => __('Search alerts', 'glance'),
    );

    foreach ($items as $i => $item) {
        $class = is_array($item) ? (string) ($item['class'] ?? '') : '';
        // The public page has no account frame, so a nav entry would strand the member there.
        if ($class === 'opt_publicprofile') {
            unset($items[$i]);
            continue;
        }
        if (isset($names[$class])) {
            $items[$i]['name'] = $names[$class];
        }
    }

    return array_values($items);
}

osc_add_filter('user_menu_filter', 'glance_account_menu');

/** On the member's own public page, a strip that says so and leads back to the account. */
function glance_own_profile_note(): void
{
    if (!osc_is_web_user_logged_in() || glance_is_panel_request() || !osc_is_public_profile()) {
        return;
    }
    if ((int) osc_user_id() !== (int) osc_logged_user_id()) {
        return;
    }
    ?>
    <div class="glance-own-note">
        <div class="wrap glance-own-note-in">
            <p><?php echo glance_icon('eye'); ?><?php _e('This is how buyers see you.', 'glance'); ?></p>
            <a href="<?php echo osc_esc_html(osc_user_dashboard_url()); ?>"><?php echo glance_icon('arrow-left'); ?><?php _e('Back to your account', 'glance'); ?></a>
        </div>
    </div>
    <?php
}

/**
 * The member's ad and alert counts, read once per request. Keys: all, active, pending,
 * expired, alerts.
 */
function glance_account_counts(): array
{
    static $counts = null;

    if ($counts !== null) {
        return $counts;
    }

    $uid    = (int) osc_logged_user_id();
    $items  = Item::getInstance();
    $counts = array(
        'all'     => (int) $items->countItemTypesByUserID($uid, 'all'),
        'active'  => (int) $items->countItemTypesByUserID($uid, 'active'),
        'pending' => (int) $items->countItemTypesByUserID($uid, 'pending_validate'),
        'expired' => (int) $items->countItemTypesByUserID($uid, 'expired'),
        'alerts'  => count((new \mindstellar\search\UserAlerts())->live($uid)),
    );

    return $counts;
}

/** The signed-in member's own row, read once. */
function glance_account_user(): array
{
    static $user = null;

    if ($user === null) {
        $row  = User::getInstance()->findByPrimaryKey((int) osc_logged_user_id());
        $user = is_array($row) ? $row : array();
    }

    return $user;
}

/** Avatar markup: the member's picture, or their initials on a tinted disc. */
function glance_account_avatar(string $name, int $size = 48): string
{
    if (osc_get_preference('enabled_user_avatars') && osc_has_user_avatar()) {
        return '<img class="glance-avatar" src="' . osc_esc_html(osc_user_avatar_url(null, 'thumbnail')) . '" alt="" width="' . $size . '" height="' . $size . '" decoding="async">';
    }

    $words    = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: array('?');
    $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));

    return '<span class="glance-avatar glance-avatar-empty" aria-hidden="true">' . osc_esc_html($initials) . '</span>';
}

/**
 * The account header: who is signed in, what they hold, and Post an ad. Printed by the site
 * header on every private account page, so core's own pages get it too.
 */
function glance_account_band(): void
{
    if (!glance_is_account_page()) {
        return;
    }

    $user  = glance_account_user();
    $name  = (string) osc_logged_user_name();
    $since = (string) ($user['dt_reg_date'] ?? '');
    $post  = osc_item_post_url_in_category();
    $facts = array();

    if ($since !== '') {
        $facts[] = '<span>' . osc_esc_html(sprintf(__('Member since %s', 'glance'), osc_format_date($since, 'F Y'))) . '</span>';
    }

    // The dashboard shows the same counts in full, so the header keeps to who you are there.
    if (!osc_is_user_dashboard()) {
        $c       = glance_account_counts();
        $facts[] = '<a href="' . osc_esc_html(osc_user_list_items_url('', 'active')) . '"><span class="tnum" data-count="active">' . $c['active'] . '</span> ' . osc_esc_html(_n('live ad', 'live ads', $c['active'], 'glance')) . '</a>';
        $facts[] = '<a href="' . osc_esc_html(osc_user_alerts_url()) . '"><span class="tnum" data-count="alerts">' . $c['alerts'] . '</span> ' . osc_esc_html(_n('search alert', 'search alerts', $c['alerts'], 'glance')) . '</a>';
    }
    ?>
    <section class="glance-band" aria-label="<?php echo osc_esc_html(__('Your account', 'glance')); ?>">
        <div class="wrap glance-band-in">
            <?php echo glance_account_avatar($name); ?>
            <div class="glance-band-who">
                <p class="glance-band-name"><?php echo osc_esc_html($name); ?></p>
                <?php if ($facts) { ?>
                    <p class="glance-band-facts"><?php echo implode('', $facts); ?></p>
                <?php } ?>
            </div>
            <?php if ($post !== '') { ?>
                <a class="btn btn-primary glance-band-post" href="<?php echo osc_esc_html($post); ?>"><?php echo glance_icon('plus'); ?><span><?php _e('Post an ad', 'glance'); ?></span></a>
            <?php } ?>
        </div>
    </section>
    <?php
}

/** Opens the account page frame core uses, so Glance's views and core's pages share one layout. */
function glance_account_open(string $heading): void
{
    echo '<div class="oe-page glance-account"><div class="oe-doc">';
    echo '<h1 class="oe-h1">' . osc_esc_html($heading) . '</h1>';
    echo '<div class="oe-account"><div class="oe-account-main">';
}

/** Closes the frame with core's own account nav, plugin entries and hook included. */
function glance_account_close(): void
{
    echo '</div>';
    require ABS_PATH . 'oc-includes/osclass/gui/account/nav.php';
    echo '</div></div></div>';
}

/** The current item's state for its owner: [key, label]. The first state the seller must act on wins. */
function glance_ad_status(): array
{
    if (!osc_item_is_enabled()) {
        return array('blocked', __('Blocked', 'glance'));
    }
    if (osc_item_is_spam()) {
        return array('blocked', __('Marked as spam', 'glance'));
    }
    if (!osc_item_is_active()) {
        return array('pending', __('Awaiting approval', 'glance'));
    }
    if (osc_item_is_expired()) {
        return array('expired', __('Expired', 'glance'));
    }

    return array('live', __('Live', 'glance'));
}

/** "Expires in 12 days", "Expired 3 days ago", "No end date"… for the current item. */
function glance_ad_expiry(): array
{
    $raw = (string) osc_item_dt_expiration();

    if (osc_item_is_premium() || $raw === '' || strpos($raw, '9999') === 0 || ($stamp = strtotime($raw)) === false) {
        return array('', __('No end date', 'glance'), false);
    }

    $days = (int) ceil(($stamp - time()) / 86400);

    if ($days > 0) {
        $label = $days === 1 ? __('Ends tomorrow', 'glance') : sprintf(_n('Ends in %d day', 'Ends in %d days', $days, 'glance'), $days);

        return array(glance_iso_date($raw), $label, $days <= 7);
    }

    $ago = max(0, -$days);

    return array(glance_iso_date($raw), $ago === 0 ? __('Ended today', 'glance') : sprintf(_n('Ended %d day ago', 'Ended %d days ago', $ago, 'glance'), $ago), false);
}

/** Views of the current item as a number, or null when the site hides views. */
function glance_ad_views(): ?int
{
    return osc_item_views_enabled() ? (int) osc_item_views() : null;
}

/** The listing limit as [used, limit], or null when the site sets none. */
function glance_account_allowance(): ?array
{
    $limit = osc_user_listing_limit();

    return $limit === -1 ? null : array(osc_user_listings_used(), $limit);
}

/** Strings account.js shows, from the view (translated here, not in the script). */
function glance_account_i18n(): string
{
    $strings = array(
        'deleted'      => __('“%s” was deleted.', 'glance'),
        'alertStopped' => __('Alert stopped: %s.', 'glance'),
        'working'      => __('Working…', 'glance'),
        'failed'       => __('That did not work. Trying again the standard way…', 'glance'),
    );

    $out = '';
    foreach ($strings as $key => $text) {
        $out .= ' data-msg-' . strtolower((string) preg_replace('/([A-Z])/', '-$1', $key)) . '="' . osc_esc_html($text) . '"';
    }

    return $out;
}

/** The shared confirm dialog for destructive account actions. Filled per action by account.js. */
function glance_account_dialog(): void
{
    ?>
    <dialog class="glance-confirm" data-account-confirm aria-labelledby="glance-confirm-title" aria-describedby="glance-confirm-text">
        <form method="dialog">
            <h2 id="glance-confirm-title" data-confirm-title></h2>
            <p id="glance-confirm-text" data-confirm-text></p>
            <div class="glance-confirm-actions">
                <button class="btn" type="submit" value="cancel" autofocus><?php _e('Keep it', 'glance'); ?></button>
                <button class="btn btn-danger-solid" type="submit" value="ok" data-confirm-ok></button>
            </div>
        </form>
    </dialog>
    <p class="visually-hidden" role="status" aria-live="polite" data-account-live></p>
    <?php
}

/** An alert's filters in plain words, one short phrase each. */
function glance_alert_phrases(): array
{
    $out = array();

    foreach (osc_alert_criteria() as $key => $part) {
        $value = (string) $part['value'];

        switch ($key) {
            case 'held':
                $out[] = $value;
                break;
            case 'pattern':
                $out[] = '“' . $value . '”';
                break;
            case 'price':
                if (strpos($value, '≤ ') === 0) {
                    $out[] = sprintf(__('up to %s', 'glance'), substr($value, strlen('≤ ')));
                } elseif (strpos($value, '≥ ') === 0) {
                    $out[] = sprintf(__('from %s', 'glance'), substr($value, strlen('≥ ')));
                } else {
                    $out[] = sprintf(__('priced %s', 'glance'), $value);
                }
                break;
            case 'picture':
                $out[] = __('with photos', 'glance');
                break;
            case 'premium':
                $out[] = __('featured only', 'glance');
                break;
            default:
                $out[] = sprintf(__('in %s', 'glance'), $value);
        }
    }

    return $out ?: array(__('Every new ad', 'glance'));
}

/** The search an alert runs, as a results URL, or '' when it cannot be rebuilt. */
function glance_alert_search_url(): string
{
    $params = \mindstellar\search\AlertEnvelope::validate((string) osc_alert_field('s_search'));

    if (!is_array($params)) {
        return '';
    }

    $keep = array('sPattern', 'sCategory', 'sCountry', 'sRegion', 'sCity', 'sCityArea', 'sPriceMin', 'sPriceMax', 'bPic', 'bPremium');
    $url  = array('page' => 'search');

    foreach ($keep as $key) {
        if (!isset($params[$key]) || $params[$key] === '' || $params[$key] === array()) {
            continue;
        }
        $value = is_array($params[$key]) ? implode(',', array_map('strval', $params[$key])) : (string) $params[$key];
        if ($value !== '') {
            $url[$key] = $value;
        }
    }

    return osc_search_url($url);
}

/** One own-ad action in core's listing_row_actions shape, as a button or link. */
function glance_ad_action(array $action, string $class, string $icon = ''): string
{
    $label   = osc_esc_html((string) $action['label']);
    $inner   = ($icon !== '' ? glance_icon($icon) : '') . '<span>' . $label . '</span>';
    $confirm = !empty($action['confirm']) ? ' data-confirm="' . osc_esc_html((string) $action['confirm']) . '"' : '';
    $url     = (string) $action['url'];

    if (($action['method'] ?? 'get') !== 'post') {
        return '<a class="' . $class . '" href="' . osc_esc_html($url) . '"' . $confirm . '>' . $inner . '</a>';
    }

    // The CSRF token only goes to this site, never to a URL a plugin points elsewhere.
    $root  = rtrim(osc_base_url(), '/') . '/';
    $local = !preg_match('/[\x00-\x20\\\\]/', $url) && (strpos($url, $root) === 0 || preg_match('#^/(?!/)#', $url));
    $html  = '<form class="glance-inline-form nocsrf" method="post" action="' . osc_esc_html($url) . '">' . ($local ? osc_csrf_token_form() : '');
    foreach ((array) ($action['fields'] ?? array()) as $name => $value) {
        $html .= '<input type="hidden" name="' . osc_esc_html((string) $name) . '" value="' . osc_esc_html((string) $value) . '">';
    }

    return $html . '<button type="submit" class="' . $class . '"' . $confirm . '>' . $inner . '</button></form>';
}

/**
 * One row of the seller's ledger for the current item. $full adds the row actions (your ads);
 * without it the row is the dashboard's compact summary. Plugins still add actions through
 * listing_row_actions, as on core's own list.
 */
function glance_ad_row(bool $full, string $context): void
{
    $id      = (int) osc_item_id();
    $url     = osc_item_url();
    $title   = (string) osc_item_title();
    $img     = osc_images_enabled_at_items() ? glance_row_cover($id) : '';
    $size    = glance_thumb_size();
    $status  = glance_ad_status();
    $ends    = glance_ad_expiry();
    $views   = glance_ad_views();
    $price   = glance_price_html();
    $actions = array();

    // Core's row filters, in core's shape, so plugins can add badges and facts to the seller's rows.
    $badges = array('status' => array('label' => $status[1], 'class' => $status[0]));
    if (osc_item_is_premium()) {
        $badges['premium'] = array('label' => __('Featured', 'glance'), 'class' => 'paid');
    }
    if (osc_item_is_highlighted()) {
        $badges['highlight'] = array('label' => __('Highlighted', 'glance'), 'class' => 'paid');
    }
    if (osc_item_is_urgent()) {
        $badges['urgent'] = array('label' => __('Urgent', 'glance'), 'class' => 'pending');
    }
    $badges = (array) osc_apply_filter('listing_row_badges', $badges, osc_item(), $context);

    $meta = array(
        'category' => array('text' => (string) osc_item_category()),
        'date'     => array(
            'text'     => sprintf(__('Posted %s', 'glance'), osc_format_date(osc_item_pub_date())),
            'datetime' => glance_iso_date(osc_item_pub_date()),
        ),
    );
    $meta = (array) osc_apply_filter('listing_row_meta', $meta, osc_item(), $context);

    if ($full) {
        $actions['edit']   = array('label' => __('Edit', 'glance'), 'url' => osc_item_edit_url());
        $actions['delete'] = array(
            'label'  => __('Delete', 'glance'),
            'url'    => osc_base_url(true),
            'method' => 'post',
            'fields' => array('page' => 'item', 'action' => 'item_delete', 'id' => $id),
        );
        foreach (osc_item_upgrade_offers(osc_item()) as $offer) {
            $actions['upgrade_' . $offer['feature']] = array(
                'label'  => $offer['label'],
                'note'   => $offer['credits'] > 0 ? sprintf(_n('%d credit', '%d credits', (int) $offer['credits'], 'glance'), (int) $offer['credits']) : __('free', 'glance'),
                'url'    => osc_item_upgrade_url($id, $offer['feature']),
                'method' => 'post',
                'group'  => 'promote',
            );
        }
        $actions = (array) osc_apply_filter('listing_row_actions', $actions, osc_item(), $context);
    }

    $promote = array();
    $extra   = array();
    foreach ($actions as $key => $action) {
        if (!is_array($action) || !isset($action['label'], $action['url']) || in_array($key, array('edit', 'delete'), true)) {
            continue;
        }
        if (($action['group'] ?? '') === 'promote') {
            $promote[] = $action;
        } else {
            $extra[] = $action;
        }
    }
    ?>
    <li class="ad-row" data-ad data-id="<?php echo $id; ?>" data-state="<?php echo $status[0]; ?>" data-title="<?php echo osc_esc_html($title); ?>">
        <a class="ad-thumb" href="<?php echo osc_esc_html($url); ?>" tabindex="-1" aria-hidden="true">
            <?php if ($img !== '') { ?>
                <img src="<?php echo osc_esc_html($img); ?>" alt="" width="<?php echo $size[0]; ?>" height="<?php echo $size[1]; ?>" loading="lazy" decoding="async">
            <?php } else { ?>
                <?php echo glance_icon('image'); ?>
            <?php } ?>
        </a>
        <div class="ad-main">
            <h3 class="ad-title"><a href="<?php echo osc_esc_html($url); ?>"><?php echo osc_esc_html($title); ?></a></h3>
            <p class="ad-facts">
                <?php foreach ($badges as $key => $badge) {
                    if (!is_array($badge) || ($badge['label'] ?? '') === '') {
                        continue;
                    }
                    $tone = $key === 'status' ? $status[0] : 'badge'; ?>
                    <span class="ad-status is-<?php echo osc_esc_html($tone); ?>"><?php echo osc_esc_html((string) $badge['label']); ?></span>
                <?php } ?>
                <?php foreach ($meta as $fact) {
                    if (!is_array($fact) || ($fact['text'] ?? '') === '') {
                        continue;
                    }
                    if (($fact['datetime'] ?? '') !== '') { ?>
                        <time class="ad-fact" datetime="<?php echo osc_esc_html((string) $fact['datetime']); ?>"><?php echo osc_esc_html((string) $fact['text']); ?></time>
                    <?php } else { ?>
                        <span class="ad-fact"><?php echo osc_esc_html((string) $fact['text']); ?></span>
                    <?php }
                } ?>
            </p>
        </div>
        <div class="ad-stats">
        <p class="ad-stat ad-views"><?php if ($views !== null) { ?><span class="tnum"><?php echo $views; ?></span> <span class="ad-stat-unit"><?php echo osc_esc_html(_n('view', 'views', $views, 'glance')); ?></span><?php } ?></p>
        <p class="ad-stat ad-ends<?php echo $ends[2] ? ' is-soon' : ''; ?>"><?php
            if ($ends[0] !== '') { ?><time class="tnum" datetime="<?php echo osc_esc_html($ends[0]); ?>"><?php echo osc_esc_html($ends[1]); ?></time><?php
            } else { echo osc_esc_html($ends[1]); } ?></p>
        </div>
        <p class="ad-price tnum"><?php echo $price; ?></p>
        <?php if ($full) { ?>
            <div class="ad-actions">
                <?php if (isset($actions['edit'])) {
                    echo glance_ad_action($actions['edit'], 'btn btn-quiet ad-act', 'pencil');
                }
                foreach ($extra as $action) {
                    echo glance_ad_action($action, empty($action['primary']) ? 'btn btn-quiet ad-act' : 'btn btn-primary ad-act ad-act-primary');
                }
                if ($promote) { ?>
                    <details class="ad-menu">
                        <summary class="btn btn-quiet ad-act"><?php echo glance_icon('megaphone'); ?><span><?php _e('Promote', 'glance'); ?></span></summary>
                        <div class="ad-menu-pop">
                            <?php foreach ($promote as $action) {
                                $action['label'] = (string) $action['label'];
                                echo str_replace('</span></button>', '</span>' . (isset($action['note']) ? '<span class="ad-menu-note tnum">' . osc_esc_html((string) $action['note']) . '</span>' : '') . '</button>', glance_ad_action($action, 'ad-menu-item'));
                            } ?>
                        </div>
                    </details>
                <?php }
                if (isset($actions['delete']) && is_array($actions['delete'])) {
                    $actions['delete']['label'] = (string) $actions['delete']['label'];
                    echo str_replace('<button type="submit"', '<button type="submit" data-ad-delete', glance_ad_action($actions['delete'], 'btn btn-quiet ad-act ad-act-danger', 'trash-2'));
                } ?>
            </div>
        <?php } ?>
    </li>
    <?php
}
