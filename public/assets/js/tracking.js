/**
 * GA4 / Google Tag Manager — โหลดเฉพาะเมื่อ partials/tracking.php วาดแท็กนี้ (production + ตั้งรหัสใน CMS)
 * ‼️ ไฟล์แยกแทน inline script เพราะ CSP ห้าม unsafe-inline
 */
(function () {
  'use strict';
  var me = document.currentScript;
  if (!me) { return; }
  var ga4 = me.dataset.ga4 || '';
  var gtm = me.dataset.gtm || '';
  window.dataLayer = window.dataLayer || [];

  function load(src) {
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
  }

  if (gtm) {
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    load('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtm));
  }
  if (ga4) {
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', ga4);
    load('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga4));
  }
})();
