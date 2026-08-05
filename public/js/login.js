(function(){
  const modal = document.getElementById('forgot-password-modal');
  const form = document.getElementById('forgot-password-form');
  const openBtn = document.querySelector('a[onclick*="showForgotPassword"]');
  const closeBtn = modal?.querySelector('.modal-close');
  const firstInput = document.getElementById('reset-username');
  let lastFocused = null;

  function getFocusable(container){
    return Array.from(container.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])'))
      .filter(el => !el.disabled && el.offsetParent !== null);
  }

  function showForgotPassword() {
    if (!modal) return;
    lastFocused = document.activeElement;
    modal.classList.add('show');
    modal.setAttribute('role','dialog');
    modal.setAttribute('aria-modal','true');
    modal.setAttribute('aria-labelledby','forgot-password-title');
    closeBtn?.setAttribute('aria-label','Dialog schließen');
    setTimeout(() => firstInput?.focus(), 0);
  }

  function closeForgotPassword() {
    if (!modal) return;
    modal.classList.remove('show');
    form?.reset();
    if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
  }

  window.showForgotPassword = showForgotPassword;
  window.closeForgotPassword = closeForgotPassword;

  modal?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      e.preventDefault();
      closeForgotPassword();
      return;
    }
    if (e.key === 'Tab') {
      const focusable = getFocusable(modal);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const username = formData.get('username');
    const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value || '';
    try {
      const response = await fetch('/src/php/api/password-reset.php?action=request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ username })
      });
      const result = await response.json();
      alert((result.success ? '✅ ' : '❌ ') + result.message);
      if (result.success) closeForgotPassword();
    } catch (error) {
      alert('❌ Fehler beim Senden der Anfrage: ' + error.message);
    }
  });
})();
