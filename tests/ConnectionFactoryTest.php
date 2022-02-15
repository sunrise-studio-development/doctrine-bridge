<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\ConnectionFactory;
use Sunrise\Bridge\Doctrine\Logger\SqlLogger;

class ConnectionFactoryTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testAutoConvertingPsrLoggerToDbalLogger() : void
    {
        $psrLogger = $this->createMock(LoggerInterface::class);

        $entityManagerRegistry = $this->getEntityManagerRegistry(null, [
            'foo' => [
                'dbal' => [
                    'sql_logger' => $psrLogger,
                ],
            ],
        ]);

        $sqlLogger = $entityManagerRegistry
            ->getConnection('foo')
            ->getConfiguration()
            ->getSQLLogger();

        $this->assertInstanceOf(SqlLogger::class, $sqlLogger);
        $this->assertSame($psrLogger, $sqlLogger->getPsrLogger());
    }
}
