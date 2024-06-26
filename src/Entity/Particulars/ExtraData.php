<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="extra_data")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ExtraDataRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['extrafields_write']],
            denormalizationContext: ['groups' => ['extrafields_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' =>
    ['text/csv']],
    normalizationContext: ['groups' => ['extra_data_read']],
    denormalizationContext: ['groups' => ['extra_data_write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact', 'type' => 'exact', 'people' => 'exact'])]

class ExtraData
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @Groups({"extrafields_read", "extra_data_read"})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var \ControleOnline\Entity\ExtraFields
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\ExtraFields")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="extra_fields_id", referencedColumnName="id")
     * })
     * @Groups({"extra_data_read"})
     */
    private $extra_fields;

    /**
     * @ORM\Column(name="entity_id", type="string", nullable=false)
     * @Groups({"extra_data_read"})
     */
    private $entity_id;

    /**
     * @ORM\Column(name="entity_name", type="string", nullable=false)
     * @Groups({"extra_data_read"})
     */
    private $entity_name;

    /**
     * @ORM\Column(name="data_value", type="string", nullable=false)
     * @Groups({"extra_data_read"})
     */
    private $value;
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
    public function setType(ExtraFields $extra_fields)
    {
        $this->extra_fields = $extra_fields;
        return $this;
    }
    public function getType()
    {
        return $this->extra_fields;
    }

    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of entity_id
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * Set the value of entity_id
     */
    public function setEntityId($entity_id): self
    {
        $this->entity_id = $entity_id;

        return $this;
    }

    /**
     * Get the value of entity_name
     */
    public function getEntityName()
    {
        return $this->entity_name;
    }

    /**
     * Set the value of entity_name
     */
    public function setEntityName($entity_name): self
    {
        $this->entity_name = $entity_name;

        return $this;
    }
}
