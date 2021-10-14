<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixture\Entity;

/**
 * Import classes
 */
use Sunrise\Bridge\Doctrine\Validator\Constraint as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 *
 * @Assert\UniqueEntity({"foo"})
 *
 * @Assert\UniqueEntity({"bar", "baz"})
 *
 * @Assert\UniqueEntity({"baz", "qux"}, atPath="xxx")
 *
 * @Assert\UniqueEntity({"qux", "bar"}, atPath="bar", message="non-unique value: {{ value }}")
 *
 * @Assert\UniqueEntity({"quux"})
 */
final class Baz
{

    /**
     * @ORM\Id()
     *
     * @ORM\Column(
     *   type="integer",
     *   nullable=false,
     * )
     *
     * @ORM\GeneratedValue(
     *   strategy="AUTO",
     * )
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(
     *   type="string",
     *   nullable=true,
     * )
     *
     * @var string
     */
    public $foo;

    /**
     * @ORM\Column(
     *   type="string",
     *   nullable=true,
     * )
     *
     * @var string
     */
    public $bar;

    /**
     * @ORM\Column(
     *   type="string",
     *   nullable=true,
     * )
     *
     * @var string
     */
    public $baz;

    /**
     * @ORM\Column(
     *   type="string",
     *   nullable=true,
     * )
     *
     * @var string
     */
    public $qux;

    /**
     * @ORM\OneToOne(
     *   targetEntity="Baz",
     * )
     *
     * @var Baz
     */
    public $quux;

    /**
     * Constructor of the class
     *
     * @param array $fields
     */
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
