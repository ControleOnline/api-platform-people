<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use App\Controller\GetCloseProfessionalsAction;
use ControleOnline\Controller\GetDefaultCompanyAction;
use ControleOnline\Controller\GetMyCompaniesAction;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ControleOnline\Controller\CreateUserAction;
use stdClass;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
use ControleOnline\Controller\IncomeStatementAction;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\PeopleRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [

        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(
            uriTemplate: '/people/{id}/add-user',
            controller: CreateUserAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new GetCollection(
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/people/company/default',
            controller: GetDefaultCompanyAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/companies/my',
            controller: GetMyCompaniesAction::class
        ),

        new GetCollection(
            uriTemplate: '/people/professionals/close/{lat}/{lng}',
            openapiContext: [],
            controller: GetCloseProfessionalsAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    security: 'is_granted(\'ROLE_CLIENT\')',
    normalizationContext: ['groups' => ['people_read']],
    denormalizationContext: ['groups' => ['people_write']]
)]
class People
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({
     *     "category_read","order_read", "document_read", "email_read", "people_read", "invoice_read",
     *      "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *      "task_read", "task_interaction_read","coupon_read","logistic_read",
     *     "pruduct_read","queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]

    private $id;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     * @Groups({
     *     "category_read","order_read", "document_read", "email_read", "people_read", "invoice_read",
     *      "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *      "task_read", "task_interaction_read","coupon_read","logistic_read",
     *     "pruduct_read","queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['enable' => 'exact'])]

    private $enable = 0;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     * @Groups({
     *     "category_read","order_read", "document_read", "email_read", "people_read", "invoice_read",
     *      "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *      "task_read", "task_interaction_read","coupon_read","logistic_read",
     *     "pruduct_read","queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['icms' => 'exact'])]

    private $icms = 1;
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({
     *     "category_read","order_read", "document_read", "email_read", "people_read",
     *     "invoice_read",  "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *      "task_read", "task_interaction_read","coupon_read", "logistic_read",
     *     "queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial'])]

    private $name;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $registerDate;
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({
     *     "category_read","order_read", "document_read", "email_read", "people_read", "invoice_read",
     *      "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *      "task_read", "task_interaction_read","coupon_read","logistic_read",
     *     "pruduct_read","queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['alias' => 'partial'])]

    private $alias;
    /**
     * @var string
     *
     * @ORM\Column(name="other_informations", type="json",  nullable=true)
     * @Groups({
     *     "order_read", "document_read", "email_read", "people_read", "invoice_read",
     *      "order_detail_status_read", "mycontract_read",
     *      "my_contract_item_read", "mycontractpeople_read", 
     *      "task_read", "task_interaction_read","coupon_read"
     * }) 
     */
    private $otherInformations;
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Groups({"pruduct_read","display_read","people_read", "my_contract_item_read", "mycontractpeople_read", "task_read", "task_interaction_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact'])]

    private $peopleType = 'F';
    /**
     * @ORM\Column(type="float", nullable=false)
     * @Groups({"people_read"})
     */
    private $billing = 0;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File", inversedBy="people")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * })
     * @Groups({"people_read","display_read"})
     */
    private $file;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Config", mappedBy="people")
     * @ORM\OrderBy({"config_key" = "ASC"})
     */
    private $config;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="alternative_image", referencedColumnName="id")
     * })
     */
    private $alternativeFile;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="background_image", referencedColumnName="id")
     * })
     */
    private $backgroundFile;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Language", inversedBy="people")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="language_id", referencedColumnName="id")
     * })
     */
    private $language;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\PeopleLink", mappedBy="company")
     */
    private $company;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\PeopleLink", mappedBy="people")
     * 
     */
    private $link;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\User", mappedBy="people")
     * @ORM\OrderBy({"username" = "ASC"})
     * @Groups({"people_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['user' => 'exact'])]

    private $user;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Document", mappedBy="people")
     * @Groups({"people_read", "task_interaction_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['document' => 'exact'])]

    private $document;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Address", mappedBy="people")
     * @ORM\OrderBy({"nickname" = "ASC"})
     * @Groups({"people_read", "logistic_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['address' => 'exact'])]

    private $address;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Phone", mappedBy="people")
     * @Groups({"people_read",   "task_interaction_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['phone' => 'exact'])]

    private $phone;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Email", mappedBy="people")
     * @Groups({"people_read", "get_contracts",  "task_interaction_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['email' => 'exact'])]

    private $email;
    /**
     * Many Peoples have Many Contracts.
     *
     * @ORM\OneToMany (targetEntity="ControleOnline\Entity\ContractPeople", mappedBy="people")
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['contractsPeople' => 'exact'])]

    private $contractsPeople;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"people_read"})
     */
    private $billingDays;
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Groups({"people_read", "my_contract_item_read", "mycontractpeople_read"})
     */
    private $paymentTerm;


    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     * @Groups({"people_read", "my_contract_item_read", "mycontractpeople_read"})
     */
    private $foundationDate = null;
    /**
     * @Groups({"people_read", "my_contract_item_read", "mycontractpeople_read"})
     */
    private $averageRating = 4;
    public function __construct()
    {
        $this->enable = 0;
        $this->icms = 1;
        $this->billing = 0;
        $this->registerDate =            new \DateTime('now');
        $this->people =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->config =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->link =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->user =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->document =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->address =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->email =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->phone =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->billingDays = 'daily';
        $this->paymentTerm = 1;

        $this->otherInformations = json_encode(
            new stdClass()
        );
    }
    public function getId()
    {
        return $this->id;
    }
    public function getAverageRating()
    {
        return $this->averageRating;
    }
    public function setAverageRating($averageRating)
    {
        $this->averageRating = $averageRating;
        return $this;
    }
    public function getIcms()
    {
        return $this->icms;
    }
    public function setIcms($icms)
    {
        $this->icms = $icms ?: 0;
        return $this;
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
    /**
     * Set name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get name.
     */
    public function getName(): string
    {
        return strtoupper($this->name);
    }
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    public function getAlias()
    {
        return strtoupper($this->alias);
    }
    public function setFile(File $file = null)
    {
        $this->file = $file;
        return $this;
    }
    public function getFile()
    {
        return $this->file;
    }
    public function setAlternativeFile(File $alternative_file = null)
    {
        $this->alternativeFile = $alternative_file;
        return $this;
    }
    public function getAlternativeFile()
    {
        return $this->alternativeFile;
    }
    public function getBackgroundFile()
    {
        return $this->backgroundFile;
    }
    public function setBackgroundFile(File $backgroundFile = null)
    {
        $this->backgroundFile = $backgroundFile;
        return $this;
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
    public function setBilling($billing)
    {
        $this->billing = $billing;
        return $this;
    }
    public function getBilling()
    {
        return $this->billing;
    }
    public function getRegisterDate(): \DateTimeInterface
    {
        return $this->registerDate;
    }
    public function setRegisterDate(\DateTimeInterface $registerDate): self
    {
        $this->registerDate = $registerDate;
        return $this;
    }
    /**
     * Add document.
     *
     * @return Document
     */
    public function addDocument(Document $document)
    {
        $this->document[] = $document;
        return $this;
    }
    /**
     * Add company.
     *
     * @return Company
     */
    public function addCompany(People $company)
    {
        $this->company[] = $company;
        return $this;
    }
    /**
     * Remove company.
     */
    public function removeCompany(People $company)
    {
        $this->company->removeElement($company);
    }
    /**
     * Get company.
     *
     * @return Collection
     */
    public function getCompany()
    {
        return $this->company;
    }
    /**
     * Add link.
     *
     * @return People
     */
    public function addLink(People $link)
    {
        $this->link[] = $link;
        return $this;
    }
    /**
     * Remove link.
     *
     * @param \Core\Entity\Link $link
     */
    public function removeLink(People $link)
    {
        $this->link->removeElement($link);
    }
    /**
     * Get link.
     *
     * @return Collection
     */
    public function getLink()
    {
        return $this->link;
    }
    /**
     * Add user.
     *
     * @return People
     */
    public function addUser(\ControleOnline\Entity\User $user)
    {
        $this->user[] = $user;
        return $this;
    }
    /**
     * Remove user.
     */
    public function removeUser(\ControleOnline\Entity\User $user)
    {
        $this->user->removeElement($user);
    }
    /**
     * Get user.
     *
     * @return Collection
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * Get document.
     *
     * @return Collection
     */
    public function getDocument()
    {
        return $this->document;
    }
    /**
     * Get address.
     *
     * @return Collection
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * Get document.
     *
     * @return Collection
     */
    public function getPhone()
    {
        return $this->phone;
    }
    /**
     * Get email.
     *
     * @return Collection
     */
    public function getEmail()
    {
        return $this->email;
    }
    public function getContractsPeople(): Collection
    {
        return $this->contractsPeople;
    }
    public function setBillingDays(string $billingDays): self
    {
        $this->billingDays = $billingDays;
        return $this;
    }
    public function getBillingDays(): string
    {
        return $this->billingDays;
    }
    public function setPaymentTerm(int $paymentTerm): self
    {
        $this->paymentTerm = $paymentTerm;
        return $this;
    }
    public function getPaymentTerm(): int
    {
        return $this->paymentTerm;
    }
    public function getFoundationDate(): ?\DateTime
    {
        return $this->foundationDate;
    }
    public function setFoundationDate(\DateTimeInterface $date): self
    {
        $this->foundationDate = $date;
        return $this;
    }
    public function getFullName(): string
    {
        if ($this->getPeopleType() == 'F') {
            return trim(preg_replace('/[^A-Za-z\s]/', '', sprintf('%s %s', $this->getName(), $this->getAlias())));
        }
        return trim(preg_replace('/[^A-Za-z\s]/', '', $this->getName()));
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
        if ($this->getFoundationDate() instanceof \DateTimeInterface) {
            return $this->getFoundationDate()->format('Y-m-d');
        }
        return null;
    }
    /**
     * Get otherInformations
     *
     * @return stdClass
     */
    public function getOtherInformations($decode = false)
    {
        return $decode ? (object) json_decode(is_array($this->otherInformations) ? json_encode($this->otherInformations) : $this->otherInformations) : $this->otherInformations;
    }
    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function addOtherInformations($key, $value)
    {
        $otherInformations = $this->getOtherInformations(true);
        $otherInformations->{$key} = $value;
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }
    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function setOtherInformations(stdClass $otherInformations)
    {
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }
    /**
     * Add Config.
     *
     * @return People
     */
    public function addConfig(\ControleOnline\Entity\Config $config)
    {
        $this->config[] = $config;
        return $this;
    }
    /**
     * Remove Config.
     */
    public function removeConfig(\ControleOnline\Entity\Config $config)
    {
        $this->config->removeElement($config);
    }
    /**
     * Get config.
     *
     * @return Collection
     */
    public function getConfig()
    {
        return $this->config;
    }
}
