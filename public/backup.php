<?php
$active = 'backup';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Backup — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <h1>Backup &amp; exportação</h1>

    <div class="surface chart-card">
      <h3>Exportar CSV</h3>
      <p>Baixa todo o histórico de séries registradas (data, exercício, série, reps, carga e nota) num arquivo CSV, pra abrir numa planilha.</p>
      <a href="api/export.php" class="btn primary full-width">Exportar CSV</a>
    </div>

    <div class="surface chart-card">
      <h3>Backup</h3>
      <p>Compacta toda a pasta de dados (exercícios, rotinas, sessões e peso corporal) num arquivo .zip com data e hora, salvo em <code class="mono">/backups</code>.</p>
      <button type="button" class="btn primary full-width" id="btn-criar-backup">Criar backup agora</button>
      <p class="form-error" id="backup-erro"></p>

      <div id="backups-lista" style="margin-top: var(--space-4);">
        <div class="empty-state">Carregando...</div>
      </div>
    </div>

    <div class="surface chart-card">
      <h3>Restaurar backup</h3>
      <p class="backup-warning">
        <?= svg_icon('alert-triangle') ?> Restaurar <strong>substitui todos os dados atuais</strong> pelos dados do arquivo enviado.
        Um backup automático do estado atual é criado antes de restaurar, mas confira o arquivo antes de continuar.
      </p>

      <div class="field">
        <label for="input-restaurar">Arquivo de backup (.zip)</label>
        <input type="file" id="input-restaurar" accept=".zip">
      </div>

      <p class="form-error" id="restaurar-erro"></p>
      <p class="form-error" id="restaurar-sucesso" style="display: none; color: var(--ok);"></p>

      <button type="button" class="btn danger full-width" id="btn-restaurar">Restaurar backup</button>
    </div>
  </div>

  <script src="assets/js/backup.js"></script>
</body>
</html>
