<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Repository\CompanyDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['company_document:read']],
    denormalizationContext: ['groups' => ['company_document:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['peopleType' => 'exact'])]
#[ORM\Table(name: 'company_document')]
#[ORM\Entity(repositoryClass: CompanyDocumentRepository::class)]
class CompanyDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'company_document')]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['company_document:read', 'company_document:write'])]
    private People $people;

    #[ORM\ManyToOne(targetEntity: DocumentType::class, inversedBy: 'company_document')]
    #[ORM\JoinColumn(name: 'document_type_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['company_document:read', 'company_document:write'])]
    private DocumentType $document_type;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPeople(): People
    {
        return $this->people;
    }

    public function setPeople(People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->document_type;
    }

    public function setDocumentType(DocumentType $document_type): self
    {
        $this->document_type = $document_type;
        return $this;
    }
}
