// Jueves de Satoshi — Gráficas (Chart.js)
// Espera window.JDS = { labels, fechas, inversion, valor, precioBtcMxn, sats, satsAcum, costoPromedio, urls }
(function () {
  if (!window.JDS || typeof Chart === 'undefined') return;
  var D = window.JDS;
  var charts = [];

  function css(v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }
  function theme() {
    return {
      text: css('--text'), soft: css('--text-soft'), border: css('--border'),
      orange: '#F7931A', black: css('--text'), green: '#1FA858', red: '#D9483B'
    };
  }
  function moneyMXN(v) { return '$' + Number(v).toLocaleString('es-MX', { maximumFractionDigits: 0 }) + ' MXN'; }
  function intFmt(v) { return Number(v).toLocaleString('es-MX'); }

  function baseOpts(t, yFmt) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { color: t.text, usePointStyle: true, boxHeight: 7 } },
        tooltip: {
          backgroundColor: 'rgba(26,26,26,0.92)',
          titleColor: '#F7931A', bodyColor: '#fff', padding: 12, cornerRadius: 10,
          callbacks: {
            label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + yFmt(ctx.parsed.y); },
            footer: function (items) {
              var i = items[0].dataIndex;
              return D.urls[i] ? '🔗 Clic para ver el post en X' : '';
            }
          }
        }
      },
      onClick: function (evt, els) {
        if (els.length && D.urls[els[0].index]) window.open(D.urls[els[0].index], '_blank');
      },
      scales: {
        x: { ticks: { color: t.soft, maxRotation: 45 }, grid: { color: 'transparent' } },
        y: { ticks: { color: t.soft, callback: function (v) { return yFmt(v); } }, grid: { color: t.border } }
      }
    };
  }

  function grad(ctx, color) {
    var g = ctx.createLinearGradient(0, 0, 0, 340);
    g.addColorStop(0, color + '55');
    g.addColorStop(1, color + '00');
    return g;
  }

  function build() {
    charts.forEach(function (c) { c.destroy(); });
    charts = [];
    var t = theme();

    // 1) Inversión vs Valor — la gráfica estrella (con el cálculo corregido:
    //    el valor acumulado usa el precio de BTC de CADA fecha)
    var el1 = document.getElementById('chartInvValor');
    if (el1) {
      var ctx1 = el1.getContext('2d');
      var opts1 = baseOpts(t, moneyMXN);
      // PnL de cada fecha en el tooltip: identifica el peor susto y la mejor racha
      opts1.plugins.tooltip.callbacks.afterBody = function (items) {
        var i = items[0].dataIndex;
        var inv = D.inversion[i], val = D.valor[i];
        if (!inv) return '';
        var pnl = val - inv, pct = pnl / inv * 100;
        return (pnl >= 0 ? '😎 PnL: +' : '😱 PnL: ') + pct.toFixed(2) + '% (' + (pnl >= 0 ? '+' : '−') + moneyMXN(Math.abs(pnl)) + ')';
      };
      charts.push(new Chart(ctx1, {
        type: 'line',
        data: {
          labels: D.labels,
          datasets: [
            {
              label: 'Inversión acumulada (MXN)',
              data: D.inversion,
              borderColor: t.black, backgroundColor: 'transparent',
              borderWidth: 2, borderDash: [6, 4], pointRadius: 3, pointHoverRadius: 7, tension: 0.1
            },
            {
              label: 'Valor de la posición (MXN)',
              data: D.valor,
              borderColor: t.orange, backgroundColor: grad(ctx1, '#F7931A'),
              borderWidth: 3, fill: true, pointRadius: 4, pointHoverRadius: 8, tension: 0.25,
              pointBackgroundColor: D.valor.map(function (v, i) { return v >= D.inversion[i] ? t.green : t.red; })
            }
          ]
        },
        options: opts1
      }));
    }

    // 2) Precio de BTC en cada compra
    var el2 = document.getElementById('chartPrecio');
    if (el2) {
      charts.push(new Chart(el2.getContext('2d'), {
        type: 'line',
        data: {
          labels: D.labels,
          datasets: [{
            label: 'Precio BTC (MXN)',
            data: D.precioBtcMxn,
            borderColor: t.orange, backgroundColor: grad(el2.getContext('2d'), '#F7931A'),
            borderWidth: 3, fill: true, pointRadius: 4, pointHoverRadius: 8, tension: 0.25
          }]
        },
        options: baseOpts(t, moneyMXN)
      }));
    }

    // 3) Sats recibidos por compra — cuando BTC baja, llegan más sats
    var el3 = document.getElementById('chartSats');
    if (el3) {
      charts.push(new Chart(el3.getContext('2d'), {
        type: 'bar',
        data: {
          labels: D.labels,
          datasets: [{
            label: 'Sats recibidos',
            data: D.sats,
            backgroundColor: D.sats.map(function (s) {
              var max = Math.max.apply(null, D.sats), min = Math.min.apply(null, D.sats);
              var p = max === min ? 1 : (s - min) / (max - min);
              return 'rgba(247, 147, 26, ' + (0.35 + p * 0.65).toFixed(2) + ')';
            }),
            borderRadius: 6
          }]
        },
        options: baseOpts(t, intFmt)
      }));
    }

    // 4) Sats acumulados — la montaña que solo crece
    var el4 = document.getElementById('chartSatsAcum');
    if (el4) {
      charts.push(new Chart(el4.getContext('2d'), {
        type: 'line',
        data: {
          labels: D.labels,
          datasets: [{
            label: 'Sats acumulados',
            data: D.satsAcum,
            borderColor: t.black, backgroundColor: grad(el4.getContext('2d'), '#F7931A'),
            borderWidth: 3, fill: true, pointRadius: 3, pointHoverRadius: 7, stepped: false, tension: 0.15
          }]
        },
        options: baseOpts(t, intFmt)
      }));
    }
  }

  window.jdsRetheme = build;
  build();
})();
