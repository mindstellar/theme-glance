<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_modified = (string) osc_static_page_mod_date();
$glance_stamp    = $glance_modified === '' ? false : strtotime($glance_modified);

glance_enqueue('account');
osc_current_web_theme_path('common/header.php');
?>
<div class="wrap">
    <article class="glance-page">
        <h1><?php echo osc_esc_html(osc_static_page_title()); ?></h1>
        <div class="prose"><?php echo osc_static_page_text(); ?></div>
        <?php if ($glance_stamp !== false && $glance_stamp > 0) { ?>
            <p class="glance-page-meta"><?php printf(
                osc_esc_html(__('Last updated %s', 'glance')),
                '<time datetime="' . osc_esc_html(date('c', $glance_stamp)) . '">' . osc_esc_html(osc_format_date($glance_modified)) . '</time>'
            ); ?></p>
        <?php } ?>
    </article>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
