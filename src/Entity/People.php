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
use stdClass;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ControleOnline\Controller\AsaasWebhookController;
use Symfony\Component\Validator\Constraints as Assert;
use ControleOnline\Controller\IncomeStatementAction;
use ControleOnline\Filter\CustomOrFilter;

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\PeopleRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [

        new Get(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
        ),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['people:write']],
            denormalizationContext: ['groups' => ['people:write']]
        ),

        new Post(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/webhook/asaas/return/{id}',
            controller: AsaasWebhookController::class
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
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
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    security: 'is_granted(\'ROLE_CLIENT\')',
    normalizationContext: ['groups' => ['people:read']],
    denormalizationContext: ['groups' => ['people:write']]
)]
#[ApiFilter(CustomOrFilter::class, properties: ['name', 'id', 'alias'])]

class People
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "contract:read","people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *      "order_product_queue:read","model:read","model_detail:read",
     *       "user:read","contract_people:read",
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read", "productsByDay:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]

    private $id;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "contract:read","people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       
     * "model:read","model_detail:read","contract_people:read",
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read", "productsByDay:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['enable' => 'exact'])]

    private $enable = 0;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "contract:read","people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *      "order_product_queue:read","model:read","model_detail:read",
     *       "user:read","contract_people:read",
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read", "productsByDay:read"
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
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "contract:read","people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *      "order_product_queue:read","model:read","model_detail:read",
     *       "contract_people:read",
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['alias' => 'partial'])]

    private $alias;
    /**
     * @var string
     *
     * @ORM\Column(name="other_informations", type="json",  nullable=true)
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       "contract_people:read",
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    private $otherInformations;
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "contract:read","people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       "contract_people:read",
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact'])]

    private $peopleType = 'F';

    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File", inversedBy="people")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * })
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       "contract_people:read",
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    private $image;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Config", mappedBy="people")
     * @ORM\OrderBy({"configKey" = "ASC"})
     */
    private $config;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="alternative_image", referencedColumnName="id")
     * })
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    private $alternative_image;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="background_image", referencedColumnName="id")
     * })
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    private $background;
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
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['link.link_type' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['link.company' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['link.people' => 'exact'])]

    private $link;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\User", mappedBy="people")
     * @ORM\OrderBy({"username" = "ASC"})
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write",
     *      "order_detail_status:read", 
     *       
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['user' => 'exact'])]

    private $user;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Document", mappedBy="people")
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write", 
     *      "order_detail_status:read", 
     *       
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['document' => 'exact'])]

    private $document;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Address", mappedBy="people")
     * @ORM\OrderBy({"nickname" = "ASC"})
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write",
     *      "order_detail_status:read", 
     *       
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['address' => 'exact'])]

    private $address;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Phone", mappedBy="people")
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write",
     *      "order_detail_status:read", 
     *       "invoice_details:read",
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['phone' => 'exact'])]

    private $phone;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Email", mappedBy="people")
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "people:write",
     *      "order_detail_status:read", 
     *       "invoice_details:read",
     *     "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['email' => 'exact'])]

    private $email;


    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     * @Groups({
     *     "category:read","order:read","order_details:read","order:write", "document:read", "email:read", "people:read", "contract:read","people:write", "invoice:read","invoice_details:read",
     *      "order_detail_status:read", 
     *       
     *      "task:read", "task_interaction:read","coupon:read","logistic:read",
     *     "pruduct:read","queue:read","display:read","notifications:read","people_provider:read"
     * })
     */
    private $foundationDate = null;


    public function __construct()
    {
        $this->enable = 0;
        $this->registerDate =            new \DateTime('now');
        $this->company =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->config =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->link =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->user =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->document =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->address =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->email =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->phone =            new \Doctrine\Common\Collections\ArrayCollection();
        $this->otherInformations = json_encode(
            new stdClass()
        );
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
    public function setLanguage(Language $language = null)
    {
        $this->language = $language;
        return $this;
    }
    public function getLanguage()
    {
        return $this->language;
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

    /**
     * Get })
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Set })
     */
    public function setBackground($background): self
    {
        $this->background = $background;

        return $this;
    }

    /**
     * Get })
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set })
     */
    public function setImage($image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get })
     */
    public function getAlternativeImage()
    {
        return $this->alternative_image;
    }

    /**
     * Set })
     */
    public function setAlternativeImage($alternative_image): self
    {
        $this->alternative_image = $alternative_image;

        return $this;
    }
}
