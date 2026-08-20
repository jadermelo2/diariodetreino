<?php
$active = 'peso';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Peso corporal — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <div class="page-header">
      <h1>Peso corporal</h1>
    </div>

    <form id="form-peso" class="surface peso-form">
      <input type="hidden" id="peso-id" value="">

      <div class="input-row">
        <div class="field">
          <label for="peso-data">Data</label>
          <input type="date" id="peso-data" required>
        </div>
        <div class="field">
          <label for="peso-valor">Peso (kg)</label>
          <input type="number" id="peso-valor" class="mono" inputmode="decimal" min="0" step="0.1" required>
        </div>
      </div>

      <div class="nota-toggle-row">
        <button type="button" class="nota-toggle" id="btn-medidas-toggle">📏 Medidas corporais (opcional)</button>
      </div>

      <div id="medidas-campos" style="display: none;">
        <div class="medidas-grid">
          <div class="field">
            <label for="medida-cintura">Cintura (cm)</label>
            <input type="number" id="medida-cintura" class="mono" inputmode="decimal" min="0" step="0.1">
          </div>
          <div class="field">
            <label for="medida-braco">Braço (cm)</label>
            <input type="number" id="medida-braco" class="mono" inputmode="decimal" min="0" step="0.1">
          </div>
          <div class="field">
            <label for="medida-peito">Peito (cm)</label>
            <input type="number" id="medida-peito" class="mono" inputmode="decimal" min="0" step="0.1">
          </div>
          <div class="field">
            <label for="medida-coxa">Coxa (cm)</label>
            <input type="number" id="medida-coxa" class="mono" inputmode="decimal" min="0" step="0.1">
          </div>
          <div class="field">
            <label for="medida-quadril">Quadril (cm)</label>
            <input type="number" id="medida-quadril" class="mono" inputmode="decimal" min="0" step="0.1">
          </div>
        </div>
      </div>

      <p class="form-error" id="peso-erro"></p>

      <div class="modal-actions" id="peso-form-acoes">
        <button type="submit" class="primary full-width">Registrar</button>
      </div>
    </form>

    <div class="surface chart-card" id="peso-chart-card" style="display: none;">
      <h3>Evolução do peso</h3>
      <div class="chart-canvas-wrap">
        <canvas id="chart-peso"></canvas>
      </div>
    </div>

    <div id="peso-lista">
      <div class="empty-state">Carregando...</div>
    </div>
  </div>

  <script src="assets/js/peso.js"></script>
</body>
</html>
