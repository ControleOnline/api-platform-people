<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleCompanyScopeGuard;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Load People by PK after company-scope AuthZ (app-community#688).
 *
 * Soft-delete filter is the only one disabled, so enable=false contacts stay
 * editable. Tenant/company filters stay on. No unscoped SQL fallback.
 */
final class PeopleItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PeopleCompanyScopeGuard $scope,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?People
    {
        $id = $this->resolveId($uriVariables['id'] ?? null);
        if ($id <= 0) {
            return null;
        }

        $this->scope->assertAccessible($id);

        $filters = $this->em->getFilters();
        $disabledSoftDelete = false;
        if (method_exists($filters, 'isEnabled') && $filters->isEnabled('softdeleteable')) {
            $filters->disable('softdeleteable');
            $disabledSoftDelete = true;
        }

        try {
            $people = $this->em->find(People::class, $id);

            return $people instanceof People ? $people : null;
        } finally {
            if ($disabledSoftDelete && !$filters->isEnabled('softdeleteable')) {
                try {
                    $filters->enable('softdeleteable');
                } catch (\Throwable) {
                    // ignore re-enable failures in tests
                }
            }
        }
    }

    private function resolveId(mixed $raw): int
    {
        if (is_array($raw)) {
            $raw = $raw['id'] ?? $raw['@id'] ?? reset($raw);
        }

        return (int) preg_replace('/\D+/', '', (string) $raw);
    }
}
