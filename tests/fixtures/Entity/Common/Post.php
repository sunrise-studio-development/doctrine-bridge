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
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32, options: ['fixed' => true])]
    private $id;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     * @var string
     */
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private $name = '';

    /**
     * @ORM\Column(type="string", length=32)
     * @var string
     */
    #[ORM\Column(type: 'string', length: 32)]
    private $summary = '';

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $category = null;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @var Collection<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private $tags;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @Unhydrable()
     * @var User|null
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Unhydrable]
    private $updatedBy = null;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $isDisabled = true;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $enablesAt = null;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $hits = 0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    #[ORM\Column(type: 'float')]
    private $score = 0.0;

    /**
     * @ORM\Column(type="dateinterval", nullable=true)
     * @var DateInterval|null
     */
    #[ORM\Column(type: 'dateinterval', nullable: true)]
    private $someInterval = null;

    /**
     * @ORM\Column(type="string")
     * @Unhydrable()
     * @var string
     */
    #[ORM\Column(type: 'string')]
    #[Unhydrable]
    private $unhydrableValue = '';

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $nullableValue = '';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $unnullableValue = '';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $untypedValue = '';

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $unsetableAssociation = null;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $nullableAssociation = null;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $unnullableAssociation = null;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="post_unaddable_association")
     * @var Collection<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'post_unaddable_association')]
    private $unaddableAssociation;

    /**
     * @ORM\Column(type="string")
     * @Unhydrable()
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $unhydrableValueMarkedThroughAnnotationOnly = '';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $privateValue = '';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $unparameterizedValue = '';

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="fe8a41bf_07b0_4855_933c_a1714545c8e5")
     * @var Collection<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'fe8a41bf_07b0_4855_933c_a1714545c8e5')]
    private $privateAssociation = null;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="f836a7cb_27c8_4f1c_bcd6_2e5d3b816414")
     * @var Collection<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'f836a7cb_27c8_4f1c_bcd6_2e5d3b816414')]
    private $unparameterizedAssociation = null;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $snake_cased_value = '';

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $arrayValue = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     * @var object
     */
    #[ORM\Column(type: 'object', nullable: true)]
    private $objectValue = null;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $unsupportedTypeValue = '';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $mixedTypeValue = '';

    /**
     * Constructor of the class
     */
    public function __construct()
    {
        $this->id = hash('md5', uniqid(__CLASS__, true));
        $this->tags = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable('now');
        $this->unaddableAssociation = new ArrayCollection();
        $this->privateAssociation = new ArrayCollection();
        $this->unparameterizedAssociation = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function isDisabled()
    {
        return $this->isDisabled;
    }

    public function isVerified()
    {
        return $this->isVerified;
    }

    public function getEnablesAt()
    {
        return $this->enablesAt;
    }

    public function getSomeInterval()
    {
        return $this->someInterval;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function getUnhydrableValue()
    {
        return $this->unhydrableValue;
    }

    public function getNullableValue()
    {
        return $this->nullableValue;
    }

    public function getUnnullableValue()
    {
        return $this->unnullableValue;
    }

    public function getUntypedValue()
    {
        return $this->untypedValue;
    }

    public function getUnsetableAssociation()
    {
        return $this->unsetableAssociation;
    }

    public function getNullableAssociation()
    {
        return $this->nullableAssociation;
    }

    public function getUnnullableAssociation()
    {
        return $this->unnullableAssociation;
    }

    public function getUnaddableAssociation()
    {
        return $this->unaddableAssociation;
    }

    public function getUnhydrableValueMarkedThroughAnnotationOnly()
    {
        return $this->unhydrableValueMarkedThroughAnnotationOnly;
    }

    public function getPrivateValue()
    {
        return $this->privateValue;
    }

    public function getUnparameterizedValue()
    {
        return $this->unparameterizedValue;
    }

    public function getPrivateAssociation()
    {
        return $this->privateAssociation;
    }

    public function getUnparameterizedAssociation()
    {
        return $this->unparameterizedAssociation;
    }

    public function getSnakeCasedValue()
    {
        return $this->snake_cased_value;
    }

    public function getArrayValue()
    {
        return $this->arrayValue;
    }

    public function getObjectValue()
    {
        return $this->objectValue;
    }

    public function getUnsupportedTypeValue()
    {
        return $this->unsupportedTypeValue;
    }

    public function getMixedTypeValue()
    {
        return $this->mixedTypeValue;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setSummary(string $summary)
    {
        $this->summary = $summary;
    }

    public function setCategory(?Category $category)
    {
        $this->category = $category;
    }

    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setUpdatedBy(User $updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    public function setIsDisabled(bool $isDisabled)
    {
        $this->isDisabled = $isDisabled;
    }

    public function setIsVerified(bool $isVerified)
    {
        $this->isVerified = $isVerified;
    }

    public function setEnablesAt(?DateTime $enablesAt)
    {
        $this->enablesAt = $enablesAt;
    }

    public function setSomeInterval(?DateInterval $someInterval)
    {
        $this->someInterval = $someInterval;
    }

    public function setHits(int $hits)
    {
        $this->hits = $hits;
    }

    public function setScore(float $score)
    {
        $this->score = $score;
    }

    public function setUnhydrableValue(string $unhydrableValue)
    {
        $this->unhydrableValue = $unhydrableValue;
    }

    public function setNullableValue(?string $nullableValue)
    {
        $this->nullableValue = $nullableValue;
    }

    public function setUnnullableValue(string $unnullableValue)
    {
        $this->unnullableValue = $unnullableValue;
    }

    public function setUntypedValue($untypedValue)
    {
        $this->untypedValue = $untypedValue;
    }

    public function setNullableAssociation(?Category $nullableAssociation)
    {
        $this->nullableAssociation = $nullableAssociation;
    }

    public function setUnnullableAssociation(Category $unnullableAssociation)
    {
        $this->unnullableAssociation = $unnullableAssociation;
    }

    public function setUnhydrableValueMarkedThroughAnnotationOnly(string $value)
    {
        $this->unhydrableValueMarkedThroughAnnotationOnly = $value;
    }

    private function setPrivateValue(string $privateValue)
    {
        $this->privateValue = $privateValue;
    }

    public function setUnparameterizedValue()
    {
    }

    private function addPrivateAssociation(Tag $privateAssociation)
    {
        $this->privateAssociation->add($privateAssociation);
    }

    public function addUnparameterizedAssociation()
    {
    }

    public function setSnakeCasedValue(string $value)
    {
        $this->snake_cased_value = $value;
    }

    public function setArrayValue(array $arrayValue)
    {
        $this->arrayValue = $arrayValue;
    }

    public function setObjectValue(object $objectValue)
    {
        $this->objectValue = $objectValue;
    }

    public function setUnsupportedTypeValue(unknown $value)
    {
        $this->unsupportedTypeValue = $value;
    }

    public function setMixedTypeValue(mixed $value)
    {
        $this->mixedTypeValue = $value;
    }
}
