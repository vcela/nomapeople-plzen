// Row actions (Nepřijde / Ale přijde / trvale smazat) are plain forms that
// POST back to index.php — this intercepts that submit and does it via
// fetch() instead, so a full page reload doesn't collapse whichever
// lesson-week <details> the admin has open. Forms without a data-confirm
// attribute (toggle_delete) submit straight away; hard_delete carries one
// and only proceeds if window.confirm() is accepted.
//
// A plain onsubmit="..." attribute would have worked too, but the site's CSP
// has no 'unsafe-inline' on script-src, so inline event handlers are
// silently dropped — this has to be an external file wired up via
// addEventListener instead.
document.addEventListener('submit', function (e) {
  var form = e.target;
  var actionInput = form.querySelector('input[name="action"]');
  if (!actionInput || (actionInput.value !== 'toggle_delete' && actionInput.value !== 'hard_delete')) return;

  e.preventDefault();
  var msg = form.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) return;

  var action = actionInput.value;
  var row = form.closest('tr');
  var card = row ? row.closest('details.group-card') : null;

  // form.action would return the <input name="action"> element instead of
  // the URL string here — a named form control shadows the form's own
  // .action property. getAttribute() isn't affected by that collision.
  fetch(form.getAttribute('action'), {
    method: 'POST',
    body: new FormData(form),
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.ok) return;

      if (action === 'hard_delete') {
        if (row) row.remove();
      } else if (row) {
        row.classList.toggle('row-deleted');
        var willBeDeleted = row.classList.contains('row-deleted');
        form.querySelector('button').textContent = willBeDeleted ? 'Ale přijde' : 'Nepřijde';
      }

      if (card && data.group) {
        if (data.group.totalCount === 0) {
          card.remove();
        } else {
          var countEl = card.querySelector('.group-count');
          if (countEl) countEl.textContent = data.group.activeCount + ' ' + data.group.activeLabel;
        }
      }

      var nextCountEl = document.getElementById('next-event-count');
      if (nextCountEl) nextCountEl.textContent = data.nextEventCount;

      var nextEmailsEl = document.getElementById('next-event-emails');
      if (nextEmailsEl) nextEmailsEl.value = data.nextEventEmails.join(', ');
      var nextEmailsSummary = document.getElementById('next-event-emails-summary');
      if (nextEmailsSummary) nextEmailsSummary.textContent = 'E-maily na nejbližší lekci (' + data.nextEventEmails.length + ')';

      var allEmailsEl = document.getElementById('all-emails');
      if (allEmailsEl) allEmailsEl.value = data.allEmails.join(', ');
      var allEmailsSummary = document.getElementById('all-emails-summary');
      if (allEmailsSummary) allEmailsSummary.textContent = 'Všechny e-maily, co se kdy přihlásily (' + data.allEmails.length + ')';
    })
    .catch(function () {
      alert('Něco se nepovedlo, zkuste to prosím znovu.');
    });
});
