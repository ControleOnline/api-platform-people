<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use ControleOnline\Repository\PeopleLinkRepository;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'people_link')]
#[ORM\Index(name: 'company_id', columns: ['company'])]
#[ORM\UniqueConstraint(name: 'people_id', columns: ['people_id', 'company'])]
#[ORM\Entity(repositoryClass: PeopleLinkRepository::class)]

#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people_link:read']],
    denormalizationContext: ['groups' => ['people_link:write']],
    security: "is_granted('ROLE_CLIENT')",
    operations: [
        new GetCollection(securityPostDenormalize: "is_granted('ROLE_CLIENT')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'company' => 'exact',
    'people' => 'exact',
    'link_type' => 'exact',
    'enable' => 'exact',
])]
class PeopleLink
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_link:read', 'people_link:write'])]
    private $id;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'company')]
    #[Groups(['people_link:read', 'people_link:write'])]
    private $company;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'link')]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $people;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $enable = 1;


    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'link_type', type: 'string', columnDefinition: "ENUM('employee','client','provider','franchisee')", nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $link_type;


    /**
     * @var float
     */
    #[ORM\Column(name: 'comission', type: 'float', nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $comission = 0;


    /**
     * @var float
     */
    #[ORM\Column(name: 'minimum_comission', type: 'float', nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $minimum_comission = 0;

    public function getId()
    {
        return $this->id;
    }

    public function setCompany(People $company = null)
    {
        $this->company = $company;

        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setPeople(People $people = null)
    {
        $this->people = $people;

        return $this;
    }

    public function getPeople()
    {
        return $this->people;
    }

    public function getEnabled()
    {
        return $this->enable;
    }

    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;

        return $this;
    }


    /**
     * Set minimum_comission
     *
     * @param float $minimum_comission
     * @return PeopleSalesman
     */
    public function setMinimumComission($minimum_comission): self
    {
        $this->minimum_comission = $minimum_comission;

        return $this;
    }

    /**
     * Get minimum_comission
     *
     * @return float
     */
    public function getMinimumComission(): float
    {
        return $this->minimum_comission;
    }


    /**
     * Set comission
     *
     * @param float $comission
     * @return PeopleSalesman
     */
    public function setComission($comission): self
    {
        $this->comission = $comission;

        return $this;
    }

    /**
     * Get comission
     *
     * @return float
     */
    public function getComission(): float
    {
        return $this->comission;
    }

    /**
     * Get the value of link_type
     */
    public function getLinkType()
    {
        return $this->link_type;
    }

    /**
     * Set the value of link_type
     */
    public function setLinkType($link_type): self
    {
        $this->link_type = $link_type;

        return $this;
    }
}
