<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use Doctrine\DBAL\Logging\DebugStack;
use Psr\Log\LoggerInterface;

/**
 * Import functions
 */
use function array_pop;
use function sprintf;

/**
 * SQLLogger
 */
final class SQLLogger extends DebugStack
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function stopQuery()
    {
        parent::stopQuery();

        $report = array_pop($this->queries);

        $this->logger->debug(sprintf(
            '[%2.3fµ] %s',
            $report['executionMS'] * 1000,
            $report['sql']
        ), [
            'params' => $report['params'],
            'types' => $report['types'],
        ]);
    }
}
