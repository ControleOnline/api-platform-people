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
use ControleOnline\Controller\PeopleMediaSaveController;
use ControleOnline\Controller\PeopleMediaUploadController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/people_media/{id}',
            security: "is_granted('ROLE_HUMAN')"
        ),
        new GetCollection(
            uriTemplate: '/people_media',
            security: "is_granted('ROLE_HUMAN')"
        ),
        new Post(
            uriTemplate: '/people_media',
            controller: PeopleMediaSaveController::class,
            security: "is_granted('ROLE_HUMAN')",
            deserialize: false,
            read: false
        ),
        new Put(
            uriTemplate: '/people_media/{id}',
            security: "is_granted('ROLE_HUMAN')",
            denormalizationContext: ['groups' => ['people_media:write']]
        ),
        new Delete(
            uriTemplate: '/people_media/{id}',
            security: "is_granted('ROLE_HUMAN')"
        ),
        new Post(
            uriTemplate: '/people_media/upload',
            controller: PeopleMediaUploadController::class,
            security: "is_granted('ROLE_HUMAN') or is_granted('ROLE_CLIENT')",
            deserialize: false,
            read: false
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_media:read']],
    denormalizationContext: ['groups' => ['people_media:write']],
    order: ['id' => 'DESC']
)]
#[ApiFilter(OrderFilter::class, properties: ['id' => 'DESC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'people' => 'exact',
    'file' => 'exact',
    'mediaType' => 'exact',
    'mediaType.type' => 'exact',
    'mediaType.peopleType' => 'exact',
])]
#[ORM\Table(name: 'people_media')]
#[ORM\Index(name: 'people_id', columns: ['people_id'])]
#[ORM\Index(name: 'file_id', columns: ['file_id'])]
#[ORM\Index(name: 'media_type_id', columns: ['media_type_id'])]
#[ORM\UniqueConstraint(name: 'people_id_2', columns: ['people_id', 'media_type_id'])]
#[ORM\Entity]
class PeopleMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[Groups(['people_media:read'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['people_media:read', 'people_media:write'])]
    private ?People $people = null;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['people_media:read', 'people_media:write'])]
    private ?File $file = null;

    #[ORM\ManyToOne(targetEntity: MediaType::class)]
    #[ORM\JoinColumn(name: 'media_type_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['people_media:read', 'people_media:write'])]
    private ?MediaType $mediaType = null;

    public function getId(): int
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

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(?MediaType $mediaType): self
    {
        $this->mediaType = $mediaType;
        return $this;
    }
}
