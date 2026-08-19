<?php

declare(strict_types=1);

require __DIR__ . '/../src/lib/json_store.php';

/**
 * index.php — Etapa 1: verificação da estrutura base.
 *
 * Lista os arquivos JSON da camada de dados e seu conteúdo bruto, só para
 * confirmar que a estrutura de pastas e o json_store.php estão
 * funcionando antes de construir as telas reais (biblioteca de
 * exercícios, rotinas, etc — próximas etapas).
 */

$files = [
    'exercises.json'  => data_path('exercises.json'),
    'routines.json'    => data_path('routines.json'),
    'bodyweight.json'  => data_path('bodyweight.json'),
];

$sessionsDir = data_path('sessions');
$sessionFiles = is_dir($sessionsDir)
    ? array_values(array_diff(scandir($sessionsDir) ?: [], ['.', '..', '.gitkeep']))
    : [];

// Round-trip simples de leitura/escrita para provar que o helper funciona
// de ponta a ponta (não altera dado nenhum: lê e regrava o mesmo array).
$selfTestOk = true;
$selfTestError = '';
try {
    $exercises = read_json($files['exercises.json']);
    $selfTestOk = write_json($files['exercises.json'], $exercises);
} catch (\Throwable $e) {
    $selfTestOk = false;
    $selfTestError = $e->getMessage();
}

$sampleId = generate_id();

?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Diário de Treino — Setup</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <div class="container">
    <h1>Diário de Treino</h1>
    <p>Etapa 1 — estrutura base e camada de dados.</p>

    <div class="surface" style="padding: var(--space-4); margin-bottom: var(--space-4);">
      <h3>Self-test do json_store.php</h3>
      <p>
        Status:
        <span class="badge <?= $selfTestOk ? 'accent' : '' ?>">
          <?= $selfTestOk ? 'OK' : 'FALHOU' ?>
        </span>
      </p>
      <?php if ($selfTestError !== ''): ?>
        <p class="mono" style="color: var(--danger);"><?= htmlspecialchars($selfTestError) ?></p>
      <?php endif; ?>
      <p>ID de exemplo gerado por <code class="mono">generate_id()</code>:
        <span class="mono"><?= htmlspecialchars($sampleId) ?></span>
      </p>
    </div>

    <?php foreach ($files as $name => $path): ?>
      <?php $data = read_json($path); ?>
      <div class="surface" style="padding: var(--space-4); margin-bottom: var(--space-4);">
        <h3 class="mono"><?= htmlspecialchars($name) ?></h3>
        <p><?= count($data) ?> registro(s)</p>
        <pre class="mono" style="white-space: pre-wrap; color: var(--text-muted); font-size: 0.85rem; margin: 0;"><?= htmlspecialchars((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
      </div>
    <?php endforeach; ?>

    <div class="surface" style="padding: var(--space-4);">
      <h3 class="mono">data/sessions/</h3>
      <p><?= count($sessionFiles) ?> arquivo(s) de sessão</p>
      <?php if (empty($sessionFiles)): ?>
        <div class="empty-state">Nenhuma sessão registrada ainda.</div>
      <?php else: ?>
        <ul class="mono">
          <?php foreach ($sessionFiles as $f): ?>
            <li><?= htmlspecialchars($f) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
