<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixture;

/**
 * Import classes
 */
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Example2DbalType
 */
final class Example2DbalType extends Type
{

    /**
     * Name of the type
     *
     * @var string
     */
    public const NAME = 'test:example:2';

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
