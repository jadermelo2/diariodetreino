<?php
$active = 'progresso';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Progresso — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <h1>Progresso</h1>

    <div class="setup-block">
      <label for="select-exercicio">Exercício</label>
      <select id="select-exercicio">
        <option value="">Selecione um exercício...</option>
      </select>
    </div>

    <div id="progresso-vazio" class="empty-state" style="display: none;"></div>

    <div id="progresso-conteudo" style="display: none;">

      <div class="record-badge">
        <span class="record-label"><?= svg_icon('trophy') ?> Recorde atual</span>
        <span class="record-valor mono" id="recorde-valor">—</span>
      </div>

      <div class="surface chart-card">
        <h3>Carga máxima por sessão</h3>
        <div class="chart-canvas-wrap">
          <canvas id="chart-carga"></canvas>
        </div>
      </div>

      <div class="surface chart-card">
        <h3>Volume por sessão</h3>

        <div class="volume-filtro-opcoes">
          <button type="button" class="btn active" data-modo-volume="exercicio">Este exercício</button>
          <button type="button" class="btn" data-modo-volume="rotina">Uma rotina</button>
          <button type="button" class="btn" data-modo-volume="total">Todos os treinos</button>
        </div>

        <div class="field" id="campo-rotina-volume" style="display: none;">
          <select id="select-rotina-volume"></select>
        </div>

        <div class="chart-canvas-wrap">
          <canvas id="chart-volume"></canvas>
        </div>
      </div>

      <div class="surface chart-card">
        <h3>Histórico de PRs (recordes pessoais)</h3>
        <ul class="pr-list" id="pr-lista">
          <li>Nenhum PR ainda.</li>
        </ul>
      </div>

    </div>
  </div>

  <script src="assets/js/progresso.js"></script>
</body>
</html>
