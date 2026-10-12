// Jueves de Satoshi — corre en el <head>, antes de pintar, para que nada parpadee.
// Va en archivo y no en línea porque la CSP no deja correr scripts en línea.
(function () {
  var html = document.documentElement;
  try {
    // Tema guardado
    var t = localStorage.getItem('jds-theme');
    if (t) html.setAttribute('data-theme', t);

    // Avisos cerrados hace menos de 30 días (jds-<nombre> = fecha del cierre).
    // El CSS los esconde con html[data-dismissed~="<nombre>"].
    var closed = [];
    for (var i = 0; i < localStorage.length; i++) {
      var k = localStorage.key(i), v = +localStorage.getItem(k);
      if (k && k.indexOf('jds-') === 0 && v > 0 && Date.now() - v < 2592e6) closed.push(k.slice(4));
    }
    if (closed.length) html.setAttribute('data-dismissed', closed.join(' '));
  } catch (e) {}
})();
