<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/export.php — exportação do histórico de treinos em CSV.
 *
 * GET -> baixa um .csv achatado: uma linha por série registrada
 *        (data, exercício, série, reps, carga, nota).
 */

require_method(['GET']);

$exercicios = read_json(data_path('exercises.json'));
$nomesPorId = array_column($exercicios, 'nome', 'id');

$sessionsDir = data_path('sessions');
$files = glob($sessionsDir . '/*.json') ?: [];
sort($files);

$nomeArquivo = 'diario-treino-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');

$out = fopen('php://output', 'w');

// BOM UTF-8 pra o Excel abrir os acentos corretamente
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['data', 'exercicio', 'serie', 'reps', 'carga', 'nota']);

foreach ($files as $file) {
    $sessao = read_json($file);
    $data = $sessao['data'] ?? '';

    foreach ($sessao['exercicios'] ?? [] as $item) {
        $exercicioId = $item['exercicio_id'] ?? '';
        $nome = $nomesPorId[$exercicioId] ?? $exercicioId;

        foreach ($item['series'] ?? [] as $indice => $serie) {
            fputcsv($out, [
                $data,
                $nome,
                $indice + 1,
                $serie['reps'] ?? '',
                $serie['carga'] ?? '',
                $serie['nota'] ?? '',
            ]);
        }
    }
}

fclose($out);
exit;
