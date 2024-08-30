document.addEventListener("turbo:load", () => {
  (() => {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    const forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity() || !validateRegisterForm()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
  })();

  const passwordField = document.querySelector('#registration_form_plainPassword_first');
  const confirmPasswordField = document.querySelector('#registration_form_plainPassword_second');


  const passwordChecks = [
    { field: '#check-password-length', test: value => /^.{8,32}$/.test(value) },
    { field: '#check-password-lowercase', test: value => /[a-z]/.test(value) },
    { field: '#check-password-uppercase', test: value => /[A-Z]/.test(value) },
    { field: '#check-password-number', test: value => /[0-9]/.test(value) },
    { field: '#check-password-special', test: value => /\W/.test(value) },
    { field: '#check-password-match', test: (value, confirmValue) => value === confirmValue },
  ];

  // Iterate over each condition and update the UI
  // Only make conditions red if the form has been validated
  const validatePasswordConditions = () => {
    const passwordFieldValue = passwordField.value;
    const confirmPasswordFieldValue = confirmPasswordField.value;

    passwordChecks.forEach(check => {
      const field = document.querySelector(check.field);
      if (field) {
        const isValid = check.field === '#check-password-match'
          ? check.test(passwordFieldValue, confirmPasswordFieldValue)
          : check.test(passwordFieldValue);
        field.classList.toggle('text-success', isValid);
        if(field.closest('form').classList.contains('was-validated')) {
          field.classList.toggle('text-danger', !isValid);
        }
      }
    });
  };

  if(passwordField && confirmPasswordField) {
    const validate = () => {
      validatePasswordConditions();
    };

    passwordField.addEventListener('input', validate);
    confirmPasswordField.addEventListener('input', validate);
  }


  // As soon as we see the first error, we can stop checking
  const validateRegisterForm = () => {
    const passwordFieldValue = passwordField.value;
    const confirmPasswordFieldValue = confirmPasswordField.value;

    for (const check of passwordChecks) {
      const isValid = check.field === '#check-password-match'
        ? check.test(passwordFieldValue, confirmPasswordFieldValue)
        : check.test(passwordFieldValue);

      if (!isValid) {
        return false;
      }
    }
    return true;
  };
})