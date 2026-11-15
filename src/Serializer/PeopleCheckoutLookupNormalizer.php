<?php

namespace ControleOnline\Serializer;

use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Checkout CPF lookup (context=checkout-cpf-lookup) can match people that are not linked to the
 * caller's company, so it must never expose contact data: only the minimum needed to identify
 * the person and confirm the CPF is returned (no phone, email, address or other documents).
 */
class PeopleCheckoutLookupNormalizer implements NormalizerInterface
{
    public function __construct(private RequestStack $requestStack) {}

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $documents = [];
        foreach ($object->getDocument() as $document) {
            $type = (string) $document->getDocumentType()?->getDocumentType();
            if (strtoupper($type) !== 'CPF') {
                continue;
            }
            $documents[] = [
                'document' => $document->getDocument(),
                'documentType' => ['documentType' => 'CPF'],
            ];
        }

        return [
            '@id' => '/people/' . $object->getId(),
            '@type' => 'People',
            'id' => $object->getId(),
            'name' => $object->getName(),
            'alias' => $object->getAlias(),
            'peopleType' => $object->getPeopleType(),
            'document' => $documents,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!$data instanceof People) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();

        return $request !== null
            && $request->isMethod('GET')
            && trim((string) $request->query->get('context', '')) === PeopleService::CHECKOUT_CPF_LOOKUP_CONTEXT;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [People::class => false];
    }
}
