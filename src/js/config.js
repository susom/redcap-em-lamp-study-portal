if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.submit').on("click", function(){
        let colRef = $(this).parents('.col-lg-12');
        let cardBody = $(this).parents('.card-body');
        let submissionData = {};

        for(let i of $(cardBody).find('.form-check-input')){
            if(i.checked){
                submissionData[$(i).attr('name')] = $(i).val();
            }

        }

        submissionData['confidence'] = $(cardBody).find('.form-control-range').val();
        submissionData['comments'] = $(cardBody).find('.form-control').val();
        submissionData['user_uuid'] = $(cardBody).attr('data-user-uuid');
        submissionData['task_uuid'] = $(cardBody).attr('data-task-uuid');

        if(Object.keys(submissionData).length === 13){ //user has completed all form info
            LAMP.put(colRef, submissionData);
        } else {
            $(colRef).find('form').css({"border": "2px solid red"});
        }
    });

    // Set default value #
    $('.confidenceCount').html($('.form-control-range').val());

    // Update value on screen
    $('.form-control-range').on('input change', function(){
        $(this).siblings('.confidenceCount').html($(this).val());
    })

}

LAMP.put = (colRef, submissionData) => {
    $.ajax({
        data: submissionData,
        type: 'POST'
    })
        .done((res) => colRef.remove()) //remove column reference
        .fail((jqXHR, textStatus, errorThrown) => console.log(textStatus, errorThrown)) //provide notification

    LAMP.decrementCounter();
}


LAMP.decrementCounter = () => {
    let count = $("#adjudicationCount").text();
    count ? $("#adjudicationCount").text(count -= 1) : ''
}

$(document).ready(function(){
    LAMP.bindEvents();
    $('.img')
        .wrap('<span style="display:inline-block"></span>')
        .css('display', 'block')
        .parent()
        .zoom({
            magnify: 0.2
        });

    $('[data-toggle="popover"]').popover({
        trigger: 'focus',
        html: true,
        content: `<img src ="${$('.example').attr('data-image')}">`
    });

    $('[data-toggle="tooltip"]').tooltip();

});
