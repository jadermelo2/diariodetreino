(function () {
  'use strict';

  var API = 'api/bodyweight.php';

  var form = document.getElementById('form-peso');
  var idInput = document.getElementById('peso-id');
  var dataInput = document.getElementById('peso-data');
  var pesoInput = document.getElementById('peso-valor');
  var erroEl = document.getElementById('peso-erro');
  var acoesEl = document.getElementById('peso-form-acoes');

  var btnMedidasToggle = document.getElementById('btn-medidas-toggle');
  var medidasCamposEl = document.getElementById('medidas-campos');

  var listaEl = document.getElementById('peso-lista');
  var chartCardEl = document.getElementById('peso-chart-card');
  var canvasPeso = document.getElementById('chart-peso');

  var MEDIDAS_CAMPOS = [
    { chave: 'cintura', id: 'medida-cintura', label: 'Cintura' },
    { chave: 'braco', id: 'medida-braco', label: 'Braço' },
    { chave: 'peito', id: 'medida-peito', label: 'Peito' },
    { chave: 'coxa', id: 'medida-coxa', label: 'Coxa' },
    { chave: 'quadril', id: 'medida-quadril', label: 'Quadril' },
  ];

  var registros = [];
  var chartPeso = null;

  var estilo = getComputedStyle(document.documentElement);
  var COR_ACCENT = estilo.getPropertyValue('--accent').trim() || '#E63946';
  var COR_TEXT_MUTED = estilo.getPropertyValue('--text-muted').trim() || '#8A8A8A';
  var COR_BORDER = estilo.getPropertyValue('--border').trim() || '#2E2E30';
  var FONTE_MONO = "'JetBrains Mono', ui-monospace, monospace";

  dataInput.value = new Date().toISOString().slice(0, 10);

  btnMedidasToggle.addEventListener('click', function () {
    var abrindo = medidasCamposEl.style.display === 'none';
    medidasCamposEl.style.display = abrindo ? '' : 'none';
  });

  form.addEventListener('submit', onSubmit);

  carregar();

  function carregar() {
    fetch(API)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        registros = Array.isArray(data) ? data : [];
        renderChart();
        renderLista();
      })
      .catch(function () {
        listaEl.innerHTML = '<div class="empty-state">Erro ao carregar os registros.</div>';
      });
  }

  function renderChart() {
    if (registros.length === 0) {
      chartCardEl.style.display = 'none';
      return;
    }

    chartCardEl.style.display = '';

    var labels = registros.map(function (r) { return r.data; });
    var pesos = registros.map(function (r) { return r.peso; });

    if (chartPeso) {
      chartPeso.destroy();
    }

    chartPeso = new Chart(canvasPeso, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Peso (kg)',
          data: pesos,
          borderColor: COR_ACCENT,
          backgroundColor: 'transparent',
          borderWidth: 2,
          tension: 0,
          pointBackgroundColor: COR_ACCENT,
          pointRadius: 3,
        }],
      },
      options: {
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
              label: function (ctx) { return ctx.parsed.y + ' kg'; },
            },
          },
        },
        scales: {
          x: {
            grid: { color: COR_BORDER },
            ticks: { color: COR_TEXT_MUTED, font: { family: FONTE_MONO, size: 10 }, maxRotation: 45 },
          },
          y: {
            grid: { color: COR_BORDER },
            ticks: { color: COR_TEXT_MUTED, font: { family: FONTE_MONO, size: 10 } },
          },
        },
      },
    });
  }

  function renderLista() {
    if (registros.length === 0) {
      listaEl.innerHTML = '<div class="empty-state">Nenhum registro de peso ainda. Registre seu peso acima para começar a acompanhar sua evolução.</div>';
      return;
    }

    var ordenados = registros.slice().sort(function (a, b) { return b.data.localeCompare(a.data); });

    var html = '<div class="list-group">';
    ordenados.forEach(function (r) {
      var medidasResumo = MEDIDAS_CAMPOS
        .filter(function (campo) { return r.medidas && r.medidas[campo.chave] !== undefined; })
        .map(function (campo) { return campo.label + ' ' + formatarNumero(r.medidas[campo.chave]) + 'cm'; })
        .join(' · ');

      html += '<div class="weight-list-item" data-id="' + escapeHtml(r.id) + '">';
      html += '  <div class="weight-info">';
      html += '    <span class="weight-data">' + escapeHtml(r.data) + '</span>';
      html += '    <span class="weight-peso">' + formatarNumero(r.peso) + ' kg</span>';
      if (medidasResumo) {
        html += '    <span class="weight-medidas">' + escapeHtml(medidasResumo) + '</span>';
      }
      html += '  </div>';
      html += '  <div class="weight-actions">';
      html += '    <button type="button" class="btn" data-editar="' + escapeHtml(r.id) + '">Editar</button>';
      html += '    <button type="button" class="btn danger" data-excluir="' + escapeHtml(r.id) + '">Excluir</button>';
      html += '  </div>';
      html += '</div>';
    });
    html += '</div>';

    listaEl.innerHTML = html;

    listaEl.querySelectorAll('[data-editar]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        editar(btn.getAttribute('data-editar'));
      });
    });

    listaEl.querySelectorAll('[data-excluir]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        excluir(btn.getAttribute('data-excluir'));
      });
    });
  }

  function editar(id) {
    var registro = registros.find(function (r) { return r.id === id; });
    if (!registro) {
      return;
    }

    idInput.value = registro.id;
    dataInput.value = registro.data;
    pesoInput.value = registro.peso;

    MEDIDAS_CAMPOS.forEach(function (campo) {
      var input = document.getElementById(campo.id);
      var valor = registro.medidas ? registro.medidas[campo.chave] : undefined;
      input.value = valor !== undefined ? valor : '';
    });

    var temMedidas = MEDIDAS_CAMPOS.some(function (campo) {
      return registro.medidas && registro.medidas[campo.chave] !== undefined;
    });
    medidasCamposEl.style.display = temMedidas ? '' : 'none';

    acoesEl.innerHTML =
      '<button type="submit" class="primary" style="flex:1;">Salvar alterações</button>' +
      '<button type="button" class="btn" id="btn-cancelar-edicao" style="flex:1;">Cancelar edição</button>';
    document.getElementById('btn-cancelar-edicao').addEventListener('click', limparFormulario);

    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function limparFormulario() {
    idInput.value = '';
    form.reset();
    dataInput.value = new Date().toISOString().slice(0, 10);
    medidasCamposEl.style.display = 'none';
    erroEl.textContent = '';
    acoesEl.innerHTML = '<button type="submit" class="primary" style="width: 100%;">Registrar</button>';
  }

  function onSubmit(e) {
    e.preventDefault();
    erroEl.textContent = '';

    var peso = parseFloat(pesoInput.value);
    if (isNaN(peso) || peso <= 0) {
      erroEl.textContent = 'Informe um peso válido.';
      return;
    }

    var medidas = {};
    MEDIDAS_CAMPOS.forEach(function (campo) {
      var input = document.getElementById(campo.id);
      if (input.value.trim() !== '') {
        medidas[campo.chave] = parseFloat(input.value);
      }
    });

    var payload = { data: dataInput.value, peso: peso, medidas: medidas };

    var id = idInput.value;
    var url = id ? (API + '?id=' + encodeURIComponent(id)) : API;
    var method = id ? 'PATCH' : 'POST';

    fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok) {
          erroEl.textContent = result.data.error || 'Erro ao salvar registro.';
          return;
        }
        limparFormulario();
        carregar();
      })
      .catch(function () {
        erroEl.textContent = 'Erro de conexão ao salvar registro.';
      });
  }

  function excluir(id) {
    if (!confirm('Excluir este registro de peso? Essa ação não pode ser desfeita.')) {
      return;
    }

    fetch(API + '?id=' + encodeURIComponent(id), { method: 'DELETE' })
      .then(function (res) { return res.json(); })
      .then(function () { carregar(); })
      .catch(function () { alert('Erro ao excluir registro.'); });
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
