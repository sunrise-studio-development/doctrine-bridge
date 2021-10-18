<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\Common;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sunrise\Bridge\Doctrine\Annotation\Unhydrable;

use function hash;
use function uniqid;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class Post
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=32, options={"fixed"=true})
     *
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32, options: ['fixed' => true])]
    private $id;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private $name = '';

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 32)]
    private $summary = '';

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     *
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $category = null;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     *
     * @var Collection<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private $tags;

    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     *
     * @Unhydrable()
     *
     * @var User|null
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Unhydrable]
    private $updatedBy = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $isDisabled = true;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var DateTime|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $enablesAt = null;

    /**
     * @ORM\Column(type="dateinterval", nullable=true)
     *
     * @var DateInterval|null
     */
    #[ORM\Column(type: 'dateinterval', nullable: true)]
    private $someInterval = null;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $hits = 0;

    /**
     * @ORM\Column(type="float")
     *
     * @var float
     */
    #[ORM\Column(type: 'float')]
    private $score = 0.0;

    /**
     * @ORM\Column(type="string")
     *
     * @Unhydrable()
     *
     * @var string
     */
    #[ORM\Column(type: 'string')]
    #[Unhydrable]
    private $unhydrableValue = '';

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $nullableValue = '';

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $unnullableValue = '';

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $untypedValue = '';

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     *
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $unsetableAssociation = null;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     *
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $nullableAssociation = null;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     *
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $unnullableAssociation = null;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="post_unaddable_association")
     *
     * @var Collection<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'post_unaddable_association')]
    private $unaddableAssociation;

    /**
     * Constructor of the class
     */
    public function __construct()
    {
        $this->id = hash('md5', uniqid(__CLASS__, true));
        $this->tags = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable('now');
        $this->unaddableAssociation = new ArrayCollection();
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getSummary() : string
    {
        return $this->summary;
    }

    public function getCategory() : ?Category
    {
        return $this->category;
    }

    public function getTags() : Collection
    {
        return $this->tags;
    }

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt() : ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy() : ?User
    {
        return $this->updatedBy;
    }

    public function isDisabled() : bool
    {
        return $this->isDisabled;
    }

    public function isVerified() : bool
    {
        return $this->isVerified;
    }

    public function getEnablesAt() : ?DateTime
    {
        return $this->enablesAt;
    }

    public function getSomeInterval() : ?DateInterval
    {
        return $this->someInterval;
    }

    public function getHits() : int
    {
        return $this->hits;
    }

    public function getScore() : float
    {
        return $this->score;
    }

    public function getUnhydrableValue() : string
    {
        return $this->unhydrableValue;
    }

    public function getNullableValue() : ?string
    {
        return $this->nullableValue;
    }

    public function getUnnullableValue() : string
    {
        return $this->unnullableValue;
    }

    public function getUntypedValue()
    {
        return $this->untypedValue;
    }

    public function getUnsetableAssociation() : ?Category
    {
        return $this->unsetableAssociation;
    }

    public function getNullableAssociation() : ?Category
    {
        return $this->nullableAssociation;
    }

    public function getUnnullableAssociation() : ?Category
    {
        return $this->unnullableAssociation;
    }

    public function getUnaddableAssociation() : Collection
    {
        return $this->unaddableAssociation;
    }

    public function setId(string $id) : void
    {
        $this->id = $id;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function setSummary(string $summary) : void
    {
        $this->summary = $summary;
    }

    public function setCategory(?Category $category) : void
    {
        $this->category = $category;
    }

    public function addTag(Tag $tag) : void
    {
        $this->tags->add($tag);
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt) : void
    {
        $this->updatedAt = $updatedAt;
    }

    public function setUpdatedBy(User $updatedBy) : void
    {
        $this->updatedBy = $updatedBy;
    }

    public function setIsDisabled(bool $isDisabled) : void
    {
        $this->isDisabled = $isDisabled;
    }

    public function setIsVerified(bool $isVerified) : void
    {
        $this->isVerified = $isVerified;
    }

    public function setEnablesAt(?DateTime $enablesAt) : void
    {
        $this->enablesAt = $enablesAt;
    }

    public function setSomeInterval(?DateInterval $someInterval) : void
    {
        $this->someInterval = $someInterval;
    }

    public function setHits(int $hits) : void
    {
        $this->hits = $hits;
    }

    public function setScore(float $score) : void
    {
        $this->score = $score;
    }

    public function setUnhydrableValue(string $unhydrableValue) : void
    {
        $this->unhydrableValue = $unhydrableValue;
    }

    public function setNullableValue(?string $nullableValue) : void
    {
        $this->nullableValue = $nullableValue;
    }

    public function setUnnullableValue(string $unnullableValue) : void
    {
        $this->unnullableValue = $unnullableValue;
    }

    public function setUntypedValue($untypedValue) : void
    {
        $this->untypedValue = $untypedValue;
    }

    public function setNullableAssociation(?Category $nullableAssociation) : void
    {
        $this->nullableAssociation = $nullableAssociation;
    }

    public function setUnnullableAssociation(Category $unnullableAssociation) : void
    {
        $this->unnullableAssociation = $unnullableAssociation;
    }
}
