function initializeFormValidation() {
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

  // Logic for the max amount button of the bank form
  const maxAmountBtn = document.getElementById('CB-max-amount-btn');
  const amountField = document.getElementById('bank_form_amount');
  const depositBtn = document.getElementById('bank_form_bankTransactionMode_0');
  const withdrawBtn = document.getElementById('bank_form_bankTransactionMode_1');
  const maxAmountUserCanDeposit = 100000;

  if(maxAmountBtn && amountField) {
    const userBank = maxAmountBtn.getAttribute('data-max-amount');
    maxAmountBtn.addEventListener('click', function () {
      if(depositBtn.checked) {
        amountField.value = maxAmountUserCanDeposit - userBank;
      } else if (withdrawBtn.checked) {
        amountField.value = userBank;
      }
    });
  };
};

  // Function to run once per page load
function runOnce(callback) {
  if (!runOnce.hasRun) {
    callback();
    runOnce.hasRun = true;
  }
}

// Handle initial page load and hard refreshes
document.addEventListener("turbo:load", () => {
  runOnce(initializeFormValidation);
});

// Handle Turbo page changes, including form submissions
document.addEventListener("turbo:render", () => {
  initializeFormValidation();
});

// Reset the runOnce flag before unload
document.addEventListener("turbo:before-cache", () => {
  runOnce.hasRun = false;
});
