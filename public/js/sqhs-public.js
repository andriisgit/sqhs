jQuery(document).ready(function($) {

    jQuery("#sqhs_start").submit(function (e) {

        e.preventDefault();

        var dataJSON = jQuery(this).serialize();

        $.ajax({
            cache: false,
            type: "POST",
            url: wp_ajax.ajax_url,
            data: dataJSON,
            success: function( response ){
                //result = jQuery.parseJSON(response);
                show_quiz();
            },
            error: function( xhr, status, error ) {
                console.log( 'Status: ' + xhr.status );
                console.log( 'Error: ' + xhr.responseText );
            }
        });

    });


    function show_quiz() {
        console.log("QUIZ");
        //document.getElementById("sqhs_header").innerHTML = "QUIZ";
    }


    /*
     * Fingerprintjs (v2.10)
     */
    var fpoptions = {excludes: {userAgent: true}};
    var fingerprintReport = function () {
        Fingerprint2.get(function (components) {
            var murmur = Fingerprint2.x64hash128(components.map(function (pair) { return pair.value }).join(), 31);
            jQuery("#fingerprint").val(murmur);
            //jQuery("p.site-description").html(murmur)
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

