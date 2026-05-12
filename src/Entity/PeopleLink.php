<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use ControleOnline\Repository\PeopleLinkRepository;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'people_link')]
#[ORM\Index(name: 'company_id', columns: ['company'])]
#[ORM\UniqueConstraint(name: 'people_id', columns: ['people_id', 'company'])]
#[ORM\Entity(repositoryClass: PeopleLinkRepository::class)]

#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people_link:read']],
    denormalizationContext: ['groups' => ['people_link:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new GetCollection(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        new Put(
            security: "is_granted('ROLE_HUMAN')",
            denormalizationContext: ['groups' => ['people_link:write']]
        ),
        new Post(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        new Delete(security: "is_granted('ROLE_HUMAN')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'company' => 'exact',
    'people' => 'exact',
    'linkType' => 'exact',
    'enable' => 'exact',
])]
class PeopleLink
{
    public const HUMAN_LINK = [
        'employee',
        'owner',
        'director',
        'manager',
        'salesman',
        'after-sales',
    ];

    public const COMMERCIAL_LINK = ['client', 'provider', 'franchisee'];

    public const PANEL_LINK = ['client', 'provider', 'franchisee'];

    public const ADMIN_LINK = ['owner', 'director', 'manager'];

    public const API_ROLE_MAP = [
        'employee' => 'ROLE_EMPLOYEE',
        'owner' => 'ROLE_OWNER',
        'director' => 'ROLE_DIRECTOR',
        'manager' => 'ROLE_MANAGER',
        'salesman' => 'ROLE_SALESMAN',
        'after-sales' => 'ROLE_AFTER_SALES',
        'client' => 'ROLE_CLIENT',
        'provider' => 'ROLE_PROVIDER',
        'franchisee' => 'ROLE_FRANCHISEE',
    ];

    public const EMPLOYEE_LINK = self::HUMAN_LINK;
    public const MANAGER_LINK  = self::ADMIN_LINK;

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
    #[ORM\Column(name: 'link_type', type: 'string', columnDefinition: "ENUM('employee','owner','director','manager','client','provider','franchisee','salesman','after-sales')", nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $linkType;


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
        return (float) ($this->comission ?? 0);
    }

    /**
     * Get the value of linkType
     */
    public function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * Set the value of linkType
     */
    public function setLinkType($linkType): self
    {
        $this->linkType = $linkType;

        return $this;
    }

    public static function toRole(string $linkType): ?string
    {
        $normalizedLinkType = trim(strtolower($linkType));

        return self::API_ROLE_MAP[$normalizedLinkType] ?? null;
    }
}
