/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery(function ($) {
    $(document).on('change', '#wptv_post_type', function () {
        //ajax function to get the ceu detail by key
        //to do check if post already in use show error message with edit link of that post
        var post_type = $(this).val();
        var data = {
            'action': 'wptv_get_post_fields',
            'type': post_type
        };
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            success: function (response) {
                $("#wptv_post_fields").html(response);
                getPostMetaFields(post_type)
                $(".wptv-accordion").accordion({header: "> div > h3"});
            },
            error: function (response) {
                $("#wptv_post_fields").html(response);
            }
        });
    });
});

jQuery(document).ready(function ($) {
    $(".wptv-accordion").accordion({header: "> div > h3"});
});

function getPostMetaFields(post_type) {
    //ajax function to get the ceu detail by key
    var data = {
        'action': 'wptv_get_post_meta_fields',
        'type': post_type
    };
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: data,
        success: function (response) {
            jQuery("#wptv_post_meta_fields").html(response);
            jQuery(".wptv-accordion").accordion({header: "> div > h3"});
        },
        error: function (response) {
            jQuery("#wptv_post_meta_fields").html(response);
        }
    });
}