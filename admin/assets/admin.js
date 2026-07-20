// Confirmation for forms marked data-confirm. A plain onsubmit="confirm(...)"
// attribute is blocked by the site's CSP (script-src has no 'unsafe-inline'),
// so this has to live in an external file instead.
document.addEventListener('submit', function (e) {
  var msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) {
    e.preventDefault();
  }
});
