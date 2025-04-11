<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['phone:read']],
            denormalizationContext: ['groups' => ['phone:write']]
        ),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['phone:read']],
    denormalizationContext: ['groups' => ['phone:write']],
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
#[ORM\Table(name: 'phone')]
#[ORM\Index(columns: ['people_id'])]
#[ORM\UniqueConstraint(name: 'phone', columns: ['phone', 'ddd', 'people_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\PhoneRepository::class)]
class Phone
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     *
     * @Groups({"invoice_details:read","order_details:read","people:read", "phone:read",  "phone:write"})
     */
    #[ORM\Column(type: 'integer', length: 10, nullable: false)]
    private $phone;
    /**
     *
     * @Groups({"invoice_details:read","order_details:read","people:read", "phone:read",  "phone:write"})
     */
    #[ORM\Column(type: 'integer', length: 2, nullable: false)]
    private $ddd;
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $confirmed = false;
    /**
     * @Groups({"invoice_details:read","order_details:read","people:read", "phone:read",  "phone:write"})
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class, inversedBy: 'phone')]
    private $people;
    public function getId()
    {
        return $this->id;
    }
    public function setDdd($ddd)
    {
        $this->ddd = $ddd;
        return $this;
    }
    public function getDdd()
    {
        return $this->ddd;
    }
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }
    public function getPhone()
    {
        return $this->phone;
    }
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }
    public function getConfirmed()
    {
        return $this->confirmed;
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
}
