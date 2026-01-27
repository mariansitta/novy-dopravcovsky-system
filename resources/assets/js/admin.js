$(function (){
    initOpenEditForms();
    initRedirectEditClose();
});

function initRedirectEditClose(){
    $('#edit-form').on('hidden.bs.modal', function(){
        window.location.href = $(this).data('route');
    });

    $('.close-modal').on('click', function () {
        $('#edit-form').modal('hide');
    })
}

function initOpenEditForms(){
    $('#edit-form').modal('show');
}