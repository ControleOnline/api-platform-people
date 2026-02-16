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
use ControleOnline\Filter\CustomOrFilter;
use Doctrine\ORM\Mapping as ORM;
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
use ControleOnline\Entity\CompanyDocument;
use DateTime;
use DateTimeInterface;
use stdClass;

#[ORM\Table(name: 'people')]
#[ORM\Entity(repositoryClass: PeopleRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people:read']],
    denormalizationContext: ['groups' => ['people:write']],
    security: "is_granted('ROLE_CLIENT')",
    operations: [
        new GetCollection(securityPostDenormalize: "is_granted('ROLE_CLIENT')"),
        new GetCollection(
            uriTemplate: '/people/company/default',
            controller: \ControleOnline\Controller\GetDefaultCompanyAction::class,
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new GetCollection(
            uriTemplate: '/people/companies/my',
            controller: \ControleOnline\Controller\GetMyCompaniesAction::class,
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
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $id;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $enable = 0;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $name = '';

    #[ORM\Column(type: 'datetime', columnDefinition: 'DATETIME')]
    private $registerDate;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $alias = '';

    #[ORM\Column(name: 'other_informations', type: 'json', nullable: true)]
    #[Groups(['people:read', 'people_link:read', 'people:write'])]
    private $otherInformations;

    #[ORM\Column(type: 'string', length: 1)]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
    private $peopleType = 'F';

    #[ORM\ManyToOne(targetEntity: File::class, inversedBy: 'people')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id', 'order_details:read')]
    #[Groups(['people:read', 'people:write'])]
    private $image;

    #[ORM\OneToMany(targetEntity: Config::class, mappedBy: 'people')]
    private $config;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'alternative_image', referencedColumnName: 'id')]
    #[Groups(['people:read', 'people:write'])]
    private $alternative_image;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'background_image', referencedColumnName: 'id')]
    #[Groups(['people:read', 'people:write'])]
    private $background;

    #[ORM\ManyToOne(targetEntity: Language::class, inversedBy: 'people')]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id')]
    private $language;

    #[ORM\OneToMany(targetEntity: PeopleLink::class, mappedBy: 'company')]
    private $company;

    #[ORM\OneToMany(targetEntity: PeopleLink::class, mappedBy: 'people')]
    private $link;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'people')]
    #[Groups(['people:read', 'people:write'])]
    private $user;

    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'people')]
    #[Groups(['people:read',  'people:write', 'order_details:read'])]
    private $document;

    #[ORM\OneToMany(targetEntity: CompanyDocument::class, mappedBy: 'people')]
    #[Groups(['people:read',  'people:write'])]
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

    #[ORM\Column(type: 'datetime', columnDefinition: 'DATETIME', nullable: false)]
    #[Groups(['people:read', 'people_link:read', 'people:write', 'order_details:read'])]
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
}
