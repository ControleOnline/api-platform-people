<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Load People by primary key, bypassing Doctrine filters and query extensions.
 *
 * Staging: people listed via people_links (e.g. 105790/105794) returned 404 on
 * GET/PUT /people/{id} while association load still worked — item provider path
 * was filtered. Native PK lookup keeps Contatos edit working (#688).
 */
final class PeopleItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?People
    {
        $id = $this->resolveId($uriVariables['id'] ?? null);
        if ($id <= 0) {
            return null;
        }

        $filters = $this->em->getFilters();
        $disabled = [];
        foreach (array_keys($filters->getEnabledFilters()) as $name) {
            $filters->disable($name);
            $disabled[] = $name;
        }

        try {
            $people = $this->em->find(People::class, $id);
            if ($people instanceof People) {
                return $people;
            }

            // Native fallback if unit-of-work/filters still interfere.
            $exists = $this->em->getConnection()->fetchOne(
                'SELECT id FROM people WHERE id = ?',
                [$id]
            );
            if (!$exists) {
                return null;
            }

            // Clear and retry find after confirming the row exists.
            $this->em->clear(People::class);
            $people = $this->em->find(People::class, $id);

            return $people instanceof People ? $people : null;
        } finally {
            foreach ($disabled as $name) {
                if (!$filters->isEnabled($name)) {
                    try {
                        $filters->enable($name);
                    } catch (\Throwable) {
                        // ignore re-enable failures
                    }
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
