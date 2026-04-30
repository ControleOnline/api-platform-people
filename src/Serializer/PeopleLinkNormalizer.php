<?php

namespace ControleOnline\Serializer;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PeopleLinkNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'people_link_normalizer_already_called';

    public function __construct(private PeopleLinkService $peopleLinkService) {}

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;
        $normalized = $this->normalizer->normalize($object, $format, $context);

        if (!is_array($normalized) || !$object instanceof PeopleLink) {
            return $normalized;
        }

        if (!$this->peopleLinkService->canViewSalesmanCommissions($object)) {
            unset($normalized['comission'], $normalized['minimum_comission']);
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PeopleLink && !isset($context[self::ALREADY_CALLED]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PeopleLink::class => false,
        ];
    }
}
