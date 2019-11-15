jQuery(document).ready(function ($) {

    jQuery("#sqhs_start").submit(function (e) {

        e.preventDefault();

        var dataJSON = jQuery(this).serialize();

        /*
        * The response we are waiting for to receive back:
        *
        * string action : binded action function name
        * string set    : quiz id
        *
        * array question: [ id, number, total, text ]
        *
        * array answers : [ [ id, text ] ...  ]
        *
        * array correct : empty - for question / [ id, 0/1 ]
        *
        */

        $.ajax({
            cache: false,
            type: "POST",
            url: wp_ajax.ajax_url,
            data: dataJSON,
            dataType: "json",
            beforeSend: function () {
                $("form#sqhs_start button[type=submit]").html('...')
            },
            success: function (response) {
                $("input[name='action']").val(response.action);
				if (response.mode) {
					$("input[name='mode']").val(response.mode);
				}
                if (response.question) {
                    sqhs_fill_upper(response.question.number.toString() + " / " + response.question.total.toString())
                    sqhs_fill_center(response.question);
                    sqhs_show_answers(response.answers, response.correct);
                    $("input[name='set']").val(response.quiz)
                }
                if (response.anketa) {
                    sqhs_fill_upper(response.anketa.header);
                    sqhs_fill_anketa_body(response.anketa.body);
                    sqhs_show_radio_bottom(response.anketa.question, response.anketa.button);
                }
            },
            error: function (xhr, status, error) {
                // Remove previous
                jQuery("#sqhs_bottom_button button[type='submit']").remove();
                jQuery("#sqhs_bottom_button input").remove();
                jQuery("#sqhs_bottom_button").html("");

                $("#sqhs_center_body").html(xhr.responseText);
            }
        });

    });


    function sqhs_fill_upper( text ) {
        $("#sqhs_upper_note").html( text );
    }

    function sqhs_fill_center(body) {
        $("#sqhs_center_body").html(body.text);
        $("input[name=question]").val(body.id)
    }


    function sqhs_fill_anketa_body(body) {
        jQuery("#sqhs_center_body").html("");
        $("#sqhs_center_body").append(body);
    }


	/**
	* @param array a 
	* @param string b Button label
	*/
    function sqhs_show_radio_bottom(a, b) {
        jQuery("#sqhs_bottom_button button[type='submit']").remove();
        jQuery("#sqhs_bottom_button input").remove();
        jQuery("#sqhs_bottom_button").html("");

		if (a) {
			var html = "";
			for (var i = 0; i < a.length; i++) {
				html = html + '<input type="radio" name="sqhs_kurs" value="' + a[i].id + '" id="sqhs_kurs' + a[i].id + '"><label for="sqhs_kurs' + a[i].id + '">' + a[i].text + '</label><br/>';
			}
			html = html + '<button type="submit" class="sqhs_final">' + b + '</button>';
			$("#sqhs_bottom_button").append(html);
		}
    }


    /**
     *
     * @param array a answers looks like [ [ id, text ] ...  ]
     * @param array|emplty c empty - for question / [ id, 0/1 ]
     */
    function sqhs_show_answers(a, c) {
        // Remove previous
        jQuery("#sqhs_bottom_button button[type='submit']").remove();
        jQuery("#sqhs_bottom_button input").remove();
        jQuery("#sqhs_bottom_button").html("");

        // Check for yes/no answers type
        var html = "", correct = "";
        /* if(a.length == 2) {
            var yesno = false,
                a1 = a[0].text.toLowerCase(),
                a2 = a[1].text.toLowerCase();
            // Ukrainian
            if ((a1 == 'так' && a2 == 'ні') || (a2 == 'так' && a1 == 'ні'))
                yesno = true;
            // English
            if ( !yesno && ((a1 == 'yes' && a2 == 'no') || (a2 == 'yes' && a1 == 'no')))
                yesno = true;
            // Danish
            if ( !yesno && ((a1 == 'ja' && a2 == 'nej') || (a2 == 'ja' && a1 == 'nej')))
                yesno = true;
            // Spanish
            if ( !yesno && ((a1 == 'si' && a2 == 'no') || (a2 == 'si' && a1 == 'no')))
                yesno = true;

            if (yesno) {
                html ='<button type="submit" name="answer[]" value="';
                html = html + a[0].id + '">' + a[0].text + '</button>';
                html = html + '<button type="submit" name="answer[]" value="';
                html = html + a[1].id + '">' + a[1].text + '</button>';
                //$("#sqhs_bottom_button").html(html);
                $("#sqhs_bottom_button").append(html);
                return
            }
        } */
        // Other types except yes/no

        for (var i = 0; i < a.length; i++) {
            if (c[i] == "1") {
                correct = "checked"
            } else {
                correct = ""
            }
            html = html + '<input type="checkbox" ' + correct + ' name="answer[]" value="' + a[i].id + '" id="answer' + a[i].id + '"><label for="answer' + a[i].id + '">' + a[i].text + '</label><br/>';
            /*html = document.createElement("input");
            html.type = "checkbox";
            html.name = "answer[]";
            html.value = a[i].id;
            document.getElementById("sqhs_bottom_button").appendChild(html);
            document.getElementById("sqhs_bottom_button").appendChild(document.createTextNode(a[i].text));
            document.getElementById("sqhs_bottom_button").appendChild(document.createElement("br"));*/
        }
        html = html + '<button type="submit">Далі</button>';
        $("#sqhs_bottom_button").append(html);
        /*html = document.createElement("button");
        html.type = "submit";
        document.getElementById("sqhs_bottom_button").appendChild(html);
        html.appendChild(document.createTextNode("Далі"))*/
    }


    /*
     * Fingerprintjs (v2.10)
     */
    var fpoptions = {excludes: {userAgent: true}};
    var sqhs_fingerprintReport = function () {
        Fingerprint2.get(function (components) {
            var murmur = Fingerprint2.x64hash128(components.map(function (pair) {
                return pair.value
            }).join(), 31);
            jQuery("#fingerprint").val(murmur);
        })
    };
    var sqhs_cancelId, sqhs_cancelFunction;

    if (window.requestIdleCallback) {
        sqhs_cancelId = requestIdleCallback(sqhs_fingerprintReport);
        sqhs_cancelFunction = cancelIdleCallback
    } else {
        sqhs_cancelId = setTimeout(sqhs_fingerprintReport, 1500);
        sqhs_cancelFunction = clearTimeout
    }

});
