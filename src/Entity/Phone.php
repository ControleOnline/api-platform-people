<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

use ControleOnline\Repository\PhoneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_CLIENT')"),
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')",
            validationContext: ['groups' => ['phone:read']],
            denormalizationContext: ['groups' => ['phone:write']]
        ),
        new Post(securityPostDenormalize: "is_granted('ROLE_CLIENT')"),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['phone:read']],
    denormalizationContext: ['groups' => ['phone:write']],
)]
#[ORM\Table(name: 'phone')]
#[ORM\Index(columns: ['people_id'])]
#[ORM\UniqueConstraint(name: 'phone', columns: ['phone', 'ddd', 'people_id'])]

#[ORM\Entity(repositoryClass: PhoneRepository::class)]
class Phone
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['connections:read', 'phone:read', 'phone:write'])]
    private int $id = 0;

    #[ORM\Column(type: 'integer', length: 10, nullable: false)]
    #[Groups(['invoice_details:read', 'order_details:read', 'order:write', 'people:read', 'connections:read', 'phone:read', 'phone:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['phone' => 'exact'])]
    private int $phone;

    #[ORM\Column(type: 'integer', length: 2, nullable: false)]
    #[Groups(['invoice_details:read', 'order_details:read', 'order:write', 'people:read', 'connections:read', 'phone:read', 'phone:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['ddi' => 'exact'])]

    private int $ddi;


    #[ORM\Column(type: 'integer', length: 2, nullable: false)]
    #[Groups(['invoice_details:read', 'order_details:read', 'order:write', 'people:read', 'connections:read', 'phone:read', 'phone:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['ddd' => 'exact'])]
    private int $ddd;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['confirmed' => 'exact'])]

    private bool $confirmed = false;

    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'phone')]
    #[Groups(['connections:read', 'phone:read', 'phone:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]

    private ?People $people = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setDdd(int $ddd): self
    {
        $this->ddd = $ddd;
        return $this;
    }

    public function getDdd(): int
    {
        return $this->ddd;
    }

    public function setPhone(int $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone(): int
    {
        return $this->phone;
    }

    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function getConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setPeople(?People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getPeople(): ?People
    {
        return $this->people;
    }

    public function getDdi(): int
    {
        return $this->ddi;
    }

    public function setDdi(int $ddi): self
    {
        $this->ddi = $ddi;

        return $this;
    }
}
