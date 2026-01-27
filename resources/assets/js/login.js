$(function (){

    initLoginFormRedirect();
});

function initLoginFormRedirect() {
    let form = $('#login-form');

    if(form.find('input[name="email"]').val() && form.find('input[name="password"]').val()) form.submit();
}
