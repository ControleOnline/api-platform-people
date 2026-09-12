<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Service\AdsenseSiteChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetPeopleDomainAdsenseCheckAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdsenseSiteChecker $checker,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $domainId = (int) preg_replace('/\D+/', '', (string) $request->attributes->get('id'));
        $peopleDomain = $this->em->getRepository(PeopleDomain::class)->find($domainId);

        if (!$peopleDomain instanceof PeopleDomain || trim((string) $peopleDomain->getDomain()) === '') {
            throw new NotFoundHttpException('People domain not found.');
        }

        return new JsonResponse($this->checker->check((string) $peopleDomain->getDomain()));
    }
}
