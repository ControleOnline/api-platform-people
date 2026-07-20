<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/media_types/{id}',
            security: "is_granted('ROLE_HUMAN')"
        ),
        new GetCollection(
            uriTemplate: '/media_types',
            security: "is_granted('ROLE_HUMAN')"
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['media_type:read']],
    denormalizationContext: ['groups' => ['media_type:write']],
    order: ['id' => 'ASC']
)]
#[ApiFilter(OrderFilter::class, properties: ['id' => 'ASC', 'type' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'type' => 'exact',
    'peopleType' => 'exact',
])]
#[ORM\Table(name: 'media_types')]
#[ORM\UniqueConstraint(name: 'media_type_unique', columns: ['type'])]
#[ORM\Entity]
class MediaType
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    #[Groups(['media_type:read', 'people_media:read'])]
    private int $id = 0;

    #[ORM\Column(name: 'type', type: 'string', length: 32, nullable: false)]
    #[Groups(['media_type:read', 'people_media:read'])]
    private string $type = '';

    #[ORM\Column(
        name: 'people_type',
        type: 'string',
        length: 1,
        columnDefinition: "SET('F','J') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
    )]
    #[Groups(['media_type:read', 'people_media:read'])]
    private string $peopleType = 'J';

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return strtolower(trim($this->type));
    }

    public function setType(string $type): self
    {
        $this->type = strtolower(trim($type));
        return $this;
    }

    public function getPeopleType(): string
    {
        return strtoupper(trim($this->peopleType));
    }

    public function setPeopleType(string $peopleType): self
    {
        $this->peopleType = strtoupper(trim($peopleType));
        return $this;
    }
}
