<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="people_provider", uniqueConstraints={@ORM\UniqueConstraint (name="provider_id", columns={"provider_id", "company_id"})}, indexes={@ORM\Index (name="company_id", columns={"company_id"}), @ORM\Index(name="IDX_2C6E59348C03F15C", columns={"provider_id"})})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\PeopleProviderRepository")
 */
#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/people_providers/{id}/change-status',
            controller: \App\Controller\ChangeProviderStatusAction::class,
            security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'edit\', object)',
            requirements: ['id' => '^\\d+$']
        ),
        new Post(),
        new GetCollection()
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['people_provider_read']],
    denormalizationContext: ['groups' => ['people_provider_write']]
    )]
class PeopleProvider
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="PeopleProvider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     */
    private $company;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peopleCompany")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="provider_id", referencedColumnName="id")
     * })
     * @ORM\OrderBy({"alias" = "ASC"})
     * @Groups({"people_read","people_provider_read"})
     */
    private $provider;
    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set company
     *
     * @param \ControleOnline\Entity\People $company
     * @return PeopleProvider
     */
    public function setCompany(\ControleOnline\Entity\People $company = null)
    {
        $this->company = $company;
        return $this;
    }
    /**
     * Get company
     *
     * @return \ControleOnline\Entity\People
     */
    public function getCompany()
    {
        return $this->company;
    }
    /**
     * Set provider
     *
     * @param \ControleOnline\Entity\People $provider
     * @return PeopleProvider
     */
    public function setprovider(\ControleOnline\Entity\People $provider = null)
    {
        $this->provider = $provider;
        return $this;
    }
    /**
     * Get provider
     *
     * @return \ControleOnline\Entity\People
     */
    public function getprovider()
    {
        return $this->provider;
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
}
