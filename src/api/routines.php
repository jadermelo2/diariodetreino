<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/routines.php — CRUD de rotinas de treino.
 *
 * GET    ?id=xxx       -> retorna uma rotina específica
 * GET                   -> lista todas as rotinas
 * POST                  -> cria uma rotina (body: nome, exercicios?)
 * POST   ?clone=xxx     -> duplica a rotina xxx (body: nome? — padrão "<nome> (cópia)")
 * PUT/PATCH ?id=xxx     -> edita uma rotina existente
 * DELETE    ?id=xxx     -> remove uma rotina
 *
 * Cada item de "exercicios" tem a forma:
 *   { "exercicio_id": "uuid", "series_padrao": 3, "reps_padrao": 10 }
 */

require_method(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);

$path = data_path('routines.json');
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Normaliza e valida a lista de exercícios de uma rotina.
 * Cada item precisa referenciar um exercicio_id existente na biblioteca;
 * series_padrao/reps_padrao são coeridos para inteiro positivo, com
 * padrão de 3 séries x 10 reps quando ausentes ou inválidos.
 */
function normalize_routine_exercises(array $input): array
{
    $exerciseIds = array_column(read_json(data_path('exercises.json')), 'id');
    $normalized = [];

    foreach ($input as $item) {
        if (!is_array($item) || empty($item['exercicio_id'])) {
            json_error('Cada item de "exercicios" precisa de "exercicio_id".', 422);
        }

        $exercicioId = (string) $item['exercicio_id'];

        if (!in_array($exercicioId, $exerciseIds, true)) {
            json_error("Exercício \"{$exercicioId}\" não existe na biblioteca.", 422);
        }

        $seriesPadrao = (int) ($item['series_padrao'] ?? 3);
        $repsPadrao = (int) ($item['reps_padrao'] ?? 10);

        // carga_padrao é opcional (nem todo exercício tem um peso planejado
        // ainda) — null quando ausente/vazio, número (inclusive 0, para
        // exercícios de peso corporal) quando informado.
        $cargaPadrao = null;
        if (isset($item['carga_padrao']) && $item['carga_padrao'] !== '' && is_numeric($item['carga_padrao'])) {
            $cargaPadrao = (float) $item['carga_padrao'];
        }

        $normalized[] = [
            'exercicio_id' => $exercicioId,
            'series_padrao' => $seriesPadrao > 0 ? $seriesPadrao : 3,
            'reps_padrao' => $repsPadrao > 0 ? $repsPadrao : 10,
            'carga_padrao' => $cargaPadrao,
        ];
    }

    return $normalized;
}

switch ($method) {
    case 'GET':
        $routines = read_json($path);

        $id = query_id();
        if ($id !== null) {
            foreach ($routines as $routine) {
                if (($routine['id'] ?? null) === $id) {
                    json_response($routine);
                }
            }
            json_error('Rotina não encontrada.', 404);
        }

        json_response($routines);
        break;

    case 'POST':
        $input = json_input();
        $cloneId = $_GET['clone'] ?? null;
        $routines = read_json($path);

        if ($cloneId !== null) {
            $original = null;
            foreach ($routines as $routine) {
                if (($routine['id'] ?? null) === $cloneId) {
                    $original = $routine;
                    break;
                }
            }

            if ($original === null) {
                json_error('Rotina a ser clonada não foi encontrada.', 404);
            }

            $nome = trim((string) ($input['nome'] ?? ''));
            if ($nome === '') {
                $nome = ($original['nome'] ?? 'Rotina') . ' (cópia)';
            }

            $clone = [
                'id' => generate_id(),
                'nome' => $nome,
                'exercicios' => $original['exercicios'] ?? [],
            ];

            $routines[] = $clone;

            if (!write_json($path, $routines)) {
                json_error('Falha ao clonar a rotina.', 500);
            }

            json_response($clone, 201);
        }

        $nome = trim((string) ($input['nome'] ?? ''));
        if ($nome === '') {
            json_error('O campo "nome" é obrigatório.', 422);
        }

        $exercicios = normalize_routine_exercises(is_array($input['exercicios'] ?? null) ? $input['exercicios'] : []);

        $routine = [
            'id' => generate_id(),
            'nome' => $nome,
            'exercicios' => $exercicios,
        ];

        $routines[] = $routine;

        if (!write_json($path, $routines)) {
            json_error('Falha ao salvar a rotina.', 500);
        }

        json_response($routine, 201);
        break;

    case 'PUT':
    case 'PATCH':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $input = json_input();
        $routines = read_json($path);

        $found = false;
        foreach ($routines as &$routine) {
            if (($routine['id'] ?? null) !== $id) {
                continue;
            }

            $found = true;

            if (array_key_exists('nome', $input)) {
                $nome = trim((string) $input['nome']);
                if ($nome === '') {
                    json_error('O campo "nome" não pode ficar vazio.', 422);
                }
                $routine['nome'] = $nome;
            }

            if (array_key_exists('exercicios', $input)) {
                $routine['exercicios'] = normalize_routine_exercises(
                    is_array($input['exercicios']) ? $input['exercicios'] : []
                );
            }

            $updated = $routine;
            break;
        }
        unset($routine);

        if (!$found) {
            json_error('Rotina não encontrada.', 404);
        }

        if (!write_json($path, $routines)) {
            json_error('Falha ao salvar a rotina.', 500);
        }

        json_response($updated);
        break;

    case 'DELETE':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $routines = read_json($path);
        $remaining = array_values(array_filter(
            $routines,
            fn ($routine) => ($routine['id'] ?? null) !== $id
        ));

        if (count($remaining) === count($routines)) {
            json_error('Rotina não encontrada.', 404);
        }

        if (!write_json($path, $remaining)) {
            json_error('Falha ao remover a rotina.', 500);
        }

        json_response(['success' => true]);
        break;
}
