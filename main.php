<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Home: the category index, then the newest ads as the same list and reading panel as search.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

glance_enqueue('list');

$hm_cats  = array_values(array_filter((array) Category::getInstance()->toTree(), static fn($c) => isset($c['pk_i_id'])));
// The headline is the sum of the index below it, so the two numbers on this screen always agree.
$hm_total = array_sum(array_map(static fn($c) => (int) ($c['i_num_items'] ?? 0), $hm_cats));

osc_query_item(array('premium' => 1, 'results_per_page' => 4));
$hm_premium = (array) View::getInstance()->_get('customItems');
if (function_exists('osc_prime_item_upgrades')) {
    osc_prime_item_upgrades($hm_premium);
}

$hm_latest = Search::getInstance()->getLatestItems(max(1, osc_max_latest_items() ?: 20));
if (function_exists('osc_prime_item_upgrades')) {
    osc_prime_item_upgrades($hm_latest);
}
View::getInstance()->_exportVariableToView('latestItems', $hm_latest);

glance_prime_rows(array_merge($hm_premium, $hm_latest));

osc_current_web_theme_path('common/header.php');
?>
<div class="wrap home-top">
    <div class="home-head">
        <h1 class="tnum"><?php echo $hm_total > 0
            ? osc_esc_html(sprintf(_n('Browse %s ad', 'Browse %s ads', $hm_total, 'glance'), number_format($hm_total)))
            : osc_esc_html(__('Browse ads', 'glance')); ?></h1>
        <?php if (count($hm_cats) > 1) { ?>
            <p class="results-count tnum"><?php printf(
                osc_esc_html(__('in %s categories', 'glance')),
                number_format(count($hm_cats))
            ); ?></p>
        <?php } ?>
    </div>

    <?php if ($hm_cats) { ?>
        <nav class="cat-index" aria-label="<?php echo osc_esc_html(__('Categories', 'glance')); ?>">
            <ul>
                <?php foreach ($hm_cats as $hm_c) {
                    $hm_n = (int) ($hm_c['i_num_items'] ?? 0); ?>
                    <li<?php echo $hm_n === 0 ? ' class="is-empty"' : ''; ?>>
                        <a href="<?php echo osc_esc_html(osc_search_url(array('sCategory' => (int) $hm_c['pk_i_id']))); ?>">
                            <span class="cat-name"><?php echo osc_esc_html((string) ($hm_c['s_name'] ?? '')); ?></span>
                            <span class="cat-lead" aria-hidden="true"></span>
                            <span class="cat-count tnum"><?php echo number_format($hm_n); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </nav>
    <?php } ?>
</div>

<div class="wrap ledger" data-ledger>
    <section class="ledger-list" aria-labelledby="home-latest">
        <?php if ($hm_premium) { ?>
            <?php osc_run_hook('home_before_featured'); ?>
            <h2 class="list-heading" id="home-featured"><?php _e('Featured', 'glance'); ?></h2>
            <ol class="rows rows-featured" data-rows data-density="<?php echo glance_list_density(); ?>" aria-labelledby="home-featured">
                <?php while (osc_has_custom_items()) {
                    osc_current_web_theme_path('common/row.php');
                } ?>
            </ol>
            <?php osc_run_hook('home_after_featured'); ?>
        <?php } ?>

        <?php osc_run_hook('home_before_listings'); ?>

        <div class="list-heading-row">
            <h2 class="list-heading" id="home-latest"><?php _e('Latest ads', 'glance'); ?></h2>
            <a class="list-more" href="<?php echo osc_esc_html(glance_browse_all_url()); ?>"><?php _e('All ads', 'glance'); ?><?php echo glance_icon('arrow-right'); ?></a>
        </div>

        <?php if ($hm_latest) { ?>
            <ol class="rows" data-rows data-density="<?php echo glance_list_density(); ?>" aria-labelledby="home-latest">
                <?php while (osc_has_latest_items()) {
                    osc_current_web_theme_path('common/row.php');
                } ?>
            </ol>
            <p class="list-foot">
                <a class="btn" href="<?php echo osc_esc_html(glance_browse_all_url()); ?>"><?php _e('See all ads', 'glance'); ?><?php echo glance_icon('arrow-right'); ?></a>
            </p>
        <?php } else { ?>
            <div class="list-empty">
                <h2><?php _e('No ads yet', 'glance'); ?></h2>
                <p><?php _e('Be the first to list something.', 'glance'); ?></p>
                <?php if (osc_item_post_url_in_category() !== '') { ?>
                    <p class="list-empty-actions"><a class="btn btn-primary" href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>"><?php _e('Post an ad', 'glance'); ?></a></p>
                <?php } ?>
            </div>
        <?php } ?>

        <?php osc_run_hook('home_after_listings'); ?>
    </section>

    <?php osc_current_web_theme_path('common/reader.php'); ?>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
