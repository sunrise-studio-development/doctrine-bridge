<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture\HydratableEntity;

/**
 * Import classes
 */
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Brand
{

    /**
     * @ORM\Id()
     * @ORM\Column()
     *
     * @var mixed
     */
    private $id;

    /**
     * @ORM\Column()
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Arus\Doctrine\Bridge\Tests\Fixture\HydratableEntity\BrandAlias",
     *   mappedBy="brand",
     * )
     *
     * @var Collection
     */
    private $aliases;

    /**
     * Constructor of the class
     */
    public function __construct()
    {
        $this->aliases = new ArrayCollection();
    }

    /**
     * @return null|string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return Collection
     */
    public function getAliases() : Collection
    {
        return $this->aliases;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name) : void
    {
        $this->name = $name . ':setter';
    }

    /**
     * @param BrandAlias $alias
     *
     * @return void
     */
    public function addAlias(BrandAlias $alias) : void
    {
        $this->aliases->add($alias);

        $alias->setName($alias->getName() . ':adder');
    }
}
