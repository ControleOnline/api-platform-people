<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * PUT /people/{id} cadastral update without relying on the item read provider.
 *
 * API Platform was surfacing "Item not found for /people/{id}" on Contatos edit
 * before the processor ran. With read:false this processor loads by PK and
 * applies writable cadastral fields (app-community#688).
 */
final class PeopleCadastralUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): People
    {
        $id = $this->resolveId($uriVariables['id'] ?? null);
        if ($id <= 0) {
            throw new NotFoundHttpException('People id is required.');
        }

        // Disable SQL filters (soft-delete etc.) so enable=false / edge rows remain editable.
        $filters = $this->em->getFilters();
        $disabled = [];
        if ($filters->isEnabled('softdeleteable')) {
            $filters->disable('softdeleteable');
            $disabled[] = 'softdeleteable';
        }

        try {
            $people = $this->em->find(People::class, $id);
            if (!$people instanceof People) {
                $people = $this->em->getRepository(People::class)
                    ->createQueryBuilder('p')
                    ->where('p.id = :id')
                    ->setParameter('id', $id)
                    ->getQuery()
                    ->getOneOrNullResult();
            }
        } finally {
            foreach ($disabled as $name) {
                if (!$filters->isEnabled($name)) {
                    $filters->enable($name);
                }
            }
        }
        if (!$people instanceof People) {
            $exists = $this->em->getConnection()->fetchOne('SELECT id FROM people WHERE id = ?', [$id]);
            if (!$exists) {
                throw new NotFoundHttpException(sprintf('Item not found for "/people/%d".', $id));
            }
            // Row exists but ORM could not hydrate — apply UPDATE via SQL.
            $payload = [];
            $request = $context['request'] ?? null;
            if (is_object($request) && method_exists($request, 'getContent')) {
                $payload = json_decode((string) $request->getContent(), true) ?: [];
            }
            $sets = [];
            $params = [];
            if (isset($payload['name']) && is_string($payload['name'])) {
                $sets[] = 'name = ?';
                $params[] = trim($payload['name']);
            }
            if (isset($payload['alias']) && is_string($payload['alias'])) {
                $sets[] = 'alias = ?';
                $params[] = trim($payload['alias']);
            }
            if (array_key_exists('enable', $payload)) {
                $sets[] = 'enable = ?';
                $params[] = filter_var($payload['enable'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            if (isset($payload['peopleType']) && is_string($payload['peopleType'])) {
                $sets[] = 'people_type = ?';
                $params[] = strtoupper(substr(trim($payload['peopleType']), 0, 1)) ?: 'F';
            }
            if ($sets) {
                $params[] = $id;
                $this->em->getConnection()->executeStatement(
                    'UPDATE people SET ' . implode(', ', $sets) . ' WHERE id = ?',
                    $params
                );
            }
            $this->em->clear(People::class);
            $people = $this->em->find(People::class, $id);
            if (!$people instanceof People) {
                // Return a minimal managed instance if still unreadable.
                $people = $this->em->getReference(People::class, $id);
            }
        }

        if ($data instanceof People) {
            if ($data->getName() !== null && $data->getName() !== '') {
                $people->setName($data->getName());
            }
            if ($data->getAlias() !== null && $data->getAlias() !== '') {
                $people->setAlias($data->getAlias());
            }
            if (method_exists($data, 'getPeopleType') && $data->getPeopleType()) {
                $people->setPeopleType($data->getPeopleType());
            }
            if (method_exists($data, 'getEnabled') || method_exists($data, 'getEnable')) {
                // enable may be 0/false — always apply when input object carries the field via request
            }
            if (method_exists($data, 'getFoundationDate') && $data->getFoundationDate()) {
                $people->setFoundationDate($data->getFoundationDate());
            }
        }

        // Prefer raw request body for enable (false is a valid value).
        $request = $context['request'] ?? null;
        if ($request === null) {
            $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        }
        if (is_object($request) && method_exists($request, 'getContent')) {
            $payload = json_decode((string) $request->getContent(), true);
            if (is_array($payload)) {
                if (array_key_exists('name', $payload) && is_string($payload['name'])) {
                    $people->setName(trim($payload['name']));
                }
                if (array_key_exists('alias', $payload) && is_string($payload['alias'])) {
                    $people->setAlias(trim($payload['alias']));
                }
                if (array_key_exists('peopleType', $payload) && is_string($payload['peopleType'])) {
                    $people->setPeopleType(strtoupper(substr(trim($payload['peopleType']), 0, 1)) ?: 'F');
                }
                if (array_key_exists('enable', $payload)) {
                    $people->setEnabled($payload['enable']);
                }
                if (!empty($payload['foundationDate']) && method_exists($people, 'setFoundationDate')) {
                    try {
                        $people->setFoundationDate(new \DateTime((string) $payload['foundationDate']));
                    } catch (\Exception) {
                        // ignore invalid date
                    }
                }
            }
        }

        $this->em->persist($people);
        $this->em->flush();

        return $people;
    }

    private function resolveId(mixed $raw): int
    {
        if (is_array($raw)) {
            $raw = $raw['id'] ?? $raw['@id'] ?? reset($raw);
        }

        return (int) preg_replace('/\D+/', '', (string) $raw);
    }
}
