<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       biir.dk
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/public/partials
 */
?>
<h1 id="sqhs_header">Welcome to Quiz Set No. <?php echo $atts['set']; ?></h1>
<form id="sqhs_start">

    <div id="sqhs_upper_note"></div>
    <div id="sqhs_center_body"></div>
    <div id="sqhs_bottom_button"><button type="submit">Go</button></div>

    <?php wp_nonce_field(); ?>
    <input type="hidden" name="question" value="">
    <input type="hidden" name="action" value="sqhs_quiz_begin">
    <input type="hidden" name="set" value="<?php echo $atts['set']; ?>">
    <input type="hidden" name="fingerprint" id="fingerprint" value="">

</form>
