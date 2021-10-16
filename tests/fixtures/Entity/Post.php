<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

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
     * @ORM\Column(type="date_immutable")
     *
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'date_immutable')]
    private $createdAt;

    /**
     * Constructor of the class
     */
    public function __construct()
    {
        $this->id = hash('md5', uniqid(__CLASS__, true));
        $this->tags = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable('now');
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSummary() : string
    {
        return $this->summary;
    }

    /**
     * @return Category|null
     */
    public function getCategory() : ?Category
    {
        return $this->category;
    }

    /**
     * @return Collection<Tag>
     */
    public function getTags() : Collection
    {
        return $this->tags;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * @param string $summary
     *
     * @return void
     */
    public function setSummary(string $summary) : void
    {
        $this->summary = $summary;
    }

    /**
     * @param Category|null $category
     *
     * @return void
     */
    public function setCategory(?Category $category) : void
    {
        $this->category = $category;
    }

    /**
     * @param Tag $tag
     *
     * @return void
     */
    public function addTag(Tag $tag) : void
    {
        $this->tags->add($tag);
    }
}
