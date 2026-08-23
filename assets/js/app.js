// Jueves de Satoshi — JS de interfaz (tema + nav móvil)
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
