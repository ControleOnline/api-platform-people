<?php

namespace ControleOnline\Tests\Serializer;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\People;
use ControleOnline\Serializer\PeopleCheckoutLookupNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PeopleCheckoutLookupNormalizerTest extends TestCase
{
    private function normalizer(?Request $request): PeopleCheckoutLookupNormalizer
    {
        $stack = new RequestStack();
        if ($request) {
            $stack->push($request);
        }

        return new PeopleCheckoutLookupNormalizer($stack);
    }

    private function people(): People
    {
        $cpfType = $this->createMock(DocumentType::class);
        $cpfType->method('getDocumentType')->willReturn('CPF');
        $rgType = $this->createMock(DocumentType::class);
        $rgType->method('getDocumentType')->willReturn('RG');

        $cpf = $this->createMock(Document::class);
        $cpf->method('getDocumentType')->willReturn($cpfType);
        $cpf->method('getDocument')->willReturn('52998224725');
        $rg = $this->createMock(Document::class);
        $rg->method('getDocumentType')->willReturn($rgType);
        $rg->method('getDocument')->willReturn('123456789');

        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn(8);
        $people->method('getName')->willReturn('Maria');
        $people->method('getAlias')->willReturn('Silva');
        $people->method('getPeopleType')->willReturn('F');
        $people->method('getDocument')->willReturn([$cpf, $rg]);

        return $people;
    }

    public function testOnlySupportsGetWithCheckoutLookupContext(): void
    {
        $people = $this->people();

        self::assertTrue($this->normalizer(new Request(['context' => 'checkout-cpf-lookup']))->supportsNormalization($people));
        self::assertFalse($this->normalizer(new Request(['context' => 'other']))->supportsNormalization($people));
        self::assertFalse($this->normalizer(new Request())->supportsNormalization($people));
        self::assertFalse($this->normalizer(new Request(['context' => 'checkout-cpf-lookup'], [], [], [], [], ['REQUEST_METHOD' => 'POST']))->supportsNormalization($people));
        self::assertFalse($this->normalizer(null)->supportsNormalization($people));
        self::assertFalse($this->normalizer(new Request(['context' => 'checkout-cpf-lookup']))->supportsNormalization(new \stdClass()));
    }

    public function testReturnsOnlyIdentificationAndCpf(): void
    {
        $data = $this->normalizer(new Request(['context' => 'checkout-cpf-lookup']))->normalize($this->people());

        self::assertSame(['@id', '@type', 'id', 'name', 'alias', 'peopleType', 'document'], array_keys($data));
        self::assertSame([['document' => '52998224725', 'documentType' => ['documentType' => 'CPF']]], $data['document']);
        foreach (['phone', 'email', 'address'] as $leak) {
            self::assertArrayNotHasKey($leak, $data);
        }
    }
}
