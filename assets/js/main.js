// Small, functional interactions only — no animation library, no framework.

document.addEventListener('DOMContentLoaded', function () {
  // Confirm before any destructive action (ticket delete, comment delete,
  // user delete) — these forms carry data-confirm with the message to show.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
