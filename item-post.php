<?php
/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Post and edit share this view; core's ItemForm supplies the fields, the
 * hidden route fields and the edit state.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$glance_edit   = osc_is_edit_page();
$glance_guest  = !osc_is_web_user_logged_in();
$glance_back   = $glance_edit ? osc_item_url() : osc_base_url();
$glance_title  = ItemForm::locale_field_id('title');
$glance_desc   = ItemForm::locale_field_id('description');

View::getInstance()->_exportVariableToView('meta_noindex', true);
glance_enqueue('post');
osc_current_web_theme_path('common/header.php');
?>
<div class="wrap post-wrap">
    <header class="post-head">
        <h1><?php echo $glance_edit ? osc_esc_html(__('Edit your ad', 'glance')) : osc_esc_html(__('Post an ad', 'glance')); ?></h1>
        <p><?php
        if ($glance_edit) {
            echo osc_esc_html(sprintf(__('Changing “%s”.', 'glance'), osc_item_title()));
        } else {
            _e('Clear photos and exact details help buyers compare your ad with others.', 'glance');
        }
        ?></p>
    </header>

    <form class="post-form" name="item" id="item-post" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="post" enctype="multipart/form-data" data-post-form
          data-msg-required="<?php echo osc_esc_html(__('This is required.', 'glance')); ?>"
          data-msg-email="<?php echo osc_esc_html(__('Enter a valid email address.', 'glance')); ?>"
          data-msg-short="<?php echo osc_esc_html(__('Write a little more.', 'glance')); ?>"
          data-msg-over="<?php echo osc_esc_html(__('This is over the length limit.', 'glance')); ?>"
          data-msg-number="<?php echo osc_esc_html(__('Enter a number, like 1200 or 1200.50.', 'glance')); ?>"
          data-msg-currency="<?php echo osc_esc_html(__('Currency', 'glance')); ?>"
          data-msg-subcat="<?php echo osc_esc_html(__('Subcategory', 'glance')); ?>"
          data-msg-subcat-pick="<?php echo osc_esc_html(__('Choose a subcategory', 'glance')); ?>"
          data-msg-fields="<?php echo osc_esc_html(__('Details for %s are ready below.', 'glance')); ?>"
          data-msg-nofields="<?php echo osc_esc_html(__('%s has no extra details to fill in.', 'glance')); ?>">
        <?php ItemForm::route_hidden(); ?>
        <ul id="error_list" class="post-errors" role="alert"></ul>

        <?php glance_post_section('what', __('What are you selling?', 'glance')); ?>
            <?php glance_post_field('catId', __('Category', 'glance'), static function () {
                ItemForm::category_select(null, null, __('Choose a category', 'glance'));
            }, array('class' => 'post-cat')); ?>
            <?php glance_post_field($glance_title, __('Title', 'glance'), static function () {
                ItemForm::title_input('title', null, osc_esc_html(osc_item_title()));
            }, array('max' => osc_max_characters_per_title(), 'hint' => __('Lead with what it is: make, model, size.', 'glance'))); ?>
        <?php glance_post_section_end(); ?>

        <?php glance_post_section('description', __('Description', 'glance'), __('Condition, history, what comes with it.', 'glance')); ?>
            <?php glance_post_field($glance_desc, __('Description', 'glance'), static function () {
                ItemForm::description_textarea('description', null, osc_esc_html(osc_item_description()));
            }, array('max' => osc_max_characters_per_description(), 'quiet' => true)); ?>
        <?php glance_post_section_end(); ?>

        <?php if (osc_images_enabled_at_items()) { ?>
            <?php glance_post_section('photos', __('Photos', 'glance'), __('The first photo is the cover. Ads with photos get read far more.', 'glance')); ?>
                <?php ItemForm::ajax_photos(); ?>
            <?php glance_post_section_end(); ?>
        <?php } ?>

        <?php glance_post_section('details', __('Details', 'glance'), '', 'post-details'); ?>
            <?php if (osc_price_enabled_at_items()) { ?>
                <div class="post-field post-price">
                    <label for="price"><?php _e('Price', 'glance'); ?> <span class="post-opt"><?php _e('optional', 'glance'); ?></span></label>
                    <div class="post-price-row">
                        <?php ItemForm::price_input_text(); ?>
                        <?php ItemForm::currency_select(osc_get_currencies(), osc_item()); ?>
                    </div>
                    <p class="post-hint" id="price-hint"><?php _e('Leave empty to show “Check with seller”.', 'glance'); ?></p>
                </div>
            <?php } ?>
            <p class="post-details-note" data-post-details-note hidden><?php _e('Pick a category to see the details buyers compare.', 'glance'); ?></p>
            <?php ItemForm::plugin_item_fields(); ?>
            <p class="visually-hidden" aria-live="polite" data-post-details-live></p>
        <?php glance_post_section_end(); ?>

        <?php glance_post_section('where', __('Where is it?', 'glance')); ?>
            <div class="post-grid post-grid-3">
                <?php glance_post_field('countryId', __('Country', 'glance'), static function () {
                    ItemForm::country_select(osc_get_countries(), ItemForm::location_record());
                }, array('optional' => true)); ?>
                <?php glance_post_field('regionId', __('Region', 'glance'), static function () {
                    ItemForm::region_select(osc_get_regions(ItemForm::selected_country()), ItemForm::location_record());
                }); ?>
                <?php glance_post_field('cityId', __('City', 'glance'), static function () {
                    ItemForm::city_select(osc_get_cities(ItemForm::selected_region()), ItemForm::location_record());
                }); ?>
            </div>
            <?php glance_post_field('address', __('Street or area', 'glance'), static function () {
                ItemForm::address_text(ItemForm::location_record());
            }, array('optional' => true)); ?>
        <?php glance_post_section_end(); ?>

        <?php glance_post_section('you', __('How buyers reach you', 'glance')); ?>
            <?php if ($glance_guest) { ?>
                <div class="post-grid">
                    <?php glance_post_field('contactName', __('Your name', 'glance'), static function () {
                        ItemForm::contact_name_text();
                    }); ?>
                    <?php glance_post_field('contactEmail', __('Email', 'glance'), static function () {
                        ItemForm::contact_email_text();
                    }, array('hint' => __('We send the link to manage your ad here.', 'glance'))); ?>
                </div>
            <?php } ?>
            <?php glance_post_field('contactPhone', __('Phone', 'glance'), static function () {
                ItemForm::contact_phone_text();
            }, array('optional' => true)); ?>
            <?php if ($glance_guest) { ?>
                <div class="post-check">
                    <?php ItemForm::show_email_checkbox(); ?>
                    <label for="showEmail"><?php _e('Show my email on the ad', 'glance'); ?></label>
                </div>
            <?php } ?>
        <?php glance_post_section_end(); ?>

        <?php if (osc_recaptcha_items_enabled() && osc_captcha_enabled()) { ?>
            <div class="post-captcha"><?php osc_show_captcha('item_post'); ?></div>
        <?php } ?>

        <div class="post-submit">
            <a class="btn btn-quiet" href="<?php echo osc_esc_html($glance_back); ?>"><?php _e('Cancel', 'glance'); ?></a>
            <button class="btn btn-primary post-send" type="submit"><?php echo $glance_edit ? osc_esc_html(__('Save changes', 'glance')) : osc_esc_html(function_exists('osc_item_post_submit_label') ? osc_item_post_submit_label() : __('Publish ad', 'glance')); ?></button>
        </div>
    </form>
</div>
<?php ItemForm::location_javascript(); ?>
<?php osc_current_web_theme_path('common/footer.php'); ?>
