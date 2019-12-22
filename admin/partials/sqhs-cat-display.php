<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       biir.dk
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/admin/partials
 */
?>
<div class="wrap">

<h1>Categories</h1>

<div class="wp-clearfix">

    <div id="col-left">
        <div class="col-wrap">
            <div class="form-wrap">
                <h2>Add New Category</h2>
                <form method="post">
                    <div class="form-field form-required term-name-wrap">
                        <label for="catt-name"><?php _e('Category name'); ?></label>
                        <input name="cat-name" type="text" value="" maxlength="48" required size="40" aria-required="true">
                        <p>The name of category.</p>
                    </div>
                    <div class="form-field term-description-wrap">
                        <label for="cat_descr"><?php _e('Category description'); ?></label>
                        <textarea name="cat_descr" rows="5" cols="40"></textarea>
                        <p>The description of category.</p>
                    </div>
                    <input type="hidden" name="action" value="add-cat">
                    <input type="hidden" name="cat" value="">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>">
                    <?php wp_nonce_field('add-cat'); ?>
                    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add New Category"></p>
                </form>
            </div>
        </div>
    </div>

    <div id="col-right">
        <div class="col-wrap">

            <div class="meta-box-sortables ui-sortable">
                <form method="post">
                    <?php
                    $sqhs_categories->prepare_items();
                    $sqhs_categories->display(); ?>
            </div>

        </div>
    </div>

</div>

</div>