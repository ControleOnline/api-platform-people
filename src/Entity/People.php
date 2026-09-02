<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ControleOnline\Controller\CreateAccountAction;
use ControleOnline\Filter\CustomOrFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\User;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Entity\CompanyDocument;
use ControleOnline\State\HydratedReadProvider;
use ControleOnline\State\PeopleSoftDeleteProcessor;
use DateTime;
use DateTimeInterface;
use stdClass;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ORM\Table(name: 'people')]
#[ORM\Entity(repositoryClass: PeopleRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people:read']],
    denormalizationContext: [
        'groups' => ['people:write'],
        AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
    ],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new GetCollection(
            provider: HydratedReadProvider::class,
            securityPostDenormalize: "is_granted('ROLE_HUMAN')"
        ),
        new GetCollection(
            uriTemplate: '/people/company/default',
            controller: \ControleOnline\Controller\GetDefaultCompanyAction::class,
            read: false,
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new GetCollection(
            uriTemplate: '/shop/franchises',
            controller: \ControleOnline\Controller\GetPublicShopFranchisesAction::class,
            read: false,
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new GetCollection(
            uriTemplate: '/people/companies/my',
            controller: \ControleOnline\Controller\GetMyCompaniesAction::class,
            read: false,
            security: "is_granted('ROLE_HUMAN')"
        ),
        new GetCollection(
            uriTemplate: '/people/franchise-owner-candidates',
            controller: \ControleOnline\Controller\GetFranchiseOwnerCandidatesAction::class,
            read: false,
            security: "is_granted('ROLE_HUMAN')"
        ),
        new Post(
            uriTemplate: '/create-account',
            controller: CreateAccountAction::class,
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            deserialize: false,
            read: false,
            output: false,
            status: 202,
        ),
        new Post(
            uriTemplate: '/users/create-account',
            controller: CreateAccountAction::class,
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            deserialize: false,
            read: false,
            output: false,
            status: 202,
        ),
        new Get(
            provider: HydratedReadProvider::class,
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new Post(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        new Put(
            security: "is_granted('ROLE_HUMAN')",
            validationContext: ['groups' => ['people:write']],
            denormalizationContext: [
                'groups' => ['people:write'],
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]
        ),
        new Delete(
            processor: PeopleSoftDeleteProcessor::class,
            security: "is_granted('ROLE_HUMAN')"
        )
    ],
    order: ['name' => 'ASC', 'id' => 'DESC']
)]
#[ApiFilter(CustomOrFilter::class, properties: [
    'name',
    'id',
    'alias',
    'email.email',
    'phone.ddd',
    'phone.phone',
    'document.document',
    'address.nickname',
    'address.search_for'
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'name',
    'alias',
    'foundationDate',
    'registerDate',
    'peopleType',
    'enable',
    'deleted'
])]
#[ApiFilter(DateFilter::class, properties: ['foundationDate', 'registerDate'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'enable' => 'exact',
    'deleted' => 'exact',
    'name' => 'partial',
    'alias' => 'partial',
    'peopleType' => 'exact',
    'link.linkType' => 'exact',
    'link.company' => 'exact',
    'link.people' => 'exact',
    'company.linkType' => 'exact',
    'company.people' => 'exact',
    'user' => 'exact',
    'document' => 'exact',
    'address' => 'exact',
    'phone' => 'exact',
    'email' => 'exact'
])]
class People
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['invoice_tax:read', 'invoice:read', 'invoice_list:read', 'people:read', 'product_people:read', 'people_link:read', 'people:write', 'order_product_queue:read', 'orders-queue:read', 'order:read', 'order_details:read', 'order_invoice:read', 'contract:read', 'import:read', 'task:read', 'order_invoice_invoice:read'])]
    private $id;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read', 'contract:read', 'import:read', 'task:read', 'order_invoice_invoice:read'])]
    private $enable = 0;

    /**
     * Soft-delete flag. Operational removal sets deleted=true; physical DELETE is not used.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read', 'contract:read', 'import:read', 'task:read'])]
    private bool $deleted = false;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    #[Groups(['people:read', 'people:write'])]
    private ?DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['invoice_tax:read', 'invoice:read', 'invoice_list:read', 'people:read', 'product_people:read', 'people_link:read', 'people:write', 'order_product_queue:read', 'orders-queue:read', 'order:read', 'order_details:read', 'order_invoice:read', 'contract:read', 'import:read', 'task:read', 'order_invoice_invoice:read'])]
    private $name = '';

    #[ORM\Column(type: 'datetime', columnDefinition: 'DATETIME')]
    private $registerDate;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['invoice_tax:read', 'invoice:read', 'invoice_list:read', 'people:read', 'product_people:read', 'people_link:read', 'people:write', 'order_details:read', 'contract:read', 'import:read', 'task:read', 'order_invoice_invoice:read'])]
    private $alias = '';

    #[ORM\Column(name: 'other_informations', type: 'json', nullable: true)]
    #[Groups(['people:read', 'people_link:read', 'people:write'])]
    private $otherInformations;

    #[ORM\Column(type: 'string', length: 1)]
    #[Groups(['people:read', 'product_people:read', 'people_link:read', 'people:write', 'order_details:read', 'contract:read', 'import:read', 'task:read', 'order_invoice_invoice:read'])]
    private $peopleType = 'F';

    #[ORM\OneToMany(targetEntity: Config::class, mappedBy: 'people')]
    private $config;

    #[ORM\ManyToOne(targetEntity: Language::class, inversedBy: 'people')]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id')]
    private $language;

    #[ORM\OneToMany(targetEntity: PeopleLink::class, mappedBy: 'company')]
    private $company;

    #[ORM\OneToMany(targetEntity: PeopleLink::class, mappedBy: 'people')]
    private $link;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'people')]
    #[Groups(['people:write'])]
    private $user;

    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'people')]
    #[Groups(['people:read', 'people:write', 'order_details:read'])]
    private $document;

    #[ORM\OneToMany(targetEntity: CompanyDocument::class, mappedBy: 'people')]
    #[Groups(['people:read', 'people:write'])]
    private $company_document;

    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'people')]
    #[Groups(['people:read', 'people_link:read', 'people:write'])]
    private $address;

    #[ORM\OneToMany(targetEntity: Phone::class, mappedBy: 'people')]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $phone;

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'people')]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $email;

    #[ORM\OneToMany(targetEntity: ProductPeople::class, mappedBy: 'people')]
    #[Groups(['people:read'])]
    private $productPeople;

    #[ORM\OneToMany(targetEntity: PeopleMedia::class, mappedBy: 'people')]
    #[Groups(['people_link:read', 'people:read'])]
    private $peopleMedia;

    #[ORM\Column(type: 'datetime', columnDefinition: 'DATETIME', nullable: false)]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $foundationDate = null;

    public function __construct()
    {
        $this->enable = 0;
        $this->deleted = false;
        $this->deletedAt = null;
        $this->registerDate = new DateTime('now');
        $this->company = new ArrayCollection();
        $this->config = new ArrayCollection();
        $this->link = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->document = new ArrayCollection();
        $this->company_document = new ArrayCollection();
        $this->address = new ArrayCollection();
        $this->email = new ArrayCollection();
        $this->phone = new ArrayCollection();
        $this->productPeople = new ArrayCollection();
        $this->peopleMedia = new ArrayCollection();
        $this->otherInformations = json_encode(new stdClass());
    }

    public function getId()
    {
        return $this->id;
    }
    public function getEnabled()
    {
        return $this->enable;
    }
    public function getEnable()
    {
        return $this->getEnabled();
    }
    public function setEnabled($enable)
    {
        if (is_numeric($enable)) {
            $enable = ((int) $enable) === 1;
        }
        $this->enable = $enable ?: 0;
        return $this;
    }
    public function setEnable($enable)
    {
        return $this->setEnabled($enable);
    }

    public function isDeleted(): bool
    {
        return (bool) $this->deleted;
    }

    public function getDeleted(): bool
    {
        return $this->isDeleted();
    }

    public function setDeleted(bool|int|string|null $deleted): self
    {
        if (is_numeric($deleted)) {
            $deleted = ((int) $deleted) === 1;
        }
        $this->deleted = (bool) $deleted;
        if ($this->deleted && $this->deletedAt === null) {
            $this->deletedAt = new DateTime('now');
        }
        if (!$this->deleted) {
            $this->deletedAt = null;
        }
        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function setPeopleType($people_type)
    {
        $this->peopleType = $people_type;
        return $this;
    }
    public function getPeopleType()
    {
        return $this->peopleType;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    private function uppercaseText(?string $value): string
    {
        $normalized = (string) $value;

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($normalized, 'UTF-8')
            : strtoupper($normalized);
    }

    public function getName(): string
    {
        return $this->uppercaseText($this->name);
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    public function getAlias()
    {
        return $this->uppercaseText($this->alias);
    }

    public function setLanguage(Language $language = null)
    {
        $this->language = $language;
        return $this;
    }
    public function getLanguage()
    {
        return $this->language;
    }

    public function getRegisterDate(): DateTimeInterface
    {
        return $this->registerDate;
    }
    public function setRegisterDate(DateTimeInterface $registerDate): self
    {
        $this->registerDate = $registerDate;
        return $this;
    }

    public function addDocument(Document $document)
    {
        $this->document[] = $document;
        return $this;
    }
    public function getDocument()
    {
        return $this->document;
    }

    public function addCompany(People $company)
    {
        $this->company[] = $company;
        return $this;
    }
    public function removeCompany(People $company)
    {
        $this->company->removeElement($company);
    }
    public function getCompany()
    {
        return $this->company;
    }

    public function addLink(People $link)
    {
        $this->link[] = $link;
        return $this;
    }
    public function removeLink(People $link)
    {
        $this->link->removeElement($link);
    }
    public function getLink()
    {
        return $this->link;
    }

    public function addUser(User $user)
    {
        $this->user[] = $user;
        return $this;
    }
    public function removeUser(User $user)
    {
        $this->user->removeElement($user);
    }
    public function getUser()
    {
        return $this->user;
    }

    public function getAddress()
    {
        return $this->address;
    }
    public function getPhone()
    {
        return $this->phone;
    }
    public function getEmail()
    {
        return $this->email;
    }

    public function getProductPeople(): Collection
    {
        return $this->productPeople;
    }

    public function addProductPeople(ProductPeople $productPeople): self
    {
        if (!$this->productPeople->contains($productPeople)) {
            $this->productPeople[] = $productPeople;
            $productPeople->setPeople($this);
        }

        return $this;
    }

    public function removeProductPeople(ProductPeople $productPeople): self
    {
        if ($this->productPeople->removeElement($productPeople) && $productPeople->getPeople() === $this) {
            $productPeople->setPeople(null);
        }

        return $this;
    }

    public function getFoundationDate(): ?DateTime
    {
        return $this->foundationDate;
    }
    public function setFoundationDate(DateTimeInterface $date): self
    {
        $this->foundationDate = $date;
        return $this;
    }

    public function getFullName(): string
    {
        if ($this->getPeopleType() == 'F') {
            return trim((string) preg_replace('/[^A-Za-z\s]/', '', sprintf('%s %s', $this->getName(), $this->getAlias())));
        }
        return trim((string) preg_replace('/[^A-Za-z\s]/', '', $this->getName()));
    }

    public function isPerson(): bool
    {
        return $this->getPeopleType() == 'F';
    }

    public function getOneEmail(): ?Email
    {
        if (($email = $this->getEmail()->first()) === false) {
            return null;
        }
        return $email;
    }

    public function getOneDocument(): ?Document
    {
        $documents = $this->getDocument()->filter(function ($peopleDocument) {
            if ($peopleDocument->getPeople()->getPeopleType() == 'F') {
                return $peopleDocument->getDocumentType()->getDocumentType() == 'CPF';
            }
            return $peopleDocument->getDocumentType()->getDocumentType() == 'CNPJ';
        });
        return ($document = $documents->first()) === false ? null : $document;
    }

    public function getBirthdayAsString(): ?string
    {
        if ($this->getFoundationDate() instanceof DateTimeInterface) {
            return $this->getFoundationDate()->format('Y-m-d');
        }
        return null;
    }

    public function getOtherInformations($decode = false)
    {
        return $decode
            ? (object) json_decode(is_array($this->otherInformations) ? json_encode($this->otherInformations) : $this->otherInformations)
            : $this->otherInformations;
    }

    public function addOtherInformations($key, $value)
    {
        $otherInformations = $this->getOtherInformations(true);
        $otherInformations->{$key} = $value;
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }

    public function setOtherInformations(stdClass|array $otherInformations)
    {
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }

    public function addConfig(Config $config)
    {
        $this->config[] = $config;
        return $this;
    }
    public function removeConfig(Config $config)
    {
        $this->config->removeElement($config);
    }
    public function getConfig()
    {
        return $this->config;
    }

    public function getCompanyDocument()
    {
        return $this->company_document;
    }

    public function addCompanyDocument(CompanyDocument $doc)
    {
        $this->company_document[] = $doc;
        return $this;
    }

    public function removeCompanyDocument(CompanyDocument $doc)
    {
        $this->company_document->removeElement($doc);
        return $this;
    }

    public function getPeopleMedia()
    {
        return $this->peopleMedia;
    }

    public function addPeopleMedia(PeopleMedia $peopleMedia): self
    {
        if (!$this->peopleMedia->contains($peopleMedia)) {
            $this->peopleMedia[] = $peopleMedia;
            $peopleMedia->setPeople($this);
        }

        return $this;
    }

    public function removePeopleMedia(PeopleMedia $peopleMedia): self
    {
        if ($this->peopleMedia->removeElement($peopleMedia)) {
            if ($peopleMedia->getPeople() === $this) {
                $peopleMedia->setPeople(null);
            }
        }

        return $this;
    }
}
