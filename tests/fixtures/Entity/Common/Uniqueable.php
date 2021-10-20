<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Sunrise\Bridge\Doctrine\Validator\Constraint as Assert;

/**
 * @ORM\Entity()
 * @Assert\UniqueEntity(fields={"foo"}, atPath="foo")
 * @Assert\UniqueEntity(fields={"bar", "baz"}, message="Non-unique {{ value }}")
 * @Assert\UniqueEntity(fields={"qux"}, em="foo")
 */
#[ORM\Entity]
class Uniqueable
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", nullable=false)
     *
     * @var string|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', nullable: false)]
    public $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    public $foo;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    public $bar;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    public $baz;

    /**
     * @ORM\ManyToOne(targetEntity=Uniqueable::class)
     *
     * @var Tag|null
     */
    #[ORM\ManyToOne(targetEntity: Uniqueable::class)]
    public $qux;

    /**
     * Constructor of the class
     *
     * @param array<string, mixed> $fields
     */
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
