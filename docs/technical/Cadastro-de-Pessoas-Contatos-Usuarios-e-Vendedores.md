# Cadastro de Pessoas, Contatos, Usuários e Vendedores

Documentação técnica do papel de **api-platform-people** no fluxo completo de cadastro de pessoas (PF/PJ), contatos, usuários e vendedores.

**Issue de origem:** [ui-people#16](https://github.com/ControleOnline/ui-people/issues/16)  
**Página canônica (UI + fluxo completo):** [ui-people wiki — Cadastro-de-Pessoas-Contatos-Usuarios-e-Vendedores](https://github.com/ControleOnline/ui-people/wiki/Cadastro-de-Pessoas-Contatos-Usuarios-e-Vendedores)

## Papel deste módulo

Backend de `People`, `PeopleLink` e serviços de descoberta/vínculo usados por MANAGER, CRM, POS e demais apps.

Não concentra UI de abas; a montagem PF×PJ (contatos só em PJ; usuários na PF) vive em `ui-customers`.

## Regras obrigatórias (enforcement / modelo)

1. **PJ não possui usuário próprio** — `User` liga-se a pessoa física; a API de users não deve tratar PJ como dono de login.
2. **PJ pode ter contatos PF** — via `PeopleLink` (HUMAN_LINK / tipos operacionais).
3. **Contato PF pode ter usuário** — criação em `api-platform-users` com `people` IRI da PF.
4. **PF não tem “contatos de empresa” no mesmo sentido da aba Contatos da PJ** — contatos comerciais de uma PF são outros fluxos; a aba Contatos do Client Details é exclusiva de `peopleType = J`.
5. **Vendedor** — `linkType` `salesman` + vínculo `sellers-client` via `SalesmanService` / `SalesmanDistributionService`; guards em `PeopleLinkService`.

## Serviços canônicos

| Serviço | Arquivo | Função |
| --- | --- | --- |
| `PeopleService` | `src/Service/PeopleService.php` | `discoveryPeople`, `discoveryLink`, `prePersist` (enable em create com company+linkType), import |
| `PeopleLinkService` | `src/Service/PeopleLinkService.php` | `securityFilter`, escrita, comissão em `sellers-client` |
| `SalesmanService` | `src/Service/SalesmanService.php` | Auto-vínculo vendedor ao criar cliente |
| `SalesmanDistributionService` | `src/Service/SalesmanDistributionService.php` | Estratégia de distribuição |
| `AccountRegistrationService` | `src/Service/AccountRegistrationService.php` | Auto-cadastro público |

## Entidades e constantes

- `People` — `peopleType` `F` \| `J`, soft-delete, media, documentos, e-mails, telefones.
- `PeopleLink` — `HUMAN_LINK`, `COMMERCIAL_LINK`, `ADMIN_LINK`, `API_ROLE_MAP`, campos de comissão / closing_period.

Detalhe de vendedor: [Cliente-Vendedor-Vinculo-e-Permissoes](Cliente-Vendedor-Vinculo-e-Permissoes).  
Auto-cadastro: [Auto-cadastro-Create-Account](Auto-cadastro-Create-Account).  
Soft-delete: [People-Soft-Delete-Schema](People-Soft-Delete-Schema).

## Comentário de ponte no código

No topo dos PHP relacionados (sugestão):

```php
// Technical wiki: https://github.com/ControleOnline/api-platform-people/wiki/Cadastro-de-Pessoas-Contatos-Usuarios-e-Vendedores
```

## Módulos relacionados

| Módulo | Entrada |
| --- | --- |
| ui-people (fluxo completo UI) | https://github.com/ControleOnline/ui-people/wiki/Cadastro-de-Pessoas-Contatos-Usuarios-e-Vendedores |
| ui-customers (abas PF/PJ) | https://github.com/ControleOnline/ui-customers/wiki/Client-Details-Abas-Navegacao |
| ui-crm | https://github.com/ControleOnline/ui-crm/wiki |
| api-community Home | https://github.com/ControleOnline/api-community/wiki |
| app-community Home | https://github.com/ControleOnline/app-community/wiki |
