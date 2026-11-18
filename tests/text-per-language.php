<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The footer note is one value per language, saved by core's settings page and read back per
 * visitor language; the old single value moves to every language on an admin request only.
 *
 * DB-backed, on a scratch database through the core's test helpers.
 * Usage:  SHOPCLASS_CORE=/path/to/shopclass/ php tests/text-per-language.php
 */

if (($argv[1] ?? '') === '--hook') {
    // Child: which requests register the migration.
    define('ABS_PATH', __DIR__);
    define('OC_ADMIN', $argv[2] === 'admin');
    function osc_add_hook($name, $fn, $priority = 5)
    {
        $GLOBALS['hooks'][$name][] = $fn;
    }
    require dirname(__DIR__) . '/inc/text.php';
    echo in_array('glance_migrate_text', $GLOBALS['hooks']['init'] ?? array(), true) ? 'hooked' : 'none';
    exit(0);
}

$core = rtrim((string) (getenv('SHOPCLASS_CORE') ?: getenv('OSC_CORE') ?: dirname(__DIR__, 2) . '/shopclass'), '/') . '/';
require_once $core . 'tests/lib/scratchdb.php';
require_once $core . 'tests/lib/harness.php';

$admin = scratchdb_session('glance_text_per_language');
define('OC_ADMIN', true);
if (!defined('OSC_CACHE_TTL')) {
    define('OSC_CACHE_TTL', 60);
}
foreach (array('osc_base_url' => 'return "http://localhost/";', 'osc_plugins_path' => 'return ABS_PATH . "oc-content/plugins/";', 'osc_base_path' => 'return ABS_PATH;', 'osc_current_web_theme' => 'return "glance";', 'osc_logged_admin_id' => 'return 0;', 'osc_is_admin_user_logged_in' => 'return false;') as $fn => $body) {
    if (!function_exists($fn)) {
        eval('function ' . $fn . '(...$args) { ' . $body . ' }');
    }
}
foreach (array('__' => 'return $text;', '_m' => 'return $text;') as $fn => $body) {
    if (!function_exists($fn)) {
        eval('function ' . $fn . '($text, $domain = "") { ' . $body . ' }');
    }
}
foreach (array('hPlugins', 'hPreference', 'hCache', 'hLocale', 'hSanitize', 'hTheme', 'hSettings') as $helper) {
    require_once ABS_PATH . "oc-includes/osclass/helpers/$helper.php";
}

/** Declare one top-level function of a theme file, so the theme's own code runs without loading the whole theme. */
function glance_test_load_function(string $file, string $name): void
{
    $tokens = token_get_all((string) file_get_contents($file));
    foreach ($tokens as $i => $token) {
        if (!is_array($token) || $token[0] !== T_FUNCTION) {
            continue;
        }
        $j = $i + 1;
        while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (!is_array($tokens[$j]) || $tokens[$j][1] !== $name) {
            continue;
        }
        $code  = '';
        $depth = 0;
        for ($k = $i; $k < count($tokens); $k++) {
            $text  = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
            $code .= $text;
            if ($text === '{' || (is_array($tokens[$k]) && in_array($tokens[$k][0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true))) {
                $depth++;
            } elseif ($text === '}' && --$depth === 0) {
                eval($code);

                return;
            }
        }
    }
    throw new RuntimeException("$name not found in $file");
}

$root = dirname(__DIR__);
define('GLANCE_DEFAULT_ACCENT', '#2f5bff');
require_once $root . '/inc/text.php';
glance_test_load_function($root . '/functions.php', 'glance_init');

if ((int) $admin->query('SELECT COUNT(*) FROM ' . DB_TABLE_PREFIX . "t_locale WHERE pk_c_code = 'en_US'")->fetch_row()[0] === 0) {
    seed_locale($admin);
}
seed_locale($admin, 'hi_IN', 'Hindi');
Preference::getInstance()->replace('language', 'en_US', 'osclass', 'STRING');
osc_settings_locales(true);
glance_init();

$stored = static function (string $key) use ($admin): ?string {
    $row = $admin->query('SELECT s_value FROM ' . DB_TABLE_PREFIX . "t_preference WHERE s_section = 'glance' AND s_name = '" . $admin->real_escape_string($key) . "'")->fetch_row();

    return $row === null ? null : (string) $row[0];
};
$as = static function (string $locale, callable $read) {
    $_COOKIE['oc_userLocale'] = $locale;
    $value = $read();
    unset($_COOKIE['oc_userLocale']);

    return $value;
};
$note = static fn (): string => glance_text_setting('footer_note', 'fallback');

harness_section('saved through core\'s settings page');

$_POST = array('accent' => '#2f5bff', 'density' => 'comfortable', 'footer_noteen_US' => 'Made in Pune', 'footer_notehi_IN' => 'Pune mein bana');
$_GET  = array();
Params::init();
$saved = osc_settings_save('glance');
pin('the save is accepted', array(), $saved['errors']);
pin('each language is its own preference', array('Made in Pune', 'Pune mein bana'), array($stored('footer_noteen_US'), $stored('footer_notehi_IN')));
osc_reset_preferences();
pin('a Hindi visitor reads the Hindi note', 'Pune mein bana', $as('hi_IN', $note));
pin('an English visitor reads the English note', 'Made in Pune', $as('en_US', $note));
osc_set_preference('footer_notehi_IN', '', 'glance');
pin('a language left empty falls back to the site language', 'Made in Pune', $as('hi_IN', $note));

harness_section('the old single value');

osc_set_preference('footer_noteen_US', '', 'glance');
osc_set_preference('footer_notehi_IN', 'Hindi line', 'glance');
osc_set_preference('footer_note', 'Old line', 'glance');
osc_reset_preferences();
pin('before the move a visitor still reads it', 'Old line', $as('en_US', $note));
glance_migrate_text();
pin('the move copies it to each empty language, keeps a set one and drops it', array('Old line', 'Hindi line', null), array($stored('footer_noteen_US'), $stored('footer_notehi_IN'), $stored('footer_note')));
pin('...and the settings page reads it per language', array('en_US' => 'Old line', 'hi_IN' => 'Hindi line'), osc_settings_values('glance')['footer_note'] ?? null);
$run = static fn (string $where): string => (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --hook ' . $where);
pin('the move is hooked on admin requests', 'hooked', $run('admin'));
pin('...and not on the front end, which reads the old value until then', 'none', $run('front'));

exit(harness_result());
