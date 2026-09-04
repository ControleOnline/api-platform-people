## Ponto de entrada

- A documentação funcional e de regras deste modulo vive na **wiki do proprio repositório** e na wiki principal da API.
- Regras transversais de qualidade, modularizacao e limites de componente vivem em `https://github.com/ControleOnline/agents-mcp/blob/master/skills/shared/code-quality.md`.
- Quando houver detalhe especifico de implementacao, prefira comentar no codigo em ingles perto da regra.
- Este arquivo deve ficar curto e servir apenas como ponte para as fontes oficiais.

## Documentação (navegação humana)

Sempre comece pela **Home** da wiki e siga as categorias abaixo.

| Categoria | Destino |
| --- | --- |
| Home do módulo | https://github.com/ControleOnline/api-platform-people/wiki |
| Wiki principal da API | https://github.com/ControleOnline/api-community/wiki |
| Wiki principal do app | https://github.com/ControleOnline/app-community/wiki |
| Visões do app (`APP_TYPE`) | https://github.com/ControleOnline/app-community/blob/master/MODOS_OPERACAO.md |

### Por categoria — pessoas, vínculos e vendedores

| Página | O que documenta |
| --- | --- |
| [Cliente × Vendedor — vínculo e permissões](https://github.com/ControleOnline/api-platform-people/wiki/Cliente-Vendedor-Vinculo-e-Permissoes) | SalesmanService, distribuição, people_link, comissões |
| [Document — metadata `$vehicle`](https://github.com/ControleOnline/api-platform-people/wiki/Document-Vehicle-Metadata-Compatibility) | Compatibilidade Doctrine; hotfix #88 webhook iFood |
| Página canônica do fluxo (CRM) | https://github.com/ControleOnline/ui-crm/wiki/Cliente-Vendedor-Vinculo-e-Permissoes |

Cópia versionada no Git: `docs/technical/Cliente-Vendedor-Vinculo-e-Permissoes.md`, `docs/technical/Document-Vehicle-Metadata-Compatibility.md`

### Visão deste módulo

`api-platform-people` é o **backend de pessoas e vínculos** (`People`, `PeopleLink`) usado por CRM, MANAGER, POS e demais apps.

No fluxo cliente × vendedor:

- `SalesmanService` cria vínculo `sellers-client` quando o cliente ainda não tem vendedor;
- `SalesmanDistributionService` escolhe vendedor (`random`, `round_robin`, `least_clients`, …);
- `PeopleLinkService` é o ponto esperado de `securityFilter` / guards (incluindo `comission` / `minimum_comission`).

A UI em `ui-crm` / `ui-customers` **não** substitui o enforcement de API.

### Módulos relacionados (mesmo fluxo)

| Módulo | Papel | Entrada da documentação |
| --- | --- | --- |
| `ui-crm` | Handoff comercial | https://github.com/ControleOnline/ui-crm/wiki |
| `ui-customers` | Aba Vendedores no detalhe | https://github.com/ControleOnline/ui-customers/wiki |
| `api-community` | Home da API | https://github.com/ControleOnline/api-community/wiki |
