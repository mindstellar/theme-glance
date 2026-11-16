<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/**
 * The footer note is one value per language, stored as the key plus the locale code.
 * Reads the visitor's language, then the site default, then the old single value.
 */
function glance_text_setting(string $name, string $default = ''): string
{
    foreach (array(osc_current_user_locale(), osc_language()) as $code) {
        $value = (string) osc_get_preference($name . $code, 'glance');
        if ($value !== '') {
            return $value;
        }
    }
    $old = (string) osc_get_preference($name, 'glance');

    return $old !== '' ? $old : $default;
}

/**
 * Moves the old single footer note to every enabled language, then drops it, so nothing
 * changes on screen. A language that already has a value keeps it.
 */
function glance_migrate_text(): void
{
    $old = (string) osc_get_preference('footer_note', 'glance');
    if ($old === '') {
        return;
    }
    foreach (array_keys(osc_settings_locales()) as $code) {
        if ((string) osc_get_preference('footer_note' . $code, 'glance') === '') {
            osc_set_preference('footer_note' . $code, $old, 'glance');
        }
    }
    osc_delete_preference('footer_note', 'glance');
    osc_reset_preferences();
}

osc_add_hook('init', 'glance_migrate_text', 1);
