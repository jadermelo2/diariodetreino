(function () {
  'use strict';

  var API = 'api/calendar.php';
  var TOTAL_DIAS = 364; // ~52 semanas, como o gráfico de contribuições do GitHub

  var vazioEl = document.getElementById('calendario-vazio');
  var conteudoEl = document.getElementById('calendario-conteudo');
  var streakAtualEl = document.getElementById('streak-atual');
  var streakRecordeEl = document.getElementById('streak-recorde');
  var totalDiasEl = document.getElementById('calendario-total-dias');
  var gridEl = document.getElementById('calendario-grid');

  var MESES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

  var estilo = getComputedStyle(document.documentElement);
  var ACCENT_RGB = hexParaRgb(estilo.getPropertyValue('--accent').trim() || '#E63946');
  var CORES_NIVEL = {
    1: 'rgba(' + ACCENT_RGB + ', 0.3)',
    2: 'rgba(' + ACCENT_RGB + ', 0.55)',
    3: 'rgba(' + ACCENT_RGB + ', 0.8)',
    4: 'rgba(' + ACCENT_RGB + ', 1)',
  };

  [1, 2, 3, 4].forEach(function (nivel) {
    var el = document.getElementById('legenda-' + nivel);
    if (el) {
      el.style.background = CORES_NIVEL[nivel];
      el.style.borderColor = 'transparent';
    }
  });

  fetch(API)
    .then(function (res) { return res.json(); })
    .then(function (dados) {
      if (!dados.total_dias_treinados) {
        mostrarVazio();
        return;
      }

      conteudoEl.style.display = '';
      vazioEl.style.display = 'none';

      streakAtualEl.textContent = dados.streak_atual;
      streakRecordeEl.textContent = dados.streak_recorde;
      totalDiasEl.textContent = dados.total_dias_treinados;

      var semanas = construirSemanas(dados.dias || {});
      renderGrid(semanas);
    })
    .catch(function () {
      vazioEl.textContent = 'Erro ao carregar o calendário.';
      vazioEl.style.display = '';
      conteudoEl.style.display = 'none';
    });

  function mostrarVazio() {
    vazioEl.textContent = 'Nenhum treino registrado ainda. Complete um treino no modo ativo para começar a construir sua sequência.';
    vazioEl.style.display = '';
    conteudoEl.style.display = 'none';
  }

  function construirSemanas(diasMap) {
    var hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    var inicio = new Date(hoje);
    inicio.setDate(hoje.getDate() - (TOTAL_DIAS - 1));
    inicio.setDate(inicio.getDate() - inicio.getDay()); // volta pro domingo anterior/atual

    var semanas = [];
    var cursor = new Date(inicio);

    while (cursor <= hoje) {
      var semana = [];
      for (var i = 0; i < 7; i++) {
        var str = formatarData(cursor);
        var futuro = cursor > hoje;
        semana.push({
          data: new Date(cursor),
          str: str,
          volume: (!futuro && diasMap[str]) ? diasMap[str].volume : 0,
          futuro: futuro,
        });
        cursor.setDate(cursor.getDate() + 1);
      }
      semanas.push(semana);
    }

    return semanas;
  }

  function renderGrid(semanas) {
    var maxVolume = 0;
    semanas.forEach(function (semana) {
      semana.forEach(function (dia) {
        if (!dia.futuro) {
          maxVolume = Math.max(maxVolume, dia.volume);
        }
      });
    });

    gridEl.innerHTML = '';
    gridEl.style.gridTemplateColumns = 'repeat(' + semanas.length + ', 12px)';

    var mesAnterior = null;

    semanas.forEach(function (semana, colIndex) {
      var primeiroValido = semana.filter(function (d) { return !d.futuro; })[0] || semana[0];
      var mes = primeiroValido.data.getMonth();

      if (mes !== mesAnterior) {
        var label = document.createElement('div');
        label.className = 'calendar-month-label';
        label.style.gridColumn = String(colIndex + 1);
        label.textContent = MESES[mes];
        gridEl.appendChild(label);
        mesAnterior = mes;
      }

      semana.forEach(function (dia, rowIndex) {
        var cell = document.createElement('div');
        cell.className = 'calendar-cell' + (dia.futuro ? ' futuro' : '');
        cell.style.gridColumn = String(colIndex + 1);
        cell.style.gridRow = String(rowIndex + 2); // linha 1 é o rótulo do mês

        if (!dia.futuro) {
          if (dia.volume > 0) {
            cell.style.background = corParaVolume(dia.volume, maxVolume);
            cell.style.borderColor = 'transparent';
            cell.title = dia.str + ' — ' + formatarNumero(dia.volume) + 'kg';
          } else {
            cell.title = dia.str + ' — sem treino';
          }
        }

        gridEl.appendChild(cell);
      });
    });
  }

  function corParaVolume(volume, maxVolume) {
    if (maxVolume <= 0) {
      return CORES_NIVEL[1];
    }
    var proporcao = volume / maxVolume;
    var nivel = Math.min(4, Math.max(1, Math.ceil(proporcao * 4)));
    return CORES_NIVEL[nivel];
  }

  function formatarData(d) {
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var dia = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + dia;
  }

  function formatarNumero(valor) {
    var num = Number(valor);
    return num % 1 === 0 ? String(num) : String(num.toFixed(1));
  }

  function hexParaRgb(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) {
      hex = hex.split('').map(function (c) { return c + c; }).join('');
    }
    var r = parseInt(hex.substring(0, 2), 16);
    var g = parseInt(hex.substring(2, 4), 16);
    var b = parseInt(hex.substring(4, 6), 16);
    return r + ', ' + g + ', ' + b;
  }
})();
