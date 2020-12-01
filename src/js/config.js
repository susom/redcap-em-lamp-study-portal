if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.submit').on("click", function(){
        let colRef = $(this).parents('.col-lg-6');
        let cardBody = $(this).parents('.card-body');
        let readable;
        let confidence = $(cardBody).find('.form-control-range').val();
        let result;
        for(let i of $(cardBody).find('.form-check-input')){
            if(i.checked){
                $(i).attr('name') === 'results' ? result = $(i).val() : readable = $(i).val();
            }

        }
        if(result && readable){
            LAMP.put(colRef, cardBody.attr('data-user-uuid'), cardBody.attr('data-task-uuid'), readable, confidence, result);
        } else {
            if(!result)
                $(colRef).find('.result-box').css("border", "2px solid red"); //set textarea to red, display tooltip perhaps
            if(!readable)
                $(colRef).find('.readable-box').css("border", "2px solid red"); //set textarea to red, display tooltip perhaps
        }
    });

    // Set default value #
    $('.confidenceCount').html($('.form-control-range').val());

    // Update value on screen
    $('.form-control-range').on('input change', function(){
        $(this).siblings('.confidenceCount').html($(this).val());
    })

}

LAMP.put = (colRef, user_uuid, taskUUID, readable, confidence, type) => {
    let obj = {
        'user_uuid': user_uuid,
        'task_uuid': taskUUID,
        'readable': readable,
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
