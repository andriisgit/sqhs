<?php
/**
 * Provide a admin area Final settings view for the SQHS plugin
 *
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/admin/partials
 */
?>

<div id="sqhs_notice" class="notice is-dismissible" <?php echo (isset($notice) && $notice) ? '' : 'style="display: none;"'; ?>>
	<p id="sqhs_notice_msg"><?php echo (isset($notice) && $notice) ? $notice : ''; ?></p>
</div>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('Final screen settings', 'sqhs' ) ; ?></h1>
	<hr class="wp-header-end"/>


	<div id="col-container" class="wp-clearfix">

		<form method="post" name="sqhs_final_settings">

			<div class="term-description-wrap" id="sqhs_final_settings_block" style="clear: both;">

                <p>
                    Required fields to the message be shown at final screen are <code>Range</code> and <code>Text</code>.
                </p>
                <p>
                    To remove the setting string just leave <code>Range</code> and <code>Text</code> empty.
                </p>

				<a id="sqhs_add_new_final_setting" href="javascript:void(0);" title="Add new setting"><span class="dashicons dashicons-plus-alt media-disabled"></span></a>
				<fieldset>
					<?php echo $fieldset ? $fieldset : ''; ?>
				</fieldset>
			</div>

            <input type="hidden" name="sqhs_final_settings_sets" value="">
			<!--<p><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Question"/></p>-->

			<?php
			submit_button();
			wp_nonce_field('sqhsfinalsetting');
			?>
		</form>

	</div>

</div>
