if(typeof LAMP === 'undefined') { var LAMP = {}; }

LAMP.bindEvents = () => {
    $('.agree, .disagree').on("click", function(){
        let colRef = $(this).parents('.col-lg-6');
        let userUuid = $(this).parent().attr('user_uuid');
        let taskUUid = $(this).parent().attr('task_uuid');
        $(this).hasClass('agree') ? LAMP.put(colRef, userUuid, taskUUid, 'agree') : LAMP.put(colRef, userUuid, taskUUid, 'disagree');
    });
}

LAMP.put = (colRef, user_uuid, task_uuid, type) => {
    colRef.remove();
    // $.ajax({
    //     url: '',
    //     type: 'PUT',
    //
    // }).done(function(){
    //     colRef.remove(); //remove column reference
    // }).fail(function(err){
    //     console.log(err); //provide notification
    // })
}

LAMP.createJson = () => {

}

LAMP.bindEvents();
