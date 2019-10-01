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
    <h1 class="wp-heading-inline">SQHS Questions</h1>
    <a href="<?php echo wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=add-question&question=new'), 'addnewquestion' ); ?>" class="page-title-action">
        <?php _e('Add new'); ?>
    </a>

    <div class="meta-box-sortables ui-sortable">
        <form method="post">
            <?php
            $sqhs_questions->prepare_items();
            $sqhs_questions->display(); ?>
    </div>
</div>
