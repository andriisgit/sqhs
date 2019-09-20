<h1><?php _e('Error') ?></h1>
<p>
    <?php
    if ( isset($err_message) )
        _e($err_message);
    ?>
</p>