<?php
/**
 * json_store.php
 *
 * Camada de persistência do Diário de Treino. Todo o "banco de dados" é
 * um conjunto de arquivos JSON dentro de /data. Este helper concentra a
 * leitura/escrita segura desses arquivos (com flock() para evitar
 * corrupção quando duas requisições escrevem ao mesmo tempo) e a geração
 * de IDs únicos para as entidades.
 *
 * Sem dependências externas (sem composer) — projetado para rodar em
 * hospedagem compartilhada simples.
 */

declare(strict_types=1);

/**
 * Lê e decodifica um arquivo JSON.
 *
 * - Se o arquivo não existir, retorna um array vazio (não é erro).
 * - Se o arquivo existir mas estiver vazio ou corrompido, também retorna
 *   array vazio (evita fatal error em runtime de produção).
 * - Usa LOCK_SH (shared lock) para não ler no meio de uma escrita.
 *
 * @param string $path Caminho absoluto (ou relativo) do arquivo .json
 * @return array
 */
function read_json(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $contents = '';
    try {
        if (flock($handle, LOCK_SH)) {
            $contents = stream_get_contents($handle) ?: '';
            flock($handle, LOCK_UN);
        }
    } finally {
        fclose($handle);
    }

    if (trim($contents) === '') {
        return [];
    }

    $data = json_decode($contents, true);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

/**
 * Escreve (sobrescreve) um arquivo JSON de forma segura.
 *
 * - Usa LOCK_EX (lock exclusivo) durante toda a escrita para evitar que
 *   duas requisições concorrentes corrompam o arquivo.
 * - Formata com JSON_PRETTY_PRINT para o arquivo ficar legível/versionável.
 * - Cria o diretório de destino se ele não existir.
 *
 * @param string $path Caminho absoluto (ou relativo) do arquivo .json
 * @param array  $data Dados a serem serializados
 * @return bool true em caso de sucesso
 */
function write_json(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
    }

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        return false;
    }

    // Abre em modo "c+" (cria se não existir, não trunca antes do lock)
    // para conseguirmos o lock antes de mexer no conteúdo do arquivo.
    $handle = fopen($path, 'c+b');
    if ($handle === false) {
        return false;
    }

    $ok = false;
    try {
        if (flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json);
            fflush($handle);
            flock($handle, LOCK_UN);
            $ok = true;
        }
    } finally {
        fclose($handle);
    }

    return $ok;
}

/**
 * Gera um ID único (UUID v4) para uso em entidades (exercício, rotina,
 * sessão, registro de peso, etc).
 *
 * Implementação simples baseada em random_bytes(), sem dependências.
 *
 * @return string UUID v4, ex: "b3f1c2a4-6e9d-4a1a-8f2e-3d4c5b6a7e8f"
 */
function generate_id(): string
{
    $data = random_bytes(16);

    // Ajusta os bits de versão (4) e variante (10) conforme RFC 4122.
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Helper de conveniência: monta o caminho absoluto para dentro de /data
 * a partir da raiz do projeto. Facilita trocar `data/` por
 * `data/{user_id}/` no futuro sem precisar mexer em todos os endpoints.
 *
 * @param string $relative Caminho relativo dentro de /data, ex: "exercises.json"
 * @return string
 */
function data_path(string $relative): string
{
    static $dataRoot = null;

    if ($dataRoot === null) {
        // src/lib/json_store.php -> src/lib -> src -> raiz do projeto
        $dataRoot = dirname(__DIR__, 2) . '/data';
    }

    return $dataRoot . '/' . ltrim($relative, '/');
}
