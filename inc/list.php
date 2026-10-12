<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Helpers for the ad list: search results, home, the row and the filter bar.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/*
 * The sort control is a native select whose options are full result URLs. With scripts on,
 * picking one navigates. Without, its form posts `glance_sort=<url>` and this sends the visitor on.
 */
function glance_sort_redirect(): void
{
    if (defined('OC_ADMIN') && OC_ADMIN === true) {
        return;
    }

    $target = (string) Params::getParam('glance_sort');
    $base   = osc_base_url();

    if ($target !== '' && strpos($target, $base) === 0 && strpbrk($target, "\r\n") === false) {
        osc_redirect_to($target);
    }
}

osc_add_hook('init', 'glance_sort_redirect');

/** The sort choices for the current search: [label, url, selected]. */
function glance_sort_options(): array
{
    $types   = Search::getAllowedTypesForSorting();
    $current = osc_search_order();
    $type    = strtolower((string) ($types[osc_search_order_type()] ?? 'desc'));
    $labels  = array(
        'relevance:desc'   => __('Best match', 'glance'),
        'dt_pub_date:desc' => __('Newest first', 'glance'),
        'i_price:asc'      => __('Price: low to high', 'glance'),
        'i_price:desc'     => __('Price: high to low', 'glance'),
        'distance:asc'     => __('Nearest first', 'glance'),
    );

    $out = array();
    foreach (osc_list_orders() as $coreLabel => $order) {
        $key   = $order['sOrder'] . ':' . $order['iOrderType'];
        $out[] = array(
            'label'    => $labels[$key] ?? $coreLabel,
            'url'      => osc_update_search_url(array(
                'sOrder'     => $order['sOrder'],
                'iOrderType' => $order['iOrderType'],
                'iPage'      => null,
            )),
            'selected' => $current === $order['sOrder'] && $type === $order['iOrderType'],
        );
    }

    if (!array_filter(array_column($out, 'selected')) && $out) {
        $out[0]['selected'] = true;
    }

    return $out;
}

/** The page heading for the current search. */
function glance_search_heading(): string
{
    $pattern = trim((string) osc_search_pattern());
    $cat     = glance_category_name(glance_search_category_id());
    $city    = (string) osc_search_city();
    $where   = $cat !== '' ? $cat : '';

    if ($pattern !== '') {
        return $where !== ''
            ? sprintf(__('“%1$s” in %2$s', 'glance'), $pattern, $where)
            : sprintf(__('Results for “%s”', 'glance'), $pattern);
    }
    if ($cat !== '' && $city !== '') {
        return sprintf(__('%1$s in %2$s', 'glance'), $cat, $city);
    }
    if ($cat !== '') {
        return $cat;
    }
    if ($city !== '') {
        return sprintf(__('Ads in %s', 'glance'), $city);
    }

    return __('All ads', 'glance');
}

/** A price typed into the search form, in the site's number format. */
function glance_search_price_label(string $value): string
{
    if ($value === '' || !is_numeric($value)) {
        return $value;
    }

    $decimals = floor((float) $value) == (float) $value ? 0 : (int) osc_locale_num_dec();

    return number_format((float) $value, $decimals, osc_locale_dec_point(), osc_locale_thousands_sep());
}

/** Every filter narrowing the current search, as [label, url that drops it]. */
function glance_active_filters(): array
{
    $drop = static fn(array $params): string => osc_update_search_url($params + array('iPage' => null));
    $out  = array();

    $pattern = trim((string) osc_search_pattern());
    if ($pattern !== '') {
        $out[] = array('label' => sprintf(__('“%s”', 'glance'), $pattern), 'url' => $drop(array('sPattern' => null)), 'key' => 'sPattern');
    }

    /* The category is the page's scope (heading, breadcrumb), not a refinement, so it gets no chip. */

    foreach (array('sCity' => osc_search_city(), 'sRegion' => osc_search_region()) as $param => $place) {
        if ((string) $place !== '') {
            $out[] = array('label' => (string) $place, 'url' => $drop(array($param => null, $param === 'sRegion' ? 'regionId' : 'cityId' => null)));
        }
    }

    if ((string) Params::getParam('sCountry') !== '' && count(osc_get_countries()) > 1) {
        $out[] = array('label' => (string) osc_search_country(), 'url' => $drop(array('sCountry' => null)));
    }

    $min = (string) osc_search_price_min();
    $max = (string) osc_search_price_max();
    if ($min !== '' || $max !== '') {
        if ($min !== '' && $max !== '') {
            $label = sprintf(__('Price %1$s – %2$s', 'glance'), glance_search_price_label($min), glance_search_price_label($max));
        } elseif ($min !== '') {
            $label = sprintf(__('Price from %s', 'glance'), glance_search_price_label($min));
        } else {
            $label = sprintf(__('Price up to %s', 'glance'), glance_search_price_label($max));
        }
        $out[] = array('label' => $label, 'url' => $drop(array('sPriceMin' => null, 'sPriceMax' => null)));
    }

    if (osc_search_has_pic()) {
        $out[] = array('label' => __('With photos', 'glance'), 'url' => $drop(array('bPic' => null)));
    }
    if (osc_search_only_premium()) {
        $out[] = array('label' => __('Featured only', 'glance'), 'url' => $drop(array('bPremium' => null)));
    }
    if (osc_search_user()) {
        $out[] = array('label' => __('One seller', 'glance'), 'url' => $drop(array('sUser' => null)));
    }
    if (osc_search_radius() > 0) {
        $out[] = array(
            'label' => sprintf(__('Within %s', 'glance'), osc_distance_label(osc_search_radius())),
            'url'   => $drop(array('iRadius' => null, 'sLat' => null, 'sLng' => null)),
        );
    }

    $meta = Params::getParam('meta');
    if (is_array($meta)) {
        foreach ($meta as $fieldId => $value) {
            $value = is_array($value) ? array_filter(array_map('strval', $value), 'strlen') : trim((string) $value);
            if ($value === '' || $value === array()) {
                continue;
            }
            $field = Field::getInstance()->findByPrimaryKey((int) $fieldId);
            $name  = is_array($field) ? (string) ($field['s_name'] ?? '') : '';
            if ($name === '') {
                continue;
            }
            if (is_array($value)) {
                $text = implode(' – ', $value);
            } elseif (is_array($field) && ($field['e_type'] ?? '') === 'CHECKBOX') {
                $text = '';
            } else {
                $text = $value;
            }
            $rest = $meta;
            unset($rest[$fieldId]);
            $out[] = array(
                'label' => $text === '' ? $name : $name . ': ' . $text,
                'url'   => $drop(array('meta' => $rest ?: null)),
            );
        }
    }

    return $out;
}

/** Output of the search_form hook (custom fields, distance) for the form, or ''. */
function glance_search_form_extra(): string
{
    $cat = glance_search_category_id();

    ob_start();
    if ($cat > 0) {
        osc_run_hook('search_form', $cat);
    } else {
        osc_run_hook('search_form');
    }

    return glance_label_core_filters(trim((string) ob_get_clean()));
}

/** Categories as a flat list for a select: [id, name, depth], parents before children. */
function glance_category_options(): array
{
    $out  = array();
    $walk = static function (array $rows, int $depth) use (&$walk, &$out): void {
        foreach ($rows as $row) {
            if (!isset($row['pk_i_id'])) {
                continue;
            }
            $out[] = array('id' => (int) $row['pk_i_id'], 'name' => (string) ($row['s_name'] ?? ''), 'depth' => $depth);
            if (!empty($row['categories']) && is_array($row['categories']) && $depth < 2) {
                $walk($row['categories'], $depth + 1);
            }
        }
    };
    $walk((array) Category::getInstance()->toTree(), 0);

    return $out;
}

/*
 * Covers and custom-field facts for a batch of item rows, one query each, so rows do not load
 * their own. The maps live on the view; common/row.php reads them.
 */
function glance_prime_rows(array $rows): void
{
    $ids = array_map(static fn($r) => (int) ($r['pk_i_id'] ?? 0), $rows);
    glance_prime_row_facts($ids);

    if (!osc_images_enabled_at_items()) {
        return;
    }

    $covers = (array) View::getInstance()->_get('glance_covers');

    View::getInstance()->_exportVariableToView('glance_covers', $covers + osc_item_cover_urls($ids, 'thumbnail'));
}

function glance_row_cover(int $id): string
{
    $covers = (array) View::getInstance()->_get('glance_covers');

    return (string) ($covers[$id] ?? '');
}

/** 0 for the first row printed on the page, 1 for the next… */
function glance_row_index(): int
{
    static $n = -1;

    return ++$n;
}

/** Width and height of core's thumbnail variant, for the img attributes. */
function glance_thumb_size(): array
{
    static $size = null;

    if ($size === null) {
        $parts = array_map('intval', explode('x', strtolower((string) osc_thumbnail_dimensions())));
        $size  = (count($parts) === 2 && $parts[0] > 0 && $parts[1] > 0) ? $parts : array(240, 180);
    }

    return $size;
}

/** Page numbers to show around the current one; 0 marks a gap. */
function glance_pager_pages(int $current, int $total, int $sides = 1): array
{
    $pages = array();

    for ($p = 1; $p <= $total; $p++) {
        if ($p === 1 || $p === $total || abs($p - $current) <= $sides) {
            $pages[] = $p;
        } elseif (end($pages) !== 0) {
            $pages[] = 0;
        }
    }

    return $pages;
}

/** The URL of results page $page (1-based). Page 1 carries no iPage. */
function glance_page_url(int $page): string
{
    return osc_update_search_url(array('iPage' => $page > 1 ? $page : null));
}

/** Whether the visitor may subscribe to alerts here, and if not whether signing in would help. */
function glance_alerts_state(): string
{
    if (!osc_users_enabled()) {
        return 'off';
    }
    if (osc_is_web_user_logged_in() || !osc_get_preference('alerts_require_login')) {
        return 'open';
    }

    return 'sign-in';
}

/** A plugin hook's output, or '' when nothing hooked in. */
function glance_hook_html(string $hook): string
{
    ob_start();
    osc_run_hook($hook);

    return trim((string) ob_get_clean());
}

/** How many active filters live in the filter form (the keyword sits in the header search). */
function glance_filter_count(array $active): int
{
    return count(array_filter($active, static fn($f) => ($f['key'] ?? '') !== 'sPattern'));
}

/*
 * Sorted, filtered or keyword variants of a browse page are not worth indexing. Core prints the
 * robots tag on its 'header' hook when the view var is set, so this only sets it.
 */
function glance_search_noindex(): void
{
    foreach (array('sPattern', 'sOrder', 'iOrderType', 'sPriceMin', 'sPriceMax', 'bPic', 'bPremium', 'sUser', 'iRadius', 'sLat', 'meta') as $param) {
        $value = Params::getParam($param);
        if (is_array($value) ? array_filter($value, static fn($v) => is_array($v) ? array_filter($v, 'strlen') : (string) $v !== '') : (string) $value !== '') {
            View::getInstance()->_exportVariableToView('meta_noindex', true);
            return;
        }
    }
}

/*
 * Custom-field filters from core print an <h6> above each control with no label. Turn each into a
 * real <label for> (or a plain caption for a From/To group that is already aria-labelledby).
 * Field names and ids stay as core printed them.
 */
function glance_label_core_filters(string $html): string
{
    if (stripos($html, '<h6') === false) {
        return $html;
    }

    $html = preg_replace(
        '~<h6>(.*?)</h6>(\s*)(<(?:input|select|textarea)\b[^>]*\bid="([^"]+)")~is',
        '<label for="$4">$1</label>$2$3',
        $html
    );

    return preg_replace('~<h6(\s[^>]*)?>(.*?)</h6>~is', '<span class="ff-caption"$1>$2</span>', (string) $html);
}

/*
 * The first filled custom-field values of every row on the page, fetched in one query and kept
 * for the request. Values the title already says (make, model, year) are skipped.
 */
function glance_prime_row_facts(array $ids): void
{
    $ids   = array_values(array_unique(array_filter(array_map('intval', $ids))));
    $known = (array) View::getInstance()->_get('glance_row_meta');
    $ids   = array_values(array_diff($ids, array_keys($known)));

    if (!$ids || !class_exists('\mindstellar\database\Db')) {
        return;
    }

    $sql = 'SELECT im.fk_i_item_id AS item_id, im.s_value AS s_value, mf.s_name AS s_name, mf.e_type AS e_type'
        . ' FROM ' . DB_TABLE_PREFIX . 't_item_meta im'
        . ' INNER JOIN ' . DB_TABLE_PREFIX . 't_meta_fields mf ON mf.pk_i_id = im.fk_i_field_id'
        . " WHERE im.s_multi = '' AND im.s_value <> '' AND im.fk_i_item_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')'
        . " AND mf.e_type IN ('TEXT','NUMBER','DROPDOWN','RADIO','CHECKBOX')"
        . ' ORDER BY im.fk_i_item_id, mf.i_position, mf.pk_i_id';

    try {
        $rows = \mindstellar\database\Db::select($sql, $ids);
    } catch (\Throwable $e) {
        $rows = array();
    }

    foreach ($ids as $id) {
        $known[$id] = array();
    }
    foreach ($rows as $r) {
        $known[(int) $r['item_id']][] = $r;
    }

    View::getInstance()->_exportVariableToView('glance_row_meta', $known);
}

/** Up to $max short facts for a row, as plain text. */
function glance_row_facts(int $id, string $title, int $max = 3): array
{
    $meta  = (array) View::getInstance()->_get('glance_row_meta');
    $title = mb_strtolower($title);
    $out   = array();

    foreach ((array) ($meta[$id] ?? array()) as $r) {
        $value = trim(html_entity_decode((string) $r['s_value'], ENT_QUOTES, 'UTF-8'));
        $name  = trim((string) $r['s_name']);
        $type  = (string) $r['e_type'];

        if ($type === 'CHECKBOX' || in_array(mb_strtolower($value), array('yes', 'no'), true)) {
            /* A bare Yes/No means nothing without its field name. */
            if ($value === '1' || mb_strtolower($value) === 'yes') {
                $text = $name;
            } else {
                continue;
            }
        } elseif ($type === 'NUMBER' && is_numeric($value)) {
            $num  = (float) $value;
            $text = abs($num) >= 10000 ? number_format($num, 0, osc_locale_dec_point(), osc_locale_thousands_sep()) : $value;
            if (preg_match('~\(([^)]{1,12})\)~', $name, $unit)) {
                $text .= ' ' . $unit[1];
            } elseif (!preg_match('~\byear\b~i', $name)) {
                $text .= ' ' . mb_strtolower($name);
            }
        } else {
            $text = $value;
        }

        if ($text === '' || mb_strlen($text) > 32) {
            continue;
        }
        if (preg_match('~(^|[^\p{L}\p{N}])' . preg_quote(mb_strtolower($value), '~') . '($|[^\p{L}\p{N}])~u', $title)) {
            continue;
        }

        $out[] = $text;
        if (count($out) >= $max) {
            break;
        }
    }

    return $out;
}

