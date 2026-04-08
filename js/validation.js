document.addEventListener('DOMContentLoaded', function () {

    // Bootstrap native validation on all forms
    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {

            // Confirm password match (register form only)
            const password = document.getElementById('password');
            const confirm  = document.getElementById('confirm_password');

            if (password && confirm) {
                if (password.value !== confirm.value) {
                    confirm.setCustomValidity('Passwords do not match.');
                    document.getElementById('confirmError').textContent =
                        'Passwords do not match.';
                } else {
                    confirm.setCustomValidity('');
                }
            }

            // Trigger Bootstrap validation styles
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });

    // Live confirm password check
    const confirm = document.getElementById('confirm_password');
    if (confirm) {
        confirm.addEventListener('input', function () {
            const password = document.getElementById('password');
            if (this.value !== password.value) {
                this.setCustomValidity('Passwords do not match.');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});