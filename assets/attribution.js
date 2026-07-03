// Marketing-source attribution — plain vanilla JS, no dependencies, same
// pattern as assets/consent.js. Figures out, in one pass, how a visitor
// reached the page (direct / organic search / paid ad / referral / other
// UTM campaign) and hands a ready-to-store label to index.html's onSubmit.
//
// This is NOT gated behind cookie consent: it reads document.referrer and
// the current URL's own query string (both already available to any script
// on the page) and stores the result in sessionStorage — no cross-site
// tracking, no third-party cookies, nothing GDPR-relevant beyond what the
// registrant already submits about themselves in the form.
(function () {
  var STORAGE_KEY = 'nm_attribution'; // sessionStorage: JSON {type, label}

  var SEARCH_ENGINES = [
    { re: /(^|\.)google\./, name: 'Google' },
    { re: /(^|\.)seznam\.cz$/, name: 'Seznam' },
    { re: /(^|\.)bing\.com$/, name: 'Bing' },
    { re: /(^|\.)yahoo\./, name: 'Yahoo' },
    { re: /(^|\.)duckduckgo\.com$/, name: 'DuckDuckGo' },
    { re: /(^|\.)centrum\.cz$/, name: 'Centrum' },
  ];

  // utm_source value (lowercased, letters only) -> display name of the ad platform.
  var AD_PLATFORMS = {
    google: 'Google Ads', googleads: 'Google Ads', adwords: 'Google Ads',
    facebook: 'Facebook Ads', fb: 'Facebook Ads',
    instagram: 'Instagram Ads', ig: 'Instagram Ads',
    meta: 'Meta Ads',
    sklik: 'Seznam Sklik',
    seznam: 'Seznam Ads',
    bing: 'Microsoft Ads', microsoft: 'Microsoft Ads',
  };

  function hostnameOf(url) {
    try { return new URL(url).hostname.replace(/^www\./, ''); } catch (e) { return ''; }
  }

  function adIdentifier(utmCampaign, utmContent, utmTerm, gclid, fbclid, msclkid) {
    var parts = [utmCampaign, utmContent, utmTerm].filter(Boolean);
    if (parts.length) return parts.join(' / ');
    var clickId = gclid || fbclid || msclkid;
    return clickId ? 'id: ' + clickId.slice(0, 24) : '';
  }

  function computeAttribution() {
    var params;
    try { params = new URLSearchParams(window.location.search); } catch (e) { params = null; }
    var get = function (key) { return (params && params.get(key) || '').trim(); };

    var utmSource = get('utm_source');
    var utmMedium = get('utm_medium');
    var utmCampaign = get('utm_campaign');
    var utmContent = get('utm_content');
    var utmTerm = get('utm_term');
    var gclid = get('gclid');
    var fbclid = get('fbclid');
    var msclkid = get('msclkid');

    var ref = '';
    try { ref = document.referrer || ''; } catch (e) {}
    var refHost = ref ? hostnameOf(ref) : '';
    var selfHost = window.location.hostname.replace(/^www\./, '');

    var id = adIdentifier(utmCampaign, utmContent, utmTerm, gclid, fbclid, msclkid);

    // 1) Paid ads — a click ID is the strongest possible signal.
    if (gclid) return { type: 'ads', label: 'Google Ads' + (id ? ' – ' + id : '') };
    if (fbclid) return { type: 'ads', label: 'Facebook Ads' + (id ? ' – ' + id : '') };
    if (msclkid) return { type: 'ads', label: 'Microsoft Ads' + (id ? ' – ' + id : '') };

    // 2) UTM-tagged paid traffic (utm_medium looks like a paid channel, or
    //    utm_source names a known ad platform).
    var platformKey = utmSource.toLowerCase().replace(/[^a-z]/g, '');
    var isPaidMedium = /^(cpc|ppc|paid|paidsocial|cpm|display)$/i.test(utmMedium);
    if (utmSource && (isPaidMedium || AD_PLATFORMS[platformKey])) {
      var platform = AD_PLATFORMS[platformKey] || (utmSource.charAt(0).toUpperCase() + utmSource.slice(1) + ' Ads');
      return { type: 'ads', label: platform + (id ? ' – ' + id : '') };
    }

    // 3) Any other UTM-tagged link (newsletter, QR kód, organický post se
    //    štítkem, ...) — kept distinct from "ads" so it isn't miscounted as
    //    paid spend, but still clearly a tracked campaign.
    if (utmSource) {
      return { type: 'campaign', label: 'Kampaň – ' + utmSource + (utmCampaign ? ' / ' + utmCampaign : '') };
    }

    // 4) Organic search engine result.
    if (refHost) {
      for (var i = 0; i < SEARCH_ENGINES.length; i++) {
        if (SEARCH_ENGINES[i].re.test(refHost)) {
          return { type: 'search', label: 'Vyhledávač – ' + SEARCH_ENGINES[i].name };
        }
      }
    }

    // 5) Link from some other site.
    if (refHost && refHost !== selfHost) {
      return { type: 'referral', label: 'Odkaz z webu – ' + refHost };
    }

    // 6) No referrer, no UTM: typed URL / bookmark / app.
    return { type: 'direct', label: 'Přímá návštěva' };
  }

  // First-touch model: compute once per browser tab session and keep
  // reusing that result, so a later reload (which may have lost the
  // original UTM parameters / referrer) doesn't overwrite the attribution
  // that actually brought the visitor here.
  function getAttribution() {
    try {
      var stored = sessionStorage.getItem(STORAGE_KEY);
      if (stored) return JSON.parse(stored);
    } catch (e) {}
    var attr = computeAttribution();
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(attr)); } catch (e) {}
    return attr;
  }

  getAttribution();
  window.nmGetAttribution = getAttribution;
})();
