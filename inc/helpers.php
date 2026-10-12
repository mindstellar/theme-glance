<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/** The searched category as one id, or 0. Core returns an array on a category route. */
function glance_search_category_id(): int
{
    $raw = osc_search_category_id();

    return is_array($raw) ? (int) reset($raw) : (int) $raw;
}

function glance_category_name(int $id): string
{
    $row = $id > 0 ? osc_get_category('id', $id) : null;

    return is_array($row) ? (string) ($row['s_name'] ?? '') : '';
}

/** The unfiltered search page. osc_search_show_all_url() keeps the current filters, so it is not used. */
function glance_browse_all_url(): string
{
    return osc_search_url(array('page' => 'search'));
}

/** The inbox URL once core messaging is on; '' hides every messaging control. */
function glance_messages_url(): string
{
    return (string) osc_apply_filter('glance_messages_url', '');
}

function glance_unread_count(): int
{
    return max(0, (int) osc_apply_filter('glance_unread_count', 0));
}

/** The categories above and including $id, root first, as [id, name, url] rows. */
function glance_category_trail(int $id): array
{
    $trail = array();
    $seen  = array();

    while ($id > 0 && count($trail) < 10 && !isset($seen[$id])) {
        $seen[$id] = true;
        $row       = osc_get_category('id', $id);

        if (!is_array($row) || !isset($row['s_name'])) {
            break;
        }

        array_unshift($trail, array(
            'id'   => $id,
            'name' => (string) $row['s_name'],
            'url'  => osc_search_url(array('sCategory' => $id)),
        ));

        $id = (int) ($row['fk_i_parent_id'] ?? 0);
    }

    return $trail;
}

function glance_iso_date(?string $date): string
{
    $stamp = $date === null || $date === '' ? false : strtotime($date);

    return $stamp === false ? '' : date('c', $stamp);
}

/** "today", "3 days ago", "2 weeks ago"… for a stored date, or '' when it cannot be read. */
function glance_time_ago(?string $date): string
{
    $stamp = $date === null || $date === '' ? false : strtotime($date);

    if ($stamp === false) {
        return '';
    }

    $days = (int) floor((time() - $stamp) / 86400);

    if ($days < 1) {
        return __('today', 'glance');
    }
    if ($days < 2) {
        return __('yesterday', 'glance');
    }
    if ($days < 14) {
        return sprintf(__('%d days ago', 'glance'), $days);
    }
    if ($days < 60) {
        return sprintf(__('%d weeks ago', 'glance'), (int) floor($days / 7));
    }
    if ($days < 730) {
        return sprintf(__('%d months ago', 'glance'), (int) floor($days / 30));
    }

    return sprintf(__('%d years ago', 'glance'), (int) floor($days / 365));
}

/**
 * The current item's price as markup, figure and currency apart so the figure can carry the
 * weight. "Check with seller" and "Free" come back as a note.
 */
function glance_price_html(): string
{
    if (!osc_price_enabled_at_items()) {
        return '';
    }

    $formatted = (string) osc_item_formatted_price();
    $price     = osc_item_price();

    if ($price === null || (float) $price === 0.0) {
        return '<span class="price-note">' . osc_esc_html($formatted) . '</span>';
    }

    $out    = osc_esc_html($formatted);
    $symbol = osc_esc_html((string) osc_item_currency_symbol());

    // Tabular figures make separators digit-wide; keep them narrow so "8,500" reads as one number.
    $out = (string) preg_replace('/(?<=\d)([.,\x{00A0}\x{202F}\x{2009}\x{066B}\x{066C}]|&nbsp;)(?=\d)/u', '<span class="sep">$1</span>', $out);

    if ($symbol !== '' && ($at = strpos($out, $symbol)) !== false) {
        $out = substr_replace($out, '<span class="cur">' . $symbol . '</span>', $at, strlen($symbol));
        // A full space beside the symbol reads as a gap in a price column; keep it narrow and unbroken.
        $out = (string) preg_replace(
            array('/(<span class="cur">[^<]*<\/span>)(?:\s|&nbsp;|\x{00A0})+/u', '/(?:\s|&nbsp;|\x{00A0})+(<span class="cur">)/u'),
            array("\$1\u{202F}", "\u{202F}\$1"),
            $out
        );
    }

    return '<span class="amount">' . $out . '</span>';
}

/** City, region or country of the current item, most specific first, joined. */
function glance_item_place(): string
{
    $parts = array_filter(array(osc_item_city(), osc_item_region()));

    if (!$parts && osc_item_country() !== '') {
        $parts = array(osc_item_country());
    }

    return implode(', ', array_slice(array_unique($parts), 0, 2));
}
