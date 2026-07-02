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
  var META_PIXEL_ID = ''; // TODO: set once a Meta Pixel is created, e.g. '1234567890123456'

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

  function loadMetaPixel() {
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
    window.fbq('init', META_PIXEL_ID);
    window.fbq('track', 'PageView');
  }

  function activate() {
    loadGoogleAnalytics();
    loadMetaPixel();
  }

  function removeBanner() {
    var el = document.getElementById('nm-cookie-banner');
    if (el) el.remove();
  }

  function renderBanner() {
    if (document.getElementById('nm-cookie-banner')) return;
    var el = document.createElement('div');
    el.id = 'nm-cookie-banner';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-label', 'Nastavení cookies');
    el.style.cssText = 'position:fixed; left:0; right:0; bottom:0; z-index:9999; background:#2a2320; color:#f4ecc6; padding:18px 24px; display:flex; flex-wrap:wrap; gap:16px; align-items:center; justify-content:space-between; font-family:"Public Sans",sans-serif; box-shadow:0 -18px 38px -24px rgba(0,0,0,0.6);';
    el.innerHTML =
      '<p style="margin:0; flex:1 1 320px; font-size:14px; line-height:1.5; max-width:60ch;">' +
      'Používáme cookies pro měření návštěvnosti (Google Analytics) a případně cílenou reklamu (Meta Pixel). ' +
      'Podrobnosti najdeš v <a href="zasady-ochrany-osobnich-udaju.html" style="color:#8fb6c7; text-decoration:underline;">Zásadách ochrany osobních údajů</a>.' +
      '</p>' +
      '<div style="display:flex; gap:10px; flex:none;">' +
      '<button type="button" id="nm-cookie-reject" style="background:transparent; border:1.5px solid #f4ecc6; color:#f4ecc6; font-weight:700; font-size:14px; padding:11px 20px; border-radius:999px; cursor:pointer;">Odmítnout</button>' +
      '<button type="button" id="nm-cookie-accept" style="background:#884858; border:none; color:#f4ecc6; font-weight:700; font-size:14px; padding:11px 20px; border-radius:999px; cursor:pointer;">Přijmout vše</button>' +
      '</div>';
    document.body.appendChild(el);
    document.getElementById('nm-cookie-accept').addEventListener('click', function () {
      setConsent('accepted');
      removeBanner();
      activate();
    });
    document.getElementById('nm-cookie-reject').addEventListener('click', function () {
      setConsent('rejected');
      removeBanner();
    });
  }

  function init() {
    var consent = getConsent();
    if (consent === 'accepted') {
      activate();
    } else if (consent !== 'rejected') {
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
