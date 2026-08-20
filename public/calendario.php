<?php
$active = 'calendario';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Calendário — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <h1>Calendário de frequência</h1>

    <div id="calendario-vazio" class="empty-state" style="display: none;"></div>

    <div id="calendario-conteudo" style="display: none;">
      <div class="streak-cards">
        <div class="surface streak-card">
          <span class="streak-emoji"><?= svg_icon('flame') ?></span>
          <span class="streak-valor mono" id="streak-atual">0</span>
          <span class="streak-label">Sequência atual</span>
        </div>
        <div class="surface streak-card">
          <span class="streak-emoji"><?= svg_icon('trophy') ?></span>
          <span class="streak-valor mono" id="streak-recorde">0</span>
          <span class="streak-label">Recorde de sequência</span>
        </div>
      </div>

      <div class="surface calendar-card">
        <h3 style="font-family: var(--font-mono); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: var(--space-3);">
          Últimas 52 semanas · <span id="calendario-total-dias">0</span> dia(s) treinado(s)
        </h3>

        <div class="calendar-scroll">
          <div class="calendar-inner">
            <div class="calendar-weekday-labels">
              <span></span>
              <span></span>
              <span>seg</span>
              <span></span>
              <span>qua</span>
              <span></span>
              <span>sex</span>
              <span></span>
            </div>
            <div class="calendar-grid" id="calendario-grid"></div>
          </div>
        </div>

        <div class="calendar-legend">
          <span>Menos</span>
          <span class="calendar-cell" style="background: var(--surface-alt);"></span>
          <span class="calendar-cell" id="legenda-1"></span>
          <span class="calendar-cell" id="legenda-2"></span>
          <span class="calendar-cell" id="legenda-3"></span>
          <span class="calendar-cell" id="legenda-4"></span>
          <span>Mais</span>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/calendario.js"></script>
</body>
</html>
