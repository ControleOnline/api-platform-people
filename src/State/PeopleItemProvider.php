<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Load People by primary key without Doctrine query extensions.
 *
 * Contatos edit (PUT /people/{id}) was returning "Item not found for /people/{id}"
 * when collection-oriented security/active filters leaked into item reads or when
 * HydratedReadProvider item path returned null. Direct find() keeps cadastral
 * update available for collaborators listed via people_links (app-community#688).
 */
final class PeopleItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?People
    {
        $raw = $uriVariables['id'] ?? null;
        if (is_array($raw)) {
            $raw = $raw['id'] ?? $raw['@id'] ?? reset($raw);
        }
        $id = (int) preg_replace('/\D+/', '', (string) $raw);
        if ($id <= 0) {
            return null;
        }

        $people = $this->em->find(People::class, $id);

        return $people instanceof People ? $people : null;
    }
}
