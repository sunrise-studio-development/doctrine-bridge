<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Validator\Constraint;

use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValue;
use Symfony\Component\Validator\Constraint;

final class UniqueValueTest extends TestCase
{
    public function testTargets(): void
    {
        self::assertSame(Constraint::PROPERTY_CONSTRAINT, (new UniqueValue())->getTargets());
    }
}
