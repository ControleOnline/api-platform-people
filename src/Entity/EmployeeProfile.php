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
use ControleOnline\Repository\EmployeeProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'employee_profile')]
#[ORM\Index(name: 'employee_profile_people_link_idx', columns: ['people_link_id'])]
#[ORM\Index(name: 'employee_profile_job_title_idx', columns: ['job_title_id'])]
#[ORM\Index(name: 'employee_profile_job_function_idx', columns: ['job_function_id'])]
#[ORM\Index(name: 'employee_profile_department_idx', columns: ['department_id'])]
#[ORM\UniqueConstraint(name: 'employee_profile_people_link_unique', columns: ['people_link_id'])]
#[ORM\Entity(repositoryClass: EmployeeProfileRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['employee_profile:read']],
    denormalizationContext: ['groups' => ['employee_profile:write']],
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
    'peopleLink' => 'exact',
    'peopleLink.people' => 'exact',
    'peopleLink.company' => 'exact',
    'peopleLink.linkType' => 'exact',
    'jobTitleCategory' => 'exact',
    'jobTitleCategory.name' => 'partial',
    'jobFunctionCategory' => 'exact',
    'jobFunctionCategory.name' => 'partial',
    'departmentCategory' => 'exact',
    'departmentCategory.name' => 'partial',
    'jobTitle' => 'partial',
    'jobFunction' => 'partial',
    'department' => 'partial',
    'employmentType' => 'exact',
    'active' => 'exact',
    'linkedinUrl' => 'partial',
    'linkedinHeadline' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'jobTitleCategory.name',
    'jobFunctionCategory.name',
    'departmentCategory.name',
    'jobTitle',
    'jobFunction',
    'department',
    'employmentType',
    'admissionDate',
    'terminationDate',
    'creationDate',
    'alterDate',
])]
class EmployeeProfile
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['employee_profile:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: PeopleLink::class)]
    #[ORM\JoinColumn(name: 'people_link_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['employee_profile:write'])]
    private ?PeopleLink $peopleLink = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'job_title_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?Category $jobTitleCategory = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'job_function_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?Category $jobFunctionCategory = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'department_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?Category $departmentCategory = null;

    #[ORM\Column(name: 'job_title', type: 'string', length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(name: 'job_function', type: 'string', length: 255, nullable: true)]
    private ?string $jobFunction = null;

    #[ORM\Column(name: 'department', type: 'string', length: 255, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(name: 'employment_type', type: 'string', length: 120, nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?string $employmentType = null;

    #[ORM\Column(name: 'admission_date', type: 'date', nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?\DateTimeInterface $admissionDate = null;

    #[ORM\Column(name: 'termination_date', type: 'date', nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?\DateTimeInterface $terminationDate = null;

    #[ORM\Column(name: 'workload_hours', type: 'integer', nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?int $workloadHours = null;

    #[ORM\Column(name: 'linkedin_url', type: 'string', length: 255, nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?string $linkedinUrl = null;

    #[ORM\Column(name: 'linkedin_headline', type: 'string', length: 255, nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?string $linkedinHeadline = null;

    #[ORM\Column(name: 'linkedin_summary', type: 'text', nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?string $linkedinSummary = null;

    #[ORM\Column(name: 'linkedin_snapshot', type: 'json', nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private array $linkedinSnapshot = [];

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private ?string $notes = null;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Groups(['employee_profile:read', 'employee_profile:write'])]
    private bool $active = true;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP')]
    #[Groups(['employee_profile:read'])]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'alter_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    #[Groups(['employee_profile:read'])]
    private ?\DateTimeInterface $alterDate = null;

    public function __construct()
    {
        $now = new \DateTime('now');
        $this->creationDate = $now;
        $this->alterDate = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeopleLink(): ?PeopleLink
    {
        return $this->peopleLink;
    }

    public function setPeopleLink(?PeopleLink $peopleLink): self
    {
        $this->peopleLink = $peopleLink;

        return $this;
    }

    public function getJobTitleCategory(): ?Category
    {
        return $this->jobTitleCategory;
    }

    public function setJobTitleCategory(?Category $jobTitleCategory): self
    {
        $this->jobTitleCategory = $jobTitleCategory;

        return $this;
    }

    public function getJobFunctionCategory(): ?Category
    {
        return $this->jobFunctionCategory;
    }

    public function setJobFunctionCategory(?Category $jobFunctionCategory): self
    {
        $this->jobFunctionCategory = $jobFunctionCategory;

        return $this;
    }

    public function getDepartmentCategory(): ?Category
    {
        return $this->departmentCategory;
    }

    public function setDepartmentCategory(?Category $departmentCategory): self
    {
        $this->departmentCategory = $departmentCategory;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $jobTitle = $jobTitle !== null ? trim($jobTitle) : null;
        $this->jobTitle = $jobTitle !== '' ? $jobTitle : null;

        return $this;
    }

    public function getJobFunction(): ?string
    {
        return $this->jobFunction;
    }

    public function setJobFunction(?string $jobFunction): self
    {
        $jobFunction = $jobFunction !== null ? trim($jobFunction) : null;
        $this->jobFunction = $jobFunction !== '' ? $jobFunction : null;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $department = $department !== null ? trim($department) : null;
        $this->department = $department !== '' ? $department : null;

        return $this;
    }

    public function getEmploymentType(): ?string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(?string $employmentType): self
    {
        $employmentType = $employmentType !== null ? trim($employmentType) : null;
        $this->employmentType = $employmentType !== '' ? $employmentType : null;

        return $this;
    }

    public function getAdmissionDate(): ?\DateTimeInterface
    {
        return $this->admissionDate;
    }

    public function setAdmissionDate(?\DateTimeInterface $admissionDate): self
    {
        $this->admissionDate = $admissionDate;

        return $this;
    }

    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->terminationDate;
    }

    public function setTerminationDate(?\DateTimeInterface $terminationDate): self
    {
        $this->terminationDate = $terminationDate;

        return $this;
    }

    public function getWorkloadHours(): ?int
    {
        return $this->workloadHours;
    }

    public function setWorkloadHours(?int $workloadHours): self
    {
        $this->workloadHours = $workloadHours !== null ? max(0, $workloadHours) : null;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): self
    {
        $linkedinUrl = $linkedinUrl !== null ? trim($linkedinUrl) : null;
        $this->linkedinUrl = $linkedinUrl !== '' ? $linkedinUrl : null;

        return $this;
    }

    public function getLinkedinHeadline(): ?string
    {
        return $this->linkedinHeadline;
    }

    public function setLinkedinHeadline(?string $linkedinHeadline): self
    {
        $linkedinHeadline = $linkedinHeadline !== null ? trim($linkedinHeadline) : null;
        $this->linkedinHeadline = $linkedinHeadline !== '' ? $linkedinHeadline : null;

        return $this;
    }

    public function getLinkedinSummary(): ?string
    {
        return $this->linkedinSummary;
    }

    public function setLinkedinSummary(?string $linkedinSummary): self
    {
        $linkedinSummary = $linkedinSummary !== null ? trim($linkedinSummary) : null;
        $this->linkedinSummary = $linkedinSummary !== '' ? $linkedinSummary : null;

        return $this;
    }

    public function getLinkedinSnapshot(): array
    {
        return $this->linkedinSnapshot;
    }

    public function setLinkedinSnapshot(array $linkedinSnapshot): self
    {
        $this->linkedinSnapshot = $linkedinSnapshot;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $notes = $notes !== null ? trim($notes) : null;
        $this->notes = $notes !== '' ? $notes : null;

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

    #[Groups(['employee_profile:read'])]
    public function getJobTitleLabel(): string
    {
        return $this->resolveCategoryLabel($this->jobTitleCategory, $this->jobTitle);
    }

    #[Groups(['employee_profile:read'])]
    public function getJobFunctionLabel(): string
    {
        return $this->resolveCategoryLabel($this->jobFunctionCategory, $this->jobFunction);
    }

    #[Groups(['employee_profile:read'])]
    public function getDepartmentLabel(): string
    {
        return $this->resolveCategoryLabel($this->departmentCategory, $this->department);
    }

    #[Groups(['employee_profile:read'])]
    public function getPeopleLinkId(): ?int
    {
        return $this->peopleLink?->getId();
    }

    #[Groups(['employee_profile:read'])]
    public function getPeopleId(): ?int
    {
        return $this->peopleLink?->getPeople()?->getId();
    }

    #[Groups(['employee_profile:read'])]
    public function getCompanyId(): ?int
    {
        return $this->peopleLink?->getCompany()?->getId();
    }

    #[Groups(['employee_profile:read'])]
    public function getLinkType(): ?string
    {
        return $this->peopleLink?->getLinkType();
    }

    #[Groups(['employee_profile:read'])]
    public function getPeopleLabel(): string
    {
        return $this->resolvePeopleLabel($this->peopleLink?->getPeople());
    }

    #[Groups(['employee_profile:read'])]
    public function getCompanyLabel(): string
    {
        return $this->resolvePeopleLabel($this->peopleLink?->getCompany());
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

    private function resolveCategoryLabel(?Category $category, ?string $legacyValue = null): string
    {
        $categoryLabel = trim((string) ($category?->getName() ?? ''));
        if ($categoryLabel !== '') {
            return $categoryLabel;
        }

        $legacyLabel = trim((string) $legacyValue);
        return $legacyLabel !== '' ? $legacyLabel : '';
    }
}
