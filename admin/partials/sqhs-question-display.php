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
    <h1 class="wp-heading-inline"><?php echo isset($question['heading']) ? $question['heading'] : 'Add Question' ; ?></h1>
    <hr class="wp-header-end"/>


    <div id="col-container" class="wp-clearfix">

        <form method="post" name="question-save">

            <input type="hidden" name="question-id" value="<?php echo isset($question['id']) ? $question['id'] : ''; ?>"/>
            <input type="hidden" name="action" value="sqhs_questionsave"/>

            <div id="col-left">
                <div class="col-wrap">

                    <h2><?php echo isset($question['subheading']) ? $question['subheading'] : 'Add New Question'; ?></h2>

                    <div class="form-field term-description-wrap">
                        <label for="question-description">Question</label>
                        <textarea name="question-description" id="question-description" rows="5" cols="40"><?php echo isset($question['text']) ? $question['text'] : ''; ?></textarea>
                    </div><br/>
                    <div class="form-field term-description-wrap">
                        <label for="question-explanation">Question explanation </label>
                        <textarea name="question-explanation" id="question-explanation" rows="5" cols="40"><?php echo isset($question['explanation']) ? $question['explanation'] : ''; ?></textarea>
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



            <div class="term-description-wrap" id="answers_block" style="clear: both;">

                <label>Answers <a id="add_answer" href="javascript:void(0);"><span class="dashicons dashicons-plus-alt"></span></a></label>

                <?php echo $answers_block ? $answers_block : ''; ?>

            </div>
            <input type="hidden" name="answers_ids" value="<?php echo $answers_ids; ?>" />


            <p><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Question"/></p>

            <?php wp_nonce_field('savequestion'); ?>
        </form>

    </div>

</div>
