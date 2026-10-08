# PeriodicReceivableDispatcher — disparo na entrada de fatura

Documentação da entrega `api-platform-people#14` (filha de `#11`).

## Objetivo

Ao **entrar uma fatura comercial** no sistema, disparar verificação e agregar comissão / royalties na fatura periódica aberta do `PeopleLink`, conforme `closing_period` e `payment_term_days`.

## Componente

| Classe | Papel |
| --- | --- |
| `Service\PeriodicReceivableDispatcher` | Orquestra `CommissionService` + `RoyaltiesService` (e futuros) |

## Contrato de uso (wiring)

Chamar **depois** de persistir a fatura de origem (ex.: `InvoiceService::postPersist` em `api-platform-financial`):

```php
// Exemplo de composição no root / financial (não nesta package)
$dispatcher = new PeriodicReceivableDispatcher(
    services: [
        new CommissionService($invoiceResolver, $orderAttacher, $sourceTypeReader),
        new RoyaltiesService($invoiceResolver, $orderAttacher, $sourceTypeReader),
    ],
    linkResolver: function (object $invoice, string $role): ?PeopleLink {
        // resolver PeopleLink salesman|franchisee a partir do pedido/empresa da fatura
    },
    amountReader: fn (object $invoice): float => (float) $invoice->getPrice(),
    orderReader: function (object $invoice): ?object {
        // primeiro Order vinculado via OrderInvoice
    },
    referenceDateReader: fn (object $invoice): \DateTimeInterface => $invoice->getIssueDate() ?? new \DateTimeImmutable(),
);

$dispatcher->dispatch($invoice);
```

A package **people** permanece sem dependência hard de `financial` / `orders`.

## Critérios atendidos nesta entrega

- [x] Serviço de disparo com lista de `PeriodicReceivableServiceInterface`
- [x] Agregação por service (resolveOpenInvoice + attachOrder + computeAmount)
- [x] Fail-open: falha de um service não quebra o fluxo da fatura origem
- [x] Testes unitários do dispatcher (suporte, agregação de 2 pedidos, ausência de link)
- [x] Documentação do fluxo e ponto de integração

## Dependências

- `#12` — colunas `closing_period` / `payment_term_days`
- `#13` — `PeriodicReceivableServiceInterface` + `CommissionService` + `RoyaltiesService`
- Wiring real (Doctrine resolvers) no root `api-community` / `api-platform-financial` (próximo passo operacional)

## Relação com UI

Tipos de invoice alinhados a `ui-crm#21+`: `comission`, `royalties`.
