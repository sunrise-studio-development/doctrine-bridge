<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixture\Entity;

/**
 * Import classes
 */
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
final class Bar
{

    /**
     * @ORM\Id()
     * @ORM\Column()
     *
     * @var mixed
     */
    public $id;

    /**
     * @ORM\Column()
     *
     * @var mixed
     */
    public $foo;

    /**
     * @var mixed
     */
    public $bar;
}
