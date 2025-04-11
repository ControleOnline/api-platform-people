<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * Document
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['document:read']], denormalizationContext: ['groups' => ['document:write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
#[ORM\Table(name: 'document')]
#[ORM\Index(name: 'type_2', columns: ['document_type_id'])]
#[ORM\Index(name: 'file_id', columns: ['file_id'])]
#[ORM\Index(name: 'type', columns: ['people_id', 'document_type_id'])]
#[ORM\UniqueConstraint(name: 'doc', columns: ['document', 'document_type_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\DocumentRepository::class)]
class Document
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var integer
     *
     * @Groups({"people:read", "document:read",  "carrier:read", "provider:read"})
     */
    #[ORM\Column(name: 'document', type: 'bigint', nullable: false)]
    private $document;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"document:read"})
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class, inversedBy: 'document')]
    private $people;
    /**
     * @var \ControleOnline\Entity\File
     */
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\File::class)]
    private $file;
    /**
     * @var \ControleOnline\Entity\DocumentType
     *
     * @Groups({"people:read", "document:read", "carrier:read"})
     */
    #[ORM\JoinColumn(name: 'document_type_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\DocumentType::class)]
    private $documentType;
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set document
     *
     * @param integer $document
     * @return Document
     */
    public function setDocument($document)
    {
        $this->document = $document;
        return $this;
    }
    /**
     * Get document
     *
     * @return integer
     */
    public function getDocument()
    {
        $document = (string) $this->document;
        // CPF
        if ($this->getDocumentType()->getDocumentType() == 'CPF') {
            return str_pad($document, 11, '0', STR_PAD_LEFT);
        }
        // CNPJ
        if ($this->getDocumentType()->getDocumentType() == 'CNPJ') {
            return str_pad($document, 14, '0', STR_PAD_LEFT);
        }
        return $this->document;
    }
    /**
     * Set file
     *
     * @param \ControleOnline\Entity\File $file
     * @return People
     */
    public function setFile(\ControleOnline\Entity\File $file = null)
    {
        $this->file = $file;
        return $this;
    }
    /**
     * Get file
     *
     * @return \ControleOnline\Entity\File
     */
    public function getFile()
    {
        return $this->file;
    }
    /**
     * Set people
     *     
     * @return Document
     */
    public function setPeople($people)
    {
        $this->people = $people;
        return $this;
    }
    /**
     * Get people          
     */
    public function getPeople()
    {
        return $this->people;
    }
    /**
     * Set documentType
     *
     * @param \ControleOnline\Entity\DocumentType $documentType
     * @return Document
     */
    public function setDocumentType(\ControleOnline\Entity\DocumentType $documentType = null)
    {
        $this->documentType = $documentType;
        return $this;
    }
    /**
     * Get documentType
     *
     * @return \ControleOnline\Entity\DocumentType
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }
}
