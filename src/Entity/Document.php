<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_CLIENT')"),
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')",
            validationContext: ['groups' => ['document:read']],
            denormalizationContext: ['groups' => ['document:write']]
        ),
        new Post(securityPostDenormalize: "is_granted('ROLE_CLIENT')"),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]

#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
#[ORM\Table(name: 'document')]
#[ORM\Index(name: 'type_2', columns: ['document_type_id'])]
#[ORM\Index(name: 'file_id', columns: ['file_id'])]
#[ORM\Index(name: 'type', columns: ['people_id', 'document_type_id'])]
#[ORM\UniqueConstraint(name: 'doc', columns: ['document', 'document_type_id'])]

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[ORM\Column(name: 'document', type: 'bigint', nullable: false)]
    #[Groups(['people:read', 'document:read', 'carrier:read', 'provider:read', 'document:write'])]
    private int $document;

    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'document')]
    #[Groups(['document:read', 'document:write'])]
    private People $people;

    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: File::class)]
    #[Groups(['document:read', 'document:write'])]
    private ?File $file = null;

    #[ORM\JoinColumn(name: 'document_type_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: DocumentType::class)]
    #[Groups(['people:read', 'document:read', 'carrier:read', 'document:write'])]
    private DocumentType $documentType;

    public function getId(): int
    {
        return $this->id;
    }

    public function setDocument(int $document): self
    {
        $this->document = $document;
        return $this;
    }

    public function getDocument(): string
    {
        $document = (string) $this->document;
        if ($this->getDocumentType()->getDocumentType() === 'CPF') {
            return str_pad($document, 11, '0', STR_PAD_LEFT);
        }
        if ($this->getDocumentType()->getDocumentType() === 'CNPJ') {
            return str_pad($document, 14, '0', STR_PAD_LEFT);
        }
        return $document;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setPeople(People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getPeople(): People
    {
        return $this->people;
    }

    public function setDocumentType(DocumentType $documentType): self
    {
        $this->documentType = $documentType;
        return $this;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->documentType;
    }
}