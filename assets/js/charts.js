// Jueves de Satoshi — Gráficas (Chart.js)
// Espera window.JDS, que arma year.php: locale, símbolo de moneda, series y textos.
// El color de acento se lee del CSS, así que cada instalación pinta con el suyo.
(function () {
  if (!window.JDS || typeof Chart === 'undefined') return;
  var D = window.JDS;
  var charts = [];

  function css(v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }

  function theme() {
    return {
      text: css('--text'),
      soft: css('--text-soft'),
      border: css('--border'),
      accent: css('--accent') || '#F7931A',
      green: css('--green') || '#1FA858',
      red: css('--red') || '#D9483B'
    };
  }

  function num(v, dec) {
    return Number(v).toLocaleString(D.locale, { maximumFractionDigits: dec === undefined ? 0 : dec });
  }
  function money(v) { return D.symbol + num(v) + ' ' + D.currency; }
  function intFmt(v) { return num(v); }

  function baseOpts(t, yFmt) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { color: t.text, usePointStyle: true, boxHeight: 7 } },
        tooltip: {
          backgroundColor: 'rgba(20,18,16,0.93)',
          titleColor: t.accent, bodyColor: '#fff', padding: 12, cornerRadius: 8,
          callbacks: {
            label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + yFmt(ctx.parsed.y); },
            footer: function (items) {
              var i = items[0].dataIndex;
              return (D.urls && D.urls[i]) ? '🔗 ' + D.i18n.clickPost : '';
            }
          }
        }
      },
      onClick: function (evt, els) {
        if (els.length && D.urls && D.urls[els[0].index]) window.open(D.urls[els[0].index], '_blank', 'noopener');
      },
      scales: {
        x: { ticks: { color: t.soft, maxRotation: 45 }, grid: { color: 'transparent' } },
        y: { ticks: { color: t.soft, callback: function (v) { return yFmt(v); } }, grid: { color: t.border } }
      }
    };
  }

  function grad(ctx, color, h) {
    var g = ctx.createLinearGradient(0, 0, 0, h || 340);
    g.addColorStop(0, color + '55');
    g.addColorStop(1, color + '00');
    return g;
  }

  function build() {
    charts.forEach(function (c) { c.destroy(); });
    charts = [];
    var t = theme();

    // 1) Inversión vs Valor — el valor de cada fecha usa el precio de BTC de ESA fecha
    var el1 = document.getElementById('chartInvValor');
    if (el1 && D.inversion.length) {
      var ctx1 = el1.getContext('2d');
      var opts1 = baseOpts(t, money);
      opts1.plugins.tooltip.callbacks.afterBody = function (items) {
        var i = items[0].dataIndex;
        var inv = D.inversion[i], val = D.valor[i];
        if (!inv) return '';
        var pnl = val - inv, pct = pnl / inv * 100;
        return (pnl >= 0 ? '😎 +' : '😱 ') + pct.toFixed(2) + '% (' + (pnl >= 0 ? '+' : '−') + money(Math.abs(pnl)) + ')';
      };
      charts.push(new Chart(ctx1, {
        type: 'line',
        data: {
          labels: D.labels,
          datasets: [
            {
              label: D.i18n.inv, data: D.inversion,
              borderColor: t.soft, backgroundColor: 'transparent',
              borderWidth: 2, borderDash: [6, 4], pointRadius: 2, tension: 0.25
            },
            {
              label: D.i18n.val, data: D.valor,
              borderColor: t.accent, backgroundColor: grad(ctx1, t.accent),
              borderWidth: 3, fill: true, pointRadius: 3, pointHoverRadius: 6, tension: 0.25
            }
          ]
        },
        options: opts1
      }));
    }

    // 2) Precio de BTC en cada compra
    var el2 = document.getElementById('chartPrecio');
    if (el2 && D.precioBtc.length) {
      var ctx2 = el2.getContext('2d');
      charts.push(new Chart(ctx2, {
        type: 'line',
        data: {
          labels: D.labels,
          datasets: [{
            label: D.i18n.price, data: D.precioBtc,
            borderColor: t.accent, backgroundColor: grad(ctx2, t.accent, 260),
            borderWidth: 3, fill: true, pointRadius: 3, pointHoverRadius: 6, tension: 0.3
          }]
        },
        options: baseOpts(t, money)
      }));
    }

    // 3) Sats por compra: cuando el precio baja, el mismo monto compra más
    var el3 = document.getElementById('chartSats');
    if (el3 && D.sats.length) {
      var opts3 = baseOpts(t, intFmt);
      opts3.plugins.legend.display = false;
      charts.push(new Chart(el3.getContext('2d'), {
        type: 'bar',
        data: {
          labels: D.labels,
          datasets: [{
            label: D.i18n.sats, data: D.sats,
            backgroundColor: D.sats.map(function (v) {
              var max = Math.max.apply(null, D.sats);
              return v === max ? t.green : t.accent;
            }),
            borderRadius: 4
          }]
        },
        options: opts3
      }));
    }

    // 4) Sats acumulados
    var el4 = document.getElementById('chartSatsAcum');
    if (el4 && D.satsAcum.length) {
      var ctx4 = el4.getContext('2d');
      var opts4 = baseOpts(t, intFmt);
      opts4.plugins.legend.display = false;
      charts.push(new Chart(ctx4, {
        type: 'line',
        data: {
          labels: D.labels,
          datasets: [{
            label: D.i18n.satsAcum, data: D.satsAcum,
            borderColor: t.green, backgroundColor: grad(ctx4, t.green, 260),
            borderWidth: 3, fill: true, pointRadius: 2, tension: 0.2
          }]
        },
        options: opts4
      }));
    }
  }

  build();
  // El toggle de tema vuelve a pintar: los colores vienen del CSS
  window.jdsRetheme = build;
})();
