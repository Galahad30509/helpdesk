// rgb-register.js
window.addEventListener('DOMContentLoaded', function() {
    const registerHeader = document.getElementById('registerHeader');
    const registerFormBlock = document.getElementById('registerFormBlock');
    registerHeader.addEventListener('click', function() {
        const collapse = new bootstrap.Collapse(registerFormBlock, {toggle: true});
    });
});
