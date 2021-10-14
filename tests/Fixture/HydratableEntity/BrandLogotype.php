<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixture\HydratableEntity;

/**
 * Import classes
 */
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class BrandLogotype
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
     * @ORM\ManyToOne(
     *   targetEntity="Sunrise\Bridge\Doctrine\Tests\Fixture\HydratableEntity\Brand",
     *   inversedBy="logotypes",
     * )
     *
     * @var Brand
     */
    private $brand;

    /**
     * @return null|string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return null|Brand
     */
    public function getBrand() : ?Brand
    {
        return $this->brand;
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
     * @param Brand $brand
     *
     * @return void
     */
    public function setBrand(Brand $brand) : void
    {
        $this->brand = $brand;
    }
}
