<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixture\Entity;

/**
 * Import classes
 */
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(
 *   repositoryClass="Sunrise\Bridge\Doctrine\Tests\Fixture\Repository\FooRepository",
 * )
 */
final class Foo
{

    /**
     * @ORM\Id()
     * @ORM\Column()
     *
     * @var mixed
     */
    public $id;
}
