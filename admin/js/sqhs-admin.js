jQuery(document).ready(function($) {

	jQuery("form[name='set-save']").submit(function (e) {

		e.preventDefault();
		var form_data = jQuery(this).serialize();
		$("input#set-name").attr('readonly',true);
		$("textarea#set-description").attr('readonly',true);
		$("input#submit").attr('class', 'button button-primary-disabled button-primary');

		$.ajax({
			cache: false,
			type: "POST",
			url: wp_ajax.ajax_url,
			data: form_data,
			async: false,
			dataType: "json",
			success: function( response ) {
				if (response.result == 'OK') {
					jQuery('p#sqhs_notice_msg').html("Saved");
					jQuery('div#sqhs_notice').addClass('notice-success');
				}
				if (response.result == 'ERR') {
					jQuery('p#sqhs_notice_msg').html(response.message);
					jQuery('div#sqhs_notice').addClass('notice-error');

				}
			},
			error: function( xhr, status, error ) {
				jQuery('p#sqhs_notice_msg').html(xhr + " :: " + status + " :: " + error);
				jQuery('div#sqhs_notice').addClass('notice-error');
			}

		});

		jQuery('div#sqhs_notice').fadeIn();
		setTimeout(function () {
			jQuery('div#sqhs_notice').fadeOut();
			jQuery('p#sqhs_notice_msg').html("&nbsp;");
			jQuery('div#sqhs_notice').removeClass('notice-error');
			jQuery('div#sqhs_notice').removeClass('notice-success');
		}, 5000);

		$("input#set-name").attr('readonly',false);
		$("textarea#set-description").attr('readonly',false);
		$("input#submit").attr('class', 'button button-primary');

	});


	jQuery("a#add_answer").click(function (e) {
        e.preventDefault();

        let answers_ids = [],
            attrs = jQuery("input[name='answers_ids']").attr("value");
		
		if (attrs.length > 0 ) {

			attrs.split(",").forEach(function (value) {
				answers_ids.push( parseInt(value) );
			});
			
			var new_answers_ids = lowestUnusedNumber(answers_ids, 1);
			$("input[name='answers_ids']").attr("value", attrs + "," + new_answers_ids.toString());

		} else {
			var new_answers_ids = '1';
			$("input[name='answers_ids']").attr("value", new_answers_ids.toString());
		}


		let output = '<div id="answer_' + new_answers_ids + '">';
		output = output + `<a class="remove_answer" href="javascript:void(0);" data="` + new_answers_ids + `"><span class="dashicons dashicons-dismiss"></span></a>`;
		output = output + `<input type="text" required name="answer_text_` + new_answers_ids + `" placeholder="Hit answer here *" value="" maxlength="49" class="regular-text"/>`;
		output = output + `<label><input name="answer_correct_` + new_answers_ids + `" type="checkbox">Correct</label>`;
		output = output + '</div>';

		$("div#answers_block").append(output);
		$("div#answers_block").on('click', 'a.remove_answer', function (e){
			e.preventDefault();
			let id = $(this).attr('data');
			removeAnswerRow(id);
		});

    });

	function lowestUnusedNumber(sequence, startingFrom) {

		const arr = sequence.slice(0);
        arr.sort((a, b) => a - b);

        return arr.reduce((lowest, num, i) => {
            const seqIndex = i + startingFrom;
            return num !== seqIndex && seqIndex < lowest ? seqIndex : lowest
        }, arr.length + startingFrom);

	}

	/**
	 * Remove answer row after click at remove icon
	 */
	jQuery("a.remove_answer").click(function (e) {
		e.preventDefault();
		let id = this.getAttribute('data');
		removeAnswerRow(id);
	});

	function removeAnswerRow(id) {
		let	divid = "div#answer_" + id.toString();
		jQuery(divid).remove();
	}


});
