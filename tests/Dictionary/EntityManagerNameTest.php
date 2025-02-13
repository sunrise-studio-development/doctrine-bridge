<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Dictionary;

use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\Dictionary\EntityManagerName;

final class EntityManagerNameTest extends TestCase
{
    public function testValue(): void
    {
        self::assertSame('default', EntityManagerName::Default->value);
        self::assertSame('default', EntityManagerName::Default->getValue());
    }
}
