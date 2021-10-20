<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Validator\Constraint;

use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\Validator\Constraint\UniqueEntity;

class UniqueEntityTest extends TestCase
{
    public function testDefaultMessage() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame('The value {{ value }} is not unique.', $constraint->message);
    }

    public function testTargets() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame([$constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testRequiredOptions() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame(['fields'], $constraint->getRequiredOptions());
    }

    public function testDefaultOption() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame('fields', $constraint->getDefaultOption());
    }
}
