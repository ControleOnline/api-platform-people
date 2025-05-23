<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups; 
use ControleOnline\Repository\PeoplePackageRepository;
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['people_package:read']], denormalizationContext: ['groups' => ['people_package:write']])]
#[ORM\Table(name: 'people_package')]
#[ORM\Index(name: 'people_id', columns: ['people_id'])]
#[ORM\Index(name: 'package_id', columns: ['package_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: PeoplePackageRepository::class)]
class PeoplePackage
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var bool
     */
    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => '1'])]
    private $active = true;
    /**
     * @var Package
     */
    #[ORM\JoinColumn(name: 'package_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Package::class)]
    private $package;
    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    private $people;
    /**
     * Get the value of id
     */
    public function getId() 
    {
        return $this->id;
    }
    /**
     * Get the value of active
     */
    public function isActive() : bool
    {
        return $this->active;
    }
    /**
     * Set the value of active
     */
    public function setActive(bool $active) : self
    {
        $this->active = $active;
        return $this;
    }
    /**
     * Get the value of package
     */
    public function getPackage() : Package
    {
        return $this->package;
    }
    /**
     * Set the value of package
     */
    public function setPackage(Package $package) : self
    {
        $this->package = $package;
        return $this;
    }
    /**
     * Get the value of people
     */
    public function getPeople() : People
    {
        return $this->people;
    }
    /**
     * Set the value of people
     */
    public function setPeople(People $people) : self
    {
        $this->people = $people;
        return $this;
    }
}
