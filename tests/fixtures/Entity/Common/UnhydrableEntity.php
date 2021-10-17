<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

class UnhydrableEntity
{

    /**
     * Constructor of the class
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
    }
}
