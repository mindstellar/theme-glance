<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Helpers for the ad page. Each reads the current item and leaves core's loop pointers where it found them.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/** Width and height from a core size string such as "640x480". */
function glance_dims(string $size, int $w = 640, int $h = 480): array
{
    if (preg_match('/^(\d+)\s*x\s*(\d+)$/i', trim($size), $m) && (int) $m[1] > 0 && (int) $m[2] > 0) {
        return array((int) $m[1], (int) $m[2]);
    }

    return array($w, $h);
}

/** The current item's photos as [src, thumb, alt] rows. Empty when photos are off or there are none. */
function glance_item_photos(): array
{
    $photos = array();

    if (!osc_images_enabled_at_items() || osc_count_item_resources() < 1) {
        return $photos;
    }

    osc_reset_resources();
    $n = 0;
    while (osc_has_item_resources()) {
        $n++;
        $alt      = trim((string) osc_resource_alt());
        $photos[] = array(
            'src'   => osc_resource_url(),
            'thumb' => osc_resource_thumbnail_url(),
            'alt'   => $alt !== '' ? $alt : sprintf(__('%1$s, photo %2$d', 'glance'), osc_item_title(), $n),
        );
    }
    osc_reset_resources();

    return $photos;
}

/** City area, city, region and country of the current item, without repeats. */
function glance_item_location(): string
{
    $parts = array_filter(array(
        trim((string) osc_item_city_area()),
        trim((string) osc_item_city()),
        trim((string) osc_item_region()),
        trim((string) osc_item_country()),
    ));

    return implode(', ', array_unique($parts));
}

/**
 * The ad's map, from core's settings: provider ('google' needs an API key, 'openstreet' needs none),
 * key, coordinates, the address to geocode when there are none, and a link to the provider's own map.
 * 'show' is false when the map is off or there is nothing to place.
 */
function glance_item_map(): array
{
    $type = (string) osc_item_map_type();
    $key  = trim((string) osc_google_maps_api_key());
    $lat  = (float) osc_item_latitude();
    $lng  = (float) osc_item_longitude();
    $has  = $lat != 0.0 || $lng != 0.0;
    $addr = implode(', ', array_unique(array_filter(array(
        trim((string) osc_item_address()),
        trim((string) osc_item_city()),
        trim((string) osc_item_region()),
        trim((string) osc_item_country()),
    ))));

    $show = ($has || $addr !== '') && ($type === 'openstreet' || ($type === 'google' && $key !== ''));
    $link = '';
    if ($show && $type === 'google') {
        $link = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($has ? $lat . ',' . $lng : $addr);
    } elseif ($show && $has) {
        $link = 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $lat) . '&mlon=' . rawurlencode((string) $lng)
            . '#map=15/' . $lat . '/' . $lng;
    } elseif ($show) {
        $link = 'https://www.openstreetmap.org/search?query=' . rawurlencode($addr);
    }

    return array(
        'show' => $show,
        'type' => $type,
        'key'  => $type === 'google' ? $key : '',
        'lat'  => $has ? (string) $lat : '',
        'lng'  => $has ? (string) $lng : '',
        'addr' => $addr,
        'link' => $link,
    );
}

/** Custom fields of the current item as [name, html] rows. Core pre-formats the values. */
function glance_item_fields(): array
{
    $rows = array();

    if (osc_count_item_meta() < 1) {
        return $rows;
    }

    while (osc_has_item_meta()) {
        $row  = osc_item_meta();
        $type = osc_field_resolve_type($row);
        $raw  = isset($row['s_value']) ? trim((string) $row['s_value']) : '';

        if ($type === 'CHECKBOX') {
            if ($raw === '' || $raw === '0') {
                continue;
            }
            $html = osc_esc_html(__('Yes', 'glance'));
        } elseif ($type === 'EMAIL') {
            if ($raw === '') {
                continue;
            }
            $html = '<a href="mailto:' . osc_esc_html($raw) . '">' . osc_esc_html($raw) . '</a>';
        } elseif ($type === 'PHONE') {
            if ($raw === '') {
                continue;
            }
            $html = '<a href="tel:' . osc_esc_html(preg_replace('/[^0-9+]/', '', $raw)) . '">' . osc_esc_html($raw) . '</a>';
        } elseif (preg_match('/^\d{5,}$/', $raw)) {
            // Group long plain numbers (mileage, area) the way the list rows print them.
            $html = osc_esc_html(number_format((float) $raw));
        } else {
            $html = (string) osc_item_meta_value();
            if (trim($html) === '') {
                continue;
            }
        }

        $rows[] = array('name' => (string) osc_item_meta_name(), 'html' => $html);
    }

    return $rows;
}

/** A coarse "Active today / this week / this month", or '' when unknown or older. */
function glance_seller_activity(?string $date): string
{
    $stamp = $date === null || $date === '' ? false : strtotime($date);

    if ($stamp === false || $stamp < strtotime('2000-01-01')) {
        return '';
    }

    $days = (time() - $stamp) / 86400;

    if ($days < 1) {
        return __('Active today', 'glance');
    }
    if ($days < 7) {
        return __('Active this week', 'glance');
    }
    if ($days < 31) {
        return __('Active this month', 'glance');
    }

    return '';
}

/**
 * The seller of the current item. Guest ads have a name only.
 *
 * @return array{name:string,id:int,since:string,active:string,ads:int,profile:string,ads_url:string,phone:string,company:bool}
 */
function glance_item_seller(): array
{
    $user = View::getInstance()->_get('user');
    $user = is_array($user) ? $user : array();
    $id   = (int) osc_item_user_id();
    $name = trim((string) osc_item_contact_name());

    if ($id < 1 || !$user) {
        return array(
            'name' => $name, 'id' => 0, 'since' => '', 'active' => '', 'ads' => 0,
            'profile' => '', 'ads_url' => '', 'phone' => '', 'company' => false,
        );
    }

    $since = (string) ($user['dt_reg_date'] ?? '');
    $ads   = max(0, (int) ($user['i_items'] ?? 0) - (osc_item_is_active() ? 1 : 0));
    $phone = trim((string) ($user['s_phone_mobile'] ?? ''));
    if ($phone === '') {
        $phone = trim((string) ($user['s_phone_land'] ?? ''));
    }

    return array(
        'name'    => $name !== '' ? $name : (string) ($user['s_name'] ?? ''),
        'id'      => $id,
        'since'   => strtotime($since) ? $since : '',
        'active'  => glance_seller_activity((string) ($user['dt_access_date'] ?? '')),
        'ads'     => $ads,
        'profile' => osc_user_public_profile_url($id),
        'ads_url' => osc_search_url(array('page' => 'search', 'sUser' => $id)),
        'phone'   => $phone,
        'company' => !empty($user['b_company']),
    );
}

/** Escaped title with short hyphenated tokens (EX-L, T-Shirt) kept on one line. */
function glance_title_html(string $title): string
{
    $parts = preg_split('/(\s+)/u', $title, -1, PREG_SPLIT_DELIM_CAPTURE);
    $out   = '';

    foreach ($parts as $part) {
        $safe = osc_esc_html($part);
        $out .= (strpos($part, '-') !== false && mb_strlen($part) <= 6) ? '<span class="nb">' . $safe . '</span>' : $safe;
    }

    return $out;
}

/** Texts item.js shows: lightbox, share. Printed on the article so it works in the side panel too. */
function glance_item_i18n(): string
{
    return osc_esc_html((string) json_encode(array(
        'photos' => __('Photos', 'glance'),
        'close'  => __('Close', 'glance'),
        'prev'   => __('Previous photo', 'glance'),
        'next'   => __('Next photo', 'glance'),
        'copied' => __('Link copied', 'glance'),
        'mapError' => __('The map could not be loaded.', 'glance'),
    ), JSON_UNESCAPED_UNICODE));
}

/** Whether the visitor owns the current item. */
function glance_item_is_owner(): bool
{
    $me = (int) osc_logged_user_id();

    return $me > 0 && $me === (int) osc_item_user_id();
}

/** '' when the visitor may write to the seller, else the reason: 'expired', 'owner' or 'login'. */
function glance_contact_block(): string
{
    if (osc_item_is_expired() || osc_item_is_inactive()) {
        return 'expired';
    }
    if (glance_item_is_owner()) {
        return 'owner';
    }
    if (osc_reg_user_can_contact() && !osc_is_web_user_logged_in()) {
        return 'login';
    }

    return '';
}

/** Core's message form for the current item, posting to the same action core's own seller page uses. */
function glance_contact_form(): void
{
    if (!function_exists('osc_gui_kept')) {
        require_once ABS_PATH . 'oc-includes/osclass/gui/page-fn.php';
    }

    $contactForm = array(
        'hidden'     => array('page' => 'item', 'action' => 'contact_post', 'id' => (int) osc_item_id()),
        'prefix'     => 'oe-your',
        'hint'       => __('The seller replies to this address.', 'glance'),
        'phone'      => true,
        'attachment' => (bool) osc_item_attachment(),
        'captcha'    => 'contact_seller',
        'hooks'      => static function () {
            osc_run_hook('item_contact_form', osc_item_id());
        },
    );

    require ABS_PATH . 'oc-includes/osclass/gui/parts/contact-form.php';
}

/**
 * Folds core's comment form behind a "Leave a comment" toggle, so the thread stays short.
 * The form itself is untouched. It opens again when it comes back with kept text after a rejected post.
 */
function glance_fold_comment_form(string $html): string
{
    if (!preg_match('~<form\b[^>]*\bclass="oe-comment-form"[^>]*>.*?</form>~s', $html, $m, PREG_OFFSET_CAPTURE)) {
        return $html;
    }

    $form = $m[0][0];
    $open = preg_match('~<textarea\b[^>]*\bname="body"[^>]*>\s*[^<\s]~', $form) ? ' open' : '';
    $fold = '<details class="ad-comment-fold" data-comment-fold' . $open . '><summary class="btn">' . glance_icon('message-circle')
        . osc_esc_html(__('Leave a comment', 'glance')) . '</summary>' . $form . '</details>';

    return substr_replace($html, $fold, $m[0][1], strlen($form));
}

/** Live ads like the current one: same category, then its parent, then the newest. Cached for five minutes. */
function glance_related_items(int $max = 6): array
{
    $max   = max(1, $max);
    $self  = (int) osc_item_id();
    $cat   = (int) osc_item_category_id();
    $key   = 'glance_related_' . $cat . '_' . $max;
    $found = false;
    $ids   = osc_cache_get($key, $found);

    if ($found !== false && is_array($ids)) {
        $ids = array_slice(array_values(array_diff($ids, array($self))), 0, $max);
        if (!$ids) {
            return array();
        }
        $search = new Search();
        $search->fromPrimaryKeys($ids);
        $items = (array) $search->doSearch(true, false);
        osc_prime_item_upgrades($items);

        return $items;
    }

    $items = array();
    $seen  = array();
    $take  = static function (Search $search) use (&$items, &$seen, $max) {
        $search->order('dt_pub_date', 'DESC');
        $search->limit(0, $max * 3);
        foreach ((array) $search->doSearch(true, false) as $row) {
            $id = (int) $row['pk_i_id'];
            if (count($items) >= $max + 1) {
                break;
            }
            if (isset($seen[$id])) {
                continue;
            }
            $items[]    = $row;
            $seen[$id] = true;
        }
    };

    if ($cat > 0) {
        $search = new Search();
        $search->addCategory($cat);
        $take($search);

        $row    = Category::getInstance()->findByPrimaryKey($cat);
        $parent = (int) ($row['fk_i_parent_id'] ?? 0);
        if (count($items) <= $max && $parent > 0) {
            $search = new Search();
            $search->addCategory($parent);
            $take($search);
        }
    }
    if (count($items) <= $max) {
        $take(new Search());
    }

    osc_cache_set($key, array_map('intval', array_column($items, 'pk_i_id')), 300);

    $items = array_values(array_filter($items, static fn ($row) => (int) $row['pk_i_id'] !== $self));
    $items = array_slice($items, 0, $max);
    osc_prime_item_upgrades($items);

    return $items;
}

/** Product, Offer and BreadcrumbList for the current item as one JSON-LD script. An item with no price gets only the BreadcrumbList, because search engines reject a Product with no offer. */
function glance_item_jsonld(array $photos, array $trail): string
{
    $url   = osc_get_canonical() !== '' ? osc_get_canonical() : osc_item_url();
    $desc  = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) osc_item_description()), ENT_QUOTES, 'UTF-8')));
    $price = osc_price_enabled_at_items() ? osc_item_price() : null;

    $product = array(
        '@type' => 'Product',
        'name'  => (string) osc_item_title(),
        'url'   => $url,
        'sku'   => (string) osc_item_id(),
    );
    if ($desc !== '') {
        $product['description'] = mb_substr($desc, 0, 500);
    }
    if ($photos) {
        $product['image'] = array_values(array_map(static fn ($p) => $p['src'], array_slice($photos, 0, 6)));
    }
    if (osc_item_category() !== '') {
        $product['category'] = (string) osc_item_category();
    }

    $currency = (string) osc_item_currency();
    if ($price !== null && is_numeric($price) && (float) $price >= 0 && $currency !== '') {
        $product['offers'] = array(
            '@type'         => 'Offer',
            'price'         => number_format((float) $price / 1000000, 2, '.', ''),
            'priceCurrency' => $currency,
            'availability'  => osc_item_is_expired() || osc_item_is_inactive()
                ? 'https://schema.org/OutOfStock'
                : 'https://schema.org/InStock',
            'url'           => $url,
        );
    }

    $graph = isset($product['offers']) ? array($product) : array();

    if ($trail) {
        $list = array(array('@type' => 'ListItem', 'position' => 1, 'name' => __('Home', 'glance'), 'item' => osc_base_url()));
        foreach ($trail as $crumb) {
            $list[] = array('@type' => 'ListItem', 'position' => count($list) + 1, 'name' => $crumb['name'], 'item' => $crumb['url']);
        }
        $list[]  = array('@type' => 'ListItem', 'position' => count($list) + 1, 'name' => (string) osc_item_title());
        $graph[] = array('@type' => 'BreadcrumbList', 'itemListElement' => $list);
    }

    if (!$graph) {
        return '';
    }
    $json = json_encode(
        array('@context' => 'https://schema.org', '@graph' => $graph),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    );

    return $json === false ? '' : '<script type="application/ld+json">' . $json . '</script>';
}
