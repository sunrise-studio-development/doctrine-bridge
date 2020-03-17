<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture;

/**
 * Import classes
 */
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Example1DbalType
 */
final class Example1DbalType extends Type
{

    /**
     * Name of the type
     *
     * @var string
     */
    public const NAME = 'test:example:1';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $field, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($field);
    }
}
