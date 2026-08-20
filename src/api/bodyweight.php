<?php

declare(strict_types=1);

require __DIR__ . '/../lib/json_store.php';
require __DIR__ . '/../lib/api_helpers.php';

/**
 * /src/api/bodyweight.php — CRUD de registros de peso corporal e medidas.
 *
 * GET    ?id=xxx   -> retorna um registro específico
 * GET               -> lista todos os registros (ordenados por data)
 * POST              -> cria um registro (body: data?, peso, medidas?)
 * PUT/PATCH ?id=xxx -> edita um registro existente
 * DELETE    ?id=xxx -> remove um registro
 */

require_method(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);

$path = data_path('bodyweight.json');
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Normaliza o objeto de medidas: mantém só chaves com valor numérico
 * (descarta vazios), convertendo tudo para float. O objeto de medidas é
 * livre — qualquer chave é aceita (cintura, braço, peito, etc).
 */
function normalize_medidas($input): array
{
    if (!is_array($input)) {
        return [];
    }

    $medidas = [];
    foreach ($input as $chave => $valor) {
        if ($valor === '' || $valor === null || !is_numeric($valor)) {
            continue;
        }
        $medidas[(string) $chave] = (float) $valor;
    }

    return $medidas;
}

/**
 * PHP não distingue array vazio de objeto vazio: json_encode([]) sempre
 * vira "[]", nunca "{}". Como o schema declara "medidas" como objeto,
 * usamos stdClass quando estiver vazio para o JSON sair como "{}".
 */
function medidas_for_storage(array $medidas)
{
    return empty($medidas) ? new stdClass() : $medidas;
}

switch ($method) {
    case 'GET':
        $registros = read_json($path);

        // read_json() decodifica objetos JSON como array associativo, então
        // um "medidas": {} salvo no arquivo volta como [] em PHP — reaplica
        // medidas_for_storage() para a resposta sair consistente com o schema.
        foreach ($registros as &$r) {
            $r['medidas'] = medidas_for_storage(is_array($r['medidas'] ?? null) ? $r['medidas'] : []);
        }
        unset($r);

        $id = query_id();
        if ($id !== null) {
            foreach ($registros as $registro) {
                if (($registro['id'] ?? null) === $id) {
                    json_response($registro);
                }
            }
            json_error('Registro não encontrado.', 404);
        }

        usort($registros, fn ($a, $b) => strcmp($a['data'] ?? '', $b['data'] ?? ''));
        json_response($registros);
        break;

    case 'POST':
        $input = json_input();

        if (!array_key_exists('peso', $input) || !is_numeric($input['peso']) || (float) $input['peso'] <= 0) {
            json_error('Campo "peso" precisa ser um número maior que zero.', 422);
        }

        $data = trim((string) ($input['data'] ?? ''));
        if ($data === '') {
            $data = date('Y-m-d');
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            json_error('Campo "data" deve estar no formato YYYY-MM-DD.', 422);
        }

        $registros = read_json($path);

        $registro = [
            'id' => generate_id(),
            'data' => $data,
            'peso' => (float) $input['peso'],
            'medidas' => medidas_for_storage(normalize_medidas($input['medidas'] ?? [])),
        ];

        $registros[] = $registro;

        if (!write_json($path, $registros)) {
            json_error('Falha ao salvar o registro.', 500);
        }

        json_response($registro, 201);
        break;

    case 'PUT':
    case 'PATCH':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $input = json_input();
        $registros = read_json($path);

        $found = false;
        foreach ($registros as &$registro) {
            if (($registro['id'] ?? null) !== $id) {
                continue;
            }

            $found = true;

            if (array_key_exists('data', $input)) {
                $data = trim((string) $input['data']);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                    json_error('Campo "data" deve estar no formato YYYY-MM-DD.', 422);
                }
                $registro['data'] = $data;
            }

            if (array_key_exists('peso', $input)) {
                if (!is_numeric($input['peso']) || (float) $input['peso'] <= 0) {
                    json_error('Campo "peso" precisa ser um número maior que zero.', 422);
                }
                $registro['peso'] = (float) $input['peso'];
            }

            if (array_key_exists('medidas', $input)) {
                $registro['medidas'] = medidas_for_storage(normalize_medidas($input['medidas']));
            }

            $updated = $registro;
            break;
        }
        unset($registro);

        if (!$found) {
            json_error('Registro não encontrado.', 404);
        }

        if (!write_json($path, $registros)) {
            json_error('Falha ao salvar o registro.', 500);
        }

        json_response($updated);
        break;

    case 'DELETE':
        $id = query_id();
        if ($id === null) {
            json_error('Parâmetro "id" é obrigatório.', 422);
        }

        $registros = read_json($path);
        $remaining = array_values(array_filter(
            $registros,
            fn ($registro) => ($registro['id'] ?? null) !== $id
        ));

        if (count($remaining) === count($registros)) {
            json_error('Registro não encontrado.', 404);
        }

        if (!write_json($path, $remaining)) {
            json_error('Falha ao remover o registro.', 500);
        }

        json_response(['success' => true]);
        break;
}
