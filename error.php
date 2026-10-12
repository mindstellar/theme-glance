<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_error = View::getInstance()->_get('error');
if (!is_string($glance_error) || $glance_error === '') {
    $glance_error = __('Something went wrong. Please try again in a moment.', 'glance');
}

glance_enqueue('account');
osc_current_web_theme_path('common/header.php');
?>
<div class="wrap">
    <section class="glance-notfound" aria-labelledby="glance-error-title">
        <h1 id="glance-error-title"><?php _e('Something went wrong', 'glance'); ?></h1>
        <p class="glance-lead"><?php echo osc_esc_html($glance_error); ?></p>
        <p class="glance-links">
            <a class="btn btn-primary" href="<?php echo osc_esc_html(osc_base_url()); ?>"><?php _e('Go to the home page', 'glance'); ?></a>
            <a class="btn" href="<?php echo osc_esc_html(osc_contact_url()); ?>"><?php _e('Contact us', 'glance'); ?></a>
        </p>
    </section>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
