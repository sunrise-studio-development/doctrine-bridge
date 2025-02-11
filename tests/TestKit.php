<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;

use function is_int;

/**
 * @psalm-require-extends TestCase
 * @phpstan-require-extends TestCase
 */
trait TestKit
{
    public function mockEntityManagerName(
        string $name,
        null|bool|int|InvocationOrder $calls = null,
    ): EntityManagerNameInterface&MockObject {
        $entityManagerName = $this->createMock(EntityManagerNameInterface::class);
        $entityManagerName->expects($this->normalizeInvocationOrder($calls))->method('getValue')->willReturn($name);

        return $entityManagerName;
    }

    private function normalizeInvocationOrder(
        null|bool|int|InvocationOrder $invocationOrder,
    ): InvocationOrder {
        if ($invocationOrder === null) {
            return $this->any();
        }
        if ($invocationOrder === false || $invocationOrder === 0) {
            return $this->never();
        }
        if ($invocationOrder === true || $invocationOrder === 1) {
            return $this->once();
        }
        if (is_int($invocationOrder)) {
            return $this->exactly($invocationOrder);
        }

        return $invocationOrder;
    }
}
