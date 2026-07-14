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

#[ORM\Table(name: 'people_schedule')]
#[ORM\Index(name: 'people_schedule_context_idx', columns: ['context'])]
#[ORM\Index(name: 'people_schedule_company_idx', columns: ['company_id'])]
#[ORM\Index(name: 'people_schedule_people_idx', columns: ['people_id'])]
#[ORM\Index(name: 'people_schedule_professional_people_idx', columns: ['professional_people_id'])]
#[ORM\Index(name: 'people_schedule_mode_idx', columns: ['mode'])]
#[ORM\Index(name: 'people_schedule_weekday_idx', columns: ['weekday'])]
#[ORM\Index(name: 'people_schedule_active_idx', columns: ['active'])]
#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_schedule:read']],
    denormalizationContext: ['groups' => ['people_schedule:write']],
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
    'professionalPeople' => 'exact',
    'mode' => 'exact',
    'weekday' => 'exact',
    'active' => 'exact',
    'label' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'context',
    'mode',
    'weekday',
    'startTime',
    'endTime',
    'startsAt',
    'endsAt',
    'creationDate',
    'alterDate',
])]
class PeopleSchedule
{
    public const MODE_RECURRING = 'recurring';
    public const MODE_APPOINTMENT = 'appointment';
    public const MODES = [
        self::MODE_RECURRING,
        self::MODE_APPOINTMENT,
    ];

    public const WEEKDAY_LABELS = [
        1 => 'Segunda',
        2 => 'Terca',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sabado',
        7 => 'Domingo',
    ];

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_schedule:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'context', type: 'string', length: 120, nullable: false)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private string $context = '';

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['people_schedule:write'])]
    private ?People $company = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['people_schedule:write'])]
    private ?People $people = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'professional_people_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['people_schedule:write'])]
    private ?People $professionalPeople = null;

    #[ORM\Column(name: 'label', type: 'string', length: 255, nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private ?string $label = null;

    #[ORM\Column(name: 'mode', type: 'string', length: 20, nullable: false, options: ['default' => 'recurring'])]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private string $mode = self::MODE_RECURRING;

    #[ORM\Column(name: 'weekday', type: 'smallint', nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private ?int $weekday = null;

    #[ORM\Column(name: 'start_time', type: 'time', nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'time', nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(name: 'starts_at', type: 'datetime', nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private ?\DateTimeInterface $startsAt = null;

    #[ORM\Column(name: 'ends_at', type: 'datetime', nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private ?\DateTimeInterface $endsAt = null;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private bool $active = true;

    #[ORM\Column(name: 'payload', type: 'json', nullable: true)]
    #[Groups(['people_schedule:read', 'people_schedule:write'])]
    private array $payload = [];

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP')]
    #[Groups(['people_schedule:read'])]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'alter_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    #[Groups(['people_schedule:read'])]
    private ?\DateTimeInterface $alterDate = null;

    public function __construct()
    {
        $now = new \DateTime('now');
        $this->creationDate = $now;
        $this->alterDate = $now;
        $this->weekday = 1;
        $this->mode = self::MODE_RECURRING;
        $this->active = true;
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

    public function getProfessionalPeople(): ?People
    {
        return $this->professionalPeople;
    }

    public function setProfessionalPeople(?People $professionalPeople): self
    {
        $this->professionalPeople = $professionalPeople;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $label = $label !== null ? trim($label) : null;
        $this->label = $label !== '' ? $label : null;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(?string $mode): self
    {
        $normalized = strtolower(trim((string) $mode));
        $this->mode = in_array($normalized, self::MODES, true)
            ? $normalized
            : self::MODE_RECURRING;

        return $this;
    }

    public function getWeekday(): ?int
    {
        return $this->weekday;
    }

    public function setWeekday(mixed $weekday): self
    {
        $weekday = (int) $weekday;
        $this->weekday = $weekday > 0 ? min(7, max(1, $weekday)) : null;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(mixed $startTime): self
    {
        $this->startTime = $this->normalizeTimeValue($startTime);

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(mixed $endTime): self
    {
        $this->endTime = $this->normalizeTimeValue($endTime);

        return $this;
    }

    public function getStartsAt(): ?\DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(mixed $startsAt): self
    {
        $this->startsAt = $this->normalizeDateTimeValue($startsAt);

        return $this;
    }

    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(mixed $endsAt): self
    {
        $this->endsAt = $this->normalizeDateTimeValue($endsAt);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getActive(): bool
    {
        return $this->isActive();
    }

    public function setActive(bool|int|string $active): self
    {
        $this->active = in_array($active, [true, 1, '1', 'true'], true);

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

    #[Groups(['people_schedule:read'])]
    public function getContextLabel(): string
    {
        return $this->formatLabel($this->context);
    }

    #[Groups(['people_schedule:read'])]
    public function getModeLabel(): string
    {
        return $this->mode === self::MODE_APPOINTMENT ? 'Compromisso' : 'Recorrente';
    }

    #[Groups(['people_schedule:read'])]
    public function getWeekdayLabel(): string
    {
        return self::WEEKDAY_LABELS[$this->weekday ?? 0] ?? ($this->weekday ? sprintf('Dia %d', $this->weekday) : '');
    }

    #[Groups(['people_schedule:read'])]
    public function getWindowLabel(): string
    {
        if ($this->mode === self::MODE_APPOINTMENT) {
            $startsAt = $this->startsAt instanceof \DateTimeInterface ? $this->startsAt->format('d/m/Y H:i') : '';
            $endsAt = $this->endsAt instanceof \DateTimeInterface ? $this->endsAt->format('d/m/Y H:i') : '';

            return trim(sprintf('%s - %s', $startsAt, $endsAt), ' -');
        }

        $weekday = $this->getWeekdayLabel();
        $start = $this->startTime instanceof \DateTimeInterface ? $this->startTime->format('H:i') : '';
        $end = $this->endTime instanceof \DateTimeInterface ? $this->endTime->format('H:i') : '';

        return trim(sprintf('%s %s - %s', $weekday, $start, $end), ' -');
    }

    #[Groups(['people_schedule:read'])]
    public function getPeriodLabel(): string
    {
        if (!$this->startsAt instanceof \DateTimeInterface && !$this->endsAt instanceof \DateTimeInterface) {
            return '';
        }

        $startsAt = $this->startsAt instanceof \DateTimeInterface ? $this->startsAt->format('d/m/Y H:i') : '';
        $endsAt = $this->endsAt instanceof \DateTimeInterface ? $this->endsAt->format('d/m/Y H:i') : '';

        return trim(sprintf('%s - %s', $startsAt, $endsAt), ' -');
    }

    #[Groups(['people_schedule:read'])]
    public function getCompanyId(): ?int
    {
        return $this->company?->getId();
    }

    #[Groups(['people_schedule:read'])]
    public function getPeopleId(): ?int
    {
        return $this->people?->getId();
    }

    #[Groups(['people_schedule:read'])]
    public function getProfessionalPeopleId(): ?int
    {
        return $this->professionalPeople?->getId();
    }

    #[Groups(['people_schedule:read'])]
    public function getCompanyLabel(): string
    {
        return $this->resolvePeopleLabel($this->company);
    }

    #[Groups(['people_schedule:read'])]
    public function getPeopleLabel(): string
    {
        return $this->resolvePeopleLabel($this->people);
    }

    #[Groups(['people_schedule:read'])]
    public function getProfessionalPeopleLabel(): string
    {
        return $this->resolvePeopleLabel($this->professionalPeople);
    }

    private function normalizeTimeValue(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            $dateTime = \DateTimeImmutable::createFromFormat($format, $normalized);
            if ($dateTime instanceof \DateTimeImmutable) {
                return $dateTime;
            }
        }

        return null;
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
