<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/calendar.php — calendário de frequência e streak (Etapa 8).
 * Somente leitura: agrega todas as sessões salvas por dia e calcula a
 * sequência (streak) atual e o recorde de sequência.
 *
 * GET -> {
 *   dias: { "YYYY-MM-DD": { volume, treinos }, ... },  (só dias com sessão)
 *   streak_atual, streak_recorde, total_dias_treinados
 * }
 */

require_method(['GET']);

$sessionsDir = data_path('sessions');

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

function session_volume(array $sessao): float
{
    $total = 0.0;
    foreach ($sessao['exercicios'] ?? [] as $item) {
        foreach ($item['series'] ?? [] as $serie) {
            $total += (float) ($serie['reps'] ?? 0) * (float) ($serie['carga'] ?? 0);
        }
    }
    return $total;
}

/**
 * Calcula a sequência atual (contando hoje pra trás — ou ontem pra trás,
 * se hoje ainda não teve treino registrado, pra não "quebrar" a sequência
 * só porque o dia ainda não terminou) e o recorde histórico de sequência,
 * a partir de um conjunto de datas treinadas (strings "YYYY-MM-DD").
 */
function calcular_streaks(array $datasTreinadas): array
{
    if (empty($datasTreinadas)) {
        return ['atual' => 0, 'recorde' => 0];
    }

    sort($datasTreinadas);
    $set = array_flip($datasTreinadas);

    $recorde = 1;
    $sequenciaAtual = 1;
    for ($i = 1, $n = count($datasTreinadas); $i < $n; $i++) {
        $anterior = new DateTime($datasTreinadas[$i - 1]);
        $atual = new DateTime($datasTreinadas[$i]);
        $diffDias = (int) $anterior->diff($atual)->days;

        $sequenciaAtual = $diffDias === 1 ? $sequenciaAtual + 1 : 1;
        $recorde = max($recorde, $sequenciaAtual);
    }

    $cursor = new DateTime(date('Y-m-d'));
    if (!isset($set[$cursor->format('Y-m-d')])) {
        $cursor->modify('-1 day');
    }

    $streakAtual = 0;
    while (isset($set[$cursor->format('Y-m-d')])) {
        $streakAtual++;
        $cursor->modify('-1 day');
    }

    return ['atual' => $streakAtual, 'recorde' => $recorde];
}

$sessions = list_all_sessions($sessionsDir);

$dias = [];
foreach ($sessions as $sessao) {
    $vol = session_volume($sessao);
    if ($vol <= 0) {
        continue;
    }

    $data = $sessao['data'] ?? null;
    if ($data === null) {
        continue;
    }

    if (!isset($dias[$data])) {
        $dias[$data] = ['volume' => 0.0, 'treinos' => 0];
    }

    $dias[$data]['volume'] += $vol;
    $dias[$data]['treinos']++;
}

foreach ($dias as &$d) {
    $d['volume'] = round($d['volume'], 1);
}
unset($d);

$streaks = calcular_streaks(array_keys($dias));

json_response([
    'dias' => $dias,
    'streak_atual' => $streaks['atual'],
    'streak_recorde' => $streaks['recorde'],
    'total_dias_treinados' => count($dias),
]);
