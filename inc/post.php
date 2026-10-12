<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Helpers for the post and edit ad form (item-post.php).
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

/**
 * One labelled field. $input prints core's control. Options: hint, optional, class,
 * quiet (label kept for screen readers only, when the section heading already says it),
 * max (character limit for the live counter), tag ('div' by default; core hides the
 * price field through its nearest div, so that one must be a div).
 */
function glance_post_field(string $for, string $label, callable $input, array $opt = array()): void
{
    $class = 'post-field' . (empty($opt['class']) ? '' : ' ' . $opt['class']);
    $max   = (int) ($opt['max'] ?? 0);

    echo '<div class="' . osc_esc_html($class) . '"' . ($max > 0 ? ' data-max="' . $max . '"' : '') . '>';
    echo '<label for="' . osc_esc_html($for) . '"' . (empty($opt['quiet']) ? '' : ' class="visually-hidden"') . '>' . osc_esc_html($label);
    if (!empty($opt['optional'])) {
        echo ' <span class="post-opt">' . osc_esc_html(__('optional', 'glance')) . '</span>';
    }
    echo '</label>';
    $input();
    if (!empty($opt['hint'])) {
        echo '<p class="post-hint" id="' . osc_esc_html($for) . '-hint">' . osc_esc_html($opt['hint']) . '</p>';
    }
    echo '</div>';
}

/** Opens a form section. Close it with glance_post_section_end(). */
function glance_post_section(string $id, string $title, string $lede = '', string $class = ''): void
{
    echo '<section class="post-section' . ($class === '' ? '' : ' ' . osc_esc_html($class)) . '" aria-labelledby="post-' . osc_esc_html($id) . '">';
    echo '<div class="post-section-head"><h2 id="post-' . osc_esc_html($id) . '">' . osc_esc_html($title) . '</h2>';
    if ($lede !== '') {
        echo '<p>' . osc_esc_html($lede) . '</p>';
    }
    echo '</div><div class="post-section-body">';
}

function glance_post_section_end(): void
{
    echo '</div></section>';
}
