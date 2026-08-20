(function () {
  'use strict';

  var API_ROTINAS = 'api/routines.php';
  var API_EXERCICIOS = 'api/exercises.php';

  var listEl = document.getElementById('lista-rotinas');
  var modal = document.getElementById('modal-rotina');
  var form = document.getElementById('form-rotina');
  var modalTitulo = document.getElementById('modal-titulo');
  var formErro = document.getElementById('form-erro');
  var linhasEl = document.getElementById('rotina-exercicios-linhas');
  var btnAddLinha = document.getElementById('btn-add-linha');

  var idInput = document.getElementById('rotina-id');
  var nomeInput = document.getElementById('rotina-nome');

  var rotinas = [];
  var exercicios = [];
  var exerciciosPorId = {};

  document.getElementById('btn-nova-rotina').addEventListener('click', function () {
    abrirModal();
  });

  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('modal-cancelar').addEventListener('click', fecharModal);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      fecharModal();
    }
  });

  btnAddLinha.addEventListener('click', function () {
    adicionarLinha();
  });

  form.addEventListener('submit', onSubmit);

  carregar();

  function carregar() {
    Promise.all([
      fetch(API_EXERCICIOS).then(function (res) { return res.json(); }),
      fetch(API_ROTINAS).then(function (res) { return res.json(); }),
    ])
      .then(function (results) {
        exercicios = Array.isArray(results[0]) ? results[0] : [];
        rotinas = Array.isArray(results[1]) ? results[1] : [];

        exerciciosPorId = {};
        exercicios.forEach(function (ex) { exerciciosPorId[ex.id] = ex; });

        render();
      })
      .catch(function () {
        listEl.innerHTML = '<div class="empty-state">Erro ao carregar rotinas.</div>';
      });
  }

  function render() {
    if (rotinas.length === 0) {
      listEl.innerHTML = '<div class="empty-state">Nenhuma rotina cadastrada ainda. Toque em "+ Nova" para começar.</div>';
      return;
    }

    var html = '<div class="list-group">';
    rotinas.forEach(function (rotina) {
      html += renderItem(rotina);
    });
    html += '</div>';

    listEl.innerHTML = html;

    listEl.querySelectorAll('.list-item-header').forEach(function (header) {
      header.addEventListener('click', function () {
        header.parentElement.classList.toggle('open');
      });
    });

    listEl.querySelectorAll('[data-editar]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        abrirModal(encontrarPorId(btn.getAttribute('data-editar')));
      });
    });

    listEl.querySelectorAll('[data-clonar]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        clonar(btn.getAttribute('data-clonar'));
      });
    });

    listEl.querySelectorAll('[data-excluir]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        excluir(btn.getAttribute('data-excluir'));
      });
    });
  }

  function renderItem(rotina) {
    var itens = rotina.exercicios || [];

    var html = '<div class="list-item" data-id="' + escapeHtml(rotina.id) + '">';
    html += '  <div class="list-item-header">';
    html += '    <span class="list-item-name">' + escapeHtml(rotina.nome) + '</span>';
    html += '    <span class="badge">' + itens.length + ' exerc.</span>';
    html += '    <span class="list-item-chevron">&#9656;</span>';
    html += '  </div>';
    html += '  <div class="list-item-body">';

    if (itens.length === 0) {
      html += '<p>Nenhum exercício adicionado a esta rotina ainda.</p>';
    } else {
      html += '<ul class="routine-exlist">';
      itens.forEach(function (item) {
        var ex = exerciciosPorId[item.exercicio_id];
        var nome = ex ? ex.nome : '(exercício removido)';
        var temCarga = item.carga_padrao !== null && item.carga_padrao !== undefined;
        var sets = item.series_padrao + '&times;' + item.reps_padrao +
          (temCarga ? ' @ ' + formatarNumero(item.carga_padrao) + 'kg' : '');
        html += '<li><span>' + escapeHtml(nome) + '</span><span class="ex-sets">' + sets + '</span></li>';
      });
      html += '</ul>';
    }

    html += '    <div class="list-item-actions">';
    html += '      <button type="button" class="btn" data-editar="' + escapeHtml(rotina.id) + '">Editar</button>';
    html += '      <button type="button" class="btn" data-clonar="' + escapeHtml(rotina.id) + '">Clonar</button>';
    html += '      <button type="button" class="btn danger" data-excluir="' + escapeHtml(rotina.id) + '">Excluir</button>';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';
    return html;
  }

  function encontrarPorId(id) {
    for (var i = 0; i < rotinas.length; i++) {
      if (rotinas[i].id === id) {
        return rotinas[i];
      }
    }
    return null;
  }

  function abrirModal(rotina) {
    formErro.textContent = '';
    form.reset();
    linhasEl.innerHTML = '';

    if (rotina) {
      modalTitulo.textContent = 'Editar rotina';
      idInput.value = rotina.id;
      nomeInput.value = rotina.nome || '';
      (rotina.exercicios || []).forEach(function (item) {
        adicionarLinha(item);
      });
    } else {
      modalTitulo.textContent = 'Nova rotina';
      idInput.value = '';
      if (exercicios.length > 0) {
        adicionarLinha();
      }
    }

    btnAddLinha.disabled = exercicios.length === 0;
    btnAddLinha.textContent = exercicios.length === 0
      ? 'Cadastre exercícios na biblioteca primeiro'
      : '+ Adicionar exercício';

    modal.classList.add('open');
    nomeInput.focus();
  }

  function fecharModal() {
    modal.classList.remove('open');
  }

  function adicionarLinha(item) {
    var div = document.createElement('div');
    div.className = 'exercicio-linha';

    var options = '<option value="">Selecione o exercício...</option>';
    exercicios.forEach(function (ex) {
      var selected = item && item.exercicio_id === ex.id ? ' selected' : '';
      options += '<option value="' + escapeHtml(ex.id) + '"' + selected + '>' + escapeHtml(ex.nome) + '</option>';
    });

    var carga = item && item.carga_padrao !== null && item.carga_padrao !== undefined ? item.carga_padrao : '';

    div.innerHTML =
      '<select class="linha-exercicio">' + options + '</select>' +
      '<button type="button" class="remove-linha" aria-label="Remover">&times;</button>' +
      '<div class="linha-campos-labels">' +
      '  <span>Séries</span><span>Reps</span><span>Carga (kg)</span>' +
      '</div>' +
      '<div class="linha-campos">' +
      '  <input type="number" class="linha-series mono" min="1" placeholder="séries" value="' + (item ? item.series_padrao : 3) + '">' +
      '  <input type="number" class="linha-reps mono" min="1" placeholder="reps" value="' + (item ? item.reps_padrao : 10) + '">' +
      '  <input type="number" class="linha-carga mono" min="0" step="0.5" placeholder="kg" value="' + carga + '">' +
      '</div>';

    div.querySelector('.remove-linha').addEventListener('click', function () {
      div.remove();
    });

    linhasEl.appendChild(div);
  }

  function onSubmit(e) {
    e.preventDefault();
    formErro.textContent = '';

    var linhas = linhasEl.querySelectorAll('.exercicio-linha');
    var exerciciosPayload = [];

    for (var i = 0; i < linhas.length; i++) {
      var linha = linhas[i];
      var exercicioId = linha.querySelector('.linha-exercicio').value;
      if (!exercicioId) {
        formErro.textContent = 'Selecione um exercício em todas as linhas (ou remova a linha vazia).';
        return;
      }
      var cargaValor = linha.querySelector('.linha-carga').value;

      exerciciosPayload.push({
        exercicio_id: exercicioId,
        series_padrao: parseInt(linha.querySelector('.linha-series').value, 10) || 3,
        reps_padrao: parseInt(linha.querySelector('.linha-reps').value, 10) || 10,
        carga_padrao: cargaValor === '' ? null : parseFloat(cargaValor),
      });
    }

    var payload = {
      nome: nomeInput.value.trim(),
      exercicios: exerciciosPayload,
    };

    var id = idInput.value;
    var url = id ? (API_ROTINAS + '?id=' + encodeURIComponent(id)) : API_ROTINAS;
    var method = id ? 'PATCH' : 'POST';

    fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok) {
          formErro.textContent = result.data.error || 'Erro ao salvar rotina.';
          return;
        }
        fecharModal();
        carregar();
      })
      .catch(function () {
        formErro.textContent = 'Erro de conexão ao salvar rotina.';
      });
  }

  function clonar(id) {
    var rotina = encontrarPorId(id);
    var sugestao = rotina ? (rotina.nome + ' (cópia)') : '';
    var novoNome = prompt('Nome da rotina clonada:', sugestao);

    if (novoNome === null) {
      return;
    }

    fetch(API_ROTINAS + '?clone=' + encodeURIComponent(id), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nome: novoNome.trim() }),
    })
      .then(function (res) { return res.json(); })
      .then(function () {
        carregar();
      })
      .catch(function () {
        alert('Erro ao clonar rotina.');
      });
  }

  function excluir(id) {
    var rotina = encontrarPorId(id);
    var nome = rotina ? rotina.nome : 'esta rotina';
    if (!confirm('Excluir "' + nome + '"? Essa ação não pode ser desfeita.')) {
      return;
    }

    fetch(API_ROTINAS + '?id=' + encodeURIComponent(id), { method: 'DELETE' })
      .then(function (res) { return res.json(); })
      .then(function () {
        carregar();
      })
      .catch(function () {
        alert('Erro ao excluir rotina.');
      });
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
