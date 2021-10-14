<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Validator\Constraint;

/**
 * Import classes
 */
use Sunrise\Bridge\Doctrine\Validator\Constraint\UniqueEntity;
use PHPUnit\Framework\TestCase;

/**
 * UniqueEntityTest
 */
class UniqueEntityTest extends TestCase
{

    /**
     * @return void
     */
    public function testDefaultMessage() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame('The value {{ value }} is not unique.', $constraint->message);
    }

    /**
     * @return void
     */
    public function testTargets() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame([$constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    /**
     * @return void
     */
    public function testRequiredOptions() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame(['fields'], $constraint->getRequiredOptions());
    }

    /**
     * @return void
     */
    public function testDefaultOption() : void
    {
        $constraint = new UniqueEntity(['foo']);

        $this->assertSame('fields', $constraint->getDefaultOption());
    }
}
