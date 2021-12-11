<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\QueryFilter;

class QueryFilterTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testFilterNull() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'null']);
        $qf->allowFilterBy('foo', 'a.b');
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b IS NULL', $qb->getDQL());
    }

    public function testFilterNotNull() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'not-null']);
        $qf->allowFilterBy('foo', 'a.b');
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b IS NOT NULL', $qb->getDQL());
    }

    public function testFilterBoolean() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'yes']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_BOOL);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame(true, $qb->getParameter('p0')->getValue());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'no']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_BOOL);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame(false, $qb->getParameter('p0')->getValue());
    }

    public function testFilterBooleanWithEmptyString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_BOOL);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertNull($qb->getParameter('p0'));
    }

    public function testFilterBooleanWithInvalidString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'non-boolean']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_BOOL);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertNull($qb->getParameter('p0'));
    }

    public function testFilterDate() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '2004-01-10']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('2004-01-10', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '1073741824']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('2004-01-10', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 1073741824]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('2004-01-10', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'not-valid-date']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => new \stdClass /* date invalid type */]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));
    }

    public function testFilterDateMin() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => '1970-01-01']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0', $qb->getDQL());
        $this->assertSame('1970-01-01', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => '0']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0', $qb->getDQL());
        $this->assertSame('1970-01-01', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => 0]]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0', $qb->getDQL());
        $this->assertSame('1970-01-01', $qb->getParameter('p0')->getValue()->format('Y-m-d'));
    }

    public function testFilterDateMax() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['until' => '2038-01-19']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b <= :p0', $qb->getDQL());
        $this->assertSame('2038-01-19', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['until' => '2147483648']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b <= :p0', $qb->getDQL());
        $this->assertSame('2038-01-19', $qb->getParameter('p0')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['until' => 2147483648]]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b <= :p0', $qb->getDQL());
        $this->assertSame('2038-01-19', $qb->getParameter('p0')->getValue()->format('Y-m-d'));
    }

    public function testFilterDateRange() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => '1970-01-01', 'until' => '2038-01-19']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0 AND a.b <= :p1', $qb->getDQL());
        $this->assertSame('1970-01-01', $qb->getParameter('p0')->getValue()->format('Y-m-d'));
        $this->assertSame('2038-01-19', $qb->getParameter('p1')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => '0', 'until' => '2147483648']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0 AND a.b <= :p1', $qb->getDQL());
        $this->assertSame('1970-01-01', $qb->getParameter('p0')->getValue()->format('Y-m-d'));
        $this->assertSame('2038-01-19', $qb->getParameter('p1')->getValue()->format('Y-m-d'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => 0, 'until' => 2147483648]]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0 AND a.b <= :p1', $qb->getDQL());
        $this->assertSame('1970-01-01', $qb->getParameter('p0')->getValue()->format('Y-m-d'));
        $this->assertSame('2038-01-19', $qb->getParameter('p1')->getValue()->format('Y-m-d'));
    }

    public function testFilterDateWithEmptyString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertNull($qb->getParameter('p0'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['from' => '']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertNull($qb->getParameter('p0'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['until' => '']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_DATE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertNull($qb->getParameter('p0'));
    }

    public function testFilterNumeric() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ' 123.45']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('123.45', $qb->getParameter('p0')->getValue());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => null]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'not number']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => new \stdClass /* invalid number type */]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));
    }

    public function testFilterNumericMin() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['min' => ' 123.45']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0', $qb->getDQL());
        $this->assertSame('123.45', $qb->getParameter('p0')->getValue());
    }

    public function testFilterNumericMax() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['max' => ' 123.45']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b <= :p0', $qb->getDQL());
        $this->assertSame('123.45', $qb->getParameter('p0')->getValue());
    }

    public function testFilterNumericRange() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['min' => ' 123.45', 'max' => ' 678.90']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_NUM);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b >= :p0 AND a.b <= :p1', $qb->getDQL());
        $this->assertSame('123.45', $qb->getParameter('p0')->getValue());
        $this->assertSame('678.90', $qb->getParameter('p1')->getValue());
    }

    public function testFilterString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'bar']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => null]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => new \stdClass /* invalid string type */]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertSame(null, $qb->getParameter('p0'));
    }

    public function testFilterStringWithEmptyString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
        $this->assertNull($qb->getParameter('p0'));
    }

    public function testFilterStringWithVeryLongString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'foobar']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->maxStringLength(3);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('foo', $qb->getParameter('p0')->getValue());
    }

    public function testFilterStringLike() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '%b_a_r%']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_LIKE);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b LIKE :p0', $qb->getDQL());
        $this->assertSame('\%b\_a\_r\%', $qb->getParameter('p0')->getValue());
    }

    public function testFilterStringLikeWildcards() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => '%b_*a*_r%']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_LIKE|$qf::WILDCARDS);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b LIKE :p0', $qb->getDQL());
        $this->assertSame('\%b\_%a%\_r\%', $qb->getParameter('p0')->getValue());
    }

    public function testFilterStringLikeStartsWith() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'bar']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_LIKE|$qf::STARTS_WITH);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b LIKE :p0', $qb->getDQL());
        $this->assertSame('bar%', $qb->getParameter('p0')->getValue());
    }

    public function testFilterStringLikeContains() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'bar']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_LIKE|$qf::CONTAINS);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b LIKE :p0', $qb->getDQL());
        $this->assertSame('%bar%', $qb->getParameter('p0')->getValue());
    }

    public function testFilterStringLikeEndsWith() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'bar']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_LIKE|$qf::ENDS_WITH);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b LIKE :p0', $qb->getDQL());
        $this->assertSame('%bar', $qb->getParameter('p0')->getValue());
    }

    public function testFilterStringWithArray() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar', 'baz']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar', 'baz']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_ONE_OF);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0 OR a.b = :p1', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());
        $this->assertSame('baz', $qb->getParameter('p1')->getValue());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar', 'baz']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_ALL_OF);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0 AND a.b = :p1', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());
        $this->assertSame('baz', $qb->getParameter('p1')->getValue());
    }

    public function testFilterStringWithEmptyArray() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => []]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_ONE_OF);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a', $qb->getDQL());
    }

    public function testFilterStringWithArrayThatContainsEmptyString() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar', '', 'baz']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_ALL_OF);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0 AND a.b = :p1', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());
        $this->assertSame('baz', $qb->getParameter('p1')->getValue());
    }

    public function testFilterStringWithArrayThatContainsInvalidValue() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar', new \stdClass, 'baz']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_ALL_OF);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0 AND a.b = :p1', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());
        $this->assertSame('baz', $qb->getParameter('p1')->getValue());
    }

    public function testFilterStringWithVeryLongArray() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => ['bar', 'baz', 'qux']]);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR, $qf::MODE_ALL_OF);
        $qf->maxArraySize(2);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE a.b = :p0 AND a.b = :p1', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());
        $this->assertSame('baz', $qb->getParameter('p1')->getValue());
    }

    public function testSort() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['sort' => 'foo']);
        $qf->allowSortBy('foo', 'a.b');
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b ASC', $qb->getDQL());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['sort' => 'foo']);
        $qf->allowSortBy('foo', 'a.b', $qf::SORT_DESC);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b DESC', $qb->getDQL());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['sort' => ['foo' => 'asc', 'bar' => 'desc']]);
        $qf->allowSortBy('foo', 'a.b', $qf::SORT_DESC);
        $qf->allowSortBy('bar', 'a.c', $qf::SORT_ASC);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b ASC, a.c DESC', $qb->getDQL());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter([]);
        $qf->defaultSortBy('a.b', $qf::SORT_ASC);
        $qf->defaultSortBy('a.c', $qf::SORT_DESC);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b ASC, a.c DESC', $qb->getDQL());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['sort' => ['foo' => 'asc', 'bar' => 'asc']]);
        $qf->allowSortBy('foo', 'a.b');
        $qf->allowSortBy('bar', 'a.c');
        $qf->defaultSortBy('a.d', $qf::SORT_ASC);
        $qf->defaultSortBy('a.e', $qf::SORT_ASC);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b ASC, a.c ASC', $qb->getDQL());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['sort' => ['foo' => 'unknown-direction']]);
        $qf->allowSortBy('foo', 'a.b');
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b ASC', $qb->getDQL());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['sort' => ['foo' => 'unknown-direction']]);
        $qf->allowSortBy('foo', 'a.b', $qf::SORT_DESC);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a ORDER BY a.b DESC', $qb->getDQL());
    }

    public function testSlice() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter([]))->apply($qb);
        $this->assertSame(10, $qb->getMaxResults());
        $this->assertSame(0, $qb->getFirstResult());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['limit' => 20, 'offset' => 30]))->apply($qb);
        $this->assertSame(20, $qb->getMaxResults());
        $this->assertSame(30, $qb->getFirstResult());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['page' => 1, 'pagesize' => 20]))->apply($qb);
        $this->assertSame(20, $qb->getMaxResults());
        $this->assertSame(0, $qb->getFirstResult());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['page' => 2, 'pagesize' => 20]))->apply($qb);
        $this->assertSame(20, $qb->getMaxResults());
        $this->assertSame(20, $qb->getFirstResult());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['page' => 3, 'pagesize' => 20]))->apply($qb);
        $this->assertSame(20, $qb->getMaxResults());
        $this->assertSame(40, $qb->getFirstResult());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['page' => -1, 'pagesize' => -1]))->apply($qb);
        $this->assertSame(10, $qb->getMaxResults());
        $this->assertSame(0, $qb->getFirstResult());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['limit' => 100]))->apply($qb);
        $this->assertSame(100, $qb->getMaxResults());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['pagesize' => 100]))->apply($qb);
        $this->assertSame(100, $qb->getMaxResults());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['limit' => 101]))->apply($qb);
        $this->assertSame(10, $qb->getMaxResults());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        (new QueryFilter(['pagesize' => 101]))->apply($qb);
        $this->assertSame(10, $qb->getMaxResults());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = (new QueryFilter([]));
        $qf->defaultLimit(20);
        $qf->apply($qb);
        $this->assertSame(20, $qb->getMaxResults());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = (new QueryFilter(['limit' => 21]));
        $qf->maxLimit(20);
        $qf->apply($qb);
        $this->assertSame(10, $qb->getMaxResults());

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = (new QueryFilter(['limit' => 21]));
        $qf->defaultLimit(20);
        $qf->maxLimit(20);
        $qf->apply($qb);
        $this->assertSame(20, $qb->getMaxResults());
    }

    public function testFilterBySeveralColumns() : void
    {
        $em = $this->getEntityManagerRegistry()->getManager();

        $qb = (new QueryBuilder($em))->select('a')->from('A', 'a');
        $qf = new QueryFilter(['foo' => 'bar', 'bar' => 'baz']);
        $qf->allowFilterBy('foo', 'a.b', $qf::TYPE_STR);
        $qf->allowFilterBy('foo', 'a.c', $qf::TYPE_STR);
        $qf->allowFilterBy('bar', 'a.d', $qf::TYPE_STR);
        $qf->apply($qb);
        $this->assertSame('SELECT a FROM A a WHERE (a.b = :p0 OR a.c = :p1) AND a.d = :p2', $qb->getDQL());
        $this->assertSame('bar', $qb->getParameter('p0')->getValue());
        $this->assertSame('bar', $qb->getParameter('p1')->getValue());
        $this->assertSame('baz', $qb->getParameter('p2')->getValue());
    }
}
