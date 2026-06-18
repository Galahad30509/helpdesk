// rgb-login.js
window.addEventListener('DOMContentLoaded', function() {
    const loginHeader = document.getElementById('loginHeader');
    const loginFormBlock = document.getElementById('loginFormBlock');
    loginHeader.addEventListener('click', function() {
        const collapse = new bootstrap.Collapse(loginFormBlock, {toggle: true});
    });
});
