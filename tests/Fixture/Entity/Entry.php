<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture\Entity;

/**
 * @Table(
 *   name="entry",
 * )
 *
 * @Entity(
 *   repositoryClass="Arus\Doctrine\Bridge\Tests\Fixture\Repository\EntryRepository"
 * )
 */
final class Entry
{

    /**
     * @Id()
     *
     * @Column(
     *   type="integer",
     *   nullable=false,
     *   options={
     *     "unsigned": true,
     *   },
     * )
     *
     * @GeneratedValue(
     *   strategy="AUTO",
     * )
     *
     * @var int
     */
    private $id;
}
