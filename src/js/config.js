if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.submit').on("click", function(){
        let colRef = $(this).parents('.col-lg-6');
        let cardBody = $(this).parents('.card-body');
        let description = $(cardBody).find('.form-control').val();
        let confidence = $(cardBody).find('.form-control-range').val();
        let result;
        for(let i of $(cardBody).find('.form-check-input')){
            if(i.checked)
                result = $(i).val();
        }

        if(result){
            LAMP.put(colRef, cardBody.attr('data-user-uuid'), cardBody.attr('data-task-uuid'), description, confidence, result);
        } else {
            $(colRef).find('.result-box').css("border", "2px solid red"); //set textarea to red, display tooltip perhaps
        }

    });

    // Set default value #
    $('.confidenceCount').html($('.form-control-range').val());

    // Update value on screen
    $('.form-control-range').on('input change', function(){
        $(this).siblings('.confidenceCount').html($(this).val());
    })

}

LAMP.put = (colRef, user_uuid, taskUUID, description, confidence, type) => {
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


LAMP.decrementCounter = () => {
    let count = $("#adjudicationCount").text();
    count ? $("#adjudicationCount").text(count -= 1) : ''
}


LAMP.bindEvents();
