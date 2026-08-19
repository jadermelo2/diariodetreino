<?php

declare(strict_types=1);

/**
 * api_helpers.php
 *
 * Helpers pequenos e reaproveitáveis para os endpoints de /src/api.
 * Sem framework, sem router — cada endpoint é um arquivo PHP simples que
 * inspeciona $_SERVER['REQUEST_METHOD'] e chama estas funções.
 */

/**
 * Envia uma resposta JSON e encerra a execução.
 *
 * @param mixed $data   Dado a ser serializado (array, objeto, etc)
 * @param int   $status Código de status HTTP
 */
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Envia uma resposta de erro no formato { "error": "mensagem" }.
 */
function json_error(string $message, int $status = 400): void
{
    json_response(['error' => $message], $status);
}

/**
 * Lê e decodifica o corpo da requisição (JSON) como array.
 * Retorna array vazio se o corpo estiver vazio ou for inválido.
 */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

/**
 * Garante que o método HTTP da requisição está entre os permitidos.
 * Caso contrário, responde 405 com o header Allow e encerra.
 *
 * @param string[] $allowed Ex: ['GET', 'POST']
 */
function require_method(array $allowed): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_error("Método {$method} não permitido.", 405);
    }
}

/**
 * Lê o parâmetro "id" da query string (?id=...). Retorna null se ausente.
 */
function query_id(): ?string
{
    $id = $_GET['id'] ?? null;
    return is_string($id) && $id !== '' ? $id : null;
}
