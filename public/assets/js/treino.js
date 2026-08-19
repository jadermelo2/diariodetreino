(function () {
  'use strict';

  var API_SESSIONS = 'api/sessions.php';
  var API_ROTINAS = 'api/routines.php';
  var API_EXERCICIOS = 'api/exercises.php';
  var STORAGE_KEY = 'diario_treino_ativo';
  var STORAGE_DESCANSO = 'diario_treino_descanso_padrao';

  // ---- elementos ----
  var viewSetup = document.getElementById('view-setup');
  var viewAtivo = document.getElementById('view-ativo');
  var viewFim = document.getElementById('view-fim');

  var setupRotinasEl = document.getElementById('setup-rotinas');
  var btnLivre = document.getElementById('btn-livre');

  var progressoEl = document.getElementById('ativo-progresso');
  var fimAvisoEl = document.getElementById('ativo-fim-aviso');
  var nomeExercicioEl = document.getElementById('ativo-exercicio-nome');
  var seriesListaEl = document.getElementById('ativo-series-lista');
  var formSerie = document.getElementById('form-serie');
  var inputReps = document.getElementById('input-reps');
  var inputCarga = document.getElementById('input-carga');
  var serieErroEl = document.getElementById('serie-erro');

  var restTimerEl = document.getElementById('rest-timer');
  var restLabelEl = document.getElementById('rest-label');
  var restCountEl = document.getElementById('rest-count');
  var restMenos = document.getElementById('rest-menos');
  var restMais = document.getElementById('rest-mais');
  var restPular = document.getElementById('rest-pular');
  var selectDescanso = document.getElementById('select-descanso');

  var btnTrocar = document.getElementById('btn-trocar-exercicio');
  var btnProximo = document.getElementById('btn-proximo-exercicio');
  var btnFinalizar = document.getElementById('btn-finalizar');

  var modalPicker = document.getElementById('modal-picker');
  var pickerLista = document.getElementById('picker-lista');
  var pickerFechar = document.getElementById('picker-fechar');

  var fimResumoEl = document.getElementById('fim-resumo');

  // ---- estado ----
  var exercicios = [];
  var exerciciosPorId = {};
  var rotinas = [];

  var state = {
    sessionId: null,
    rotinaId: null,
    exerciseIds: [],
    currentIndex: -1,
    sessionData: null,
  };

  var restInterval = null;
  var restRemaining = 0;
  var pickerModoAdicionar = false;

  // ---- inicialização ----
  Promise.all([
    fetch(API_EXERCICIOS).then(function (res) { return res.json(); }),
    fetch(API_ROTINAS).then(function (res) { return res.json(); }),
  ]).then(function (results) {
    exercicios = Array.isArray(results[0]) ? results[0] : [];
    rotinas = Array.isArray(results[1]) ? results[1] : [];
    exercicios.forEach(function (ex) { exerciciosPorId[ex.id] = ex; });

    var descansoSalvo = localStorage.getItem(STORAGE_DESCANSO);
    if (descansoSalvo) {
      selectDescanso.value = descansoSalvo;
    }

    tentarRetomar();
  });

  selectDescanso.addEventListener('change', function () {
    localStorage.setItem(STORAGE_DESCANSO, selectDescanso.value);
  });

  btnLivre.addEventListener('click', function () {
    iniciarTreino(null);
  });

  formSerie.addEventListener('submit', onRegistrarSerie);

  btnTrocar.addEventListener('click', function () {
    abrirPicker(false);
  });

  btnProximo.addEventListener('click', proximoExercicio);
  btnFinalizar.addEventListener('click', finalizarTreino);

  pickerFechar.addEventListener('click', fecharPicker);
  modalPicker.addEventListener('click', function (e) {
    if (e.target === modalPicker) {
      fecharPicker();
    }
  });

  restMenos.addEventListener('click', function () { ajustarDescanso(-15); });
  restMais.addEventListener('click', function () { ajustarDescanso(15); });
  restPular.addEventListener('click', pararDescanso);

  // ---- tela: setup ----

  function renderSetup() {
    if (rotinas.length === 0) {
      setupRotinasEl.innerHTML = '<div class="empty-state">Nenhuma rotina cadastrada ainda. ' +
        '<a href="rotinas.php">Crie uma rotina</a> ou comece um treino livre.</div>';
      return;
    }

    var html = '';
    rotinas.forEach(function (rotina) {
      var qtd = (rotina.exercicios || []).length;
      html += '<button type="button" class="btn rotina-pick" data-rotina="' + escapeHtml(rotina.id) + '">' +
        '<span>' + escapeHtml(rotina.nome) + '</span>' +
        '<span class="badge">' + qtd + ' exerc.</span>' +
        '</button>';
    });
    setupRotinasEl.innerHTML = html;

    setupRotinasEl.querySelectorAll('[data-rotina]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        iniciarTreino(btn.getAttribute('data-rotina'));
      });
    });
  }

  function iniciarTreino(rotinaId) {
    fetch(API_SESSIONS, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ rotina_id: rotinaId }),
    })
      .then(function (res) { return res.json(); })
      .then(function (session) {
        state.sessionId = session.id;
        state.rotinaId = session.rotina_id;
        state.sessionData = session;
        state.exerciseIds = (session.exercicios || []).map(function (e) { return e.exercicio_id; });
        state.currentIndex = state.exerciseIds.length > 0 ? 0 : -1;
        salvarEstado();

        if (state.currentIndex === -1) {
          mostrarView('ativo');
          abrirPicker(true);
        } else {
          mostrarView('ativo');
          renderAtivo();
        }
      })
      .catch(function () {
        alert('Erro ao iniciar o treino. Tente novamente.');
      });
  }

  // ---- retomada via localStorage ----

  function tentarRetomar() {
    var raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) {
      mostrarView('setup');
      renderSetup();
      return;
    }

    var saved;
    try {
      saved = JSON.parse(raw);
    } catch (e) {
      localStorage.removeItem(STORAGE_KEY);
      mostrarView('setup');
      renderSetup();
      return;
    }

    fetch(API_SESSIONS + '?id=' + encodeURIComponent(saved.sessionId))
      .then(function (res) {
        if (!res.ok) {
          throw new Error('not found');
        }
        return res.json();
      })
      .then(function (session) {
        state.sessionId = session.id;
        state.rotinaId = session.rotina_id;
        state.sessionData = session;
        state.exerciseIds = Array.isArray(saved.exerciseIds) && saved.exerciseIds.length > 0
          ? saved.exerciseIds
          : (session.exercicios || []).map(function (e) { return e.exercicio_id; });
        state.currentIndex = Math.min(
          Math.max(saved.currentIndex || 0, 0),
          Math.max(state.exerciseIds.length - 1, 0)
        );

        if (state.exerciseIds.length === 0) {
          mostrarView('ativo');
          abrirPicker(true);
        } else {
          mostrarView('ativo');
          renderAtivo();
        }
      })
      .catch(function () {
        localStorage.removeItem(STORAGE_KEY);
        mostrarView('setup');
        renderSetup();
      });
  }

  function salvarEstado() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      sessionId: state.sessionId,
      exerciseIds: state.exerciseIds,
      currentIndex: state.currentIndex,
    }));
  }

  function limparEstado() {
    localStorage.removeItem(STORAGE_KEY);
  }

  function mostrarView(nome) {
    viewSetup.style.display = nome === 'setup' ? '' : 'none';
    viewAtivo.style.display = nome === 'ativo' ? '' : 'none';
    viewFim.style.display = nome === 'fim' ? '' : 'none';
  }

  // ---- tela: exercício ativo ----

  function exercicioAtualId() {
    return state.exerciseIds[state.currentIndex];
  }

  function entradaSessaoDoExercicio(exercicioId) {
    var lista = (state.sessionData && state.sessionData.exercicios) || [];
    for (var i = 0; i < lista.length; i++) {
      if (lista[i].exercicio_id === exercicioId) {
        return lista[i];
      }
    }
    return null;
  }

  function renderAtivo() {
    pararDescanso();
    serieErroEl.textContent = '';

    var exId = exercicioAtualId();
    var ex = exerciciosPorId[exId];

    progressoEl.textContent = (state.currentIndex + 1) + '/' + state.exerciseIds.length;
    nomeExercicioEl.textContent = ex ? ex.nome : '(exercício removido da biblioteca)';

    var atingiuFimRotina = state.rotinaId !== null && state.currentIndex === state.exerciseIds.length - 1;
    fimAvisoEl.classList.toggle('show', false);

    var entrada = entradaSessaoDoExercicio(exId);
    var series = entrada ? entrada.series : [];

    if (series.length === 0) {
      seriesListaEl.innerHTML = '<li class="mono" style="color: var(--text-muted);">Nenhuma série ainda.</li>';
    } else {
      var html = '';
      series.forEach(function (s, i) {
        html += '<li><span class="set-index">#' + (i + 1) + '</span>' +
          '<span class="set-value">' + s.reps + ' reps &times; ' + formatarCarga(s.carga) + 'kg</span></li>';
      });
      seriesListaEl.innerHTML = html;
    }

    inputReps.value = '';
    inputCarga.value = '';
    inputReps.focus();
  }

  function onRegistrarSerie(e) {
    e.preventDefault();
    serieErroEl.textContent = '';

    var reps = parseInt(inputReps.value, 10);
    var carga = parseFloat(inputCarga.value);

    if (!reps || reps <= 0) {
      serieErroEl.textContent = 'Informe as repetições.';
      return;
    }
    if (isNaN(carga) || carga < 0) {
      serieErroEl.textContent = 'Informe a carga (pode ser 0).';
      return;
    }

    var exId = exercicioAtualId();

    fetch(API_SESSIONS + '?id=' + encodeURIComponent(state.sessionId), {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add_set', exercicio_id: exId, reps: reps, carga: carga }),
    })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok) {
          serieErroEl.textContent = result.data.error || 'Erro ao registrar série.';
          return;
        }
        state.sessionData = result.data;
        renderAtivo();
        iniciarDescanso(parseInt(selectDescanso.value, 10) || 90);
      })
      .catch(function () {
        serieErroEl.textContent = 'Erro de conexão ao registrar série.';
      });
  }

  function proximoExercicio() {
    pararDescanso();

    var proximoIndex = state.currentIndex + 1;

    if (proximoIndex < state.exerciseIds.length) {
      state.currentIndex = proximoIndex;
      salvarEstado();
      renderAtivo();
      return;
    }

    // chegou ao fim da lista atual
    if (state.rotinaId === null) {
      // treino livre: abre o seletor para adicionar o próximo exercício
      abrirPicker(true);
    } else {
      // rotina: fica no último exercício e avisa que a rotina terminou
      fimAvisoEl.classList.add('show');
    }
  }

  // ---- timer de descanso ----

  function iniciarDescanso(segundos) {
    pararDescanso();
    restRemaining = segundos;
    restTimerEl.classList.remove('done');
    restTimerEl.classList.add('show');
    restLabelEl.textContent = 'Descanso';
    atualizarRestDisplay();

    restInterval = setInterval(function () {
      restRemaining--;
      if (restRemaining <= 0) {
        restRemaining = 0;
        atualizarRestDisplay();
        onDescansoFim();
      } else {
        atualizarRestDisplay();
      }
    }, 1000);
  }

  function ajustarDescanso(delta) {
    if (!restInterval && !restTimerEl.classList.contains('show')) {
      return;
    }
    restRemaining = Math.max(0, restRemaining + delta);
    atualizarRestDisplay();
    if (restRemaining === 0) {
      onDescansoFim();
    }
  }

  function atualizarRestDisplay() {
    var min = Math.floor(restRemaining / 60);
    var seg = restRemaining % 60;
    restCountEl.textContent = (min > 0 ? min + ':' + String(seg).padStart(2, '0') : String(seg));
  }

  function onDescansoFim() {
    if (restInterval) {
      clearInterval(restInterval);
      restInterval = null;
    }
    restTimerEl.classList.add('done');
    restLabelEl.textContent = 'Descanso concluído!';

    if (navigator.vibrate) {
      navigator.vibrate([200, 100, 200, 100, 200]);
    }
    tocarBeep();
  }

  function pararDescanso() {
    if (restInterval) {
      clearInterval(restInterval);
      restInterval = null;
    }
    restTimerEl.classList.remove('show', 'done');
  }

  function tocarBeep() {
    try {
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) {
        return;
      }
      var ctx = new AudioCtx();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.6);
      osc.onended = function () { ctx.close(); };
    } catch (e) {
      // ambiente sem suporte a áudio — ignora silenciosamente
    }
  }

  // ---- picker de exercício (trocar / adicionar) ----

  function abrirPicker(modoAdicionar) {
    pickerModoAdicionar = modoAdicionar;

    if (exercicios.length === 0) {
      pickerLista.innerHTML = '<div class="empty-state">Nenhum exercício cadastrado ainda. ' +
        '<a href="exercicios.php">Cadastre exercícios</a> antes de treinar.</div>';
    } else {
      var html = '';
      exercicios.forEach(function (ex) {
        html += '<div class="picker-item" data-exercicio="' + escapeHtml(ex.id) + '">' +
          '<span>' + escapeHtml(ex.nome) + '</span>' +
          '<span class="grupo-tag">' + escapeHtml(ex.grupo_muscular) + '</span>' +
          '</div>';
      });
      pickerLista.innerHTML = html;

      pickerLista.querySelectorAll('[data-exercicio]').forEach(function (item) {
        item.addEventListener('click', function () {
          selecionarExercicio(item.getAttribute('data-exercicio'));
        });
      });
    }

    modalPicker.classList.add('open');
  }

  function fecharPicker() {
    modalPicker.classList.remove('open');
  }

  function selecionarExercicio(exercicioId) {
    var index = state.exerciseIds.indexOf(exercicioId);

    if (index === -1) {
      state.exerciseIds.push(exercicioId);
      index = state.exerciseIds.length - 1;
    }

    state.currentIndex = index;
    salvarEstado();
    fecharPicker();
    renderAtivo();
  }

  // ---- finalizar treino ----

  function finalizarTreino() {
    if (!confirm('Finalizar o treino? Você poderá conferir o resumo em seguida.')) {
      return;
    }

    pararDescanso();

    var totalSeries = 0;
    var exerciciosTrabalhados = 0;
    (state.sessionData.exercicios || []).forEach(function (item) {
      if (item.series.length > 0) {
        exerciciosTrabalhados++;
        totalSeries += item.series.length;
      }
    });

    var concluir = function () {
      limparEstado();
      fimResumoEl.textContent = exerciciosTrabalhados + ' exercício(s) trabalhado(s) · ' +
        totalSeries + ' série(s) registrada(s).';
      mostrarView('fim');
    };

    if (totalSeries === 0) {
      fetch(API_SESSIONS + '?id=' + encodeURIComponent(state.sessionId), { method: 'DELETE' })
        .then(concluir)
        .catch(concluir);
    } else {
      concluir();
    }
  }

  // ---- utils ----

  function formatarCarga(valor) {
    var num = Number(valor);
    return num % 1 === 0 ? String(num) : String(num.toFixed(1));
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
