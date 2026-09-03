<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Repository\DocumentTypeRepository;
use ControleOnline\Entity\City;
use ControleOnline\Entity\State;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_HUMAN\')'),
        new GetCollection(security: 'is_granted(\'ROLE_HUMAN\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['document_type:read']],
    denormalizationContext: ['groups' => ['document_type:write']]
)]
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

    #[ORM\Column(name: 'owner_type', type: 'string', length: 20, nullable: false, options: ['default' => 'people'])]
    #[Groups(['document:read', 'document_type:read', 'document_type:write'])]
    private string $ownerType = 'people';

    #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: State::class)]
    #[Groups(['document:read', 'document_type:read', 'document_type:write'])]
    private ?State $state = null;

    #[ORM\JoinColumn(name: 'city_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: City::class)]
    #[Groups(['document:read', 'document_type:read', 'document_type:write'])]
    private ?City $city = null;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact'])]

    #[ORM\Column(name: 'people_type', type: 'string', length: 1, nullable: false)]
    #[Groups(['people:read', 'document:read', 'document_type:read'])]
    private string $peopleType;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['company_document.people' => 'exact'])]
    #[ORM\OneToMany(targetEntity: CompanyDocument::class, mappedBy: 'document_type')]
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

    public function setOwnerType(string $ownerType): self
    {
        $ownerType = strtolower(trim($ownerType));
        if (!in_array($ownerType, ['people', 'vehicle'], true)) {
            throw new \InvalidArgumentException('ownerType must be people or vehicle.');
        }
        $this->ownerType = $ownerType;
        return $this;
    }

    public function getOwnerType(): string { return $this->ownerType; }
    public function setState(?State $state): self { $this->state = $state; return $this; }
    public function getState(): ?State { return $this->state; }
    public function setCity(?City $city): self { $this->city = $city; return $this; }
    public function getCity(): ?City { return $this->city; }

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
