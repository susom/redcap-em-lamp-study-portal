if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.agree, .disagree').on("click", function(){
        let colRef = $(this).parents('.col-lg-6');
        let userUuid = $(this).parent().attr('data-user-uuid');
        let taskUUID = $(this).parent().attr('data-task-uuid');
        $(this).hasClass('agree') ? LAMP.put(colRef, userUuid, taskUUID, '1') : LAMP.put(colRef, userUuid, taskUUID, '2');
    });
}

LAMP.put = (colRef, user_uuid, taskUUID, type) => {
    let obj = {
        'user_uuid': user_uuid,
        'task_uuid': taskUUID,
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


LAMP.decrementCounter = () => {
    let count = $("#adjudicationCount").text();
    count ? $("#adjudicationCount").text(count-=1) : ''
}


LAMP.bindEvents();
