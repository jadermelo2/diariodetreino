(function () {
  'use strict';

  var API_PROGRESS = 'api/progress.php';
  var API_EXERCICIOS = 'api/exercises.php';
  var API_ROTINAS = 'api/routines.php';

  var selectExercicio = document.getElementById('select-exercicio');
  var vazioEl = document.getElementById('progresso-vazio');
  var conteudoEl = document.getElementById('progresso-conteudo');
  var recordeValorEl = document.getElementById('recorde-valor');
  var prListaEl = document.getElementById('pr-lista');

  var volumeBotoes = document.querySelectorAll('[data-modo-volume]');
  var campoRotinaVolume = document.getElementById('campo-rotina-volume');
  var selectRotinaVolume = document.getElementById('select-rotina-volume');

  var canvasCarga = document.getElementById('chart-carga');
  var canvasVolume = document.getElementById('chart-volume');

  var chartCarga = null;
  var chartVolume = null;
  var modoVolume = 'exercicio';
  var rotinas = [];

  // ---- tema (lido das custom properties do CSS, pra não duplicar cor aqui) ----
  var estilo = getComputedStyle(document.documentElement);
  var COR_ACCENT = estilo.getPropertyValue('--accent').trim() || '#E63946';
  var COR_OK = estilo.getPropertyValue('--ok').trim() || '#4CAF6D';
  var COR_TEXT_MUTED = estilo.getPropertyValue('--text-muted').trim() || '#8A8A8A';
  var COR_BORDER = estilo.getPropertyValue('--border').trim() || '#2E2E30';
  var FONTE_MONO = "'JetBrains Mono', ui-monospace, monospace";

  Promise.all([
    fetch(API_EXERCICIOS).then(function (res) { return res.json(); }),
    fetch(API_ROTINAS).then(function (res) { return res.json(); }),
  ]).then(function (results) {
    var exercicios = Array.isArray(results[0]) ? results[0] : [];
    rotinas = Array.isArray(results[1]) ? results[1] : [];

    popularSelectExercicios(exercicios);
    popularSelectRotinas(rotinas);
  });

  selectExercicio.addEventListener('change', function () {
    if (selectExercicio.value) {
      carregarProgresso(selectExercicio.value);
    } else {
      mostrarVazio('Selecione um exercício para ver o progresso.');
    }
  });

  volumeBotoes.forEach(function (btn) {
    btn.addEventListener('click', function () {
      modoVolume = btn.getAttribute('data-modo-volume');
      volumeBotoes.forEach(function (b) { b.classList.toggle('active', b === btn); });
      campoRotinaVolume.style.display = modoVolume === 'rotina' ? '' : 'none';
      carregarVolume();
    });
  });

  selectRotinaVolume.addEventListener('change', carregarVolume);

  function popularSelectExercicios(exercicios) {
    if (exercicios.length === 0) {
      mostrarVazio('Nenhum exercício cadastrado ainda. Cadastre exercícios e registre alguns treinos para ver seu progresso aqui.');
      return;
    }

    var html = '<option value="">Selecione um exercício...</option>';
    exercicios.forEach(function (ex) {
      html += '<option value="' + escapeHtml(ex.id) + '">' + escapeHtml(ex.nome) + '</option>';
    });
    selectExercicio.innerHTML = html;

    mostrarVazio('Selecione um exercício para ver o progresso.');
  }

  function popularSelectRotinas(rotinas) {
    if (rotinas.length === 0) {
      selectRotinaVolume.innerHTML = '<option value="">Nenhuma rotina cadastrada</option>';
      return;
    }
    var html = '';
    rotinas.forEach(function (r) {
      html += '<option value="' + escapeHtml(r.id) + '">' + escapeHtml(r.nome) + '</option>';
    });
    selectRotinaVolume.innerHTML = html;
  }

  function mostrarVazio(mensagem) {
    vazioEl.textContent = mensagem;
    vazioEl.style.display = '';
    conteudoEl.style.display = 'none';
  }

  function mostrarConteudo() {
    vazioEl.style.display = 'none';
    conteudoEl.style.display = '';
  }

  function carregarProgresso(exercicioId) {
    fetch(API_PROGRESS + '?exercicio_id=' + encodeURIComponent(exercicioId))
      .then(function (res) { return res.json(); })
      .then(function (dados) {
        if (!dados.labels || dados.labels.length === 0) {
          mostrarVazio('Nenhum treino registrado para este exercício ainda. Registre algumas séries no modo treino ativo.');
          return;
        }

        mostrarConteudo();
        renderChartCarga(dados);
        renderRecordeEPRs(dados);
        carregarVolume();
      })
      .catch(function () {
        mostrarVazio('Erro ao carregar o progresso deste exercício.');
      });
  }

  function renderChartCarga(dados) {
    var maiorAteAgora = -Infinity;
    var pointColors = dados.carga_maxima.map(function (carga) {
      var isPR = carga > maiorAteAgora;
      if (isPR) {
        maiorAteAgora = carga;
      }
      return isPR ? COR_OK : COR_ACCENT;
    });
    var pointRadii = dados.carga_maxima.map(function (carga, i) {
      return pointColors[i] === COR_OK ? 5 : 3;
    });

    if (chartCarga) {
      chartCarga.destroy();
    }

    chartCarga = new Chart(canvasCarga, {
      type: 'line',
      data: {
        labels: dados.labels,
        datasets: [{
          label: 'Carga máxima (kg)',
          data: dados.carga_maxima,
          borderColor: COR_ACCENT,
          backgroundColor: 'transparent',
          borderWidth: 2,
          tension: 0,
          pointBackgroundColor: pointColors,
          pointBorderColor: pointColors,
          pointRadius: pointRadii,
        }],
      },
      options: opcoesBase('kg'),
    });
  }

  function renderRecordeEPRs(dados) {
    recordeValorEl.textContent = dados.recorde_atual !== null
      ? formatarNumero(dados.recorde_atual) + ' kg'
      : '—';

    var prs = dados.prs || [];
    if (prs.length === 0) {
      prListaEl.innerHTML = '<li>Nenhum PR ainda.</li>';
      return;
    }

    var html = '';
    prs.slice().reverse().forEach(function (pr) {
      html += '<li><span>' + ICONS.trophy + ' ' + escapeHtml(pr.data) + '</span>' +
        '<span class="pr-carga">' + formatarNumero(pr.carga) + 'kg</span></li>';
    });
    prListaEl.innerHTML = html;
  }

  function carregarVolume() {
    var exercicioId = selectExercicio.value;
    if (!exercicioId) {
      return;
    }

    var url = API_PROGRESS + '?volume=1';
    if (modoVolume === 'exercicio') {
      url += '&exercicio_id=' + encodeURIComponent(exercicioId);
    } else if (modoVolume === 'rotina') {
      var rotinaId = selectRotinaVolume.value;
      if (!rotinaId) {
        renderChartVolume({ labels: [], volume: [] });
        return;
      }
      url += '&rotina_id=' + encodeURIComponent(rotinaId);
    }
    // modo 'total': sem filtro nenhum

    fetch(url)
      .then(function (res) { return res.json(); })
      .then(renderChartVolume)
      .catch(function () {
        renderChartVolume({ labels: [], volume: [] });
      });
  }

  function renderChartVolume(dados) {
    if (chartVolume) {
      chartVolume.destroy();
    }

    chartVolume = new Chart(canvasVolume, {
      type: 'bar',
      data: {
        labels: dados.labels || [],
        datasets: [{
          label: 'Volume (kg)',
          data: dados.volume || [],
          backgroundColor: COR_ACCENT,
          borderWidth: 0,
        }],
      },
      options: opcoesBase('kg'),
    });
  }

  function opcoesBase(sufixoY) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0F0F10',
          borderColor: COR_BORDER,
          borderWidth: 1,
          titleFont: { family: FONTE_MONO },
          bodyFont: { family: FONTE_MONO },
          callbacks: {
            label: function (ctx) {
              return ctx.parsed.y + (sufixoY ? ' ' + sufixoY : '');
            },
          },
        },
      },
      scales: {
        x: {
          grid: { color: COR_BORDER },
          ticks: { color: COR_TEXT_MUTED, font: { family: FONTE_MONO, size: 10 }, maxRotation: 45, minRotation: 0 },
        },
        y: {
          beginAtZero: true,
          grid: { color: COR_BORDER },
          ticks: { color: COR_TEXT_MUTED, font: { family: FONTE_MONO, size: 10 } },
        },
      },
    };
  }

  function formatarNumero(valor) {
    var num = Number(valor);
    return num % 1 === 0 ? String(num) : String(num.toFixed(1));
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
