<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\CreateMarketingEventAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Anonymous / site marketing conversion events (UTM, visitor_id, page views, etc.).
 * Public POST; authenticated read for CRM.
 */
#[ORM\Table(name: 'marketing_event')]
#[ORM\Index(name: 'marketing_event_visitor_idx', columns: ['visitor_id'])]
#[ORM\Index(name: 'marketing_event_event_name_idx', columns: ['event_name'])]
#[ORM\Index(name: 'marketing_event_event_at_idx', columns: ['event_at'])]
#[ORM\Index(name: 'marketing_event_people_idx', columns: ['people_id'])]
#[ORM\Index(name: 'marketing_event_idempotency_idx', columns: ['idempotency_key'], unique: true)]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\MarketingEventRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal'],
    normalizationContext: ['groups' => ['marketing_event:read']],
    denormalizationContext: ['groups' => ['marketing_event:write']],
    operations: [
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new GetCollection(security: "is_granted('ROLE_HUMAN')"),
        new Post(
            uriTemplate: '/marketing_events',
            controller: CreateMarketingEventAction::class,
            deserialize: false,
            read: false,
            security: "is_granted('PUBLIC_ACCESS')",
            name: 'create_marketing_event'
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'visitorId' => 'exact',
    'eventName' => 'exact',
    'people' => 'exact',
    'utmSource' => 'partial',
    'utmCampaign' => 'partial',
    'email' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'eventName',
    'eventAt',
    'creationDate',
])]
class MarketingEvent
{
    public const EVENT_PAGE_VIEW = 'page_view';
    public const EVENT_FORM_SUBMIT = 'form_submit';
    public const EVENT_WHATSAPP_CLICK = 'whatsapp_click';
    public const EVENT_LEAD_CREATED = 'lead_created';

    public const ALLOWED_EVENTS = [
        self::EVENT_PAGE_VIEW,
        self::EVENT_FORM_SUBMIT,
        self::EVENT_WHATSAPP_CLICK,
        self::EVENT_LEAD_CREATED,
    ];

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['marketing_event:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'event_name', type: 'string', length: 64, nullable: false)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private string $eventName = '';

    #[ORM\Column(name: 'event_at', type: 'datetime_immutable', nullable: false)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private \DateTimeImmutable $eventAt;

    #[ORM\Column(name: 'page_url', type: 'string', length: 2048, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $pageUrl = null;

    #[ORM\Column(name: 'visitor_id', type: 'string', length: 64, nullable: false)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private string $visitorId = '';

    #[ORM\Column(name: 'lead_id', type: 'string', length: 64, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $leadId = null;

    #[ORM\Column(name: 'utm_source', type: 'string', length: 255, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $utmSource = null;

    #[ORM\Column(name: 'utm_medium', type: 'string', length: 255, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $utmMedium = null;

    #[ORM\Column(name: 'utm_campaign', type: 'string', length: 255, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $utmCampaign = null;

    #[ORM\Column(name: 'utm_term', type: 'string', length: 255, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $utmTerm = null;

    #[ORM\Column(name: 'utm_content', type: 'string', length: 255, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $utmContent = null;

    #[ORM\Column(name: 'referrer', type: 'string', length: 2048, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $referrer = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['marketing_event:read', 'marketing_event:write'])]
    private ?People $people = null;

    /** Hash for idempotency: visitor_id + event_name + timestamp (or client hash). */
    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 64, nullable: false, unique: true)]
    #[Groups(['marketing_event:read'])]
    private string $idempotencyKey = '';

    #[ORM\Column(name: 'payload_hash', type: 'string', length: 64, nullable: true)]
    #[Groups(['marketing_event:read'])]
    private ?string $payloadHash = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime_immutable', nullable: false)]
    #[Groups(['marketing_event:read'])]
    private \DateTimeImmutable $creationDate;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->eventAt = $now;
        $this->creationDate = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getEventAt(): \DateTimeImmutable
    {
        return $this->eventAt;
    }

    public function setEventAt(\DateTimeImmutable $eventAt): self
    {
        $this->eventAt = $eventAt;
        return $this;
    }

    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    public function setPageUrl(?string $pageUrl): self
    {
        $this->pageUrl = $pageUrl;
        return $this;
    }

    public function getVisitorId(): string
    {
        return $this->visitorId;
    }

    public function setVisitorId(string $visitorId): self
    {
        $this->visitorId = $visitorId;
        return $this;
    }

    public function getLeadId(): ?string
    {
        return $this->leadId;
    }

    public function setLeadId(?string $leadId): self
    {
        $this->leadId = $leadId;
        return $this;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): self
    {
        $this->utmSource = $utmSource;
        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): self
    {
        $this->utmMedium = $utmMedium;
        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): self
    {
        $this->utmCampaign = $utmCampaign;
        return $this;
    }

    public function getUtmTerm(): ?string
    {
        return $this->utmTerm;
    }

    public function setUtmTerm(?string $utmTerm): self
    {
        $this->utmTerm = $utmTerm;
        return $this;
    }

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function setUtmContent(?string $utmContent): self
    {
        $this->utmContent = $utmContent;
        return $this;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function setReferrer(?string $referrer): self
    {
        $this->referrer = $referrer;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
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

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function setIdempotencyKey(string $idempotencyKey): self
    {
        $this->idempotencyKey = $idempotencyKey;
        return $this;
    }

    public function getPayloadHash(): ?string
    {
        return $this->payloadHash;
    }

    public function setPayloadHash(?string $payloadHash): self
    {
        $this->payloadHash = $payloadHash;
        return $this;
    }

    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }
}
