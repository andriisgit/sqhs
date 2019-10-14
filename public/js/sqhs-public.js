jQuery(document).ready(function($) {

    jQuery("#sqhs_start").submit(function (e) {

        e.preventDefault();

        var dataJSON = jQuery(this).serialize();

        $.ajax({
            cache: false,
            type: "POST",
            url: wp_ajax.ajax_url,
            data: dataJSON,
            beforeSend: function () {
                $("form#sqhs_start button[type=submit]").html('...')
            },
            success: function( response ){
                if ( response.status == "OK" )
                    show_question(response.question);
                else
                    $("#sqhs_center_body").html(response.message)
            },
            error: function( xhr, status, error ) {
                console.log( 'Status: ' + xhr.status );
                console.log( 'Error: ' + xhr.responseText );
            }
        });

    });


    function show_question(question) {
        $("#sqhs_upper_note").html(question.number.toString() + " / " + question.total.toString());
        $("#sqhs_center_body").html(question.text);
        $("input[name=question]").val(question.id);
    }


    /*
     * Fingerprintjs (v2.10)
     */
    var fpoptions = {excludes: {userAgent: true}};
    var fingerprintReport = function () {
        Fingerprint2.get(function (components) {
            var murmur = Fingerprint2.x64hash128(components.map(function (pair) { return pair.value }).join(), 31);
            jQuery("#fingerprint").val(murmur);
        })
    };
    var cancelId, cancelFunction;

    if (window.requestIdleCallback) {
        cancelId = requestIdleCallback(fingerprintReport);
        cancelFunction = cancelIdleCallback
    } else {
        cancelId = setTimeout(fingerprintReport, 1500);
        cancelFunction = clearTimeout
    }

});

