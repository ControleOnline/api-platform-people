<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Soft-delete People instead of physical DELETE (app-community#374).
 * DELETE /people/{id} sets deleted=true + deletedAt; never removes the row.
 */
final class PeopleSoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
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
            $this->em->flush();

            return null;
        }

        return $data;
    }
}
