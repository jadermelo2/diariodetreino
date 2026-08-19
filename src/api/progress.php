<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/progress.php — dados agregados para os gráficos de evolução
 * (Etapa 6). Todo o cálculo é feito aqui, lendo os JSONs de sessão, para
 * o front só precisar entregar os arrays prontos ao Chart.js.
 *
 * GET ?exercicio_id=xxx
 *   -> evolução de carga máxima e volume por sessão para um exercício,
 *      mais a lista de PRs (recordes pessoais) e o recorde atual.
 *
 * GET ?volume=1 [&exercicio_id=xxx | &rotina_id=xxx]
 *   -> volume por sessão: de um exercício específico, de uma rotina
 *      (soma de todos os exercícios daquela sessão), ou de todos os
 *      treinos (sem nenhum dos dois filtros).
 */

require_method(['GET']);

$sessionsDir = data_path('sessions');

/**
 * Lê todas as sessões em ordem cronológica (o nome do arquivo começa com
 * a data, então ordenar os nomes já ordena as sessões).
 */
function list_all_sessions(string $sessionsDir): array
{
    $files = glob($sessionsDir . '/*.json') ?: [];
    sort($files);

    $sessions = [];
    foreach ($files as $file) {
        $sessions[] = read_json($file);
    }

    return $sessions;
}

/**
 * Soma reps × carga de todas as séries de uma sessão. Se $exercicioId for
 * informado, restringe a soma às séries daquele exercício.
 */
function session_volume(array $sessao, ?string $exercicioId = null): float
{
    $total = 0.0;

    foreach ($sessao['exercicios'] ?? [] as $item) {
        if ($exercicioId !== null && ($item['exercicio_id'] ?? null) !== $exercicioId) {
            continue;
        }

        foreach ($item['series'] ?? [] as $serie) {
            $total += (float) ($serie['reps'] ?? 0) * (float) ($serie['carga'] ?? 0);
        }
    }

    return $total;
}

$sessions = list_all_sessions($sessionsDir);

// ---- modo: volume por sessão (de um exercício, de uma rotina, ou total) ----
if (isset($_GET['volume'])) {
    $exercicioId = isset($_GET['exercicio_id']) && $_GET['exercicio_id'] !== '' ? (string) $_GET['exercicio_id'] : null;
    $rotinaId = isset($_GET['rotina_id']) && $_GET['rotina_id'] !== '' ? (string) $_GET['rotina_id'] : null;

    $labels = [];
    $volumes = [];

    foreach ($sessions as $sessao) {
        if ($rotinaId !== null && ($sessao['rotina_id'] ?? null) !== $rotinaId) {
            continue;
        }

        if ($exercicioId !== null) {
            $temExercicio = false;
            foreach ($sessao['exercicios'] ?? [] as $item) {
                if (($item['exercicio_id'] ?? null) === $exercicioId && !empty($item['series'])) {
                    $temExercicio = true;
                    break;
                }
            }
            if (!$temExercicio) {
                continue;
            }
        }

        $vol = session_volume($sessao, $exercicioId);
        if ($vol <= 0) {
            continue;
        }

        $labels[] = $sessao['data'] ?? '';
        $volumes[] = round($vol, 1);
    }

    json_response([
        'labels' => $labels,
        'volume' => $volumes,
    ]);
}

// ---- modo: evolução de um exercício (carga máxima + volume + PRs) ----
if (isset($_GET['exercicio_id']) && $_GET['exercicio_id'] !== '') {
    $exercicioId = (string) $_GET['exercicio_id'];

    $labels = [];
    $cargaMaxima = [];
    $volume = [];
    $seriesCount = [];
    $prs = [];
    $recorde = null;

    foreach ($sessions as $sessao) {
        $entrada = null;
        foreach ($sessao['exercicios'] ?? [] as $item) {
            if (($item['exercicio_id'] ?? null) === $exercicioId) {
                $entrada = $item;
                break;
            }
        }

        if ($entrada === null || empty($entrada['series'])) {
            continue;
        }

        $cargas = array_map(fn ($s) => (float) ($s['carga'] ?? 0), $entrada['series']);
        $max = max($cargas);
        $data = $sessao['data'] ?? '';

        $labels[] = $data;
        $cargaMaxima[] = $max;
        $volume[] = round(session_volume($sessao, $exercicioId), 1);
        $seriesCount[] = count($entrada['series']);

        if ($recorde === null || $max > $recorde) {
            $recorde = $max;
            $prs[] = ['data' => $data, 'carga' => $max];
        }
    }

    json_response([
        'exercicio_id' => $exercicioId,
        'labels' => $labels,
        'carga_maxima' => $cargaMaxima,
        'volume' => $volume,
        'series_count' => $seriesCount,
        'prs' => $prs,
        'recorde_atual' => $recorde,
    ]);
}

json_error('Informe "exercicio_id" ou "volume=1".', 422);
