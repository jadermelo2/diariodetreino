<?php
/**
 * nav.php — navegação simples entre as telas.
 *
 * Incluído no topo de cada página em /public. A variável $active (string)
 * deve ser definida antes do include para destacar o item atual.
 * Novas telas (rotinas, treino ativo, progresso, etc) vão sendo
 * adicionadas aqui conforme as próximas etapas.
 */

$active = $active ?? '';

$links = [
    'inicio' => ['href' => 'index.php', 'label' => 'Início'],
    'treino' => ['href' => 'treino.php', 'label' => 'Treino'],
    'progresso' => ['href' => 'progresso.php', 'label' => 'Progresso'],
    'peso' => ['href' => 'peso.php', 'label' => 'Peso'],
    'calendario' => ['href' => 'calendario.php', 'label' => 'Calendário'],
    'exercicios' => ['href' => 'exercicios.php', 'label' => 'Exercícios'],
    'rotinas' => ['href' => 'rotinas.php', 'label' => 'Rotinas'],
    'backup' => ['href' => 'backup.php', 'label' => 'Backup'],
];
?>
<nav class="topnav">
  <div class="topnav-inner">
    <span class="topnav-brand mono">DIÁRIO//TREINO</span>
    <div class="topnav-links">
      <?php foreach ($links as $key => $link): ?>
        <a href="<?= htmlspecialchars($link['href']) ?>"
           class="topnav-link <?= $active === $key ? 'active' : '' ?>">
          <?= htmlspecialchars($link['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>
