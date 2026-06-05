<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\Groups; 
use Symfony\Component\Serializer\Attribute\SerializedName;
use ControleOnline\Repository\PeopleDomainRepository;


use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleDomain
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_HUMAN\')'),
        new GetCollection(security: 'is_granted(\'ROLE_HUMAN\')'),
        new Put(
            security: 'is_granted(\'ROLE_HUMAN\')',
            denormalizationContext: ['groups' => ['people_domain:write']]
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_domain:read']],
    denormalizationContext: ['groups' => ['people_domain:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['domain' => 'ASC', 'id' => 'DESC'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact', 'people' => 'exact', 'domain' => 'partial', 'theme' => 'exact'])]
#[ORM\Table(name: 'people_domain')]
#[ORM\Entity(repositoryClass: PeopleDomainRepository::class)]

class PeopleDomain
{

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_domain:read'])]
    private $id;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    #[Groups(['people_domain:read', 'people_domain:write'])]
    private $people;



    /**
     * @var Theme
     */
    #[ORM\JoinColumn(name: 'theme_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Theme::class)]
    #[Groups(['people_domain:read', 'people_domain:write'])]
    private $theme;

    /**
     * @var string
     */
    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: false)]
    #[Groups(['people_domain:read', 'people_domain:write'])]
    private $domain;

    /**
     * @var string
     */
    #[ORM\Column(name: 'domain_type', type: 'string', length: 255, nullable: false)]
    #[Groups(['people_domain:read', 'people_domain:write'])]
    #[SerializedName('domainType')]
    private $domain_type;

    public function __construct()
    {
        $this->domain_type = 'cfp';
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

    /**
     * Set people
     *
     * @param People $people
     * @return PeopleDomain
     */
    public function setPeople(?People $people = null)
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get people
     *
     * @return People
     */
    public function getPeople(): ?People
    {
        return $this->people;
    }

    /**
     * Set domain
     *
     * @param string domain
     * @return PeopleDomain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set domain
     *
     * @param string domain_type
     * @return PeopleDomain
     */
    public function setDomainType($domain_type)
    {
        $this->domain_type = $domain_type;

        return $this;
    }

    /**
     * Get domain_type
     *
     * @return string
     */
    public function getDomainType()
    {
        return $this->domain_type;
    }

    /**
     * Get the value of theme
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * Set the value of theme
     */
    public function setTheme(?Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }
}
