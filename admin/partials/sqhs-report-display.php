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
    <h1 class="wp-heading-inline">Completed quizzes</h1>
    <div class="meta-box-sortables ui-sortable">
        <form method="GET">
            <?php
            $sqhs_quizzes->prepare_items();
            $sqhs_quizzes->my_dropdown();
            $sqhs_quizzes->search_box('Email search', 'search_id');
            $sqhs_quizzes->display();
            ?>
    </div>
</div>
