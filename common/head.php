<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_on_item = osc_is_ad_page();
$glance_desc    = meta_description();
$glance_image   = '';

if ($glance_on_item && osc_images_enabled_at_items() && osc_count_item_resources() > 0) {
    osc_reset_resources();
    if (osc_has_item_resources()) {
        $glance_image = osc_resource_url();
    }
    osc_reset_resources();
}
if ($glance_image === '') {
    $logo = glance_logo_url();
    if ($logo !== '' && !preg_match('/\.svg$/i', (string) parse_url($logo, PHP_URL_PATH))) {
        $glance_image = $logo;
    }
}
?>
<script>var d=document.documentElement;d.classList.add('js');if(window.HTMLDialogElement)d.classList.add('can-dialog');try{var t=localStorage.getItem('glance-theme');if(t==='light'||t==='dark')d.dataset.theme=t;localStorage.setItem('glance-probe','1');localStorage.removeItem('glance-probe');d.classList.add('can-save')}catch(e){}</script>
<?php osc_head(); ?>
<link rel="preload" href="<?php echo osc_esc_html(osc_current_web_theme_url('fonts/schibsted-grotesk-latin-wght-normal.woff2')); ?>" as="font" type="font/woff2" crossorigin>
<?php echo glance_late_styles(); ?>
<?php echo glance_accent_style(); ?>
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#161a24" media="(prefers-color-scheme: dark)">
<meta property="og:type" content="<?php echo $glance_on_item ? 'product' : 'website'; ?>">
<meta property="og:site_name" content="<?php echo osc_esc_html(osc_page_title()); ?>">
<meta property="og:title" content="<?php echo osc_esc_html($glance_on_item ? osc_item_title() : strip_tags(meta_title())); ?>">
<?php if ($glance_desc !== '') { ?>
<meta property="og:description" content="<?php echo osc_esc_html($glance_desc); ?>">
<?php } ?>
<?php if (osc_get_canonical() !== '') { ?>
<meta property="og:url" content="<?php echo osc_esc_html(osc_get_canonical()); ?>">
<?php } ?>
<?php if ($glance_image !== '') { ?>
<meta property="og:image" content="<?php echo osc_esc_html($glance_image); ?>">
<meta name="twitter:card" content="summary_large_image">
<?php } else { ?>
<meta name="twitter:card" content="summary">
<?php } ?>
