<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups; 
use People;
use Role;
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleRole
 */
#[ORM\Table(name: 'people_role')]
#[ORM\Index(name: 'people_id', columns: ['people_id'])]
#[ORM\Index(name: 'role_id', columns: ['role_id'])]
#[ORM\Index(name: 'IDX_55A046DA979B1AD6', columns: ['company_id'])]
#[ORM\UniqueConstraint(name: 'company_id', columns: ['company_id', 'people_id', 'role_id'])]
#[ORM\Entity]

class PeopleRole
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    private $company;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    private $people;

    /**
     * @var Role
     */
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Role::class)]
    private $role;



    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * Get the value of company
     */
    public function getCompany(): \ControleOnline\Entity\People
    {
        return $this->company;
    }

    /**
     * Set the value of company
     */
    public function setCompany(\ControleOnline\Entity\People $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the value of people
     */
    public function getPeople(): \ControleOnline\Entity\People
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople(\ControleOnline\Entity\People $people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get the value of role
     */
    public function getRole(): \ControleOnline\Entity\Role
    {
        return $this->role;
    }

    /**
     * Set the value of role
     */
    public function setRole(\ControleOnline\Entity\Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
