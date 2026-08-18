<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Repository\PeopleCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Associação pessoa ↔ categoria com período (timeline).
 * Contextos canônicos:
 * - PF: profession, position (cargo — exige people_company_id)
 * - PJ: sector, activity_branch
 */
#[ORM\Table(name: 'people_category')]
#[ORM\Index(name: 'idx_people_category_people', columns: ['people_id'])]
#[ORM\Index(name: 'idx_people_category_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_people_category_company', columns: ['people_company_id'])]
#[ORM\Entity(repositoryClass: PeopleCategoryRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people_category:read']],
    denormalizationContext: ['groups' => ['people_category:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new GetCollection(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new Post(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        new Put(security: "is_granted('ROLE_HUMAN')"),
        new Delete(security: "is_granted('ROLE_HUMAN')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'people' => 'exact',
    'category' => 'exact',
    'peopleCompany' => 'exact',
    'category.context' => 'exact',
    'category.name' => 'partial',
])]
#[ApiFilter(DateFilter::class, properties: ['startDate', 'endDate'])]
#[ApiFilter(OrderFilter::class, properties: ['startDate', 'endDate', 'id'])]
class PeopleCategory
{
    public const CONTEXT_PROFESSION = 'profession';
    public const CONTEXT_POSITION = 'position';
    public const CONTEXT_SECTOR = 'sector';
    public const CONTEXT_ACTIVITY_BRANCH = 'activity_branch';

    public const PF_CONTEXTS = [self::CONTEXT_PROFESSION, self::CONTEXT_POSITION];
    public const PJ_CONTEXTS = [self::CONTEXT_SECTOR, self::CONTEXT_ACTIVITY_BRANCH];

    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_category:read', 'people_category:write', 'people:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank]
    #[Groups(['people_category:read', 'people_category:write'])]
    private ?People $people = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank]
    #[Groups(['people_category:read', 'people_category:write', 'people:read'])]
    private ?Category $category = null;

    /**
     * Obrigatório quando a categoria tem context = position (cargo vinculado à empresa).
     */
    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_company_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['people_category:read', 'people_category:write', 'people:read'])]
    private ?People $peopleCompany = null;

    #[ORM\Column(name: 'start_date', type: 'date', nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['people_category:read', 'people_category:write', 'people:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: 'date', nullable: true)]
    #[Groups(['people_category:read', 'people_category:write', 'people:read'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Groups(['people_category:read', 'people_category:write', 'people:read'])]
    private bool $active = true;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP')]
    #[Groups(['people_category:read'])]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'alter_date', type: 'datetime', nullable: false, columnDefinition: 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    #[Groups(['people_category:read'])]
    private ?\DateTimeInterface $alterDate = null;

    public function __construct()
    {
        $now = new \DateTime('now');
        $this->creationDate = $now;
        $this->alterDate = $now;
        $this->startDate = $now;
        $this->active = true;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getPeopleCompany(): ?People
    {
        return $this->peopleCompany;
    }

    public function setPeopleCompany(?People $peopleCompany): self
    {
        $this->peopleCompany = $peopleCompany;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(mixed $startDate): self
    {
        $this->startDate = $this->normalizeDateValue($startDate);
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(mixed $endDate): self
    {
        $this->endDate = $endDate !== null && $endDate !== '' ? $this->normalizeDateValue($endDate) : null;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function getAlterDate(): ?\DateTimeInterface
    {
        return $this->alterDate;
    }

    private function normalizeDateValue(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            try {
                return new \DateTime($value);
            } catch (\Exception) {
                return null;
            }
        }
        return null;
    }
}
