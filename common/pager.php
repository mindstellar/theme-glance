<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Results pages. The URL counts from 1 (page 1 has no iPage); osc_search_page() counts from 0.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$pg_total = (int) osc_search_total_pages();
$pg_cur   = (int) osc_search_page() + 1;

if ($pg_total < 2) {
    return;
}
?>
<nav class="pager-nav" aria-label="<?php echo osc_esc_html(__('Results pages', 'glance')); ?>">
    <p class="pager-range tnum"><?php printf(
        osc_esc_html(__('%1$s–%2$s of %3$s', 'glance')),
        number_format((int) osc_search_start() + 1),
        number_format((int) osc_search_end()),
        osc_esc_html(function_exists('osc_search_total_text') ? osc_search_total_text() : number_format((int) osc_search_total_items()))
    ); ?></p>
    <ul class="pager">
        <li class="pager-step">
            <?php if ($pg_cur > 1) { ?>
                <a rel="prev" href="<?php echo osc_esc_html(glance_page_url($pg_cur - 1)); ?>"><?php echo glance_icon('chevron-left'); ?><span><?php _e('Previous', 'glance'); ?></span></a>
            <?php } else { ?>
                <span aria-disabled="true"><?php echo glance_icon('chevron-left'); ?><span><?php _e('Previous', 'glance'); ?></span></span>
            <?php } ?>
        </li>
        <?php foreach (glance_pager_pages($pg_cur, $pg_total) as $pg_n) { ?>
            <?php if ($pg_n === 0) { ?>
                <li class="pager-gap" aria-hidden="true"><span>…</span></li>
            <?php } elseif ($pg_n === $pg_cur) { ?>
                <li><a href="<?php echo osc_esc_html(glance_page_url($pg_n)); ?>" aria-current="page"><span class="visually-hidden"><?php _e('Page', 'glance'); ?> </span><?php echo $pg_n; ?></a></li>
            <?php } else { ?>
                <li><a href="<?php echo osc_esc_html(glance_page_url($pg_n)); ?>"><span class="visually-hidden"><?php _e('Page', 'glance'); ?> </span><?php echo $pg_n; ?></a></li>
            <?php } ?>
        <?php } ?>
        <li class="pager-step">
            <?php if ($pg_cur < $pg_total) { ?>
                <a rel="next" href="<?php echo osc_esc_html(glance_page_url($pg_cur + 1)); ?>"><span><?php _e('Next', 'glance'); ?></span><?php echo glance_icon('chevron-right'); ?></a>
            <?php } else { ?>
                <span aria-disabled="true"><span><?php _e('Next', 'glance'); ?></span><?php echo glance_icon('chevron-right'); ?></span>
            <?php } ?>
        </li>
    </ul>
</nav>
