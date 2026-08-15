# Cliente × Vendedor — vínculo e permissões (api-platform-people)

Documentação técnica do papel de `api-platform-people` na entrega `ControleOnline/ui-crm#2`.

## Papel deste módulo

Backend do vínculo vendedor ↔ cliente e da distribuição automática de vendedores.

## Serviços

### `SalesmanService`

- Escuta `EntityChangedEvent` em `PeopleLink`.
- Quando `linkType = client` e o cliente ainda não tem vendedor da empresa:
  - se o usuário logado for vendedor da empresa → usa esse vendedor;
  - senão → `SalesmanDistributionService::discoverSalesman`.
- Cria vínculo `sellers-client` via `PeopleService::discoveryLink`.

Arquivo: `src/Service/SalesmanService.php`

### `SalesmanDistributionService`

Config por empresa: `salesman-distribution-strategy`

| Estratégia | Comportamento |
| --- | --- |
| `random` (default) | vendedor aleatório da empresa |
| `round_robin` | próximo após o último que recebeu cliente |
| `least_clients` | vendedor com menos clientes |
| `last_received` | base para round-robin / consulta do último |

Arquivo: `src/Service/SalesmanDistributionService.php`

### `PeopleLinkService`

Ponto canônico para `securityFilter` e guards de leitura/escrita de `people_link` (incluindo ocultar `comission` / `minimum_comission` para leitores sem gestão).

## Modelo

- `PeopleLink.linkType`:
  - `client`, `salesman`, `sellers-client`
- Campos sensíveis: `comission`, `minimum_comission`

## Regras de autorização (obrigatórias)

1. Fora de contexto administrativo equivalente a `MANAGER`, não expor comissão nem permitir gestão de `sellers-client`.
2. Escrita não pode usar “pode ler” como critério genérico para outros `linkType`.
3. Escopo multiempresa: não herdar gestão só porque o vendedor atua em mais de uma empresa.

## Documento canônico do fluxo completo

Ver também: `ControleOnline/ui-crm` → `docs/technical/Cliente-Vendedor-Vinculo-e-Permissoes.md`
