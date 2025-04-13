<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\EmailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['email:read']],
            denormalizationContext: ['groups' => ['email:write']]
        ),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['email:read']],
    denormalizationContext: ['groups' => ['email:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
#[ORM\Table(name: 'email')]
#[ORM\Index(columns: ['people_id'])]
#[ORM\UniqueConstraint(name: 'email', columns: ['email'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: EmailRepository::class)]
class Email
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Groups(['invoice_details:read', 'order_details:read', 'people:read', 'email:read', 'get_contracts', 'carrier:read', 'email:write'])]
    private string $email;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $confirmed = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['invoice_details:read', 'order_details:read', 'people:read', 'email:read', 'get_contracts', 'carrier:read'])]
    private ?string $types = null;

    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'email')]
    #[Groups(['email:read'])]
    private ?People $people = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setEmail(string $email): self
    {
        $this->email = trim($email);
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTypes(): object
    {
        return $this->types ? json_decode($this->types) : new \stdClass();
    }

    public function addType(string $key, mixed $value): self
    {
        $types = $this->getTypes();
        $types->$key = $value;
        $this->types = json_encode($types);
        return $this;
    }

    public function setTypes(object $types): self
    {
        $this->types = json_encode($types);
        return $this;
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
}