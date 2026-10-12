<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The reading panel beside the list (search and home). js/list.js fills it with the ad's own
 * [data-glance-panel] part; on a phone the same inner block moves into the bottom sheet dialog.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$rd_pos = (array) View::getInstance()->_get('glance_list_pos');
?>
<aside class="reader" data-reader aria-label="<?php echo osc_esc_html(__('Ad reader', 'glance')); ?>">
    <div class="reader-inner" data-reader-inner>
        <div class="reader-bar" data-reader-bar>
            <span class="reader-grip" data-reader-grip aria-hidden="true"></span>
            <button type="button" class="btn btn-quiet btn-icon" data-reader-close>
                <?php echo glance_icon('x', __('Close ad', 'glance')); ?>
            </button>
            <div class="reader-step" role="group" aria-label="<?php echo osc_esc_html(__('Move through the results', 'glance')); ?>">
                <button type="button" class="btn btn-quiet btn-icon" data-reader-prev>
                    <?php echo glance_icon('chevron-left', __('Previous ad', 'glance')); ?>
                </button>
                <span class="reader-pos tnum" data-reader-pos data-format="<?php echo osc_esc_html(__('%1$s of %2$s', 'glance')); ?>"
                      data-offset="<?php echo (int) ($rd_pos['offset'] ?? 0); ?>" data-total="<?php echo (int) ($rd_pos['total'] ?? 0); ?>"></span>
                <button type="button" class="btn btn-quiet btn-icon" data-reader-next>
                    <?php echo glance_icon('chevron-right', __('Next ad', 'glance')); ?>
                </button>
                <?php if (!empty($rd_pos['next'])) { ?>
                    <a class="btn btn-quiet reader-nextpage" href="<?php echo osc_esc_html($rd_pos['next']); ?>" data-reader-nextpage hidden>
                        <span><?php _e('Next page', 'glance'); ?></span><?php echo glance_icon('arrow-right'); ?>
                    </a>
                <?php } ?>
            </div>
            <a class="btn btn-quiet reader-full" href="#" data-reader-full>
                <span><?php _e('Full page', 'glance'); ?></span><?php echo glance_icon('external-link'); ?>
            </a>
            <p class="reader-hints" aria-hidden="true">
                <span><kbd><?php echo glance_icon('chevron-up'); ?></kbd><kbd><?php echo glance_icon('chevron-down'); ?></kbd> <?php _e('move', 'glance'); ?></span>
                <span><kbd><?php echo glance_icon('chevron-left'); ?></kbd><kbd><?php echo glance_icon('chevron-right'); ?></kbd> <?php _e('next ad', 'glance'); ?></span>
                <span><kbd><?php _e('Esc', 'glance'); ?></kbd> <?php _e('close', 'glance'); ?></span>
            </p>
        </div>
        <div class="reader-body" data-reader-body tabindex="-1">
            <div class="reader-empty" data-reader-empty>
                <?php echo glance_icon('layers'); ?>
                <p class="reader-empty-js"><?php _e('Pick an ad to read it here.', 'glance'); ?></p>
                <p class="reader-empty-nojs"><?php _e('Pick an ad to read it in full.', 'glance'); ?></p>
                <p class="reader-keys">
                    <span><kbd><?php echo glance_icon('chevron-up'); ?><span class="visually-hidden"><?php _e('Up arrow', 'glance'); ?></span></kbd><kbd><?php echo glance_icon('chevron-down'); ?><span class="visually-hidden"><?php _e('Down arrow', 'glance'); ?></span></kbd> <?php _e('move', 'glance'); ?></span>
                    <span><kbd><?php _e('Enter', 'glance'); ?></kbd> <?php _e('open', 'glance'); ?></span>
                    <span><kbd><?php _e('Esc', 'glance'); ?></kbd> <?php _e('close', 'glance'); ?></span>
                </p>
            </div>
            <div class="reader-loading" data-reader-loading aria-hidden="true">
                <span class="reader-loading-media"></span>
                <p class="reader-loading-title" data-reader-loading-title></p>
                <span class="reader-loading-bar is-price"></span>
                <span class="reader-loading-bar"></span>
                <span class="reader-loading-bar"></span>
                <span class="reader-loading-bar"></span>
                <span class="reader-loading-bar"></span>
            </div>
        </div>
    </div>
</aside>
<dialog class="sheet reader-sheet" data-reader-sheet aria-label="<?php echo osc_esc_html(__('Ad', 'glance')); ?>"></dialog>
<p class="visually-hidden" data-reader-status role="status" data-loading="<?php echo osc_esc_html(__('Loading ad…', 'glance')); ?>"></p>
