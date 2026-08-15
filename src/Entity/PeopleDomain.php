<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Controller\GetPeopleDomainOverviewAction;
use ControleOnline\Repository\PeopleDomainRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * PeopleDomain
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_HUMAN\')'),
        new GetCollection(security: 'is_granted(\'ROLE_HUMAN\')'),
        new Get(
            uriTemplate: '/people_domains/{id}/overview',
            controller: GetPeopleDomainOverviewAction::class,
            read: false,
            security: 'is_granted(\'ROLE_HUMAN\')'
        ),
        new Post(security: 'is_granted(\'ROLE_HUMAN\')'),
        new Put(
            security: 'is_granted(\'ROLE_HUMAN\')',
            denormalizationContext: ['groups' => ['people_domain:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_HUMAN\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_domain:read']],
    denormalizationContext: ['groups' => ['people_domain:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['domain' => 'ASC', 'id' => 'DESC'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact', 'people' => 'exact', 'domain' => 'partial', 'domainType' => 'exact', 'theme' => 'exact', 'apiPeopleDomain' => 'exact'])]
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
     * @var PeopleDomain
     */
    #[ORM\JoinColumn(name: 'people_domain_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: PeopleDomain::class)]
    #[Groups(['people_domain:read', 'people_domain:write'])]
    private $apiPeopleDomain;

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
        $this->domain_type = 'ERP';
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

    #[Groups(['people_domain:read'])]
    public function getPeopleLabel(): string
    {
        if (!$this->people) {
            return '';
        }

        return trim((string) ($this->people->getAlias() ?: $this->people->getName()));
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

    #[Groups(['people_domain:read'])]
    public function getThemeLabel(): string
    {
        return $this->theme ? (string) $this->theme->getTheme() : '';
    }

    /**
     * Set the value of theme
     */
    public function setTheme(?Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get the value of apiPeopleDomain
     */
    public function getApiPeopleDomain(): ?PeopleDomain
    {
        return $this->apiPeopleDomain;
    }

    #[Groups(['people_domain:read'])]
    public function getApiPeopleDomainLabel(): string
    {
        return $this->apiPeopleDomain ? (string) $this->apiPeopleDomain->getDomain() : '';
    }

    /**
     * Set the value of apiPeopleDomain
     */
    public function setApiPeopleDomain(?PeopleDomain $apiPeopleDomain): self
    {
        $this->apiPeopleDomain = $apiPeopleDomain;

        return $this;
    }
}
