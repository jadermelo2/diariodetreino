<?php
$active = 'exercicios';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Exercícios — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <div class="page-header">
      <h1>Exercícios</h1>
      <button type="button" class="primary" id="btn-novo-exercicio">+ Novo</button>
    </div>

    <div id="lista-exercicios">
      <div class="empty-state">Carregando...</div>
    </div>
  </div>

  <!-- Modal de criar/editar exercício -->
  <div class="modal-overlay" id="modal-exercicio">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modal-titulo">Novo exercício</h3>
        <button type="button" class="modal-close" id="modal-fechar" aria-label="Fechar">&times;</button>
      </div>

      <form id="form-exercicio">
        <input type="hidden" id="exercicio-id" value="">

        <div class="field">
          <label for="exercicio-nome">Nome</label>
          <input type="text" id="exercicio-nome" name="nome" placeholder="Ex: Supino reto" required>
        </div>

        <div class="field">
          <label for="exercicio-grupo">Grupo muscular</label>
          <select id="exercicio-grupo" name="grupo_muscular" required>
            <option value="">Selecione...</option>
            <option>Peito</option>
            <option>Costas</option>
            <option>Ombro</option>
            <option>Bíceps</option>
            <option>Tríceps</option>
            <option>Pernas</option>
            <option>Glúteos</option>
            <option>Abdômen</option>
            <option>Panturrilha</option>
            <option>Cardio</option>
            <option>Outro</option>
          </select>
        </div>

        <div class="field">
          <label for="exercicio-video">Vídeo do YouTube (opcional)</label>
          <input type="url" id="exercicio-video" name="video_url" placeholder="https://youtube.com/watch?v=...">
        </div>

        <p class="form-error" id="form-erro"></p>

        <div class="modal-actions">
          <button type="button" class="btn" id="modal-cancelar">Cancelar</button>
          <button type="submit" class="primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/exercicios.js"></script>
</body>
</html>
