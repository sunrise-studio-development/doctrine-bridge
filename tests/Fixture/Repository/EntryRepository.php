<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture\Repository;

/**
 * Import classes
 */
use Doctrine\ORM\EntityRepository;

/**
 * EntryRepository
 */
final class EntryRepository extends EntityRepository
{

    /**
     * @Inject("foo")
     *
     * @var string
     */
    public $foo;

    /**
     * @Inject("bar")
     *
     * @var string
     */
    public $bar;
}
