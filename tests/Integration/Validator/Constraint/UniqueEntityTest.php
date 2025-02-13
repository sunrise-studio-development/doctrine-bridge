<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Validator\Constraint;

use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntity;
use Symfony\Component\Validator\Constraint;

final class UniqueEntityTest extends TestCase
{
    public function testTargets(): void
    {
        self::assertSame(Constraint::CLASS_CONSTRAINT, (new UniqueEntity(fields: []))->getTargets());
    }
}
