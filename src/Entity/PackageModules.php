<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['package_modules:read']], denormalizationContext: ['groups' => ['package_modules:write']])]
#[ORM\Table(name: 'package_modules')]
#[ORM\Index(name: 'module_id', columns: ['module_id'])]
#[ORM\Index(name: 'IDX_A1EC265BF44CABFF', columns: ['package_id'])]
#[ORM\UniqueConstraint(name: 'package_id', columns: ['package_id', 'module_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\PackageModulesRepository::class)]
class PackageModules
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var int
     */
    #[ORM\Column(name: 'users', type: 'integer', nullable: false)]
    private $users;
    /**
     * @var Module
     */
    #[ORM\JoinColumn(name: 'module_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Module::class)]
    private $module;
    /**
     * @var Package
     */
    #[ORM\JoinColumn(name: 'package_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Package::class)]
    private $package;
    /**
     * Get the value of id
     */
    public function getId() 
    {
        return $this->id;
    }
    /**
     * Get the value of users
     */
    public function getUsers() 
    {
        return $this->users;
    }
    /**
     * Set the value of users
     */
    public function setUsers($users) : self
    {
        $this->users = $users;
        return $this;
    }
    /**
     * Get the value of module
     */
    public function getModule() : Module
    {
        return $this->module;
    }
    /**
     * Set the value of module
     */
    public function setModule(Module $module) : self
    {
        $this->module = $module;
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
}
