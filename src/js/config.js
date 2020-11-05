if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.agree, .disagree').on("click", function(){
        let colRef = $(this).parents('.col-lg-6');
        let cardBody = $(this).parent().siblings('.card-body');
        let description = $(cardBody).find('.form-control').val();

        $(this).hasClass('agree')
            ? LAMP.put(colRef, cardBody.attr('data-user-uuid'), cardBody.attr('data-task-uuid'), description, '1')
            : LAMP.put(colRef, cardBody.attr('data-user-uuid'), cardBody.attr('data-task-uuid'), description, '2');
    });
}

LAMP.put = (colRef, user_uuid, taskUUID, description, type) => {
    if(!description){
        $(colRef).find('.form-control').css("border", "2px solid red"); //set textarea to red, display tooltip perhaps
    } else {
        return;

        let obj = {
            'user_uuid': user_uuid,
            'task_uuid': taskUUID,
            'description': description,
            'type': type
        };

        $.ajax({
            data: obj,
            type: 'POST'
        })
            .done((res) => colRef.remove()) //remove column reference
            .fail((err) => console.log(err)) //provide notification

        LAMP.decrementCounter();
    }
}


LAMP.decrementCounter = () => {
    let count = $("#adjudicationCount").text();
    count ? $("#adjudicationCount").text(count-=1) : ''
}


LAMP.bindEvents();
