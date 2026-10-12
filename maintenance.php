<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/* Standalone: core serves this without chrome, so it links the base stylesheet for tokens and the font. */
$glance_site    = osc_page_title();
$glance_message = defined('OSC_MAINTENANCE_MESSAGE') ? (string) OSC_MAINTENANCE_MESSAGE : '';
?><!doctype html>
<html <?php osc_language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo osc_esc_html(sprintf(__('%s is down for maintenance', 'glance'), $glance_site)); ?></title>
<link rel="stylesheet" href="<?php echo osc_esc_html(osc_asset_url_versioned(osc_current_web_theme_url('css/glance.css'))); ?>">
<style>
.maint { flex: 1; display: grid; place-content: center; justify-items: start; gap: var(--sp-3); max-width: 34rem; margin-inline: auto; padding: var(--sp-7) var(--gutter); }
.maint h1 { font-size: var(--fs-xxl); }
.maint p { margin: 0; color: var(--muted); font-size: var(--fs-l); }
.maint .brand-mark { margin-bottom: var(--sp-3); }
</style>
</head>
<body>
<main class="maint">
    <span class="brand-mark" aria-hidden="true"><?php echo glance_icon('list'); ?></span>
    <h1><?php _e('We will be back soon', 'glance'); ?></h1>
    <p><?php echo $glance_message !== '' ? nl2br(osc_esc_html($glance_message), false) : osc_esc_html(sprintf(__('%s is down for a short maintenance. Please try again in a few minutes.', 'glance'), $glance_site)); ?></p>
</main>
</body>
</html>
