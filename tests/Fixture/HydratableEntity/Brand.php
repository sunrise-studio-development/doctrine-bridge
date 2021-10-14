<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixture\HydratableEntity;

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
     *   targetEntity="Sunrise\Bridge\Doctrine\Tests\Fixture\HydratableEntity\BrandAlias",
     *   mappedBy="brand",
     * )
     *
     * @var Collection
     */
    private $aliases;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Sunrise\Bridge\Doctrine\Tests\Fixture\HydratableEntity\BrandLogotype",
     *   mappedBy="brand",
     * )
     *
     * @var Collection
     */
    private $logotypes;

    /**
     * Constructor of the class
     */
    public function __construct()
    {
        $this->aliases = new ArrayCollection();
        $this->logotypes = new ArrayCollection();
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
     * @return Collection
     */
    public function getLogotypes() : Collection
    {
        return $this->logotypes;
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

    /**
     * @param BrandLogotype $logotype
     *
     * @return void
     */
    public function addLogotype(BrandLogotype $logotype) : void
    {
        $this->logotypes->add($logotype);

        $logotype->setName($logotype->getName() . ':adder');
    }
}
