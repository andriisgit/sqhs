jQuery(document).ready(function($) {

    jQuery('#sqhs-filter-by-date').on('change', function(e) {
        jQuery(this).closest('form')
            .trigger('submit')
    })

});