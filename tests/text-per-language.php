<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The footer note is one value per language: a visitor gets their language, then the site default,
 * and the old single value moves to every language once.
 * Usage:  php tests/text-per-language.php
 */

$root  = dirname(__DIR__);
$fail  = [];
$prefs = [];
$cur   = 'en_US';
$hooks = [];
define('ABS_PATH', $root);
$pin = static function (string $what, $want, $got) use (&$fail): void {
    if ($want !== $got) {
        $fail[] = $what . "\n    want " . json_encode($want) . "\n    got  " . json_encode($got);
    }
};

function osc_get_preference($key, $section = 'osclass')
{
    return $GLOBALS['prefs'][$section . '/' . $key] ?? '';
}
function osc_set_preference($key, $value = '', $section = 'osclass')
{
    $GLOBALS['prefs'][$section . '/' . $key] = $value;
}
function osc_delete_preference($key, $section = 'osclass')
{
    unset($GLOBALS['prefs'][$section . '/' . $key]);
}
function osc_reset_preferences()
{
}
function osc_add_hook($name, $fn, $priority = 5)
{
    $GLOBALS['hooks'][$name][] = $fn;
}
function osc_settings_locales()
{
    return ['en_US' => 'English', 'hi_IN' => 'Hindi', 'fr_FR' => 'French'];
}
function osc_current_user_locale()
{
    return $GLOBALS['cur'];
}
function osc_language()
{
    return 'en_US';
}

require $root . '/inc/text.php';

$read = static fn (): string => glance_text_setting('footer_note', 'fallback');

$prefs = ['glance/footer_noteen_US' => 'Hello', 'glance/footer_notehi_IN' => 'Namaste'];
$cur   = 'hi_IN';
$pin('locale B sees B text', 'Namaste', $read());
$cur = 'fr_FR';
$pin('a locale with no value gets the default locale text', 'Hello', $read());
$prefs = [];
$pin('nothing saved gives the fallback', 'fallback', $read());

$prefs = ['glance/footer_note' => 'Old line', 'glance/footer_notehi_IN' => 'Hindi line'];
$pin('the move is hooked on init', true, in_array('glance_migrate_text', $hooks['init'] ?? [], true));
$cur = 'hi_IN';
$pin('unmigrated old value still shows in a locale with no value', 'Hindi line', $read());
glance_migrate_text();
$pin('migration: old value copied, set one kept, old key gone', ['Old line', 'Hindi line', 'Old line', null], [
    $prefs['glance/footer_noteen_US'] ?? null, $prefs['glance/footer_notehi_IN'] ?? null,
    $prefs['glance/footer_notefr_FR'] ?? null, $prefs['glance/footer_note'] ?? null,
]);
$cur = 'fr_FR';
$pin('after migration a locale shows the old text', 'Old line', $read());
$prefs['glance/footer_notefr_FR'] = '';
$prefs['glance/footer_noteen_US'] = '';
$pin('cleared everywhere shows the fallback', 'fallback', $read());

$src = file_get_contents($root . '/functions.php');
$pin('footer note is declared translated', true, (bool) preg_match("/'footer_note'.*?->translate\(\)/s", $src));
$pin('footer reads it per language', true, str_contains(file_get_contents($root . '/common/footer.php'), 'glance_text_setting('));

if ($fail) {
    fwrite(STDERR, "FAIL\n  " . implode("\n  ", $fail) . "\n");
    exit(1);
}
echo "ok\n";
