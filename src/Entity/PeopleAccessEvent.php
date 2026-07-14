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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'people_access_event')]
#[ORM\Index(name: 'people_access_event_context_idx', columns: ['context'])]
#[ORM\Index(name: 'people_access_event_company_idx', columns: ['company_id'])]
#[ORM\Index(name: 'people_access_event_people_idx', columns: ['people_id'])]
#[ORM\Index(name: 'people_access_event_event_at_idx', columns: ['event_at'])]
#[ORM\Index(name: 'people_access_event_direction_idx', columns: ['direction'])]
#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_access_event:read']],
    denormalizationContext: ['groups' => ['people_access_event:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new GetCollection(security: "is_granted('ROLE_HUMAN')"),
        new Post(security: "is_granted('ROLE_HUMAN')"),
        new Put(security: "is_granted('ROLE_HUMAN')"),
        new Delete(security: "is_granted('ROLE_HUMAN')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'context' => 'exact',
    'company' => 'exact',
    'people' => 'exact',
    'direction' => 'exact',
    'source' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'context',
    'direction',
    'eventAt',
    'source',
    'creationDate',
    'alterDate',
])]
class PeopleAccessEvent
{
    public const DIRECTION_ENTRY = 'entry';
    public const DIRECTION_EXIT = 'exit';
    public const DIRECTIONS = [
        self::DIRECTION_ENTRY,
        self::DIRECTION_EXIT,
    ];

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_access_event:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'context', type: 'string', length: 120, nullable: false)]
    #[Groups(['people_access_event:read', 'people_access_event:write'])]
    private string $context = '';

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['people_access_event:write'])]
    private ?People $company = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['people_access_event:write'])]
    private ?People $people = null;

    #[ORM\Column(name: 'direction', type: 'string', length: 20, nullable: false, options: ['default' => 'entry'])]
    #[Groups(['people_access_event:read', 'people_access_event:write'])]
    private string $direction = self::DIRECTION_ENTRY;

    #[ORM\Column(name: 'event_at', type: 'datetime', nullable: false)]
    #[Groups(['people_access_event:read', 'people_access_event:write'])]
    private ?\DateTimeInterface $eventAt = null;

    #[ORM\Column(name: 'source', type: 'string', length: 120, nullable: false, options: ['default' => 'manual'])]
    #[Groups(['people_access_event:read', 'people_access_event:write'])]
    private string $source = 'manual';

    #[ORM\Column(name: 'payload', type: 'json', nullable: true)]
    #[Groups(['people_access_event:read', 'people_access_event:write'])]
    private array $payload = [];

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP')]
    #[Groups(['people_access_event:read'])]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'alter_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    #[Groups(['people_access_event:read'])]
    private ?\DateTimeInterface $alterDate = null;

    public function __construct()
    {
        $now = new \DateTime('now');
        $this->eventAt = $now;
        $this->creationDate = $now;
        $this->alterDate = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = strtolower(trim($context));

        return $this;
    }

    public function getCompany(): ?People
    {
        return $this->company;
    }

    public function setCompany(?People $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getPeople(): ?People
    {
        return $this->people;
    }

    public function setPeople(?People $people): self
    {
        $this->people = $people;

        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(?string $direction): self
    {
        $normalized = strtolower(trim((string) $direction));
        $this->direction = in_array($normalized, self::DIRECTIONS, true)
            ? $normalized
            : self::DIRECTION_ENTRY;

        return $this;
    }

    public function getEventAt(): ?\DateTimeInterface
    {
        return $this->eventAt;
    }

    public function setEventAt(mixed $eventAt): self
    {
        $this->eventAt = $this->normalizeDateTimeValue($eventAt);

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $source = $source !== null ? trim($source) : '';
        $this->source = $source !== '' ? strtolower($source) : 'manual';

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(?\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getAlterDate(): ?\DateTimeInterface
    {
        return $this->alterDate;
    }

    public function setAlterDate(?\DateTimeInterface $alterDate): self
    {
        $this->alterDate = $alterDate;

        return $this;
    }

    #[Groups(['people_access_event:read'])]
    public function getContextLabel(): string
    {
        return $this->formatLabel($this->context);
    }

    #[Groups(['people_access_event:read'])]
    public function getDirectionLabel(): string
    {
        return $this->direction === self::DIRECTION_EXIT ? 'Saida' : 'Entrada';
    }

    #[Groups(['people_access_event:read'])]
    public function getCompanyId(): ?int
    {
        return $this->company?->getId();
    }

    #[Groups(['people_access_event:read'])]
    public function getPeopleId(): ?int
    {
        return $this->people?->getId();
    }

    #[Groups(['people_access_event:read'])]
    public function getCompanyLabel(): string
    {
        return $this->resolvePeopleLabel($this->company);
    }

    #[Groups(['people_access_event:read'])]
    public function getPeopleLabel(): string
    {
        return $this->resolvePeopleLabel($this->people);
    }

    private function normalizeDateTimeValue(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Exception) {
            return null;
        }
    }

    private function resolvePeopleLabel(?People $people): string
    {
        if (!$people instanceof People) {
            return '';
        }

        $alias = trim((string) $people->getAlias());
        $name = trim((string) $people->getName());

        if ($alias !== '' && $name !== '' && $alias !== $name) {
            return sprintf('%s - %s', $alias, $name);
        }

        return $alias !== '' ? $alias : $name;
    }

    private function formatLabel(string $value): string
    {
        $normalized = trim(str_replace(['_', '-'], ' ', strtolower($value)));
        if ($normalized === '') {
            return '';
        }

        return ucfirst($normalized);
    }
}
