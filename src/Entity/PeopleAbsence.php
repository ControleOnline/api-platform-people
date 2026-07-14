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
use ControleOnline\Repository\PeopleAbsenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'people_absence')]
#[ORM\Index(name: 'people_absence_context_idx', columns: ['context'])]
#[ORM\Index(name: 'people_absence_company_idx', columns: ['company_id'])]
#[ORM\Index(name: 'people_absence_people_idx', columns: ['people_id'])]
#[ORM\Index(name: 'people_absence_absence_date_idx', columns: ['absence_date'])]
#[ORM\Index(name: 'people_absence_active_idx', columns: ['active'])]
#[ORM\Entity(repositoryClass: PeopleAbsenceRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_absence:read']],
    denormalizationContext: ['groups' => ['people_absence:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new GetCollection(security: "is_granted('ROLE_HUMAN')"),
        new Post(security: "is_granted('ROLE_HUMAN')"),
        new Put(security: "is_granted('ROLE_HUMAN')"),
        new Delete(security: "is_granted('ROLE_HUMAN')"),
    ]
)]
class PeopleAbsence
{
    public const CONTEXT_EMPLOYMENT = 'employment';

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ApiFilter(SearchFilter::class, properties: ['id' => 'exact'])]
    #[ApiFilter(OrderFilter::class, properties: ['id' => 'ASC'])]
    #[Groups(['people_absence:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'context', type: 'string', length: 120, nullable: false)]
    #[ApiFilter(SearchFilter::class, properties: ['context' => 'exact'])]
    #[ApiFilter(OrderFilter::class, properties: ['context' => 'ASC'])]
    #[Groups(['people_absence:read', 'people_absence:write'])]
    private string $context = self::CONTEXT_EMPLOYMENT;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ApiFilter(SearchFilter::class, properties: ['company' => 'exact'])]
    #[Groups(['people_absence:write'])]
    private ?People $company = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ApiFilter(SearchFilter::class, properties: ['people' => 'exact'])]
    #[Groups(['people_absence:write'])]
    private ?People $people = null;

    #[ORM\Column(name: 'absence_date', type: 'date', nullable: false)]
    #[ApiFilter(SearchFilter::class, properties: ['absenceDate' => 'exact'])]
    #[ApiFilter(OrderFilter::class, properties: ['absenceDate' => 'ASC'])]
    #[Groups(['people_absence:read', 'people_absence:write'])]
    private ?\DateTimeInterface $absenceDate = null;

    #[ORM\Column(name: 'reason', type: 'text', nullable: true)]
    #[ApiFilter(SearchFilter::class, properties: ['reason' => 'partial'])]
    #[Groups(['people_absence:read', 'people_absence:write'])]
    private ?string $reason = null;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'justification_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['people_absence:write'])]
    private ?File $justificationFile = null;

    #[ORM\Column(name: 'payload', type: 'json', nullable: true)]
    #[Groups(['people_absence:read', 'people_absence:write'])]
    private array $payload = [];

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => true])]
    #[ApiFilter(SearchFilter::class, properties: ['active' => 'exact'])]
    #[ApiFilter(OrderFilter::class, properties: ['active' => 'ASC'])]
    #[Groups(['people_absence:read', 'people_absence:write'])]
    private bool $active = true;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP')]
    #[ApiFilter(OrderFilter::class, properties: ['creationDate' => 'ASC'])]
    #[Groups(['people_absence:read'])]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'alter_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    #[ApiFilter(OrderFilter::class, properties: ['alterDate' => 'ASC'])]
    #[Groups(['people_absence:read'])]
    private ?\DateTimeInterface $alterDate = null;

    public function __construct()
    {
        $now = new \DateTime('now');
        $this->creationDate = $now;
        $this->alterDate = $now;
        $this->absenceDate = $now;
        $this->context = self::CONTEXT_EMPLOYMENT;
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

    public function getAbsenceDate(): ?\DateTimeInterface
    {
        return $this->absenceDate;
    }

    public function setAbsenceDate(mixed $absenceDate): self
    {
        $this->absenceDate = $this->normalizeDateValue($absenceDate);

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $reason = $reason !== null ? trim($reason) : null;
        $this->reason = $reason !== '' ? $reason : null;

        return $this;
    }

    public function getJustificationFile(): ?File
    {
        return $this->justificationFile;
    }

    public function setJustificationFile(?File $justificationFile): self
    {
        $this->justificationFile = $justificationFile;

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

    #[Groups(['people_absence:read'])]
    public function getContextLabel(): string
    {
        return $this->formatLabel($this->context);
    }

    #[Groups(['people_absence:read'])]
    public function getCompanyId(): ?int
    {
        return $this->company?->getId();
    }

    #[Groups(['people_absence:read'])]
    public function getPeopleId(): ?int
    {
        return $this->people?->getId();
    }

    #[Groups(['people_absence:read'])]
    public function getCompanyLabel(): string
    {
        return $this->resolvePeopleLabel($this->company);
    }

    #[Groups(['people_absence:read'])]
    public function getPeopleLabel(): string
    {
        return $this->resolvePeopleLabel($this->people);
    }

    #[Groups(['people_absence:read'])]
    public function getAbsenceDateLabel(): string
    {
        return $this->absenceDate instanceof \DateTimeInterface
            ? $this->absenceDate->format('d/m/Y')
            : '-';
    }

    #[Groups(['people_absence:read'])]
    public function getJustificationFileId(): ?int
    {
        return $this->justificationFile?->getId();
    }

    #[Groups(['people_absence:read'])]
    public function getJustificationFileLabel(): string
    {
        if (!$this->justificationFile instanceof File) {
            return '-';
        }

        $fileName = trim((string) $this->justificationFile->getFileName());
        $extension = trim((string) $this->justificationFile->getExtension());

        if ($fileName === '') {
            return '-';
        }

        return $extension !== '' ? sprintf('%s.%s', $fileName, $extension) : $fileName;
    }

    #[Groups(['people_absence:read'])]
    public function getJustificationLabel(): string
    {
        $reason = trim((string) $this->reason);
        if ($reason !== '') {
            return $reason;
        }

        return $this->getJustificationFileLabel();
    }

    #[Groups(['people_absence:read'])]
    public function getHasJustification(): bool
    {
        return trim((string) $this->reason) !== '' || $this->justificationFile instanceof File;
    }

    #[Groups(['people_absence:read'])]
    public function getStatusLabel(): string
    {
        return $this->getHasJustification() ? 'Justificada' : 'Falta';
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
