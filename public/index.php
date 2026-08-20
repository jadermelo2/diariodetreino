<?php
$active = 'inicio';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">
    <div class="home-hero">
      <h1 class="mono">DIÁRIO//TREINO</h1>
      <p>Monitor de treino de musculação — foco em evolução e progresso.</p>
    </div>

    <div class="home-stats" id="home-stats">
      <div class="surface streak-card">
        <span class="streak-emoji">🔥</span>
        <span class="streak-valor mono" id="home-streak">—</span>
        <span class="streak-label">sequência</span>
      </div>
      <div class="surface streak-card">
        <span class="streak-emoji">📚</span>
        <span class="streak-valor mono" id="home-exercicios">—</span>
        <span class="streak-label">exercícios</span>
      </div>
      <div class="surface streak-card">
        <span class="streak-emoji">📅</span>
        <span class="streak-valor mono" id="home-dias">—</span>
        <span class="streak-label">dias treinados</span>
      </div>
    </div>

    <a href="treino.php" class="btn primary full-width home-cta">▶ Iniciar treino</a>

    <div class="home-grid">
      <a class="home-card" href="progresso.php">
        <span class="home-card-emoji">📈</span>
        <span class="home-card-label">Progresso</span>
      </a>
      <a class="home-card" href="peso.php">
        <span class="home-card-emoji">⚖️</span>
        <span class="home-card-label">Peso</span>
      </a>
      <a class="home-card" href="calendario.php">
        <span class="home-card-emoji">🗓️</span>
        <span class="home-card-label">Calendário</span>
      </a>
      <a class="home-card" href="exercicios.php">
        <span class="home-card-emoji">🏋️</span>
        <span class="home-card-label">Exercícios</span>
      </a>
      <a class="home-card" href="rotinas.php">
        <span class="home-card-emoji">📋</span>
        <span class="home-card-label">Rotinas</span>
      </a>
      <a class="home-card" href="backup.php">
        <span class="home-card-emoji">💾</span>
        <span class="home-card-label">Backup</span>
      </a>
    </div>
  </div>

  <script src="assets/js/inicio.js"></script>
</body>
</html>
