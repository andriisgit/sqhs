jQuery(document).ready(function($) {

    jQuery("#sqhs_add_new_final_setting span").removeClass("media-disabled");

    jQuery("#sqhs_add_new_final_setting").click(function (e) {
        e.preventDefault();

        let lastdiv = jQuery("form[name='sqhs_final_settings'] fieldset div:last").attr("id");
        let lastnum = lastdiv.substring(16, lastdiv.length);
        let newnum = parseInt(lastnum) + 1;

        let output = `<div id="sqhs_final_item_${newnum}">
                <label for="active_${newnum}">Active: <input name="active_${newnum}" type="checkbox" id="active_${newnum}"></label>
                &nbsp;&nbsp;
                <label for="sqhs_range_from_${newnum}"><span >From:</span></label>
                <input type="number" name="sqhs_range_from_${newnum}" id="sqhs_range_from_${newnum}" class="small-text" step="1" min="0" max="100"/>
                <label for="sqhs_range_to_${newnum}"><span>To:</span></label>
                <input type="number" name="sqhs_range_to_${newnum}" id="sqhs_range_to_${newnum}" class="small-text" step="1" min="0" max="100"/>
                &nbsp;&nbsp;
                <label for="sqhs_text_${newnum}"><span >Text body:</span></label>
                <input type="text" name="sqhs_text_${newnum}" id="sqhs_text_${newnum}" class="regular-text"/>
                &nbsp;&nbsp;
                <label for="sqhs_img_v"><span >Image URL:</span></label>
                <input type="text" name="sqhs_img_${newnum}" id="sqhs_img_${newnum}" class="regular-text code"/>
            </div>`;

        $("form[name='sqhs_final_settings'] fieldset").append(output);
    });


    jQuery("form[name='sqhs_final_settings']").submit(function (e) {

        var divs = [];

        $("form[name='sqhs_final_settings'] fieldset div").each(function(){
            let el =$(this);
            let elid = el.attr("id");
            divs.push(elid.substring(16, elid.length));
        });

        $("input[name='sqhs_final_settings_sets']").val(divs.toString());
    })

});