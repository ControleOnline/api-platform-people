<?php

namespace ControleOnline\Tests\Serializer;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use ControleOnline\Serializer\PeopleLinkNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PeopleLinkNormalizerTest extends TestCase
{
    public function testNormalizerHidesCommissionFieldsWhenUserCannotViewThem(): void
    {
        $peopleLinkService = $this->createMock(PeopleLinkService::class);
        $peopleLinkService
            ->method('canViewSalesmanCommissions')
            ->willReturn(false);

        $innerNormalizer = $this->createMock(NormalizerInterface::class);
        $innerNormalizer
            ->method('normalize')
            ->willReturn([
                'id' => 88,
                'comission' => 12.5,
                'minimum_comission' => 4.0,
            ]);

        $normalizer = new PeopleLinkNormalizer($peopleLinkService);
        $normalizer->setNormalizer($innerNormalizer);

        $normalized = $normalizer->normalize($this->createSalesmanClientLink());

        self::assertSame(['id' => 88], $normalized);
    }

    public function testNormalizerPreservesCommissionFieldsForAuthorizedReaders(): void
    {
        $peopleLinkService = $this->createMock(PeopleLinkService::class);
        $peopleLinkService
            ->method('canViewSalesmanCommissions')
            ->willReturn(true);

        $innerNormalizer = $this->createMock(NormalizerInterface::class);
        $innerNormalizer
            ->method('normalize')
            ->willReturn([
                'id' => 88,
                'comission' => 12.5,
                'minimum_comission' => 4.0,
            ]);

        $normalizer = new PeopleLinkNormalizer($peopleLinkService);
        $normalizer->setNormalizer($innerNormalizer);

        $normalized = $normalizer->normalize($this->createSalesmanClientLink());

        self::assertSame(
            [
                'id' => 88,
                'comission' => 12.5,
                'minimum_comission' => 4.0,
            ],
            $normalized
        );
    }

    private function createSalesmanClientLink(): PeopleLink
    {
        $link = new PeopleLink();
        $link->setLinkType('sellers-client');

        return $link;
    }
}
