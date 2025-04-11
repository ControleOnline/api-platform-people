<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ControleOnline\Entity\State;
use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleStates
 */
#[ORM\Table(name: 'people_states')]
#[ORM\Index(name: 'people_id', columns: ['people_id'])]
#[ORM\UniqueConstraint(name: 'state', columns: ['state_id'])]
#[ORM\Entity]
#[ORM\EntityListeners([LogListener::class])]
class PeopleStates
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \ControleOnline\Entity\People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]
    private $people;

    /**
     * @var \ControleOnline\Entity\State
     */
    #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\State::class)]
    private $state;

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
     * Set state
     *
     * @param State $state
     * @return PeopleStates
     */
    public function setState(State $state = null)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set people
     *
     * @param People $people
     * @return PeopleStates
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
}
