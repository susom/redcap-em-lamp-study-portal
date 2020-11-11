if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.agree, .disagree').on("click", function(){
        let colRef = $(this).parents('.col-lg-6');
        let cardBody = $(this).parent().siblings('.card-body');
        let description = $(cardBody).find('.form-control').val();
        let confidence = $(cardBody).find('.form-control-range').val();


        $(this).hasClass('agree')
            ? LAMP.put(colRef, cardBody.attr('data-user-uuid'), cardBody.attr('data-task-uuid'), description, confidence,'1')
            : LAMP.put(colRef, cardBody.attr('data-user-uuid'), cardBody.attr('data-task-uuid'), description, confidence,'2');
    });

    // Set default value #
    $('.confidenceCount').html($('.form-control-range').val());

    // Update value on screen
    $('.form-control-range').on('input change', function(){
        $(this).siblings('.confidenceCount').html($(this).val());
    })

}

LAMP.put = (colRef, user_uuid, taskUUID, description, confidence, type) => {
    if (!description) {
        $(colRef).find('.form-control').css("border", "2px solid red"); //set textarea to red, display tooltip perhaps
    } else {
        //return;

        let obj = {
            'user_uuid': user_uuid,
            'task_uuid': taskUUID,
            'notes': description,
            'confidence': confidence,
            'results': type
        };

        $.ajax({
            data: obj,
            type: 'POST'
        })
            .done((res) => colRef.remove()) //remove column reference
            .fail((jqXHR, textStatus, errorThrown) => console.log(textStatus, errorThrown)) //provide notification

        LAMP.decrementCounter();
    }
}


LAMP.decrementCounter = () => {
    let count = $("#adjudicationCount").text();
    count ? $("#adjudicationCount").text(count -= 1) : ''
}


LAMP.bindEvents();
