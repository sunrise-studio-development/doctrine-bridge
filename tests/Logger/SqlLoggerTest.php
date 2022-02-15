<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\Logger\SqlLogger;
use Sunrise\Bridge\Doctrine\Tests\Fixtures;

class SqlLoggerTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testLogging() : void
    {
        $log = [];

        $logger = $this->createMock(LoggerInterface::class);

        $logger->method('debug')->will($this->returnCallback(function (string $sql, array $context) use (&$log) {
            $log[] = [
                'sql' => $sql,
                'context' => $context,
            ];
        }));

        $sqlLogger = new SqlLogger($logger);
        $sqlLogger->startQuery('SELECT 1', ['foo'], ['bar']);
        $sqlLogger->stopQuery();

        $sqlLogger->stopQuery(); // doing nothing...

        $this->assertCount(1, $log);
    }
}
