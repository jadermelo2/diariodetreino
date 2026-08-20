<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/backup.php — backup e restauração da pasta /data inteira.
 *
 * GET  ?action=listar             -> lista os backups salvos em /backups
 * GET  ?action=baixar&arquivo=xxx -> baixa um backup específico (.zip)
 * POST ?action=criar              -> compacta /data inteira num novo .zip
 * POST ?action=restaurar          -> restaura a partir de um .zip enviado
 *      (multipart/form-data, campo "backup"). Antes de sobrescrever nada,
 *      cria automaticamente um backup do estado atual (rede de segurança).
 */

require_method(['GET', 'POST']);

$backupsDir = dirname(__DIR__, 2) . '/backups';
$dataDir = rtrim(data_path(''), '/'); // data_path('') termina com "/", removemos pra não bagunçar os cálculos de caminho relativo abaixo
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($method === 'POST' ? '' : 'listar');

function garantir_pasta_backups(string $backupsDir): void
{
    if (!is_dir($backupsDir)) {
        mkdir($backupsDir, 0775, true);
    }
}

/**
 * Adiciona recursivamente todo o conteúdo de $pastaReal ao zip, sob o
 * prefixo $prefixoZip (ex: "data/exercises.json", "data/sessions/xxx.json").
 */
function adicionar_pasta_ao_zip(ZipArchive $zip, string $pastaReal, string $prefixoZip): void
{
    if (!is_dir($pastaReal)) {
        return;
    }

    $itens = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pastaReal, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($itens as $item) {
        $caminhoReal = $item->getPathname();
        $caminhoRelativo = $prefixoZip . '/' . substr($caminhoReal, strlen($pastaReal) + 1);
        $caminhoRelativo = str_replace('\\', '/', $caminhoRelativo);
        $zip->addFile($caminhoReal, $caminhoRelativo);
    }
}

function criar_backup(string $backupsDir, string $dataDir, string $prefixo = 'backup'): array
{
    if (!class_exists('ZipArchive')) {
        json_error('A extensão ZipArchive não está disponível neste servidor.', 500);
    }

    garantir_pasta_backups($backupsDir);

    $filename = $prefixo . '-' . date('Y-m-d_His') . '.zip';
    $path = $backupsDir . '/' . $filename;

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        json_error('Falha ao criar o arquivo de backup.', 500);
    }

    adicionar_pasta_ao_zip($zip, $dataDir, 'data');
    $zip->close();

    return [
        'arquivo' => $filename,
        'tamanho' => filesize($path),
        'criado_em' => date('c', filemtime($path)),
    ];
}

function listar_backups(string $backupsDir): array
{
    if (!is_dir($backupsDir)) {
        return [];
    }

    $files = glob($backupsDir . '/*.zip') ?: [];

    $backups = array_map(function ($file) {
        return [
            'arquivo' => basename($file),
            'tamanho' => filesize($file),
            'criado_em' => date('c', filemtime($file)),
        ];
    }, $files);

    usort($backups, fn ($a, $b) => strcmp($b['criado_em'], $a['criado_em']));

    return $backups;
}

function remover_pasta_recursiva(string $dir, bool $manterRaiz = false): void
{
    if (!is_dir($dir)) {
        return;
    }

    $itens = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($itens as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    if (!$manterRaiz) {
        rmdir($dir);
    }
}

function copiar_pasta_recursiva(string $origem, string $destino): void
{
    if (!is_dir($destino)) {
        mkdir($destino, 0775, true);
    }

    $itens = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($origem, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($itens as $item) {
        $relativo = substr($item->getPathname(), strlen($origem) + 1);
        $alvo = $destino . '/' . $relativo;

        if ($item->isDir()) {
            if (!is_dir($alvo)) {
                mkdir($alvo, 0775, true);
            }
        } else {
            copy($item->getPathname(), $alvo);
        }
    }
}

// ---- roteamento por método + action ----

if ($method === 'GET' && $action === 'listar') {
    json_response(listar_backups($backupsDir));
}

if ($method === 'GET' && $action === 'baixar') {
    $arquivo = $_GET['arquivo'] ?? '';

    // só permite baixar arquivos que já estão em /backups, sem "../" nem
    // caminhos absolutos — valida contra o nome exato gerado por nós.
    if (!is_string($arquivo) || $arquivo === '' || basename($arquivo) !== $arquivo || !preg_match('/^[\w.-]+\.zip$/', $arquivo)) {
        json_error('Nome de arquivo inválido.', 422);
    }

    $path = $backupsDir . '/' . $arquivo;
    if (!is_file($path)) {
        json_error('Backup não encontrado.', 404);
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $arquivo . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

if ($method === 'POST' && $action === 'criar') {
    json_response(criar_backup($backupsDir, $dataDir), 201);
}

if ($method === 'POST' && $action === 'restaurar') {
    if (!class_exists('ZipArchive')) {
        json_error('A extensão ZipArchive não está disponível neste servidor.', 500);
    }

    if (!isset($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
        json_error('Envie um arquivo .zip de backup válido.', 422);
    }

    $tmpUpload = $_FILES['backup']['tmp_name'];
    $nomeOriginal = $_FILES['backup']['name'];

    if (strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) !== 'zip') {
        json_error('O arquivo precisa ser um .zip.', 422);
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpUpload) !== true) {
        json_error('Não foi possível abrir o arquivo enviado — ele parece corrompido ou não é um .zip válido.', 422);
    }

    if ($zip->locateName('data/exercises.json') === false) {
        $zip->close();
        json_error('Esse arquivo não parece ser um backup do Diário de Treino (faltando data/exercises.json).', 422);
    }

    // rede de segurança: guarda o estado atual antes de sobrescrever
    criar_backup($backupsDir, $dataDir, 'pre-restore');

    $tmpDir = sys_get_temp_dir() . '/diario_restore_' . uniqid();
    mkdir($tmpDir, 0775, true);
    $zip->extractTo($tmpDir);
    $zip->close();

    $novoDataDir = $tmpDir . '/data';
    if (!is_dir($novoDataDir)) {
        remover_pasta_recursiva($tmpDir);
        json_error('Backup inválido: pasta "data" não encontrada dentro do .zip.', 422);
    }

    remover_pasta_recursiva($dataDir, true);
    copiar_pasta_recursiva($novoDataDir, $dataDir);
    remover_pasta_recursiva($tmpDir);

    json_response(['success' => true, 'mensagem' => 'Dados restaurados com sucesso.']);
}

json_error('Ação inválida.', 422);
