<?php

namespace ControleOnline\Entity\Particulars;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="particulars")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ParticularsRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' =>
    ['text/csv']],
    normalizationContext: ['groups' => ['particulars_read']],
    denormalizationContext: ['groups' => ['particulars_write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact', 'type' => 'exact', 'people' => 'exact'])]

class Particulars
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
     * @var \ControleOnline\Entity\Particulars\ParticularsType
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Particulars\ParticularsType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="particulars_type_id", referencedColumnName="id")
     * })
     * @Groups({"particulars_read"})
     */
    private $type;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;
    /**
     * @ORM\Column(name="particular_value", type="string", nullable=false)
     * @Groups({"particulars_read"})
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
    public function setType(ParticularsType $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function getType(): ParticularsType
    {
        return $this->type;
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
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }
    public function getValue(): string
    {
        return $this->value;
    }
}
