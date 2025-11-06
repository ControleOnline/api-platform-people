<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups; 
use ControleOnline\Repository\PeopleDomainRepository;
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleDomain
 */
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
    private $id;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    private $people;



    /**
     * @var Theme
     */
    #[ORM\JoinColumn(name: 'theme_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Theme::class)]
    private $theme;

    /**
     * @var string
     */
    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: false)]
    private $domain;

    /**
     * @var string
     */
    #[ORM\Column(name: 'domain_type', type: 'string', length: 255, nullable: false)]
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
    public function setPeople(People $people = null)
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get people
     *
     * @return People
     */
    public function getPeople()
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
    public function getTheme(): Theme
    {
        return $this->theme;
    }

    /**
     * Set the value of theme
     */
    public function setTheme(Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }
}
