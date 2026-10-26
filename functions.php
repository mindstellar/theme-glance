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
