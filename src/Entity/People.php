<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\File;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\User;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Listener\LogListener;
use ControleOnline\Controller\GetDefaultCompanyAction;
use ControleOnline\Controller\GetMyCompaniesAction;
use ControleOnline\Filter\CustomOrFilter;
use DateTime;
use DateTimeInterface;
use stdClass;

#[ORM\Table(name: 'people')]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: PeopleRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people:read']],
    denormalizationContext: ['groups' => ['people:write']],
    security: "is_granted('ROLE_CLIENT')",
    operations: [
        new GetCollection(
            securityPostDenormalize: "is_granted('ROLE_CLIENT')"
        ),
        new GetCollection(
            uriTemplate: '/people/company/default',
            controller: GetDefaultCompanyAction::class,
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new GetCollection(
            uriTemplate: '/people/companies/my',
            controller: GetMyCompaniesAction::class,
            security: "is_granted('ROLE_CLIENT')"
        ),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(securityPostDenormalize: "is_granted('ROLE_CLIENT')"),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')",
            validationContext: ['groups' => ['people:write']],
            denormalizationContext: ['groups' => ['people:write']]
        ),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ]
)]
#[ApiFilter(CustomOrFilter::class, properties: ['name', 'id', 'alias'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'enable' => 'exact',
    'name' => 'partial',
    'alias' => 'partial',
    'peopleType' => 'exact',
    'link.link_type' => 'exact',
    'link.company' => 'exact',
    'link.people' => 'exact',
    'user' => 'exact',
    'document' => 'exact',
    'address' => 'exact',
    'phone' => 'exact',
    'email' => 'exact'
])]
class People
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'contract:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'order_product_queue:read',
        'model:read',
        'model_detail:read',
        'user:read',
        'contract_people:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read',
        'productsByDay:read'
    ])]
    private $id;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'contract:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'order_product_queue:read',
        'model:read',
        'model_detail:read',
        'user:read',
        'contract_people:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read',
        'productsByDay:read'
    ])]
    private $enable = 0;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'contract:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'order_product_queue:read',
        'model:read',
        'model_detail:read',
        'user:read',
        'contract_people:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read',
        'productsByDay:read'
    ])]
    private $name = '';

    #[ORM\Column(type: 'datetime', nullable: false, columnDefinition: 'DATETIME')]
    private $registerDate;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'contract:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'order_product_queue:read',
        'model:read',
        'model_detail:read',
        'contract_people:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $alias = '';

    #[ORM\Column(name: 'other_informations', type: 'json', nullable: true)]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'contract_people:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $otherInformations;

    #[ORM\Column(type: 'string', length: 1, nullable: false)]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'contract:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'contract_people:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $peopleType = 'F';

    #[ORM\ManyToOne(targetEntity: File::class, inversedBy: 'people')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    #[Groups([
        'category:read',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'contract_people:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $image;

    #[ORM\OneToMany(targetEntity: Config::class, mappedBy: 'people')]
    #[ORM\OrderBy(['configKey' => 'ASC'])]
    private $config;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'alternative_image', referencedColumnName: 'id')]
    #[Groups([
        'category:read',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $alternative_image;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'background_image', referencedColumnName: 'id')]
    #[Groups([
        'category:read',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $background;

    #[ORM\ManyToOne(targetEntity: Language::class, inversedBy: 'people')]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id')]
    private $language;

    #[ORM\OneToMany(targetEntity: PeopleLink::class, mappedBy: 'company')]
    private $company;

    #[ORM\OneToMany(targetEntity: PeopleLink::class, mappedBy: 'people')]
    private $link;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'people')]
    #[ORM\OrderBy(['username' => 'ASC'])]
    #[Groups([
        'category:read',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'order_detail_status:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $user;

    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'people')]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        // 'document:read', 
        'email:read',
        'people:read',
        'people:write',
        'order_detail_status:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $document;

    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'people')]
    #[ORM\OrderBy(['nickname' => 'ASC'])]
    #[Groups([
        'category:read',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'order_detail_status:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $address;

    #[ORM\OneToMany(targetEntity: Phone::class, mappedBy: 'people')]
    #[Groups([
        'category:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'order_detail_status:read',
        'invoice_details:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $phone;

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'people')]
    #[Groups([
        'category:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'people:write',
        'order_detail_status:read',
        'invoice_details:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $email;

    #[ORM\Column(type: 'datetime', nullable: false, columnDefinition: 'DATETIME')]
    #[Groups([
        'category:read',
        'connections:read',
        'order:read',
        'order_details:read', 'order:write',
        'order:write',
        'document:read',
        'email:read',
        'people:read',
        'contract:read',
        'people:write',
        'invoice:read',
        'invoice_details:read',
        'order_detail_status:read',
        'task:read',
        'task_interaction:read',
        'coupon:read',
        'logistic:read',
        'pruduct:read',
        'queue:read',
        'display:read',
        'notifications:read',
        'people_provider:read'
    ])]
    private $foundationDate = null;

    public function __construct()
    {
        $this->enable = 0;
        $this->registerDate = new DateTime('now');
        $this->company = new ArrayCollection();
        $this->config = new ArrayCollection();
        $this->link = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->document = new ArrayCollection();
        $this->address = new ArrayCollection();
        $this->email = new ArrayCollection();
        $this->phone = new ArrayCollection();
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

    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;
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

    public function getName(): string
    {
        return strtoupper((string) $this->name);
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function getAlias()
    {
        return strtoupper((string) $this->alias);
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

    public function getDocument()
    {
        return $this->document;
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
        return $decode ? (object) json_decode(is_array($this->otherInformations) ? json_encode($this->otherInformations) : $this->otherInformations) : $this->otherInformations;
    }

    public function addOtherInformations($key, $value)
    {
        $otherInformations = $this->getOtherInformations(true);
        $otherInformations->{$key} = $value;
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }

    public function setOtherInformations(stdClass $otherInformations)
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

    public function getBackground()
    {
        return $this->background;
    }

    public function setBackground($background): self
    {
        $this->background = $background;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getAlternativeImage()
    {
        return $this->alternative_image;
    }

    public function setAlternativeImage($alternative_image): self
    {
        $this->alternative_image = $alternative_image;
        return $this;
    }
}
