<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

/**
 * Import classes
 */
use PHPUnit\Framework\TestCase;

/**
 * CommandsProviderTest
 */
class CommandsProviderTest extends TestCase
{
    use Fixture\ContainerAwareTrait;

    /**
     * @return void
     */
    public function testCommands() : void
    {
        $commands = $this->getContainer()->get('doctrine')->getCommands();

        $this->assertCount(32, $commands);
    }
}
