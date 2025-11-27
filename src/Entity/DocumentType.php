<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Repository\DocumentTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['document_type:read']],
    denormalizationContext: ['groups' => ['document_type:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact'])]
#[ORM\Table(name: 'document_type')]
#[ORM\Entity(repositoryClass: DocumentTypeRepository::class)]
class DocumentType
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[ORM\Column(name: 'document_type', type: 'string', length: 50, nullable: false)]
    #[Groups(['people:read', 'document:read', 'document_type:read', 'carrier:read'])]
    private string $documentType;

    #[ORM\Column(name: 'people_type', type: 'string', length: 1, nullable: false)]
    #[Groups(['people:read', 'document:read', 'document_type:read'])]
    private string $peopleType;

    #[ORM\OneToMany(targetEntity: CompanyDocument::class, mappedBy: 'document_type')]
    #[Groups(['document_type:read'])]
    private $company_document;

    public function getId(): int
    {
        return $this->id;
    }

    public function setDocumentType(string $documentType): self
    {
        $this->documentType = $documentType;
        return $this;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function setPeopleType(string $peopleType): self
    {
        $this->peopleType = $peopleType;
        return $this;
    }

    public function getPeopleType(): string
    {
        return $this->peopleType;
    }

    public function getCompanyDocument()
    {
        return $this->company_document;
    }

    public function setCompanyDocument($company_document): self
    {
        $this->company_document = $company_document;
        return $this;
    }
}
