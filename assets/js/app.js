// ReeferON CRM - minimal JS (UI polish later)
document.addEventListener('DOMContentLoaded', function () {
  // Confirm before cancel / deactivate style actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      var msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});
