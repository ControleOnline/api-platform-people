<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

use ControleOnline\Repository\DocumentFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new Put(
            security: "is_granted('ROLE_HUMAN')",
            denormalizationContext: ['groups' => ['document_file:write']]
        ),
        new Delete(security: "is_granted('ROLE_HUMAN')"),
        new Post(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        new GetCollection(security: "is_granted('ROLE_HUMAN')")
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['document_file:read']],
    denormalizationContext: ['groups' => ['document_file:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['document' => 'exact', 'file' => 'exact', 'file.fileType' => 'exact'])]
#[ORM\Table(name: 'document_file')]
#[ORM\Index(name: 'document_id', columns: ['document_id'])]
#[ORM\Index(name: 'file_id', columns: ['file_id'])]
#[ORM\UniqueConstraint(name: 'document_file_unique', columns: ['document_id', 'file_id'])]
#[ORM\Entity(repositoryClass: DocumentFileRepository::class)]
class DocumentFile
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['document:read', 'document_file:read', 'people:read'])]
    private int $id = 0;

    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'documentFiles')]
    #[Groups(['document_file:read', 'document_file:write'])]
    private Document $document;

    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: File::class)]
    #[Groups(['document:read', 'document_file:read', 'document_file:write', 'people:read'])]
    private File $file;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function setDocument(Document $document): self
    {
        $this->document = $document;
        return $this;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;
        return $this;
    }
}
