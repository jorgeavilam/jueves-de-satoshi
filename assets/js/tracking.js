// Jueves de Satoshi — analítica (GA4, GTM, píxel de Meta) y aviso de cookies.
// Los IDs llegan en los atributos data-ga4, data-gtm y data-pixel de este <script>
// (sin JavaScript en línea). Carga desde la primera visita; si el visitante
// rechaza, se apaga, borra sus cookies y no vuelve a cargar en este navegador.
(function () {
  var me = document.currentScript;
  if (!me) return;
  var ga4 = me.getAttribute('data-ga4') || '';
  var gtm = me.getAttribute('data-gtm') || '';
  var pixel = me.getAttribute('data-pixel') || '';
  var KEY = 'jds-consent';
  var started = false;

  function choice() { try { return localStorage.getItem(KEY) || ''; } catch (e) { return ''; } }
  function save(v) { try { localStorage.setItem(KEY, v); } catch (e) {} }
  function load(src) {
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
  }

  window.dataLayer = window.dataLayer || [];
  function gtag() { window.dataLayer.push(arguments); }

  function start() {
    if (started) {
      // Rechazó y luego aceptó en la misma página: se vuelve a encender
      if (ga4) window['ga-disable-' + ga4] = false;
      if (window.gtag) window.gtag('consent', 'update', { analytics_storage: 'granted' });
      if (window.fbq) window.fbq('consent', 'grant');
      return;
    }
    started = true;
    if (ga4 || gtm) {
      window.gtag = gtag;
      // Sin publicidad: solo la medición de visitas
      gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied',
                                   ad_user_data: 'denied', ad_personalization: 'denied' });
    }
    if (ga4) {
      window['ga-disable-' + ga4] = false;
      gtag('js', new Date());
      gtag('config', ga4);
      load('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga4));
    }
    if (gtm) {
      window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
      load('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtm));
    }
    if (pixel && !window.fbq) {
      var n = window.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
      if (!window._fbq) window._fbq = n;
      n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
      window.fbq('init', pixel);
      window.fbq('track', 'PageView');
      load('https://connect.facebook.net/en_US/fbevents.js');
    }
  }

  function stop() {
    if (ga4) window['ga-disable-' + ga4] = true;
    if (window.gtag) window.gtag('consent', 'update', { analytics_storage: 'denied' });
    if (window.fbq) window.fbq('consent', 'revoke');
    // Borra las cookies de Analytics y de Meta en el dominio y sus padres
    var parts = location.hostname.split('.');
    document.cookie.split(';').forEach(function (c) {
      var name = c.split('=')[0].trim();
      if (!/^(_ga|_ga_.+|_gid|_gat.*|_fbp|_fbc)$/.test(name)) return;
      document.cookie = name + '=; Max-Age=0; path=/';
      for (var i = 0; i < parts.length - 1; i++) {
        document.cookie = name + '=; Max-Age=0; path=/; domain=.' + parts.slice(i).join('.');
      }
    });
  }

  if (choice() !== 'denied') start();

  function banner() { return document.getElementById('cookieBanner'); }

  document.addEventListener('DOMContentLoaded', function () {
    var b = banner();
    if (b && !choice()) b.hidden = false;
    // Eventos al cargar la página (p. ej. un formulario enviado)
    if (window.gtag) {
      var views = document.querySelectorAll('[data-ga-view]');
      for (var i = 0; i < views.length; i++) window.gtag('event', views[i].getAttribute('data-ga-view'));
    }
  });

  document.addEventListener('click', function (e) {
    var el = e.target && e.target.closest ? e.target.closest('[data-consent],[data-consent-open],[data-ga-event]') : null;
    if (!el) return;
    if (el.hasAttribute('data-consent-open')) {
      var bo = banner();
      if (bo) bo.hidden = false;
      return;
    }
    if (el.hasAttribute('data-consent')) {
      var v = el.getAttribute('data-consent') === 'denied' ? 'denied' : 'granted';
      save(v);
      if (v === 'denied') stop(); else start();
      var bc = banner();
      if (bc) bc.hidden = true;
      return;
    }
    // Clic en una llamada a la acción con data-ga-event
    if (window.gtag && choice() !== 'denied') {
      var params = {};
      var placement = el.getAttribute('data-ga-placement');
      if (placement) params.placement = placement;
      window.gtag('event', el.getAttribute('data-ga-event'), params);
    }
  });
})();
