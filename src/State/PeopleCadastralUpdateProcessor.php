<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleCompanyScopeGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * PUT /people/{id} cadastral update with company-scope AuthZ (#688).
 *
 * read:false avoids API Platform "Item not found" before the processor.
 * AuthZ runs first. Soft-delete is the only disabled filter. No raw UPDATE.
 */
final class PeopleCadastralUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PeopleCompanyScopeGuard $scope,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): People
    {
        $id = $this->resolveId($uriVariables['id'] ?? null);
        if ($id <= 0) {
            throw new NotFoundHttpException('People id is required.');
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
        } finally {
            if ($disabledSoftDelete && !$filters->isEnabled('softdeleteable')) {
                $filters->enable('softdeleteable');
            }
        }

        if (!$people instanceof People) {
            throw new NotFoundHttpException(sprintf('Item not found for "/people/%d".', $id));
        }

        $request = $context['request'] ?? null;
        $payload = [];
        if (is_object($request) && method_exists($request, 'getContent')) {
            $decoded = json_decode((string) $request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if ($data instanceof People) {
            if ($data->getName()) {
                $people->setName($data->getName());
            }
            if ($data->getAlias()) {
                $people->setAlias($data->getAlias());
            }
            if (method_exists($data, 'getPeopleType') && $data->getPeopleType()) {
                $people->setPeopleType($data->getPeopleType());
            }
            if (method_exists($data, 'getFoundationDate') && $data->getFoundationDate()) {
                $people->setFoundationDate($data->getFoundationDate());
            }
        }

        if (isset($payload['name']) && is_string($payload['name'])) {
            $people->setName(trim($payload['name']));
        }
        if (isset($payload['alias']) && is_string($payload['alias'])) {
            $people->setAlias(trim($payload['alias']));
        }
        if (isset($payload['peopleType']) && is_string($payload['peopleType'])) {
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
