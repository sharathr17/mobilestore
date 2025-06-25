document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Real-time password validation
    password.addEventListener('input', function() {
        validatePassword();
    });
    
    confirmPassword.addEventListener('input', function() {
        validatePasswordMatch();
    });
    
    // Form submission validation
    form.addEventListener('submit', function(event) {
        if (password.value.length < 8) {
            event.preventDefault();
            alert('Password must be at least 8 characters long');
            password.focus();
            return false;
        }
        
        if (password.value !== confirmPassword.value) {
            event.preventDefault();
            alert('Passwords do not match');
            confirmPassword.focus();
            return false;
        }
    });
    
    // Helper functions
    function validatePassword() {
        const passwordHint = document.querySelector('.password-hint');
        
        if (password.value.length < 8) {
            password.classList.add('invalid');
            passwordHint.classList.add('error-hint');
        } else {
            password.classList.remove('invalid');
            passwordHint.classList.remove('error-hint');
        }
    }
    
    function validatePasswordMatch() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('invalid');
        } else {
            confirmPassword.classList.remove('invalid');
        }
    }
});