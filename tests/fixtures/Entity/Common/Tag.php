<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

use function hash;
use function uniqid;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class Tag
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
     * @ORM\Column(type="string", length=32)
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 32)]
    private $summary = '';

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
     * @return string
     */
    public function getSummary() : string
    {
        return $this->summary;
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

    /**
     * @param string $summary
     *
     * @return void
     */
    public function setSummary(string $summary) : void
    {
        $this->summary = $summary;
    }
}
