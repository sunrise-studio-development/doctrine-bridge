<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture\Entity;

/**
 * Import classes
 */
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(
 *   repositoryClass="Arus\Doctrine\Bridge\Tests\Fixture\Repository\FooRepository",
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
