<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

define('GLANCE_VERSION', '1.1.1');
define('GLANCE_DIR', __DIR__ . '/');
define('GLANCE_DEFAULT_ACCENT', '#2f5bff');

require_once GLANCE_DIR . 'inc/icons.php';
require_once GLANCE_DIR . 'inc/helpers.php';

foreach (array('list', 'item', 'account', 'post') as $glance_inc) {
    if (is_file(GLANCE_DIR . 'inc/' . $glance_inc . '.php')) {
        require_once GLANCE_DIR . 'inc/' . $glance_inc . '.php';
    }
}
unset($glance_inc);

osc_add_theme_support('chrome', array(
    'header' => 'common/header.php',
    'footer' => 'common/footer.php',
));

osc_add_theme_support('head');

// Glance styles core's pages and comments block itself.
osc_add_theme_support('core-styles', false);

osc_add_filter('scripts_defer', static fn() => true);

function glance_init(): void
{
    osc_add_theme_support('widget_locations', array(
        'header' => array(
            'label'       => __('Under the header', 'glance'),
            'description' => __('A thin strip below the header bar, on every page.', 'glance'),
        ),
        'footer' => array(
            'label'       => __('Footer', 'glance'),
            'description' => __('Above the copyright line.', 'glance'),
        ),
    ));

    if (defined('OC_ADMIN') && OC_ADMIN === true) {
        $map = function_exists('map_search_render');
        (new \mindstellar\admin\ui\FormSpec('glance'))
            ->title(__('Glance', 'glance'))
            ->menu('appearance')
            ->image('logo', __('Logo', 'glance'), __('Shown in the header instead of the site name. Keep it about 36 px tall.', 'glance'))
            ->color('accent', __('Accent colour', 'glance'), __('Used for buttons, links and the open ad. Dark mode uses a lighter shade of it.', 'glance'))
                ->default(GLANCE_DEFAULT_ACCENT)
                ->set('pattern', '/^#[0-9a-fA-F]{6}$/')
            ->select('density', __('List density', 'glance'), array(
                'comfortable' => __('Comfortable', 'glance'),
                'compact'     => __('Compact — more ads on screen', 'glance'),
            ), __('How tightly the ad list is packed on search and home.', 'glance'))
                ->default('comfortable')
            ->text('footer_note', __('Footer note', 'glance'), __('One short line under the site name in the footer. Leave empty to hide it.', 'glance'))
            ->checkbox('home_map', __('Home page map', 'glance'), $map
                ? __('Uses the Map search plugin. A map of listings above the latest ads. It shows only when distance search is on and listings have a map point.', 'glance')
                : __('Needs the Map search plugin. Install and activate it under Plugins to use this.', 'glance'))
                ->default(0)
                ->attrs(array('disabled' => !$map))
            ->register();
    }
}

osc_add_hook('init', 'glance_init');

/** A saved theme setting, or $default when none is stored. */
function glance_setting(string $name, $default = '')
{
    $value = osc_get_preference($name, 'glance');

    return $value === null || $value === '' ? $default : $value;
}

function glance_accent(): string
{
    $color = (string) glance_setting('accent', GLANCE_DEFAULT_ACCENT);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : GLANCE_DEFAULT_ACCENT;
}

/** The owner's accent as an inline token override, or '' for the default. */
function glance_accent_style(): string
{
    $color = glance_accent();

    return $color === GLANCE_DEFAULT_ACCENT ? '' : '<style>:root{--accent-base:' . $color . '}</style>' . "\n";
}

/** 'comfortable' or 'compact', for the list's data-density attribute. */
function glance_list_density(): string
{
    return glance_setting('density', 'comfortable') === 'compact' ? 'compact' : 'comfortable';
}

/** The Map search plugin's map on the home page, when the owner turned it on. Prints nothing otherwise. */
function glance_home_map(): void
{
    if (function_exists('map_search_render') && !empty(glance_setting('home_map', ''))) {
        map_search_render(array('class' => 'glance-home-map'));
    }
}

/** True when list.js fetches an ad to show it in the panel. */
function glance_is_panel_request(): bool
{
    return ($_SERVER['HTTP_X_GLANCE_PANEL'] ?? '') === '1';
}

function glance_logo_url(): string
{
    return (string) osc_settings_image_url('glance', 'logo');
}

/** The minified sibling of a theme asset when a release build shipped one, else the source. */
function glance_asset(string $path): string
{
    $min = preg_replace('/\.(css|js)$/', '.min.$1', $path);

    if ($min !== $path && is_file(GLANCE_DIR . $min)) {
        $path = $min;
    }

    return osc_asset_url_versioned(osc_current_web_theme_url($path));
}

/*
 * Each view names the surfaces it needs before it includes the header:
 * 'list' (search, home), 'item' (ad page and the panel), 'account', 'post'.
 * They are enqueued after the base layer when the head runs.
 */
function glance_enqueue(string ...$surfaces): void
{
    $queued = (array) View::getInstance()->_get('glance_surfaces');
    View::getInstance()->_exportVariableToView('glance_surfaces', array_unique(array_merge($queued, $surfaces)));
}

function glance_enqueue_assets(): void
{
    $map = array(
        'list'    => array('css' => array('list', 'item'), 'js' => array('item', 'list')),
        'item'    => array('css' => array('item'), 'js' => array('item')),
        'account' => array('css' => array('account'), 'js' => array('account')),
        'post'    => array('css' => array('account', 'post'), 'js' => array('post')),
    );

    $surfaces = array_values(array_intersect((array) View::getInstance()->_get('glance_surfaces'), array_keys($map)));

    // A release build ships one stylesheet per page type (build.mjs); use it when there is one.
    $bundle = count($surfaces) === 1 ? 'css/bundle-' . $surfaces[0] . '.min.css' : '';
    $bundled = $bundle !== '' && is_file(GLANCE_DIR . $bundle);

    osc_enqueue_style('glance', $bundled ? osc_asset_url_versioned(osc_current_web_theme_url($bundle)) : glance_asset('css/glance.css'));
    osc_register_script('glance', glance_asset('js/glance.js'));
    osc_enqueue_script('glance');

    // List pages only need the ad styles once the panel opens, so they load without blocking.
    if ($bundled && $surfaces[0] === 'list') {
        View::getInstance()->_exportVariableToView('glance_late_css', glance_asset('css/item.css'));
    }

    foreach ($surfaces as $surface) {
        foreach ($bundled ? array() : $map[$surface]['css'] as $css) {
            osc_enqueue_style('glance-' . $css, glance_asset('css/' . $css . '.css'));
        }
        foreach ($map[$surface]['js'] ?? array() as $js) {
            osc_register_script('glance-' . $js, glance_asset('js/' . $js . '.js'), array('glance'));
            osc_enqueue_script('glance-' . $js);
        }
    }
}

osc_add_hook('header', 'glance_enqueue_assets', 1);

/** The non-blocking stylesheet link set by glance_enqueue_assets(), if any. list.js waits for it. */
function glance_late_styles(): string
{
    $url = (string) View::getInstance()->_get('glance_late_css');

    return $url === '' ? '' : '<link rel="stylesheet" href="' . osc_esc_html($url) . '" media="print" onload="this.media=\'all\'" data-glance-late-css>' . PHP_EOL;
}

/** A widget zone, or nothing at all when it is empty. */
function glance_widget_zone(string $location, string $class): void
{
    ob_start();
    osc_show_widgets($location);
    $html = trim((string) ob_get_clean());

    if ($html !== '') {
        echo '<div class="' . osc_esc_html($class) . '"><div class="wrap">' . $html . '</div></div>';
    }
}

/**
 * The "Full width" page template: the theme's own page.php without the narrow reading column.
 * The id is shared by our themes, so a page keeps the template when the site changes theme.
 */
function glance_page_templates(): void
{
    if (function_exists('osc_register_page_template')) {
        osc_register_page_template('theme.full_width', array(
            'label'       => __('Full width', 'glance'),
            'description' => __('The page uses the whole width of the content area.', 'glance'),
            'render'      => 'page.php',
        ));
    }
}

osc_add_hook('init', 'glance_page_templates');

/** Names the menu places the theme shows in Appearance > Menus. Older cores have no menus. */
function glance_menu_places(): void
{
    if (function_exists('osc_menu')) {
        osc_add_theme_support('menus', array(
            'main'   => array('label' => __('Header', 'glance'), 'depth' => 1),
            'footer' => array('label' => __('Footer', 'glance'), 'depth' => 1),
        ));
    }
}

osc_add_hook('init', 'glance_menu_places');

/**
 * The menu the owner saved in Appearance > Menus, or null when there is none (or the core has no menus).
 *
 * @return array{label:string,items:array<int,array<string,mixed>>}|null
 */
function glance_saved_menu(string $id): ?array
{
    if (!function_exists('osc_menu') || !class_exists('mindstellar\\menu\\MenuService')) {
        return null;
    }
    $saved = \mindstellar\menu\MenuService::getInstance()->load($id);
    if ($saved === null) {
        return null;
    }
    $items = array();
    foreach (osc_menu($id) as $item) {
        if (!empty($item['url']) && isset($item['label']) && (string) $item['label'] !== '') {
            $items[] = $item;
        }
    }

    return $items === array() ? null : array('label' => (string) $saved['label'], 'items' => $items);
}

/** True when a menu link points at the page being shown. */
function glance_menu_current(string $url): bool
{
    $normal = static function (string $u): string {
        $path = rtrim((string) preg_replace('#/index\.php$#', '', (string) parse_url($u, PHP_URL_PATH)), '/');
        parse_str((string) parse_url($u, PHP_URL_QUERY), $query);
        ksort($query);

        return $path . '?' . http_build_query($query);
    };

    return $normal($url) === $normal((string) ($_SERVER['REQUEST_URI'] ?? '/'));
}

/** Whether the current static page uses the Full width template. */
function glance_is_full_width(): bool
{
    if (!osc_is_static_page()) {
        return false;
    }
    $meta = json_decode((string) osc_static_page_field('s_meta'), true);

    return is_array($meta) && ($meta['template'] ?? '') === 'theme.full_width';
}

osc_add_filter('body_class', static function ($classes) {
    if (glance_is_full_width()) {
        $classes[] = 'layout-full-width';
    }

    return $classes;
});
