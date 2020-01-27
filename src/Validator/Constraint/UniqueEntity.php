<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Validator\Constraint;

/**
 * Import classes
 */
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"CLASS"})
 */
class UniqueEntity extends Constraint
{

    /**
     * @var string
     */
    public const NOT_UNIQUE_ERROR = 'd3cf3b2e-f934-422e-ae60-b4eca745aa33';

    /**
     * @var string[]
     */
    public $fields;

    /**
     * @var string
     */
    public $message = 'The value {{ value }} is not unique.';

    /**
     * @var string
     */
    public $atPath;

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return ['fields'];
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'fields';
    }
}
