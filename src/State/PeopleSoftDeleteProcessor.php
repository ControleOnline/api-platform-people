<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Maps HTTP DELETE /people/{id} to soft-delete (deleted=true, deleted_at=now).
 * Also disables all people_links where this person is the linked people (enable=0)
 * so employee/collaborator listings and ERP vínculos stop exposing the removed person.
 * Physical DELETE is never performed.
 */
final class PeopleSoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof People) {
            return $data;
        }

        if ($operation instanceof DeleteOperationInterface || ($operation->getMethod() ?? '') === 'DELETE') {
            if ($data->isDeleted()) {
                throw new BadRequestHttpException('People already soft-deleted.');
            }

            $data->setDeleted(true);
            $this->em->persist($data);

            // Disable ERP vínculos (people_link) for this person as linked people.
            // Company-side links (where this person is the company) are left intact.
            $links = $this->em->getRepository(PeopleLink::class)->findBy(['people' => $data]);
            foreach ($links as $link) {
                if (!$link instanceof PeopleLink) {
                    continue;
                }
                if ((int) $link->getEnabled() !== 0) {
                    $link->setEnabled(0);
                    $this->em->persist($link);
                }
            }

            $this->em->flush();

            return null;
        }

        return $data;
    }
}
