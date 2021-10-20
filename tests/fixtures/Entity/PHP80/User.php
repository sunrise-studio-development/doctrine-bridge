<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\PHP80;

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
     * @ORM\Column(type="string", length=255)
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $password = null;

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
     * @return Password|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param Password|string
     *
     * @return void
     */
    public function setPassword(Password|string $password) : void
    {
        // if (!($password instanceof Password)) {
        //     $password = Password::create($password);
        // }

        $this->password = $password;
    }
}
