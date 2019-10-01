<?php
/**
 * Provide a admin area view for the plugin
 *
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/admin/partials
 */
?>
<div id="sqhs_notice" class="notice is-dismissible" style="display: none;"><p id="sqhs_notice_msg"></p></div>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo isset($set['heading']) ? $set['heading'] : 'Add Questions Set' ; ?></h1>
    <hr class="wp-header-end">


    <div id="col-container" class="wp-clearfix">

        <form method="post" name="set-save">

        <input type="hidden" name="set-id" value="<?php echo isset($set['id']) ? $set['id'] : ''; ?>">
        <input type="hidden" name="action" value="sqhs_setsave">

        <div id="col-left">
            <div class="col-wrap">

                <h2><?php echo isset($set['subheading']) ? $set['subheading'] : 'Add New Set'; ?></h2>

                <div class="form-field term-description-wrap">
                    <label for="set-name">Set name</label>
                    <input name="set-name" id="set-name" type="text" value="<?php echo isset($set['name']) ? $set['name'] : '' ; ?>" required size="40" aria-required="true">
                </div>
                <div class="form-field term-description-wrap">
                    <label for="set-description">Description</label>
                    <textarea name="set-description" id="set-description" rows="5" cols="40"><?php echo isset($set['description']) ? $set['description'] : ''; ?></textarea>
                </div>
                <label for="max_que_qua">Max question quantity in the Set:&nbsp;&nbsp;</label>
                <input name="max_que_qua" type="number" step="1" min="0" id="max_que_qua" value="<?php echo isset($set['max_question_quantity']) ? $set['max_question_quantity'] : 5; ?>" class="small-text">
                <div class="form-field term-description-wrap">
                   <p><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Set"></p>
               </div>

            </div>
        </div>

        <div id="col-right">
            <div class="col-wrap">

                <div id="poststuff">

                    <div id="categorydiv" class="postbox ">
                        <h2 class="hndle ui-sortable-handle"><span>Categories</span></h2>
                        <div class="inside">
                            <div id="taxonomy-category" class="categorydiv">
                                <div id="category-all" class="tabs-panel">
                                    <ul id="categorychecklist" data-wp-lists="list:category" class="categorychecklist form-no-clear">
                                        <?php echo $li; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        <?php wp_nonce_field('saveset'); ?>
        </form>

    </div>


</div>

