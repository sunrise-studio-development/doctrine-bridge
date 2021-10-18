<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

/**
 * This object cannot be hydrated because its constructor has required parameters.
 */
class InvalidEntity
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
