// Password Reset JavaScript
// Loaded only on the password reset page.
// The reset token is read from the data-token attribute of the form element
// to avoid injecting it directly into JS code.

(function () {
  'use strict';

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  const form = document.getElementById('reset-password-form');
  if (!form) return;

  // Read token safely from a data attribute set by PHP (htmlspecialchars-escaped)
  const token = form.dataset.token || '';

  // Verify token on page load
  (async function () {
    if (!token) {
      showResetMessage('error', 'Kein Token gefunden. Bitte fordern Sie einen neuen Passwort-Reset-Link an.');
      form.style.display = 'none';
      return;
    }

    try {
      const response = await fetch('/src/php/api/password-reset.php?action=verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken()
        },
        body: JSON.stringify({ token: token })
      });

      const result = await response.json();

      if (result.success) {
        document.getElementById('username-value').textContent = result.username;
        document.getElementById('reset-username-display').style.display = 'block';
      } else {
        showResetMessage('error', result.message || 'Ungültiger oder abgelaufener Token.');
        form.style.display = 'none';
      }
    } catch (error) {
      showResetMessage('error', 'Fehler beim Überprüfen des Tokens.');
      form.style.display = 'none';
    }
  })();

  // Handle password reset form submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn = form.querySelector('[type="submit"]');
    const password = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    if (password !== confirmPassword) {
      showResetMessage('error', 'Die Passwörter stimmen nicht überein.');
      return;
    }

    if (password.length < 10) {
      showResetMessage('error', 'Passwort muss mindestens 10 Zeichen lang sein');
      return;
    }

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<span class="material-icons" style="animation: spin 1s linear infinite;">refresh</span> Bitte warten...';
    }

    try {
      const response = await fetch('/src/php/api/password-reset.php?action=reset', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken()
        },
        body: JSON.stringify({ token: token, password: password })
      });

      const result = await response.json();

      if (result.success) {
        showResetMessage('success', result.message + ' Sie werden zum Login weitergeleitet...');
        form.style.display = 'none';
        setTimeout(() => {
          window.location.href = '/login.php';
        }, 2000);
      } else {
        showResetMessage('error', result.message);
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = submitBtn.dataset.originalText;
        }
      }
    } catch (error) {
      showResetMessage('error', 'Fehler beim Zurücksetzen des Passworts: ' + error.message);
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalText;
      }
    }
  });

  function showResetMessage(type, message) {
    const messageDiv = document.getElementById('reset-message');
    messageDiv.className = 'alert alert-' + type;
    messageDiv.textContent = message;
    messageDiv.style.display = 'block';
  }
})();
