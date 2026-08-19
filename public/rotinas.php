<?php
$active = 'rotinas';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Rotinas — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <div class="page-header">
      <h1>Rotinas</h1>
      <button type="button" class="primary" id="btn-nova-rotina">+ Nova</button>
    </div>

    <div id="lista-rotinas">
      <div class="empty-state">Carregando...</div>
    </div>
  </div>

  <!-- Modal de criar/editar rotina -->
  <div class="modal-overlay" id="modal-rotina">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modal-titulo">Nova rotina</h3>
        <button type="button" class="modal-close" id="modal-fechar" aria-label="Fechar">&times;</button>
      </div>

      <form id="form-rotina">
        <input type="hidden" id="rotina-id" value="">

        <div class="field">
          <label for="rotina-nome">Nome</label>
          <input type="text" id="rotina-nome" name="nome" placeholder="Ex: Treino A — Peito/Tríceps" required>
        </div>

        <div class="field">
          <label>Exercícios</label>
          <div id="rotina-exercicios-linhas"></div>
          <button type="button" class="btn" id="btn-add-linha" style="width: 100%; margin-top: var(--space-2);">+ Adicionar exercício</button>
        </div>

        <p class="form-error" id="form-erro"></p>

        <div class="modal-actions">
          <button type="button" class="btn" id="modal-cancelar">Cancelar</button>
          <button type="submit" class="primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/rotinas.js"></script>
</body>
</html>
