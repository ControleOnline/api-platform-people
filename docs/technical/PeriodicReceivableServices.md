# Periodic receivable services — commission & royalties

Documentação do contrato entregue em `api-platform-people#13` (filha de `#11`).

## Objetivo

Padronizar a agregação de faturas periódicas de **comissão** (vendedor) e **royalties** (franquia) sob a mesma interface, usando `PeopleLink.closing_period` e `PeopleLink.payment_term_days` (#12).

## Componentes

| Classe | Papel |
| --- | --- |
| `Service\Contract\PeriodicReceivableServiceInterface` | Contrato comum |
| `Service\AbstractPeriodicReceivableService` | Período, due date, `computeAmount` |
| `Service\CommissionService` | Tipo de fatura `comission` — vendedor recebe da empresa |
| `Service\RoyaltiesService` | Tipo de fatura `royalties` — franqueado paga franqueadora |

## Contrato

- `supports(object $sourceInvoice): bool`
- `resolveOpenInvoice(PeopleLink, DateTimeInterface): object` — uma fatura aberta por vínculo + janela de fechamento
- `attachOrder(object $aggregateInvoice, object $order): void` — rastreio pedido → fatura
- `computeAmount(float, PeopleLink, ?PeopleLink $clientOverride): float` — `% comission` com piso `minimum_comission` e override por cliente
- `resolveDueDate` / `resolvePeriodStart` / `resolvePeriodEnd`
- `getInvoiceType(): string`

## Fechamento (`closing_period`)

Valores: `daily` | `weekly` | `biweekly` | `monthly` (default).

- **weekly**: segunda → domingo da semana ISO
- **biweekly**: dias 1–15 e 16–fim do mês
- **monthly**: 1º → último dia do mês

`due date` = fim do período + `payment_term_days`.

## Wiring

Este pacote **não** depende de `api-platform-financial` nem de `api-platform-orders`.  
`CommissionService` / `RoyaltiesService` recebem callables opcionais no construtor:

- `invoiceResolver(PeopleLink, DateTimeInterface, string $type): object`
- `orderAttacher(object $invoice, object $order): void`
- `sourceTypeReader(object $sourceInvoice): ?string`

A composição real (Doctrine repositories, tipo de invoice, vínculo order) deve ser feita no root `api-community` / financial (task filha #14).

## Testes

`tests/Service/PeriodicReceivableServiceTest.php` cobre:

- implementação da interface pelos dois services
- cálculo de comissão + mínimo + override
- períodos weekly / monthly / biweekly + due date
- agregação de 2 pedidos na mesma fatura mensal

## Relação com outras tasks

- **#12** — colunas `closing_period` / `payment_term_days` em `people_link`
- **#14** — disparo na entrada de fatura / pedido
- **#11** — task pai
