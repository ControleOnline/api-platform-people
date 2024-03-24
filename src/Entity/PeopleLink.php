<?php

namespace ControleOnline\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table(name="people_people", uniqueConstraints={@ORM\UniqueConstraint(name="people_id", columns={"people_id", "people_company"})}, indexes={@ORM\Index(name="people_company_id", columns={"people_company"})})
 * @ORM\Entity(repositoryClass="ControleOnline\Repository\PeopleLinkRepository")
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class PeopleLink
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peoplePeopleCompany")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_company_id", referencedColumnName="id")
     * })
     */
    private $people_company;

    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peoplePeople")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;

    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;


    /**
     * @var string
     * 
     *
     * @ORM\Column(name="link_type", type="string", columnDefinition="ENUM('employee','client','provider','franchisee')", nullable=false)
     */
    private $link_type;


    /**
     * @var float
     *
     * @ORM\Column(name="comission", type="float", nullable=false)
     */
    private $comission;


    /**
     * @var float
     *
     * @ORM\Column(name="minimum_comission", type="float", nullable=false)
     */
    private $minimum_comission;

    public function getId()
    {
        return $this->id;
    }

    public function setPeopleCompany(People $people_company = null)
    {
        $this->people_company = $people_company;

        return $this;
    }

    public function getPeopleCompany()
    {
        return $this->people_company;
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
