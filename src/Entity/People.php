<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\AdminCustomerSalesmanAction;
use App\Controller\AdminPersonAddressesAction;
use App\Controller\AdminPersonBillingAction;
use App\Controller\AdminPersonCompaniesAction;
use App\Controller\AdminPersonDocumentsAction;
use App\Controller\AdminPersonEmailsAction;
use App\Controller\AdminPersonLinksAction;
use App\Controller\AdminPersonFilesAction;
use App\Controller\AdminPersonPhonesAction;
use App\Controller\AdminPersonSummaryAction;
use App\Controller\AdminPersonUsersAction;
use App\Controller\ChangeStatusAction;
use App\Controller\CreateClientAction;
use App\Controller\CreateContactAction;
use App\Controller\CreatePeopleCustomerAction;
use App\Controller\CreateProfessionalAction;
use App\Controller\DownloadPersonFileAction;
use App\Controller\GetClientCollectionAction;
use App\Controller\GetClientCompanyAction;
use App\Controller\GetCloseProfessionalsAction;
use App\Controller\GetCustomerCollectionAction;
use App\Controller\GetDefaultCompanyAction;
use App\Controller\GetMyCompaniesAction;
use App\Controller\GetMySaleCompaniesAction;
use App\Controller\GetPeopleMeAction;
use App\Controller\GetProfessionalCollectionAction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\SearchClassesPeopleAction;
use App\Controller\SearchContactAction;
use App\Controller\SearchContactCompanyAction;
use App\Controller\SearchCustomerSalesmanAction;
use App\Controller\SearchLessonsPeopleAction;
use App\Controller\SearchPeopleAction;
use App\Controller\SearchTasksPeopleAction;
use App\Controller\UpdateClientAction;
use App\Controller\UpdatePeopleProfileAction;
use App\Controller\UploadPersonFilesAction;
use App\Controller\VerifyPeopleStatusAction;
use ControleOnline\Controller\CreateUserAction;
use stdClass;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\PeopleRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [

        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/contact',
            controller: SearchContactAction::class
        ),
        new Post(
            uriTemplate: '/people/{id}/add-user',
            controller: CreateUserAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new Put(
            security: 'is_granted(\'edit\', object)',
            uriTemplate: '/people/{id}/profile/{component}',
            requirements: ['component' => '^(phone|address|email|user|document|link)+$'],
            controller: UpdatePeopleProfileAction::class
        ),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/status',
            controller: VerifyPeopleStatusAction::class
        ),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/classes',
            controller: SearchClassesPeopleAction::class
        ),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/lessons',
            controller: SearchLessonsPeopleAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/change-status',
            controller: ChangeStatusAction::class,
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)'
        ),
        new Get(
            uriTemplate: '/customers/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/customers/{id}/links',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonLinksAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/links',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonLinksAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/links',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonLinksAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonAddressesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonAddressesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonAddressesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonDocumentsAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonDocumentsAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonDocumentsAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonBillingAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonBillingAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonPhonesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonPhonesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonPhonesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonEmailsAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonEmailsAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonEmailsAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonUsersAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonUsersAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonUsersAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminCustomerSalesmanAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminCustomerSalesmanAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminCustomerSalesmanAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonSummaryAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonSummaryAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/files/{fileId}',
            requirements: ['id' => '^\\d+$', 'fileId' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: DownloadPersonFileAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonCompaniesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonCompaniesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonCompaniesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/professionals/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonSummaryAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonSummaryAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/links',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonLinksAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/links',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonLinksAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/links',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonLinksAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonAddressesAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonAddressesAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonAddressesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonDocumentsAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonDocumentsAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonDocumentsAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonEmailsAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonEmailsAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonEmailsAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonPhonesAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonPhonesAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonPhonesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonBillingAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonBillingAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonFilesAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/files/{fileId}',
            requirements: ['id' => '^\\d+$', 'fileId' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: DownloadPersonFileAction::class
        ),

        new Delete(
            uriTemplate: '/professionals/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonFilesAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonUsersAction::class
        ),

        new Put(
            uriTemplate: '/professionals/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonUsersAction::class
        ),

        new Delete(
            uriTemplate: '/professionals/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonUsersAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: AdminPersonCompaniesAction::class
        ),

        new Put(
            uriTemplate: '/professionals/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: AdminPersonCompaniesAction::class
        ),

        new Delete(
            uriTemplate: '/professionals/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: AdminPersonCompaniesAction::class
        ),

        new GetCollection(
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people'
        ),

        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/people/company/default',
            controller: GetDefaultCompanyAction::class
        ),

        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/customer',
            controller: CreatePeopleCustomerAction::class
        ),

        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/customers',
            controller: GetCustomerCollectionAction::class
        ),

        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/customers/search-salesman',
            controller: SearchCustomerSalesmanAction::class
        ),
        new Post(
            uriTemplate: '/customers/files',
            security: 'is_granted(\'ROLE_CLIENT\')',
            controller: UploadPersonFilesAction::class,
            deserialize: false
        ),
        new Post(
            uriTemplate: '/people/contact',
            controller: CreateContactAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/companies/my',
            controller: GetMyCompaniesAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/my-sale-companies',
            controller: GetMySaleCompaniesAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people-search',
            controller: SearchPeopleAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/contact',
            controller: SearchContactCompanyAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/client-company',
            controller: GetClientCompanyAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/me',
            controller: GetPeopleMeAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/tasks/people',
            controller: ControleOnline\Entity\SearchTasksPeopleAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/professionals/close/{lat}/{lng}',
            openapiContext: [],
            controller: GetCloseProfessionalsAction::class
        ),
        new Post(
            uriTemplate: '/professionals',
            controller: CreateProfessionalAction::class,
            securityPostDenormalize: 'is_granted(\'create\', object)'
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/professionals',
            controller: GetProfessionalCollectionAction::class
        ),
        new Get(
            uriTemplate: '/companies/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/companies/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminCompanySalesmanAction::class
        ),
        new Put(
            uriTemplate: '/companies/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminCompanySalesmanAction::class
        ),
        new Delete(
            uriTemplate: '/companies/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminCompanySalesmanAction::class
        ),
        new Get(
            uriTemplate: '/clients/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new Put(
            uriTemplate: '/clients/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'ROLE_CLIENT\')',
            controller: UpdateClientAction::class
        ), new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/clients',
            controller: GetClientCollectionAction::class
        ),
        new Post(
            uriTemplate: '/clients',
            controller: CreateClientAction::class
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
     * @Groups({"category_read","pruduct_read","display_read",  
     *      "task_read", "task_interaction_read","coupon_read","logistic_read","notifications_read","people_provider_read"})
     */
    private $id;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $icms = 1;
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({
     *     "category_read","order_read", "document_read", "email_read", "people_read",
     *     "invoice_read",  "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *      
     *       
     *      "task_read", "task_interaction_read","coupon_read", "logistic_read",
     *     "queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
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
     *       
     *      "task_read", "task_interaction_read","coupon_read","logistic_read",
     *     "pruduct_read","queue_read","display_read","notifications_read","people_provider_read"
     * })
     */
    private $alias;
    /**
     * @var string
     *
     * @ORM\Column(name="other_informations", type="json",  nullable=true)
     * @Groups({
     *     "order_read", "document_read", "email_read", "people_read", "invoice_read",
     *      "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", 
     *       
     *      "task_read", "task_interaction_read","coupon_read"
     * }) 
     */
    private $otherInformations;
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Groups({"pruduct_read","display_read","people_read", "my_contract_item_read", "mycontractpeople_read", "task_read", "task_interaction_read"})
     */
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
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\PeopleLink", mappedBy="people")
     * @ORM\OrderBy({"link" = "ASC"})
     */
    private $people;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\PeopleLink", mappedBy="company")
     * @ORM\OrderBy({"link" = "ASC"})
     */
    private $company;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\User", mappedBy="people")
     * @ORM\OrderBy({"username" = "ASC"})
     * @Groups({"people_read"})
     */
    private $user;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Document", mappedBy="people")
     * @Groups({"people_read", "task_interaction_read"})
     */
    private $document;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Address", mappedBy="people")
     * @ORM\OrderBy({"nickname" = "ASC"})
     * @Groups({"people_read", "logistic_read"})
     */
    private $address;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Phone", mappedBy="people")
     * @Groups({"people_read",   "task_interaction_read"})
     */
    private $phone;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Email", mappedBy="people")
     * @Groups({"people_read", "get_contracts",  "task_interaction_read"})
     */
    private $email;
    /**
     * Many Peoples have Many Contracts.
     *
     * @ORM\OneToMany (targetEntity="ControleOnline\Entity\ContractPeople", mappedBy="people")
     */
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
        $this->company =            new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return People
     */
    public function addDocument(Document $document)
    {
        $this->document[] = $document;
        return $this;
    }
    /**
     * Add people.
     *
     * @return People
     */
    public function addPeople(People $people)
    {
        $this->people[] = $people;
        return $this;
    }
    /**
     * Remove people.
     */
    public function removePeople(People $people)
    {
        $this->people->removeElement($people);
    }
    /**
     * Get people.
     *
     * @return Collection
     */
    public function getPeople()
    {
        return $this->people;
    }
    /**
     * Add company.
     *
     * @return People
     */
    public function addCompany(People $company)
    {
        $this->company[] = $company;
        return $this;
    }
    /**
     * Remove company.
     *
     * @param \Core\Entity\Company $company
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
