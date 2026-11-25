<?php

namespace ControleOnline\Tests\Service\Imports;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class PeopleImportRowTest extends TestCase
{
    private function service(): PeopleService
    {
        $service = $this->getMockBuilder(PeopleService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['discoveryPeople', 'addEmail', 'addPhone', 'discoveryLink'])
            ->getMock();

        // findLinkedPeopleByName(): no employee with that name yet
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()
            ->onlyMethods(['getOneOrNullResult'])->getMock();
        $query->method('getOneOrNullResult')->willReturn(null);
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()
            ->onlyMethods(['join', 'andWhere', 'setParameter', 'setMaxResults', 'getQuery'])->getMock();
        foreach (['join', 'andWhere', 'setParameter', 'setMaxResults'] as $method) {
            $qb->method($method)->willReturnSelf();
        }
        $qb->method('getQuery')->willReturn($query);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        (new \ReflectionProperty(PeopleService::class, 'manager'))->setValue($service, $manager);

        return $service;
    }

    public function testNormalizesHeaderKeyedAndPositionalRows(): void
    {
        $service = $this->service();
        $keyed = $service->normalizeImportRow(['Empresa' => ' ACME ', 'Bairro' => 'Centro', 'Extra' => 'x']);
        $this->assertSame('ACME', $keyed['Empresa']);
        $this->assertSame('Centro', $keyed['Bairro']);
        $this->assertSame('', $keyed['Complemento']);
        $this->assertArrayNotHasKey('Extra', $keyed);

        $positional = $service->normalizeImportRow(['ACME', '1', 'Seg', 'Joao', '', '', 'Rua', 'Centro']);
        $this->assertSame('Joao', $positional['Responsavel']);
        $this->assertSame('Centro', $positional['Bairro']);
        $this->assertSame('', $positional['Cidade']);
    }

    public function testSplitsEmailsAndPhones(): void
    {
        $service = $this->service();
        $this->assertSame(
            ['a@acme.com', 'b@acme.com'],
            $service->splitImportEmails('A@acme.com; b@acme.com, invalido ,a@acme.com')
        );
        $this->assertSame(
            [
                ['ddi' => 55, 'ddd' => 11, 'phone' => '999999999'],
                ['ddi' => 55, 'ddd' => 31, 'phone' => '31360353'],
            ],
            $service->splitImportPhones('(11) 99999-9999, +55 31 3136-0353 / 123')
        );
    }

    public function testImportsCompanyWithContactAndLinksToProvider(): void
    {
        $service = $this->service();
        $provider = new People();
        $company = new People();
        $contact = new People();

        $service->expects($this->exactly(2))->method('discoveryPeople')
            ->willReturnCallback(function ($document, $email, $phone, $name, $type) use ($company, $contact) {
                if ($name === 'ACME LTDA') {
                    $this->assertSame('12345678000199', $document);
                    $this->assertSame('contato@acme.com', $email);
                    $this->assertSame(['ddi' => 55, 'ddd' => 11, 'phone' => '999999999'], $phone);
                    $this->assertSame('J', $type);
                    return $company;
                }
                $this->assertSame('Joao Silva', $name);
                $this->assertSame('F', $type);
                return $contact;
            });
        $service->expects($this->once())->method('addEmail')->with($company, 'fin@acme.com');
        $service->expects($this->once())->method('addPhone')
            ->with($company, ['ddi' => 55, 'ddd' => 11, 'phone' => '88888888']);

        $links = [];
        $service->method('discoveryLink')->willReturnCallback(function ($from, $to, $type) use (&$links) {
            $links[] = [$from, $to, $type];
            return new PeopleLink();
        });

        $result = $service->importFromCSV([
            'Empresa' => 'ACME LTDA',
            'CNPJ' => '12.345.678/0001-99',
            'Responsavel' => 'Joao Silva',
            'Telefones' => '11999999999,1188888888',
            'Emails' => 'contato@acme.com,fin@acme.com',
        ], $provider, 'prospect');

        $this->assertSame($company, $result);
        $this->assertSame([[$company, $contact, 'employee'], [$provider, $company, 'prospect']], $links);
    }

    public function testRejectsRowWithoutIdentification(): void
    {
        $this->expectExceptionMessage('Informe Empresa, CNPJ ou Responsavel.');
        $this->service()->importFromCSV(['Cidade' => 'BH'], new People(), 'prospect');
    }
}
