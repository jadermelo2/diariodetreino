<?php
/**
 * Gateway público para o endpoint de exercícios.
 *
 * Em hospedagem compartilhada, o document root normalmente aponta só
 * para /public — /src fica fora do alcance do servidor web (por design:
 * é onde vive a lógica "privada"). Este arquivo é só uma porta de
 * entrada HTTP fina; a implementação real do CRUD mora em
 * /src/api/exercises.php e é a mesma para qualquer forma de deploy.
 */
require __DIR__ . '/../../src/api/exercises.php';
