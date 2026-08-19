<?php
$active = 'treino';
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Treino ativo — Diário de Treino</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="container">

    <!-- ===================== TELA: escolher rotina / livre ===================== -->
    <div id="view-setup">
      <h1>Treino ativo</h1>
      <p>Escolha uma rotina para seguir, ou inicie um treino livre.</p>

      <div class="setup-block">
        <h3>Rotinas salvas</h3>
        <div id="setup-rotinas">
          <div class="empty-state">Carregando...</div>
        </div>
      </div>

      <div class="setup-block">
        <button type="button" class="btn" id="btn-livre" style="width: 100%;">Treino livre (sem rotina)</button>
      </div>
    </div>

    <!-- ===================== TELA: exercício ativo ===================== -->
    <div id="view-ativo" style="display: none;">
      <div class="workout-header">
        <span class="badge accent mono" id="ativo-progresso">1/1</span>
        <button type="button" class="btn" id="btn-trocar-exercicio">Trocar exercício</button>
      </div>

      <div class="exercise-avisofim" id="ativo-fim-aviso">
        🏁 Fim da rotina — toque em "Finalizar treino" quando terminar, ou continue registrando séries extras.
      </div>

      <h2 class="exercise-name" id="ativo-exercicio-nome">—</h2>

      <p class="ultima-vez mono" id="ultima-vez" style="display: none;"></p>

      <div class="surface sets-log">
        <div class="sets-log-title">Séries registradas nesta sessão</div>
        <ul class="sets-log-list" id="ativo-series-lista">
          <li class="mono" style="color: var(--text-muted);">Nenhuma série ainda.</li>
        </ul>
      </div>

      <div class="usar-padrao-prompt" id="usar-padrao-prompt" style="display: none;">
        <span id="usar-padrao-texto"></span>
        <div class="usar-padrao-actions">
          <button type="button" class="btn primary" id="btn-usar-padrao">Usar como padrão</button>
          <button type="button" class="btn" id="btn-usar-padrao-dispensar">Dispensar</button>
        </div>
      </div>

      <form id="form-serie">
        <div class="input-row">
          <div class="field">
            <label for="input-reps">Reps</label>
            <input type="number" id="input-reps" class="mono input-big" inputmode="numeric" min="1" required>
          </div>
          <div class="field">
            <label for="input-carga">Carga (kg)</label>
            <input type="number" id="input-carga" class="mono input-big" inputmode="decimal" min="0" step="0.5" required>
          </div>
        </div>

        <div class="nota-toggle-row">
          <button type="button" class="nota-toggle" id="btn-nota-toggle">📝 Nota (opcional)</button>
        </div>
        <div class="field" id="nota-field" style="display: none;">
          <input type="text" id="input-nota" maxlength="140" placeholder="Ex: senti dor no ombro, pegada mais aberta...">
        </div>

        <p class="form-error" id="serie-erro"></p>
        <button type="submit" class="primary btn-register">Registrar série</button>
      </form>

      <div class="rest-timer" id="rest-timer">
        <div class="rest-timer-label" id="rest-label">Descanso</div>
        <div class="rest-timer-count mono" id="rest-count">90</div>
        <div class="rest-timer-actions">
          <button type="button" class="btn" id="rest-menos">-15s</button>
          <button type="button" class="btn" id="rest-mais">+15s</button>
          <button type="button" class="btn" id="rest-pular">Pular descanso</button>
        </div>
      </div>

      <div class="setup-block">
        <label for="select-descanso">Descanso padrão</label>
        <select id="select-descanso">
          <option value="30">30s</option>
          <option value="45">45s</option>
          <option value="60">60s</option>
          <option value="90" selected>90s</option>
          <option value="120">120s</option>
          <option value="180">180s</option>
        </select>
      </div>

      <div class="workout-actions">
        <button type="button" class="btn" id="btn-proximo-exercicio">Próximo exercício / Pular</button>
        <button type="button" class="btn danger" id="btn-finalizar">Finalizar treino</button>
      </div>
    </div>

    <!-- ===================== TELA: resumo final ===================== -->
    <div id="view-fim" style="display: none;">
      <h1>Treino finalizado 💪</h1>
      <div class="surface finish-summary">
        <p id="fim-resumo" class="mono" style="color: var(--text);"></p>
      </div>
      <a href="treino.php" class="btn primary" style="width: 100%;">Iniciar outro treino</a>
    </div>

  </div>

  <!-- Modal: escolher/trocar exercício -->
  <div class="modal-overlay" id="modal-picker">
    <div class="modal">
      <div class="modal-header">
        <h3>Escolher exercício</h3>
        <button type="button" class="modal-close" id="picker-fechar" aria-label="Fechar">&times;</button>
      </div>
      <div class="picker-list" id="picker-lista"></div>
    </div>
  </div>

  <script src="assets/js/treino.js"></script>
</body>
</html>
