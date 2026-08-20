/**
 * icons.js — mesmos ícones de linha de includes/icons.php, em formato
 * pronto pra interpolar em innerHTML (usado só nos poucos trechos que são
 * montados via JavaScript: PR na lista de progresso, nota registrada no
 * treino ativo, mensagem de sucesso do backup). Carregado uma vez em
 * nav.php, disponível global como `ICONS.<nome>` em qualquer outro script
 * da página.
 */
(function () {
  'use strict';

  var ATTRS = 'class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';

  function svg(inner) {
    return '<svg ' + ATTRS + '>' + inner + '</svg>';
  }

  window.ICONS = {
    trophy: svg(
      '<path d="M8 4.5h8v4a4 4 0 0 1-8 0v-4z"/>' +
      '<path d="M8 5.5H5.2A2.8 2.8 0 0 0 8 9.5"/>' +
      '<path d="M16 5.5h2.8A2.8 2.8 0 0 1 16 9.5"/>' +
      '<path d="M12 12.5v3"/>' +
      '<path d="M8.5 19.5h7"/>' +
      '<path d="M10 19.5v-2a2 2 0 0 1 4 0v2"/>'
    ),
    pencil: svg(
      '<path d="M4.5 19.5l.8-3.4L15.8 5.6l2.6 2.6L7.9 18.7l-3.4.8z"/>' +
      '<path d="M14 7.4l2.6 2.6"/>'
    ),
    check: svg('<polyline points="4.5,13 9,17.5 19.5,6"/>'),
  };
})();
