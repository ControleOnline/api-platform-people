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

#[ORM\Table(name: 'people_export_job')]
#[ORM\Index(name: 'people_export_job_context_idx', columns: ['context'])]
#[ORM\Index(name: 'people_export_job_kind_idx', columns: ['kind'])]
#[ORM\Index(name: 'people_export_job_status_idx', columns: ['status'])]
#[ORM\Index(name: 'people_export_job_company_idx', columns: ['company_id'])]
#[ORM\Index(name: 'people_export_job_people_idx', columns: ['people_id'])]
#[ORM\Index(name: 'people_export_job_period_start_idx', columns: ['period_start'])]
#[ORM\Index(name: 'people_export_job_period_end_idx', columns: ['period_end'])]
#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_export_job:read']],
    denormalizationContext: ['groups' => ['people_export_job:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new GetCollection(security: "is_granted('ROLE_HUMAN')"),
        new Put(security: "is_granted('ROLE_HUMAN')"),
        new Delete(security: "is_granted('ROLE_HUMAN')"),
        new Post(
            security: "is_granted('ROLE_HUMAN')",
            uriTemplate: '/people_export_jobs/generate',
            controller: \ControleOnline\Controller\GeneratePeopleExportJobController::class,
            deserialize: false,
            read: false,
            output: false
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'context' => 'exact',
    'kind' => 'exact',
    'company' => 'exact',
    'people' => 'exact',
    'status' => 'exact',
    'periodStart' => 'exact',
    'periodEnd' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'context',
    'kind',
    'status',
    'periodStart',
    'periodEnd',
    'finishedAt',
    'creationDate',
    'alterDate',
])]
class PeopleExportJob
{
    public const CONTEXT_EMPLOYMENT = 'employment';
    public const KIND_TIMESHEET = 'timesheet';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_ERROR = 'error';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_DONE,
        self::STATUS_ERROR,
    ];

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_export_job:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'context', type: 'string', length: 120, nullable: false)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private string $context = self::CONTEXT_EMPLOYMENT;

    #[ORM\Column(name: 'kind', type: 'string', length: 80, nullable: false)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private string $kind = self::KIND_TIMESHEET;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['people_export_job:write'])]
    private ?People $company = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['people_export_job:write'])]
    private ?People $people = null;

    #[ORM\Column(name: 'period_start', type: 'date', nullable: false)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private ?\DateTimeInterface $periodStart = null;

    #[ORM\Column(name: 'period_end', type: 'date', nullable: false)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private ?\DateTimeInterface $periodEnd = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false, options: ['default' => 'pending'])]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['people_export_job:write'])]
    private ?File $file = null;

    #[ORM\Column(name: 'filters', type: 'json', nullable: true)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private array $filters = [];

    #[ORM\Column(name: 'error_message', type: 'text', nullable: true)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'finished_at', type: 'datetime', nullable: true)]
    #[Groups(['people_export_job:read', 'people_export_job:write'])]
    private ?\DateTimeInterface $finishedAt = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP')]
    #[Groups(['people_export_job:read'])]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'alter_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    #[Groups(['people_export_job:read'])]
    private ?\DateTimeInterface $alterDate = null;

    public function __construct()
    {
        $now = new \DateTime('now');
        $this->creationDate = $now;
        $this->alterDate = $now;
        $this->periodStart = $now;
        $this->periodEnd = $now;
        $this->status = self::STATUS_PENDING;
        $this->context = self::CONTEXT_EMPLOYMENT;
        $this->kind = self::KIND_TIMESHEET;
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

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): self
    {
        $this->kind = strtolower(trim($kind));

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

    public function getPeriodStart(): ?\DateTimeInterface
    {
        return $this->periodStart;
    }

    public function setPeriodStart(mixed $periodStart): self
    {
        $this->periodStart = $this->normalizeDateValue($periodStart);

        return $this;
    }

    public function getPeriodEnd(): ?\DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(mixed $periodEnd): self
    {
        $this->periodEnd = $this->normalizeDateValue($periodEnd);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $normalized = strtolower(trim((string) $status));
        $this->status = in_array($normalized, self::STATUSES, true)
            ? $normalized
            : self::STATUS_PENDING;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $errorMessage = $errorMessage !== null ? trim($errorMessage) : null;
        $this->errorMessage = $errorMessage !== '' ? $errorMessage : null;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

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

    #[Groups(['people_export_job:read'])]
    public function getCompanyId(): ?int
    {
        return $this->company?->getId();
    }

    #[Groups(['people_export_job:read'])]
    public function getPeopleId(): ?int
    {
        return $this->people?->getId();
    }

    #[Groups(['people_export_job:read'])]
    public function getFileId(): ?int
    {
        return $this->file?->getId();
    }

    #[Groups(['people_export_job:read'])]
    public function getFileLabel(): string
    {
        if (!$this->file instanceof File) {
            return '';
        }

        $fileName = trim((string) $this->file->getFileName());
        $extension = trim((string) $this->file->getExtension());

        if ($fileName === '') {
            return '';
        }

        return $extension !== '' ? sprintf('%s.%s', $fileName, $extension) : $fileName;
    }

    #[Groups(['people_export_job:read'])]
    public function getFileUrl(): string
    {
        return $this->file instanceof File ? sprintf('/files/%d/download', $this->file->getId()) : '';
    }

    #[Groups(['people_export_job:read'])]
    public function getContextLabel(): string
    {
        return $this->formatLabel($this->context);
    }

    #[Groups(['people_export_job:read'])]
    public function getKindLabel(): string
    {
        return $this->kind === self::KIND_TIMESHEET ? 'Folha de ponto' : $this->formatLabel($this->kind);
    }

    #[Groups(['people_export_job:read'])]
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_PROCESSING => 'Processando',
            self::STATUS_DONE => 'Concluido',
            self::STATUS_ERROR => 'Erro',
            default => $this->formatLabel($this->status),
        };
    }

    #[Groups(['people_export_job:read'])]
    public function getPeriodLabel(): string
    {
        $start = $this->periodStart instanceof \DateTimeInterface ? $this->periodStart->format('d/m/Y') : '';
        $end = $this->periodEnd instanceof \DateTimeInterface ? $this->periodEnd->format('d/m/Y') : '';

        return trim(sprintf('%s - %s', $start, $end), ' -');
    }

    #[Groups(['people_export_job:read'])]
    public function getCompanyLabel(): string
    {
        return $this->resolvePeopleLabel($this->company);
    }

    #[Groups(['people_export_job:read'])]
    public function getPeopleLabel(): string
    {
        return $this->resolvePeopleLabel($this->people);
    }

    private function normalizeDateValue(mixed $value): ?\DateTimeInterface
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
