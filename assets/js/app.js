// Jueves de Satoshi — JS de interfaz (tema + nav móvil)
(function () {
  var toggle = document.getElementById('themeToggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var html = document.documentElement;
      var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('jds-theme', next);
      if (window.jdsRetheme) window.jdsRetheme(); // re-pintar gráficas
    });
  }
  var burger = document.getElementById('navBurger');
  if (burger) {
    burger.addEventListener('click', function () {
      document.getElementById('mainNav').classList.toggle('open');
    });
  }
})();
