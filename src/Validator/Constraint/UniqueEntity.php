<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine\Validator\Constraint;

/**
 * Import classes
 */
use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"CLASS"})
 *
 * @NamedArgumentConstructor
 *
 * @Attributes({
 *   @Attribute("fields",  type="array<string>", required=true),
 *   @Attribute("message", type="string"),
 *   @Attribute("atPath",  type="string"),
 *   @Attribute("em",      type="string"),
 *   @Attribute("options", type="mixed"),
 *   @Attribute("groups",  type="array<string>"),
 *   @Attribute("payload", type="mixed"),
 * })
 */
#[Attribute(Attribute::TARGET_CLASS|Attribute::IS_REPEATABLE)]
class UniqueEntity extends Constraint
{

    /**
     * @var string
     */
    public const NOT_UNIQUE_ERROR = 'd3cf3b2e-f934-422e-ae60-b4eca745aa33';

    /**
     * @var string
     */
    public const DEFAULT_ERROR_MESSAGE = 'The value {{ value }} is not unique.';

    /**
     * @var string[]
     */
    public $fields;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string|null
     */
    public $atPath = null;

    /**
     * @var string|null
     */
    public $em = null;

    /**
     * Constructor of the class
     *
     * @param  string[]       $fields
     * @param  string|null    $message
     * @param  string|null    $atPath
     * @param  string|null    $em
     * @param  mixed          $options
     * @param  string[]|null  $groups
     * @param  mixed          $payload
     */
    public function __construct(
        array $fields,
        ?string $message = null,
        ?string $atPath = null,
        ?string $em = null,
        $options = null,
        ?array $groups = null,
        $payload = null
    ) {
        $options['fields'] = $fields; // will be setted from the parent constructor.

        $this->message = $message ?? self::DEFAULT_ERROR_MESSAGE;
        $this->atPath = $atPath;
        $this->em = $em;

        parent::__construct($options, $groups, $payload);
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['fields'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'fields';
    }
}
