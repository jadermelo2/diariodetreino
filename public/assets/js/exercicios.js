(function () {
  'use strict';

  var API = 'api/exercises.php';

  var listEl = document.getElementById('lista-exercicios');
  var modal = document.getElementById('modal-exercicio');
  var form = document.getElementById('form-exercicio');
  var modalTitulo = document.getElementById('modal-titulo');
  var formErro = document.getElementById('form-erro');

  var idInput = document.getElementById('exercicio-id');
  var nomeInput = document.getElementById('exercicio-nome');
  var grupoInput = document.getElementById('exercicio-grupo');
  var videoInput = document.getElementById('exercicio-video');

  var exercicios = [];

  document.getElementById('btn-novo-exercicio').addEventListener('click', function () {
    abrirModal();
  });

  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('modal-cancelar').addEventListener('click', fecharModal);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      fecharModal();
    }
  });

  form.addEventListener('submit', onSubmit);

  carregar();

  function carregar() {
    fetch(API)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        exercicios = Array.isArray(data) ? data : [];
        render();
      })
      .catch(function () {
        listEl.innerHTML = '<div class="empty-state">Erro ao carregar exercícios.</div>';
      });
  }

  function render() {
    if (exercicios.length === 0) {
      listEl.innerHTML = '<div class="empty-state">Nenhum exercício cadastrado ainda. Toque em "+ Novo" para começar.</div>';
      return;
    }

    var grupos = {};
    exercicios.forEach(function (ex) {
      var grupo = ex.grupo_muscular || 'Sem grupo';
      if (!grupos[grupo]) {
        grupos[grupo] = [];
      }
      grupos[grupo].push(ex);
    });

    var nomesGrupos = Object.keys(grupos).sort(function (a, b) {
      return a.localeCompare(b, 'pt-BR');
    });

    var html = '';
    nomesGrupos.forEach(function (grupo) {
      html += '<div class="group-block">';
      html += '<div class="group-title">' + escapeHtml(grupo) + ' &middot; ' + grupos[grupo].length + '</div>';
      html += '<div class="list-group">';
      grupos[grupo].forEach(function (ex) {
        html += renderItem(ex);
      });
      html += '</div></div>';
    });

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

    listEl.querySelectorAll('[data-excluir]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        excluir(btn.getAttribute('data-excluir'));
      });
    });
  }

  function renderItem(ex) {
    var embedUrl = youtubeEmbedUrl(ex.video_url);

    var html = '<div class="list-item" data-id="' + escapeHtml(ex.id) + '">';
    html += '  <div class="list-item-header">';
    html += '    <span class="list-item-name">' + escapeHtml(ex.nome) + '</span>';
    html += '    <span class="list-item-chevron">&#9656;</span>';
    html += '  </div>';
    html += '  <div class="list-item-body">';
    if (embedUrl) {
      html += '<div class="video-embed"><iframe src="' + escapeHtml(embedUrl) + '" allowfullscreen loading="lazy"></iframe></div>';
    }
    html += '    <div class="list-item-actions">';
    html += '      <button type="button" class="btn" data-editar="' + escapeHtml(ex.id) + '">Editar</button>';
    html += '      <button type="button" class="btn danger" data-excluir="' + escapeHtml(ex.id) + '">Excluir</button>';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';
    return html;
  }

  function encontrarPorId(id) {
    for (var i = 0; i < exercicios.length; i++) {
      if (exercicios[i].id === id) {
        return exercicios[i];
      }
    }
    return null;
  }

  function abrirModal(exercicio) {
    formErro.textContent = '';
    form.reset();

    if (exercicio) {
      modalTitulo.textContent = 'Editar exercício';
      idInput.value = exercicio.id;
      nomeInput.value = exercicio.nome || '';
      grupoInput.value = exercicio.grupo_muscular || '';
      videoInput.value = exercicio.video_url || '';
    } else {
      modalTitulo.textContent = 'Novo exercício';
      idInput.value = '';
    }

    modal.classList.add('open');
    nomeInput.focus();
  }

  function fecharModal() {
    modal.classList.remove('open');
  }

  function onSubmit(e) {
    e.preventDefault();
    formErro.textContent = '';

    var payload = {
      nome: nomeInput.value.trim(),
      grupo_muscular: grupoInput.value.trim(),
      video_url: videoInput.value.trim(),
    };

    var id = idInput.value;
    var url = id ? (API + '?id=' + encodeURIComponent(id)) : API;
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
          formErro.textContent = result.data.error || 'Erro ao salvar exercício.';
          return;
        }
        fecharModal();
        carregar();
      })
      .catch(function () {
        formErro.textContent = 'Erro de conexão ao salvar exercício.';
      });
  }

  function excluir(id) {
    var exercicio = encontrarPorId(id);
    var nome = exercicio ? exercicio.nome : 'este exercício';
    if (!confirm('Excluir "' + nome + '"? Essa ação não pode ser desfeita.')) {
      return;
    }

    fetch(API + '?id=' + encodeURIComponent(id), { method: 'DELETE' })
      .then(function (res) { return res.json(); })
      .then(function () {
        carregar();
      })
      .catch(function () {
        alert('Erro ao excluir exercício.');
      });
  }

  function youtubeEmbedUrl(url) {
    if (!url) {
      return null;
    }

    var id = null;
    var patterns = [
      /(?:youtube\.com\/watch\?v=)([\w-]{11})/,
      /(?:youtu\.be\/)([\w-]{11})/,
      /(?:youtube\.com\/embed\/)([\w-]{11})/,
      /(?:youtube\.com\/shorts\/)([\w-]{11})/,
    ];

    for (var i = 0; i < patterns.length; i++) {
      var match = url.match(patterns[i]);
      if (match) {
        id = match[1];
        break;
      }
    }

    return id ? ('https://www.youtube.com/embed/' + id) : null;
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
