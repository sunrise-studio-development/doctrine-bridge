<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine;

/**
 * Import classes
 */
use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Import functions
 */
use function addcslashes;
use function ctype_digit;
use function date_create;
use function filter_var;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function strtr;
use function trim;

/**
 * Import constants
 */
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_INT;
use const PHP_INT_MAX;

/**
 * QueryFilter
 */
final class QueryFilter
{

    /**
     * @var int
     */
    public const SORT_ASC = 0;

    /**
     * @var int
     */
    public const SORT_DESC = 1;

    /**
     * @var int
     */
    public const TYPE_BOOL = 0;

    /**
     * @var int
     */
    public const TYPE_NUM = 1;

    /**
     * @var int
     */
    public const TYPE_STR = 2;

    /**
     * @var int
     */
    public const TYPE_DATE = 3;

    /**
     * @var int
     */
    public const MODE_LIKE = 1;

    /**
     * @var int
     */
    public const WILDCARDS = 2;

    /**
     * @var int
     */
    public const STARTS_WITH = 4;

    /**
     * @var int
     */
    public const CONTAINS = 8;

    /**
     * @var int
     */
    public const ENDS_WITH = 16;

    /**
     * @var array<string, string|array<string, string>>
     */
    private $data;

    /**
     * @var array<string, array<string, int|string|null>>
     *
     * @psalm-var array<string, array{
     *      column: string,
     *      type: int,
     *      mode: int|null,
     * }>
     */
    private $filterBy = [];

    /**
     * @var array<string, array<string, int|string>>
     *
     * @psalm-var array<string, array{
     *      column: string,
     *      sort: int,
     * }>
     */
    private $sortBy = [];

    /**
     * @var array<int, array<string, int|string>>
     *
     * @psalm-var array<int, array{
     *      column: string,
     *      sort: int,
     * }>
     */
    private $defaultSortBy = [];

    /**
     * @var int
     */
    private $defaultLimit = 10;

    /**
     * @var int
     */
    private $maxLimit = 100;

    /**
     * @OpenApi\ParameterQuery(
     *   name="limit",
     *   schema=@OpenApi\SchemaReference(".limit"),
     * )
     *
     * @OpenApi\Schema(
     *   type="integer",
     *   minimum=1,
     *   default=1,
     *   example=1,
     * )
     *
     * @var int|null
     */
    private $limit = null;

    /**
     * @OpenApi\ParameterQuery(
     *   name="offset",
     *   schema=@OpenApi\SchemaReference(".offset"),
     * )
     *
     * @OpenApi\Schema(
     *   type="integer",
     *   minimum=0,
     *   default=0,
     *   example=0,
     * )
     *
     * @var int|null
     */
    private $offset = null;

    /**
     * @OpenApi\ParameterQuery(
     *   name="page",
     *   schema=@OpenApi\SchemaReference(".page"),
     * )
     *
     * @OpenApi\Schema(
     *   type="integer",
     *   minimum=1,
     *   default=1,
     *   example=1,
     * )
     *
     * @var int|null
     */
    private $page = null;

    /**
     * Constructor of the class
     *
     * @param array<string, string|array<string, string>> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @param string $column
     * @param int $type
     * @param int|null $mode
     *
     * @return void
     */
    public function allowFilterBy(string $key, string $column, int $type = self::TYPE_STR, ?int $mode = null) : void
    {
        $this->filterBy[$key]['column'] = $column;
        $this->filterBy[$key]['type'] = $type;
        $this->filterBy[$key]['mode'] = $mode;
    }

    /**
     * @param string $key
     * @param string $column
     * @param int $sort
     *
     * @return void
     */
    public function allowSortBy(string $key, string $column, int $sort = self::SORT_ASC) : void
    {
        $this->sortBy[$key]['column'] = $column;
        $this->sortBy[$key]['sort'] = $sort;
    }

    /**
     * @param string $column
     * @param int $sort
     *
     * @return void
     */
    public function defaultSortBy(string $column, int $sort = self::SORT_ASC) : void
    {
        $this->defaultSortBy[] = [
            'column' => $column,
            'sort' => $sort,
        ];
    }

    /**
     * @param int $limit
     *
     * @return void
     */
    public function defaultLimit(int $limit) : void
    {
        $this->defaultLimit = $limit;
    }

    /**
     * @param int $limit
     *
     * @return void
     */
    public function maxLimit(int $limit) : void
    {
        $this->maxLimit = $limit;
    }

    /**
     * Applies the filter to the given query builder
     *
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $qb) : QueryBuilder
    {
        $this->filter($qb);
        $this->sort($qb);
        $this->slice($qb);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return void
     */
    private function filter(QueryBuilder $qb) : void
    {
        foreach ($this->filterBy as $key => $filterBy) {
            if (!isset($this->data[$key])) {
                continue;
            }

            if ('null' === $this->data[$key]) {
                $qb->andWhere($qb->expr()->isNull($filterBy['column']));
                continue;
            }

            if ('not-null' === $this->data[$key]) {
                $qb->andWhere($qb->expr()->isNotNull($filterBy['column']));
                continue;
            }

            if (self::TYPE_BOOL === $filterBy['type']) {
                $this->filterByBooleanValue($qb, $filterBy['column'], $this->data[$key]);
                continue;
            }

            if (self::TYPE_NUM === $filterBy['type']) {
                $this->filterByNumericValue($qb, $filterBy['column'], $this->data[$key]);
                continue;
            }

            if (self::TYPE_STR === $filterBy['type']) {
                $this->filterByStringValue($qb, $filterBy['column'], $filterBy['mode'], $this->data[$key]);
                continue;
            }

            if (self::TYPE_DATE === $filterBy['type']) {
                $this->filterByDateValue($qb, $filterBy['column'], $this->data[$key]);
                continue;
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return void
     */
    private function sort(QueryBuilder $qb) : void
    {
        if (isset($this->data['sort'])) {
            // ?sort=foo
            if (is_string($this->data['sort'])) {
                if (isset($this->sortBy[$this->data['sort']])) {
                    $qb->addOrderBy(
                        $this->sortBy[$this->data['sort']]['column'],
                        $this->resolveSortDirection($this->sortBy[$this->data['sort']]['sort'], 'ASC')
                    );
                }
            }

            // ?sort[foo]=asc&sort[bar]=desc
            if (is_array($this->data['sort'])) {
                foreach ($this->data['sort'] as $key => $dir) {
                    if (isset($this->sortBy[$key])) {
                        $qb->addOrderBy(
                            $this->sortBy[$key]['column'],
                            $this->resolveSortDirection($dir) ??
                            $this->resolveSortDirection($this->sortBy[$key]['sort'], 'ASC')
                        );
                    }
                }
            }
        }

        if ([] === $qb->getDQLPart('orderBy')) {
            foreach ($this->defaultSortBy as $sortBy) {
                $qb->addOrderBy($sortBy['column'], $this->resolveSortDirection($sortBy['sort'], 'ASC'));
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return void
     */
    private function slice(QueryBuilder $qb) : void
    {
        $qb->setMaxResults($this->getLimit());
        $qb->setFirstResult($this->getOffset());
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param mixed $value
     *
     * @return void
     */
    private function filterByBooleanValue(QueryBuilder $qb, string $column, $value) : void
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if (isset($value)) {
            $p = 'p' . $qb->getParameters()->count();
            $qb->andWhere($qb->expr()->eq($column, ':' . $p));
            $qb->setParameter($p, $value);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param mixed $value
     *
     * @return void
     */
    private function filterByNumericValue(QueryBuilder $qb, string $column, $value) : void
    {
        // ?foo[min]=1&foo[max]=2
        if (is_array($value)) {
            if (isset($value['min']) && is_numeric($value['min'])) {
                $p = 'p' . $qb->getParameters()->count();
                $qb->andWhere($qb->expr()->gte($column, ':' . $p));
                $qb->setParameter($p, is_string($value['min']) ? trim($value['min']) : $value['min']);
            }

            if (isset($value['max']) && is_numeric($value['max'])) {
                $p = 'p' . $qb->getParameters()->count();
                $qb->andWhere($qb->expr()->lte($column, ':' . $p));
                $qb->setParameter($p, is_string($value['max']) ? trim($value['max']) : $value['max']);
            }

            return;
        }

        // ?foo=1
        if (is_numeric($value)) {
            $p = 'p' . $qb->getParameters()->count();
            $qb->andWhere($qb->expr()->eq($column, ':' . $p));
            $qb->setParameter($p, is_string($value) ? trim($value) : $value);

            return;
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param int|null $mode
     * @param mixed $value
     *
     * @return void
     */
    private function filterByStringValue(QueryBuilder $qb, string $column, ?int $mode, $value) : void
    {
        if (is_string($value)) {
            if (null === $mode) {
                $p = 'p' . $qb->getParameters()->count();
                $qb->andWhere($qb->expr()->eq($column, ':' . $p));
                $qb->setParameter($p, $value);

                return;
            }

            if ($mode & self::MODE_LIKE) {
                $value = addcslashes($value, '%_');
                if ($mode & self::WILDCARDS) {
                    $value = strtr($value, '*', '%');
                }

                if ($mode & self::STARTS_WITH) {
                    $value = $value . '%';
                } elseif ($mode & self::CONTAINS) {
                    $value = '%' . $value . '%';
                } elseif ($mode & self::ENDS_WITH) {
                    $value = '%' . $value;
                }

                $p = 'p' . $qb->getParameters()->count();
                $qb->andWhere($qb->expr()->like($column, ':' . $p));
                $qb->setParameter($p, $value);

                return;
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param mixed $value
     *
     * @return void
     */
    private function filterByDateValue(QueryBuilder $qb, string $column, $value) : void
    {
        // ?event[from]=1970-01-01&event[until]=2038-01-19 or
        // ?event[from]=0&event[until]=2147472000
        if (is_array($value)) {
            if (isset($value['from'])) {
                $from = $this->createDate($value['from']);
                if (isset($from)) {
                    $p = 'p' . $qb->getParameters()->count();
                    $qb->andWhere($qb->expr()->gte($column, ':' . $p));
                    $qb->setParameter($p, $from);
                }
            }

            if (isset($value['until'])) {
                $until = $this->createDate($value['until']);
                if (isset($until)) {
                    $p = 'p' . $qb->getParameters()->count();
                    $qb->andWhere($qb->expr()->lte($column, ':' . $p));
                    $qb->setParameter($p, $until);
                }
            }

            return;
        }

        $until = $this->createDate($value);
        if (isset($until)) {
            $p = 'p' . $qb->getParameters()->count();
            $qb->andWhere($qb->expr()->eq($column, ':' . $p));
            $qb->setParameter($p, $until);
        }
    }

    /**
     * @return int
     */
    private function getLimit() : int
    {
        if (isset($this->limit)) {
            return $this->limit;
        }

        // ?limit=10
        if (isset($this->data['limit'])) {
            return $this->limit = filter_var($this->data['limit'], FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => $this->defaultLimit,
                    'min_range' => 1,
                    'max_range' => $this->maxLimit,
                ],
            ]);
        }

        // ?pagesize=10
        if (isset($this->data['pagesize'])) {
            return $this->limit = filter_var($this->data['pagesize'], FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => $this->defaultLimit,
                    'min_range' => 1,
                    'max_range' => $this->maxLimit,
                ],
            ]);
        }

        return $this->defaultLimit;
    }

    /**
     * @return int
     */
    private function getOffset() : int
    {
        // ?offset=0
        if (isset($this->data['offset'])) {
            return filter_var($this->data['offset'], FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => 0,
                    'min_range' => 0,
                    'max_range' => PHP_INT_MAX,
                ],
            ]);
        }

        // ?page=1
        if (isset($this->data['page'])) {
            $page = filter_var($this->data['page'], FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => 1,
                    'min_range' => 1,
                    'max_range' => PHP_INT_MAX,
                ],
            ]);

            return $this->getLimit() * ($page - 1);
        }

        return 0;
    }

    /**
     * Creates a new DateTime object from the given value
     *
     * @param mixed $value
     *
     * @return DateTimeInterface|null
     */
    private function createDate($value) : ?DateTimeInterface
    {
        if (is_string($value)) {
            if (ctype_digit($value)) {
                return date_create()->setTimestamp((int) $value) ?: null;
            }

            return date_create($value) ?: null;
        }

        if (is_int($value)) {
            return date_create()->setTimestamp($value) ?: null;
        }

        return null;
    }

    /**
     * Resolves the given sort direction
     *
     * @param int|string $dir
     * @param string|null $default
     *
     * @return string|null
     */
    private function resolveSortDirection($dir, ?string $default = null) : ?string
    {
        switch (true) {
            case $dir === self::SORT_ASC:
            case $dir === 'ASC':
            case $dir === 'asc':
                return 'ASC';
            case $dir === self::SORT_DESC:
            case $dir === 'DESC':
            case $dir === 'desc':
                return 'DESC';
        }

        return $default;
    }
}
