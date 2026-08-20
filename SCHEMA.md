# Schema dos dados — Diário de Treino

Toda a persistência é em arquivos JSON dentro de `/data`. Não há banco de
dados relacional. Cada entidade abaixo descreve a forma de um "registro"
dentro do array armazenado no respectivo arquivo (ou, no caso de sessões,
um arquivo por registro).

Uso pessoal único por enquanto (sem login). A pasta `data/` foi desenhada
para no futuro virar `data/{user_id}/` sem exigir mudança de schema —
basta trocar a raiz resolvida por `data_path()` em
`src/lib/json_store.php`.

Todos os IDs são UUID v4 gerados por `generate_id()` (`src/lib/json_store.php`).
Datas são strings no formato `YYYY-MM-DD` (ou `YYYY-MM-DD HH:MM:SS` quando
o horário importa, como em sessões).

---

## Exercise — `data/exercises.json`

Array de objetos:

```json
{
  "id": "uuid",
  "nome": "Supino reto",
  "grupo_muscular": "Peito",
  "video_url": ""
}
```

- `video_url` (opcional): URL de um vídeo do YouTube demonstrando o
  exercício. Vazio (`""`) por padrão.

---

## Routine — `data/routines.json`

Array de objetos:

```json
{
  "id": "uuid",
  "nome": "Treino A — Peito/Tríceps",
  "exercicios": [
    {
      "exercicio_id": "uuid-do-exercicio",
      "series_padrao": 3,
      "reps_padrao": 10,
      "carga_padrao": 40
    }
  ]
}
```

- `exercicios`: lista ordenada (a ordem é a ordem de execução sugerida).
- `series_padrao` / `reps_padrao`: valores sugeridos ao iniciar um treino
  a partir desta rotina; podem ser ajustados durante a sessão.
- `carga_padrao` (opcional): peso planejado em kg para esse exercício
  nesta rotina. `null` quando ainda não foi definido nenhum peso — nesse
  caso, no modo treino ativo (Etapa 4/5) os campos de carga começam
  vazios até o usuário registrar a primeira série. É atualizado
  automaticamente quando o usuário toca em "Salvar como padrão da
  rotina" durante um treino (ver Etapa 4/5), ou editado manualmente na
  tela de Rotinas.

---

## Session — `data/sessions/{data}-{id}.json`

Um arquivo por sessão de treino, nomeado `{YYYY-MM-DD}-{id}.json` para
facilitar listagem cronológica só pelo nome do arquivo.

```json
{
  "id": "uuid",
  "data": "2026-08-19",
  "rotina_id": "uuid-da-rotina-ou-null",
  "exercicios": [
    {
      "exercicio_id": "uuid-do-exercicio",
      "series": [
        {
          "reps": 10,
          "carga": 40,
          "nota": ""
        }
      ]
    }
  ]
}
```

- `rotina_id` (opcional): `null` quando o treino foi feito "livre", sem
  partir de uma rotina salva.
- `nota` (opcional): observação curta por série (ex: "falhou a última
  rep"), vazio (`""`) por padrão.
- `carga`: peso usado na série, em kg (número, pode ter casas decimais).

---

## Bodyweight entry — `data/bodyweight.json`

Array de objetos:

```json
{
  "id": "uuid",
  "data": "2026-08-19",
  "peso": 82.4,
  "medidas": {
    "cintura": 84,
    "braco": 38
  }
}
```

- `medidas` (opcional): objeto livre de medidas corporais em cm. Chaves
  sugeridas: `cintura`, `braco`, `peito`, `coxa`, `quadril` — mas o objeto
  aceita qualquer chave, para não travar o usuário num conjunto fixo.
