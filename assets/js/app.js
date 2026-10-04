/**
 * SISENSUS 2026 — JavaScript minimal (vanilla, tanpa pustaka eksternal)
 * 1) Konfirmasi pada aksi destruktif  2) Toggle navigasi mobile
 * Tanpa dependensi pihak ketiga — sesuai CSP ketat (script-src 'self').
 */
(function () {
  'use strict';

  // --- Konfirmasi aksi destruktif (formulir dengan data-confirm) ---
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var msg = form.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?';
      if (!window.confirm(msg)) {
        ev.preventDefault();
      }
    });
  });

  // --- Toggle sidebar pada layar sempit ---
  var toggle = document.querySelector('[data-nav-toggle]');
  var sidenav = document.getElementById('app-sidenav');
  if (toggle && sidenav) {
    toggle.addEventListener('click', function () {
      var open = sidenav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();
