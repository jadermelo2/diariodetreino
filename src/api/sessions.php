<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/sessions.php — sessões de treino (modo treino ativo).
 *
 * Diferente de exercises.php/routines.php, cada sessão é o próprio seu
 * arquivo JSON em data/sessions/{data}-{id}.json (não um array num único
 * arquivo) — assim os arquivos de sessão não crescem indefinidamente e
 * cada treino pode ser lido/escrito isoladamente.
 *
 * GET    ?id=xxx        -> retorna uma sessão específica
 * GET    ?historico_exercicio=xxx&excluir_sessao=yyy (opcional)
 *                        -> última série registrada desse exercício em
 *                           qualquer sessão anterior (usado pelo modo treino
 *                           ativo para mostrar "Última vez: 3×10 com 40kg"
 *                           e pré-preencher reps/carga)
 * GET                    -> lista todas as sessões (mais recentes primeiro)
 * POST                   -> cria uma sessão nova
 *   body: { rotina_id?: string|null, data?: "YYYY-MM-DD" }
 *   se rotina_id for informado, pré-popula "exercicios" na ordem da rotina
 *   (cada um começando com series: []); "data" default é hoje.
 * PATCH  ?id=xxx         -> adiciona uma série a um exercício da sessão
 *   body: { action: "add_set", exercicio_id, reps, carga, nota? }
 *   cria a entrada do exercício na sessão se ainda não existir.
 * DELETE ?id=xxx         -> remove o arquivo da sessão (ex: treino vazio descartado)
 */

require_method(['GET', 'POST', 'PATCH', 'DELETE']);

$sessionsDir = data_path('sessions');
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Localiza o caminho do arquivo de uma sessão pelo id, buscando por
 * "*-{id}.json" dentro de data/sessions/ (o prefixo é a data, que não
 * conhecemos de antemão a partir só do id).
 */
function find_session_path(string $sessionsDir, string $id): ?string
{
    $matches = glob($sessionsDir . '/*-' . $id . '.json');
    return $matches && count($matches) > 0 ? $matches[0] : null;
}

/**
 * Lista todos os arquivos de sessão (ordenados pelo nome do arquivo, que
 * começa com a data — então já sai em ordem cronológica).
 */
function list_session_files(string $sessionsDir): array
{
    if (!is_dir($sessionsDir)) {
        return [];
    }

    $files = glob($sessionsDir . '/*.json') ?: [];
    sort($files);
    return $files;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['historico_exercicio'])) {
            $exercicioId = trim((string) $_GET['historico_exercicio']);
            if ($exercicioId === '') {
                json_error('Parâmetro "historico_exercicio" inválido.', 422);
            }

            // Exclui a sessão em andamento da busca, para "última vez" nunca
            // refletir séries que acabaram de ser registradas agora mesmo.
            $excluirSessao = isset($_GET['excluir_sessao']) ? (string) $_GET['excluir_sessao'] : null;

            // Arquivos começam com a data (YYYY-MM-DD-{id}.json), então
            // ordenar e inverter dá a ordem cronológica mais recente primeiro.
            $arquivosRecentes = array_reverse(list_session_files($sessionsDir));

            foreach ($arquivosRecentes as $file) {
                $sessao = read_json($file);

                if ($excluirSessao !== null && ($sessao['id'] ?? null) === $excluirSessao) {
                    continue;
                }

                foreach ($sessao['exercicios'] ?? [] as $item) {
                    if (($item['exercicio_id'] ?? null) === $exercicioId && !empty($item['series'])) {
                        json_response([
                            'encontrado' => true,
                            'data' => $sessao['data'] ?? null,
                            'series' => $item['series'],
                        ]);
                    }
                }
            }

            json_response(['encontrado' => false]);
        }

        $id = query_id();

        if ($id !== null) {
            $path = find_session_path($sessionsDir, $id);
            if ($path === null) {
                json_error('Sessão não encontrada.', 404);
            }
            json_response(read_json($path));
        }

        $sessions = [];
        foreach (array_reverse(list_session_files($sessionsDir)) as $file) {
            $sessions[] = read_json($file);
        }

        json_response($sessions);
        break;

    case 'POST':
        $input = json_input();

        $rotinaId = null;
        if (!empty($input['rotina_id'])) {
            $rotinaId = (string) $input['rotina_id'];
        }

        $data = trim((string) ($input['data'] ?? ''));
        if ($data === '') {
            $data = date('Y-m-d');
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            json_error('Campo "data" deve estar no formato YYYY-MM-DD.', 422);
        }

        $exercicios = [];

        if ($rotinaId !== null) {
            $routines = read_json(data_path('routines.json'));
            $routine = null;
            foreach ($routines as $r) {
                if (($r['id'] ?? null) === $rotinaId) {
                    $routine = $r;
                    break;
                }
            }

            if ($routine === null) {
                json_error('Rotina não encontrada.', 404);
            }

            foreach ($routine['exercicios'] ?? [] as $item) {
                $exercicios[] = [
                    'exercicio_id' => $item['exercicio_id'],
                    'series' => [],
                ];
            }
        }

        $id = generate_id();
        $session = [
            'id' => $id,
            'data' => $data,
            'rotina_id' => $rotinaId,
            'exercicios' => $exercicios,
        ];

        $path = $sessionsDir . '/' . $data . '-' . $id . '.json';

        if (!write_json($path, $session)) {
            json_error('Falha ao criar a sessão.', 500);
        }

        json_response($session, 201);
        break;

    case 'PATCH':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $path = find_session_path($sessionsDir, $id);
        if ($path === null) {
            json_error('Sessão não encontrada.', 404);
        }

        $input = json_input();
        $action = $input['action'] ?? 'add_set';

        if ($action !== 'add_set') {
            json_error('Ação inválida.', 422);
        }

        $exercicioId = trim((string) ($input['exercicio_id'] ?? ''));
        if ($exercicioId === '') {
            json_error('Campo "exercicio_id" é obrigatório.', 422);
        }

        if (!array_key_exists('reps', $input) || !is_numeric($input['reps']) || (int) $input['reps'] <= 0) {
            json_error('Campo "reps" precisa ser um número maior que zero.', 422);
        }

        if (!array_key_exists('carga', $input) || !is_numeric($input['carga']) || (float) $input['carga'] < 0) {
            json_error('Campo "carga" precisa ser um número maior ou igual a zero.', 422);
        }

        $novaSerie = [
            'reps' => (int) $input['reps'],
            'carga' => (float) $input['carga'],
            'nota' => trim((string) ($input['nota'] ?? '')),
        ];

        $session = read_json($path);
        $session['exercicios'] = $session['exercicios'] ?? [];

        $found = false;
        foreach ($session['exercicios'] as &$exercicio) {
            if (($exercicio['exercicio_id'] ?? null) === $exercicioId) {
                $exercicio['series'][] = $novaSerie;
                $found = true;
                break;
            }
        }
        unset($exercicio);

        if (!$found) {
            $session['exercicios'][] = [
                'exercicio_id' => $exercicioId,
                'series' => [$novaSerie],
            ];
        }

        if (!write_json($path, $session)) {
            json_error('Falha ao salvar a série.', 500);
        }

        json_response($session);
        break;

    case 'DELETE':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $path = find_session_path($sessionsDir, $id);
        if ($path === null) {
            json_error('Sessão não encontrada.', 404);
        }

        if (!@unlink($path)) {
            json_error('Falha ao remover a sessão.', 500);
        }

        json_response(['success' => true]);
        break;
}
