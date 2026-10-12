// Jueves de Satoshi — JS de interfaz. Todo el JavaScript del sitio vive en assets/js/:
// la CSP no deja correr scripts en línea ni atributos on*.
(function () {
  var toggle = document.getElementById('themeToggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var html = document.documentElement;
      var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      try { localStorage.setItem('jds-theme', next); } catch (e) {}
      if (window.jdsRetheme) window.jdsRetheme(); // re-pintar gráficas
    });
  }
  var burger = document.getElementById('navBurger');
  if (burger) {
    burger.addEventListener('click', function () {
      var nav = document.getElementById('mainNav');
      if (nav) nav.classList.toggle('open');
    });
  }
})();

// Movimiento reducido: quitar la animación y estacionar el carrito SOBRE el riel.
// Un display:none en <animateMotion> no detiene la animación de forma confiable,
// y sin transform el carrito se iría a la esquina del SVG.
(function () {
  try {
    if (!window.matchMedia || !window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  } catch (e) { return; }

  document.querySelectorAll('.coaster svg').forEach(function (svg) {
    var path = svg.querySelector('defs path[id]');
    var carts = svg.querySelectorAll('.coaster-cart');
    if (!path || !carts.length) return;

    Array.prototype.forEach.call(svg.querySelectorAll('animateMotion'), function (a) {
      a.parentNode.removeChild(a);
    });

    var total = path.getTotalLength();
    carts.forEach(function (cart, i) {
      var p = path.getPointAtLength(total * ((i + 1) / (carts.length + 1)));
      cart.setAttribute('transform', 'translate(' + p.x.toFixed(1) + ',' + p.y.toFixed(1) + ')');
    });
  });
})();

// Correo público protegido: el HTML lo trae invertido y aquí se arma el mailto
(function () {
  var links = document.querySelectorAll('a.protected-email');
  for (var i = 0; i < links.length; i++) {
    var a = links[i];
    var u = (a.getAttribute('data-u') || '').split('').reverse().join('');
    var d = (a.getAttribute('data-d') || '').split('').reverse().join('');
    if (!u || !d) continue;
    a.href = 'mailto:' + u + '@' + d;
    a.textContent = u + '@' + d;
  }
})();

// Confirmación antes de borrar: data-confirm en el enlace (la CSP no deja onclick)
document.addEventListener('click', function (e) {
  var a = e.target && e.target.closest ? e.target.closest('[data-confirm]') : null;
  if (a && !window.confirm(a.getAttribute('data-confirm'))) e.preventDefault();
});

// Avisos que se cierran por 30 días: data-dismiss="<nombre>" en el botón.
// early.js los esconde antes de pintar en las visitas siguientes.
document.addEventListener('click', function (e) {
  var b = e.target && e.target.closest ? e.target.closest('[data-dismiss]') : null;
  if (!b) return;
  var name = b.getAttribute('data-dismiss');
  try { localStorage.setItem('jds-' + name, String(Date.now())); } catch (err) {}
  var box = b.closest('[data-dismissible]');
  if (box) box.style.display = 'none';
});

// reCAPTCHA v3 en formularios con data-recaptcha="<clave del sitio>"
(function () {
  var forms = document.querySelectorAll('form[data-recaptcha]');
  Array.prototype.forEach.call(forms, function (form) {
    form.addEventListener('submit', function (e) {
      var hidden = form.querySelector('input[name="g-recaptcha-response"]');
      if (!hidden || hidden.value || typeof grecaptcha === 'undefined') return; // ya hay token, o no cargó
      e.preventDefault();
      grecaptcha.ready(function () {
        grecaptcha.execute(form.getAttribute('data-recaptcha'), { action: form.getAttribute('data-recaptcha-action') || 'submit' })
          .then(function (token) { hidden.value = token; form.submit(); });
      });
    });
  });
})();

// Lee un bloque <script type="application/json" id="..."> (datos, no código)
function jdsData(id) {
  var el = document.getElementById(id);
  if (!el) return null;
  try { return JSON.parse(el.textContent); } catch (e) { return null; }
}

// Marca e instalador: el nombre sugerido cambia con el día de compra
(function () {
  var data = jdsData('brandNameData');
  var day = document.getElementById('fDay'), name = document.getElementById('fName'), hint = document.getElementById('hName');
  if (!data || !day || !hint) return;
  var fill = document.getElementById('brandNameData').hasAttribute('data-fill-empty');
  day.addEventListener('change', function () {
    var s = data.sugg[parseInt(day.value, 10) - 1];
    hint.textContent = data.tpl.replace('@@', s);
    if (fill && name && !name.value) name.value = s;
  });
})();

// Instalador: el tipo de cambio de respaldo solo importa en monedas que CoinGecko no cotiza
(function () {
  var data = jdsData('fxData');
  var cur = document.querySelector('select[name=currency]');
  var box = document.getElementById('fxBox'), curLbl = document.getElementById('fxCur'), fxHint = document.getElementById('fxHint');
  if (!data || !cur || !box) return;
  function fx() {
    box.style.display = data.noCg.indexOf(cur.value) !== -1 ? '' : 'none';
    if (curLbl) curLbl.textContent = cur.value;
    if (fxHint) fxHint.textContent = data.tpl.replace('@@', cur.value);
  }
  cur.addEventListener('change', fx);
  fx();
})();

// Alta de compra: vista previa del USD calculado (monto / tipo de cambio)
(function () {
  var m = document.getElementById('fMonto'), tc = document.getElementById('fTC'),
      p = document.getElementById('fPrecio'), out = document.getElementById('fUSD');
  if (!m || !tc || !out) return;
  var loc = out.getAttribute('data-locale') || undefined;
  function upd() {
    var monto = parseFloat(m.value), t = parseFloat(tc.value), precio = p ? parseFloat(p.value) : 0, txt = '';
    if (monto > 0 && t > 0) txt = '$' + (monto / t).toFixed(2) + ' USD';
    if (precio > 0 && t > 0) txt += (txt ? '  ·  ' : '') + 'BTC: $' + (precio / t).toLocaleString(loc, { maximumFractionDigits: 0 }) + ' USD';
    out.value = txt;
  }
  [m, tc, p].forEach(function (el) { if (el) el.addEventListener('input', upd); });
  upd();
})();

// Herramientas: los campos de «otra» solo aparecen si se elige esa opción
(function () {
  var sels = document.querySelectorAll('.tool-select');
  Array.prototype.forEach.call(sels, function (sel) {
    sel.addEventListener('change', function () {
      var box = document.getElementById('other_' + sel.getAttribute('data-kind'));
      if (box) box.style.display = sel.value === 'other' ? '' : 'none';
    });
  });
})();
