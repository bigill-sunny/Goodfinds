(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    const forms = document.querySelectorAll(
      '#registerForm, #loginForm, #checkoutForm, #productForm'
    );

    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!validateForm(form)) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    const toggleBtn = document.getElementById('togglePassword');
    const pwField   = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');

    if (toggleBtn && pwField) {
      toggleBtn.addEventListener('click', function () {
        const isPassword = pwField.type === 'password';
        pwField.type = isPassword ? 'text' : 'password';
        eyeIcon.innerHTML = isPassword
          ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
          : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
      });
    }

  });

  function validateForm(form) {
    let valid = true;

    const confirmField  = form.querySelector('#confirm');
    const passwordField = form.querySelector('#password');

    if (confirmField && passwordField) {
      if (passwordField.value !== confirmField.value) {
        confirmField.setCustomValidity('Passwords do not match.');
        valid = false;
      } else {
        confirmField.setCustomValidity('');
      }
    }

    const priceField = form.querySelector('#price');
    if (priceField && (isNaN(priceField.value) || parseFloat(priceField.value) <= 0)) {
      priceField.setCustomValidity('Enter a valid price greater than 0.');
      valid = false;
    } else if (priceField) {
      priceField.setCustomValidity('');
    }

    const stockField = form.querySelector('#stock');
    if (stockField && (isNaN(stockField.value) || parseInt(stockField.value) < 0)) {
      stockField.setCustomValidity('Quantity must be 0 or more.');
      valid = false;
    } else if (stockField) {
      stockField.setCustomValidity('');
    }

    if (!form.checkValidity()) valid = false;

    return valid;
  }

})();