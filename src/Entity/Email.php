<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use stdClass;

/**
 * Email
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\EmailRepository")
 * @ORM\Table (name="email", uniqueConstraints={@ORM\UniqueConstraint (name="email", columns={"email"})}, indexes={@ORM\Index (columns={"people_id"})})
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['email:read']],
            denormalizationContext: ['groups' => ['email:write']]
        ),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),

    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['email:read']],
    denormalizationContext: ['groups' => ['email:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
class Email
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"invoice_details:read","order_details:read","people:read", "email:read",  "get_contracts", "carrier:read","email:write"})
     */
    private $email;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $confirmed = false;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"invoice_details:read","order_details:read","people:read", "email:read",  "get_contracts", "carrier:read"})
     */
    private $types = false;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="email")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     * @Groups({"email:read"})
     */
    private $people;
    public function getId()
    {
        return $this->id;
    }
    public function setEmail($email)
    {
        $this->email = trim($email);
        return $this;
    }
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * Get otherInformations
     *
     * @return stdClass
     */
    public function getTypes()
    {
        return count((array) $this->types) > 0 ? json_decode($this->types) : new stdClass();
    }
    /**
     * Set comments
     *
     * @param string $type
     * @return Email
     */
    public function addType($key, $value)
    {
        $types = $this->getTypes();
        $types->{$key} = $value;
        $this->types = json_encode($types);
        return $this;
    }
    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function setTypes(stdClass $types)
    {
        $this->types = json_encode($types);
        return $this;
    }
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }
    public function getConfirmed()
    {
        return $this->confirmed;
    }
    public function setPeople(People $people = null)
    {
        $this->people = $people;
        return $this;
    }
    public function getPeople()
    {
        return $this->people;
    }
}
