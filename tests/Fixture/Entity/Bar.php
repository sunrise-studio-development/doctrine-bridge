<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture\Entity;

/**
 * Import classes
 */
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(
 *   repositoryClass="Arus\Doctrine\Bridge\Tests\Fixture\Repository\BarRepository",
 * )
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
