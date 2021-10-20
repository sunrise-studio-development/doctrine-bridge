<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

use function hash;
use function uniqid;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class User
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
     * Constructor of the class
     */
    public function __construct()
    {
        $this->id = hash('md5', uniqid(__CLASS__, true));
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
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}
