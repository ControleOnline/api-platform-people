<?php

namespace ControleOnline\Entity\Particulars;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="particulars_type")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ParticularsTypeRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['particularstype_write']],
            denormalizationContext: ['groups' => ['particularstype_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['particularstype_read']],
    denormalizationContext: ['groups' => ['particularstype_write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact', 'context' => 'exact', 'fieldType' => 'exact'])]
class ParticularsType
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @Groups({"particularstype_read", "particulars_read"})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="type_value", type="string", length=255, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $typeValue;
    /**
     * @ORM\Column(name="field_type", type="string", length=255, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $fieldType;
    /**
     * @ORM\Column(name="context", type="string", length=255, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $context;
    /**
     * @ORM\Column(name="required", type="string", length=255, nullable=true)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $required;
    /**
     * @ORM\Column(name="field_configs", type="string", nullable=true)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $fieldConfigs;
    /**
     * Constructor
     */
    public function __construct()
    {
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function setTypeValue(string $value): self
    {
        $this->typeValue = $value;
        return $this;
    }
    public function getTypeValue(): string
    {
        return $this->typeValue;
    }
    public function setFieldType(string $value): self
    {
        $this->fieldType = $value;
        return $this;
    }
    public function getFieldType(): string
    {
        return $this->fieldType;
    }
    public function setContext(string $value): self
    {
        $this->context = $value;
        return $this;
    }
    public function getContext(): string
    {
        return $this->context;
    }
    public function setRequired(string $value): self
    {
        $this->required = $value;
        return $this;
    }
    public function getRequired(): ?string
    {
        return $this->required;
    }
    public function setFieldConfigs(string $value): self
    {
        $this->fieldConfigs = $value;
        return $this;
    }
    public function getFieldConfigs(): ?string
    {
        return $this->fieldConfigs;
    }
}
