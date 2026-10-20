<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * POST /people_links is idempotent on (company, people, linkType).
 * Creating a contact via POST /people already creates the link in postPersist;
 * the front may also POST people_links → UNIQUE franchisee_id (company_id, people_id, link_type).
 */
final class PeopleLinkUpsertProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PeopleLinkService $peopleLinkService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof PeopleLink) {
            return $data;
        }

        $company = $data->getCompany();
        $people = $data->getPeople();
        $linkType = $data->getLinkType();

        if ($company !== null && $people !== null && $linkType !== null && $linkType !== '') {
            $existing = $this->em->getRepository(PeopleLink::class)->findOneBy([
                'company' => $company,
                'people' => $people,
                'linkType' => $linkType,
            ]);

            if ($existing instanceof PeopleLink) {
                if ($this->em->contains($data) && $data !== $existing) {
                    $this->em->detach($data);
                }

                // AuthZ on the persisted row
                $this->peopleLinkService->preUpdate($existing);

                $existing->setEnabled($data->getEnabled());
                // Optional write fields from payload (do not reset commissions to 0 unintentionally
                // unless the payload explicitly carries values — keep existing when new defaults).
                if ($data->getComission() != 0 || $existing->getComission() == 0) {
                    $existing->setComission($data->getComission());
                }
                if ($data->getMinimumComission() != 0 || $existing->getMinimumComission() == 0) {
                    $existing->setMinimumComission($data->getMinimumComission());
                }
                $existing->setClosingPeriod($data->getClosingPeriod());
                $existing->setPaymentTermDays($data->getPaymentTermDays());

                $this->em->persist($existing);
                $this->em->flush();

                return $existing;
            }
        }

        $this->peopleLinkService->prePersist($data);
        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
