(function () {
  'use strict';

  var API = 'api/backup.php';

  var btnCriarBackup = document.getElementById('btn-criar-backup');
  var backupErroEl = document.getElementById('backup-erro');
  var listaEl = document.getElementById('backups-lista');

  var inputRestaurar = document.getElementById('input-restaurar');
  var btnRestaurar = document.getElementById('btn-restaurar');
  var restaurarErroEl = document.getElementById('restaurar-erro');
  var restaurarSucessoEl = document.getElementById('restaurar-sucesso');

  btnCriarBackup.addEventListener('click', criarBackup);
  btnRestaurar.addEventListener('click', restaurarBackup);

  carregarBackups();

  function carregarBackups() {
    fetch(API + '?action=listar')
      .then(function (res) { return res.json(); })
      .then(renderBackups)
      .catch(function () {
        listaEl.innerHTML = '<div class="empty-state">Erro ao carregar a lista de backups.</div>';
      });
  }

  function renderBackups(backups) {
    if (!Array.isArray(backups) || backups.length === 0) {
      listaEl.innerHTML = '<div class="empty-state">Nenhum backup criado ainda.</div>';
      return;
    }

    var html = '';
    backups.forEach(function (b) {
      html += '<div class="backup-list-item">';
      html += '  <div class="backup-info">';
      html += '    <span class="backup-nome">' + escapeHtml(b.arquivo) + '</span>';
      html += '    <span class="backup-meta">' + formatarData(b.criado_em) + ' · ' + formatarTamanho(b.tamanho) + '</span>';
      html += '  </div>';
      html += '  <a class="btn" href="' + API + '?action=baixar&arquivo=' + encodeURIComponent(b.arquivo) + '">Baixar</a>';
      html += '</div>';
    });
    listaEl.innerHTML = html;
  }

  function criarBackup() {
    backupErroEl.textContent = '';
    btnCriarBackup.disabled = true;
    btnCriarBackup.textContent = 'Criando backup...';

    fetch(API + '?action=criar', { method: 'POST' })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok) {
          backupErroEl.textContent = result.data.error || 'Erro ao criar backup.';
          return;
        }
        carregarBackups();
      })
      .catch(function () {
        backupErroEl.textContent = 'Erro de conexão ao criar backup.';
      })
      .finally(function () {
        btnCriarBackup.disabled = false;
        btnCriarBackup.textContent = 'Criar backup agora';
      });
  }

  function restaurarBackup() {
    restaurarErroEl.textContent = '';
    restaurarSucessoEl.style.display = 'none';

    var arquivo = inputRestaurar.files[0];
    if (!arquivo) {
      restaurarErroEl.textContent = 'Selecione um arquivo .zip de backup primeiro.';
      return;
    }

    if (!confirm('Isso vai SUBSTITUIR todos os dados atuais (exercícios, rotinas, treinos e peso) pelos dados de "' +
      arquivo.name + '". Um backup automático do estado atual será criado antes. Confirmar restauração?')) {
      return;
    }

    var formData = new FormData();
    formData.append('backup', arquivo);

    btnRestaurar.disabled = true;
    btnRestaurar.textContent = 'Restaurando...';

    fetch(API + '?action=restaurar', { method: 'POST', body: formData })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok) {
          restaurarErroEl.textContent = result.data.error || 'Erro ao restaurar backup.';
          return;
        }
        restaurarSucessoEl.textContent = '✔ ' + (result.data.mensagem || 'Dados restaurados com sucesso.') +
          ' Recarregue as outras telas para ver os dados atualizados.';
        restaurarSucessoEl.style.display = '';
        inputRestaurar.value = '';
        carregarBackups();
      })
      .catch(function () {
        restaurarErroEl.textContent = 'Erro de conexão ao restaurar backup.';
      })
      .finally(function () {
        btnRestaurar.disabled = false;
        btnRestaurar.textContent = 'Restaurar backup';
      });
  }

  function formatarTamanho(bytes) {
    if (bytes < 1024) {
      return bytes + ' B';
    }
    if (bytes < 1024 * 1024) {
      return (bytes / 1024).toFixed(1) + ' KB';
    }
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function formatarData(isoString) {
    var d = new Date(isoString);
    if (isNaN(d.getTime())) {
      return isoString;
    }
    return d.toLocaleString('pt-BR');
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
