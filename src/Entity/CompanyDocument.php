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
#[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact'])]
#[ORM\Table(name: 'company_document')]

#[ORM\Entity(repositoryClass: CompanyDocumentRepository::class)]
class CompanyDocument
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'company_document')]
    #[Groups(['company_document:read', 'company_document:write'])]
    private People $people;

    #[ORM\JoinColumn(name: 'document_type_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: DocumentType::class, inversedBy: 'company_document')]
    #[Groups(['company_document:read', 'company_document:write'])]
    private People $document_type;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of people
     */
    public function getPeople(): People
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople(People $people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get the value of document_type
     */
    public function getDocumentType(): People
    {
        return $this->document_type;
    }

    /**
     * Set the value of document_type
     */
    public function setDocumentType(People $document_type): self
    {
        $this->document_type = $document_type;

        return $this;
    }
}