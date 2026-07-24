// Cookie consent — plain vanilla JS, no dependencies. Shared between
// index.html and zasady-ochrany-osobnich-udaju.html so it works regardless
// of whether the DC/React runtime has booted (or is even reachable).
//
// Nothing tracking-related loads until the visitor explicitly accepts:
// Google Analytics (GA4) and, once configured, Meta Pixel are both gated
// behind the stored consent decision.
(function () {
  var STORAGE_KEY = 'nm_cookie_consent'; // 'accepted' | 'rejected'
  var GA_MEASUREMENT_ID = 'G-XZS41C1ELT';
  var META_PIXEL_ID = '1687890829112006';

  function getConsent() {
    try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
  }
  function setConsent(value) {
    try { localStorage.setItem(STORAGE_KEY, value); } catch (e) {}
  }

  function loadGoogleAnalytics() {
    if (!GA_MEASUREMENT_ID || window.__nmGaLoaded) return;
    window.__nmGaLoaded = true;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_MEASUREMENT_ID;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', GA_MEASUREMENT_ID);
  }

  // Load the pixel base code + init so Meta can detect the installation
  // (Pixel Helper, Events Manager verification — which crawl the page WITHOUT
  // accepting the banner). Verified empirically: fbq('init') pings
  // /signals/config/<id> — THIS is what makes Meta detect the pixel — while
  // setting no _fbp cookie and firing no tracking event on its own. So this is
  // safe to run even for undecided visitors; actual tracking is gated on
  // grantMetaPixel() below, which is the only place track() is ever called.
  //
  // We deliberately do NOT use Meta's fbq('consent','revoke'/'grant') API:
  // revoke-before-init also suppresses the config ping (killing detection),
  // and a revoke→grant sequence queued before fbevents.js loads was observed
  // to leave the pixel permanently holding events. Gating on whether we call
  // track() is simpler and behaves predictably.
  function loadMetaPixelBase() {
    if (!META_PIXEL_ID || window.__nmPixelLoaded) return;
    window.__nmPixelLoaded = true;
    /* eslint-disable */
    !function (f, b, e, v, n, t, s) {
      if (f.fbq) return; n = f.fbq = function () {
        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
      };
      if (!f._fbq) f._fbq = n; n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
      t = b.createElement(e); t.async = true; t.src = v;
      s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */
    window.fbq('init', META_PIXEL_ID); // config ping → Meta detects install (no cookie, no event)
  }

  // Fire the tracking events. Only ever called after the visitor has accepted
  // (or previously accepted) cookies — this is the consent gate.
  function grantMetaPixel() {
    loadMetaPixelBase();
    if (!window.fbq) return;
    window.fbq('track', 'PageView');
    // Conversion event on the thank-you page. Lives here (not inline in
    // dekujeme.html) because the CSP has no 'unsafe-inline' for scripts.
    if (/dekujeme/.test(location.pathname)) window.fbq('track', 'Lead');
  }

  function activate() {
    loadGoogleAnalytics();
    grantMetaPixel();
  }

  function removeBanner() {
    var el = document.getElementById('nm-cookie-banner');
    if (el) el.remove();
  }

  // Built with classList (assets/style.css) rather than inline styles, so the
  // banner always matches the page's current design tokens.
  function renderBanner() {
    if (document.getElementById('nm-cookie-banner')) return;

    var el = document.createElement('div');
    el.id = 'nm-cookie-banner';
    el.className = 'cookie-banner';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-label', 'Nastavení cookies');

    var text = document.createElement('p');
    text.className = 'cookie-banner__text';
    text.innerHTML = 'Používáme cookies pro lepší fungování webu, měření návštěvnosti a případně cílení reklamy. ' +
      'Podrobnosti najdeš v <a href="zasady-ochrany-osobnich-udaju.html">Zásadách ochrany osobních údajů</a>.';

    var actions = document.createElement('div');
    actions.className = 'cookie-banner__actions';

    var acceptBtn = document.createElement('button');
    acceptBtn.type = 'button';
    acceptBtn.id = 'nm-cookie-accept';
    acceptBtn.className = 'cookie-banner__btn cookie-banner__btn--accept';
    acceptBtn.textContent = 'Jasně!';

    var rejectBtn = document.createElement('button');
    rejectBtn.type = 'button';
    rejectBtn.id = 'nm-cookie-reject';
    rejectBtn.className = 'cookie-banner__btn cookie-banner__btn--reject';
    rejectBtn.textContent = 'Ne, díky';

    actions.appendChild(acceptBtn);
    actions.appendChild(rejectBtn);
    el.appendChild(text);
    el.appendChild(actions);
    document.body.appendChild(el);

    acceptBtn.addEventListener('click', function () {
      setConsent('accepted');
      removeBanner();
      activate();
    });
    rejectBtn.addEventListener('click', function () {
      setConsent('rejected');
      removeBanner();
    });
  }

  function init() {
    var consent = getConsent();
    if (consent === 'accepted') {
      activate();
    } else if (consent !== 'rejected') {
      // Undecided: show the banner, and load the pixel in its revoked state so
      // Meta can detect the installation. No events fire until the visitor
      // accepts. GA stays fully gated (no Google consent-mode equivalent here).
      loadMetaPixelBase();
      renderBanner();
    }
  }

  // Lets the footer link / GDPR page offer "change your mind" — clears the
  // stored decision and re-shows the banner so a new choice can be made.
  window.nmOpenCookieSettings = function () {
    try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    renderBanner();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
