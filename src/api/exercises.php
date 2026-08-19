<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/exercises.php — CRUD da biblioteca de exercícios.
 *
 * GET    ?id=xxx   -> retorna um exercício específico
 * GET               -> lista todos os exercícios
 * POST              -> cria um exercício (body: nome, grupo_muscular, video_url?)
 * PUT/PATCH ?id=xxx -> edita um exercício existente
 * DELETE    ?id=xxx -> remove um exercício
 */

require_method(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);

$path = data_path('exercises.json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $exercises = read_json($path);

        $id = query_id();
        if ($id !== null) {
            foreach ($exercises as $exercise) {
                if (($exercise['id'] ?? null) === $id) {
                    json_response($exercise);
                }
            }
            json_error('Exercício não encontrado.', 404);
        }

        json_response($exercises);
        break;

    case 'POST':
        $input = json_input();

        $nome = trim((string) ($input['nome'] ?? ''));
        $grupoMuscular = trim((string) ($input['grupo_muscular'] ?? ''));
        $videoUrl = trim((string) ($input['video_url'] ?? ''));

        if ($nome === '' || $grupoMuscular === '') {
            json_error('Campos "nome" e "grupo_muscular" são obrigatórios.', 422);
        }

        $exercises = read_json($path);

        $exercise = [
            'id' => generate_id(),
            'nome' => $nome,
            'grupo_muscular' => $grupoMuscular,
            'video_url' => $videoUrl,
        ];

        $exercises[] = $exercise;

        if (!write_json($path, $exercises)) {
            json_error('Falha ao salvar o exercício.', 500);
        }

        json_response($exercise, 201);
        break;

    case 'PUT':
    case 'PATCH':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $input = json_input();
        $exercises = read_json($path);

        $found = false;
        foreach ($exercises as &$exercise) {
            if (($exercise['id'] ?? null) !== $id) {
                continue;
            }

            $found = true;

            if (array_key_exists('nome', $input)) {
                $nome = trim((string) $input['nome']);
                if ($nome === '') {
                    json_error('O campo "nome" não pode ficar vazio.', 422);
                }
                $exercise['nome'] = $nome;
            }

            if (array_key_exists('grupo_muscular', $input)) {
                $grupoMuscular = trim((string) $input['grupo_muscular']);
                if ($grupoMuscular === '') {
                    json_error('O campo "grupo_muscular" não pode ficar vazio.', 422);
                }
                $exercise['grupo_muscular'] = $grupoMuscular;
            }

            if (array_key_exists('video_url', $input)) {
                $exercise['video_url'] = trim((string) $input['video_url']);
            }

            $updated = $exercise;
            break;
        }
        unset($exercise);

        if (!$found) {
            json_error('Exercício não encontrado.', 404);
        }

        if (!write_json($path, $exercises)) {
            json_error('Falha ao salvar o exercício.', 500);
        }

        json_response($updated);
        break;

    case 'DELETE':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $exercises = read_json($path);
        $remaining = array_values(array_filter(
            $exercises,
            fn ($exercise) => ($exercise['id'] ?? null) !== $id
        ));

        if (count($remaining) === count($exercises)) {
            json_error('Exercício não encontrado.', 404);
        }

        if (!write_json($path, $remaining)) {
            json_error('Falha ao remover o exercício.', 500);
        }

        json_response(['success' => true]);
        break;
}
