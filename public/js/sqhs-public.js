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
		document.getElementById("sqhs_header").innerHTML = "QUIZ";
	}

});

