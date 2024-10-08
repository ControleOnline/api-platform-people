<?php

namespace ControleOnline\Entity;

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
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="document", uniqueConstraints={@ORM\UniqueConstraint (name="doc", columns={"document", "document_type_id"})}, indexes={@ORM\Index (name="type_2", columns={"document_type_id"}), @ORM\Index(name="file_id", columns={"file_id"}), @ORM\Index(name="type", columns={"people_id", "document_type_id"})})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\DocumentRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['document:read']], denormalizationContext: ['groups' => ['document:write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
class Document
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var integer
     *
     * @ORM\Column(name="document", type="bigint", nullable=false)
     * @Groups({"people:read", "document:read",  "carrier:read", "provider:read"})
     */
    private $document;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="document")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"document:read"})
     */
    private $people;
    /**
     * @var \ControleOnline\Entity\File
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $file;
    /**
     * @var \ControleOnline\Entity\DocumentType
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\DocumentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="document_type_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people:read", "document:read", "carrier:read"})
     */
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
