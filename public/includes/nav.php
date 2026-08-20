<?php
/**
 * nav.php — navegação entre as telas.
 *
 * Menu lateral fixo, com ícone sempre visível pra cada tela (nunca fica
 * escondido atrás de um "hambúrguer") e um botão de expandir que revela
 * o nome de cada tela + o nome do app. O estado expandido/recolhido é
 * lembrado em localStorage entre navegações (o app é multi-página, sem
 * SPA, então cada página aplica o estado salvo assim que carrega).
 *
 * Incluído logo no início de <body> em cada página. A variável $active
 * (string) deve ser definida antes do include para destacar a tela atual.
 */

require_once __DIR__ . '/icons.php';

$active = $active ?? '';

$links = [
    'inicio' => ['href' => 'index.php', 'label' => 'Início', 'icon' => 'home'],
    'treino' => ['href' => 'treino.php', 'label' => 'Treino', 'icon' => 'play'],
    'progresso' => ['href' => 'progresso.php', 'label' => 'Progresso', 'icon' => 'trending-up'],
    'peso' => ['href' => 'peso.php', 'label' => 'Peso', 'icon' => 'scale'],
    'calendario' => ['href' => 'calendario.php', 'label' => 'Calendário', 'icon' => 'calendar'],
    'exercicios' => ['href' => 'exercicios.php', 'label' => 'Exercícios', 'icon' => 'dumbbell'],
    'rotinas' => ['href' => 'rotinas.php', 'label' => 'Rotinas', 'icon' => 'clipboard'],
    'backup' => ['href' => 'backup.php', 'label' => 'Backup', 'icon' => 'save'],
];
?>
<script>
  // Aplica o estado salvo (expandido/recolhido) o quanto antes, pra não
  // piscar o menu no tamanho errado por uma fração de segundo.
  (function () {
    try {
      if (localStorage.getItem('diario_sidenav_expanded') === '1') {
        document.body.classList.add('sidenav-expanded');
      }
    } catch (e) {
      // localStorage indisponível (modo privado bloqueando, etc) — ignora
    }
  })();
</script>
<aside class="sidenav">
  <div class="sidenav-brand">
    <span class="sidenav-brand-icon mono">D∷T</span>
    <span class="sidenav-brand-full mono">DIÁRIO//TREINO</span>
  </div>

  <nav class="sidenav-links">
    <?php foreach ($links as $key => $link): ?>
      <a href="<?= htmlspecialchars($link['href']) ?>"
         class="sidenav-link <?= $active === $key ? 'active' : '' ?>"
         title="<?= htmlspecialchars($link['label']) ?>">
        <span class="sidenav-icon"><?= svg_icon($link['icon']) ?></span>
        <span class="sidenav-label"><?= htmlspecialchars($link['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <button type="button" class="sidenav-expand-btn" id="sidenav-expand-btn" aria-label="Expandir menu">
    <span class="sidenav-icon sidenav-expand-chevron"><?= svg_icon('chevron-right') ?></span>
    <span class="sidenav-label">Recolher</span>
  </button>
</aside>
<div class="sidenav-backdrop" id="sidenav-backdrop"></div>
<script src="assets/js/icons.js"></script>
<script>
  (function () {
    function definirExpandido(expandido) {
      document.body.classList.toggle('sidenav-expanded', expandido);
      try {
        localStorage.setItem('diario_sidenav_expanded', expandido ? '1' : '0');
      } catch (e) {
        // localStorage indisponível — o toggle ainda funciona nesta página,
        // só não é lembrado na próxima navegação
      }
      var btn = document.getElementById('sidenav-expand-btn');
      if (btn) {
        btn.setAttribute('aria-label', expandido ? 'Recolher menu' : 'Expandir menu');
      }
    }

    var btn = document.getElementById('sidenav-expand-btn');
    if (btn) {
      btn.addEventListener('click', function () {
        definirExpandido(!document.body.classList.contains('sidenav-expanded'));
      });
    }

    // o menu expandido flutua por cima do conteúdo (overlay) — tocar no
    // fundo escurecido recolhe de novo, como um "clique fora"
    var backdrop = document.getElementById('sidenav-backdrop');
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        definirExpandido(false);
      });
    }
  })();
</script>
