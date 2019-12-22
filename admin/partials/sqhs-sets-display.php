<?php

/**
 * Provide a admin area view of list of Questions Sets
 *
 * @link       biir.dk
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/admin/partials
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Questions Sets</h1>
    <a href="<?php echo wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=add-set&set=new'), 'addnewset' ); ?>" class="page-title-action">
        <?php _e('Add new'); ?>
    </a>

    <div class="meta-box-sortables ui-sortable">
        <form method="post">
            <?php
            $sqhs_sets->prepare_items();
            $sqhs_sets->display(); ?>
    </div>
</div>
