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
use function mb_strlen;
use function mb_substr;
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
     * @var int
     */
    public const MODE_ONE_OF = 32;

    /**
     * @var int
     */
    public const MODE_ALL_OF = 64;

    /**
     * @var array<string, string|array<string, string>>
     */
    private $data;

    /**
     * @var array<string, array<string, array<string, int|string|null>>>
     *
     * @psalm-var array<string, array<string, array{
     *      type: int,
     *      mode: int|null,
     * }>>
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
     * @var int
     */
    private $maxArraySize = 100;

    /**
     * @var int
     */
    private $maxStringLength = 1000;

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
        $this->filterBy[$key][$column]['type'] = $type;
        $this->filterBy[$key][$column]['mode'] = $mode;
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
     * @param int $size
     *
     * @return void
     */
    public function maxArraySize(int $size) : void
    {
        $this->maxArraySize = $size;
    }

    /**
     * @param int $length
     *
     * @return void
     */
    public function maxStringLength(int $length) : void
    {
        $this->maxStringLength = $length;
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

        $qb->setMaxResults($this->getLimit());
        $qb->setFirstResult($this->getOffset());

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

            $exprs = [];
            foreach ($filterBy as $column => $params) {
                if ('null' === $this->data[$key]) {
                    $exprs[] = $qb->expr()->isNull($column);
                    continue;
                }

                if ('not-null' === $this->data[$key]) {
                    $exprs[] = $qb->expr()->isNotNull($column);
                    continue;
                }

                if (self::TYPE_BOOL === $params['type']) {
                    $expr = $this->createExpressionForBooleanValue($qb, $column, $this->data[$key]);
                    if (isset($expr)) {
                        $exprs[] = $expr;
                    }

                    continue;
                }

                if (self::TYPE_DATE === $params['type']) {
                    $expr = $this->createExpressionForDateValue($qb, $column, $this->data[$key]);
                    if (isset($expr)) {
                        $exprs[] = $expr;
                    }

                    continue;
                }

                if (self::TYPE_NUM === $params['type']) {
                    $expr = $this->createExpressionForNumericValue($qb, $column, $this->data[$key]);
                    if (isset($expr)) {
                        $exprs[] = $expr;
                    }

                    continue;
                }

                if (self::TYPE_STR === $params['type']) {
                    $expr = $this->createExpressionForStringValue($qb, $column, $params['mode'], $this->data[$key]);
                    if (isset($expr)) {
                        $exprs[] = $expr;
                    }

                    continue;
                }
            }

            if (!empty($exprs)) {
                $qb->andWhere($qb->expr()->orX(...$exprs));
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
     * @param string $column
     * @param mixed $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|null
     */
    private function createExpressionForBooleanValue(QueryBuilder $qb, string $column, $value)
    {
        // an empty string equals the false...
        // e.g. ?is_enabled=
        if ('' === $value) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if (isset($value)) {
            $p = 'p' . $qb->getParameters()->count();
            $qb->setParameter($p, $value);

            return $qb->expr()->eq($column, ':' . $p);
        }

        return null;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param mixed $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|\Doctrine\ORM\Query\Expr\Andx|null
     */
    private function createExpressionForDateValue(QueryBuilder $qb, string $column, $value)
    {
        // ?event[from]=1970-01-01&event[until]=2038-01-19 or
        // ?event[from]=0&event[until]=2147472000
        if (is_array($value)) {
            $exprs = [];

            if (isset($value['from'])) {
                $from = $this->createDate($value['from']);
                if (isset($from)) {
                    $p = 'p' . $qb->getParameters()->count();
                    $qb->setParameter($p, $from);
                    $exprs[] = $qb->expr()->gte($column, ':' . $p);
                }
            }

            if (isset($value['until'])) {
                $until = $this->createDate($value['until']);
                if (isset($until)) {
                    $p = 'p' . $qb->getParameters()->count();
                    $qb->setParameter($p, $until);
                    $exprs[] = $qb->expr()->lte($column, ':' . $p);
                }
            }

            return !empty($exprs) ? $qb->expr()->andX(...$exprs) : null;
        }

        $date = $this->createDate($value);
        if (isset($date)) {
            $p = 'p' . $qb->getParameters()->count();
            $qb->setParameter($p, $date);

            return $qb->expr()->eq($column, ':' . $p);
        }

        return null;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param mixed $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|\Doctrine\ORM\Query\Expr\Andx|null
     */
    private function createExpressionForNumericValue(QueryBuilder $qb, string $column, $value)
    {
        // ?foo[min]=1&foo[max]=2
        if (is_array($value)) {
            $exprs = [];

            if (isset($value['min']) && is_numeric($value['min'])) {
                $p = 'p' . $qb->getParameters()->count();
                $qb->setParameter($p, is_string($value['min']) ? trim($value['min']) : $value['min']);
                $exprs[] = $qb->expr()->gte($column, ':' . $p);
            }

            if (isset($value['max']) && is_numeric($value['max'])) {
                $p = 'p' . $qb->getParameters()->count();
                $qb->setParameter($p, is_string($value['max']) ? trim($value['max']) : $value['max']);
                $exprs[] = $qb->expr()->lte($column, ':' . $p);
            }

            return !empty($exprs) ? $qb->expr()->andX(...$exprs) : null;
        }

        // ?foo=1
        if (is_numeric($value)) {
            $p = 'p' . $qb->getParameters()->count();
            $qb->setParameter($p, is_string($value) ? trim($value) : $value);

            return $qb->expr()->eq($column, ':' . $p);
        }

        return null;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param int|null $mode
     * @param mixed $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|\Doctrine\ORM\Query\Expr\Andx|\Doctrine\ORM\Query\Expr\Orx|null
     */
    private function createExpressionForStringValue(QueryBuilder $qb, string $column, ?int $mode, $value)
    {
        if (is_array($value)) {
            $i = 0;
            $exprs = [];
            foreach ($value as $v) {
                if (++$i > $this->maxArraySize) {
                    break;
                } elseif (!is_string($v)) {
                    continue;
                }

                $expr = $this->createExpressionForString($qb, $column, $mode, $v);
                if (isset($expr)) {
                    $exprs[] = $expr;
                }
            }

            if ($mode & self::MODE_ONE_OF) {
                return !empty($exprs) ? $qb->expr()->orX(...$exprs) : null;
            }

            if ($mode & self::MODE_ALL_OF) {
                return !empty($exprs) ? $qb->expr()->andX(...$exprs) : null;
            }

            return $exprs[0] ?? null;
        }

        if (is_string($value)) {
            return $this->createExpressionForString($qb, $column, $mode, $value);
        }

        return null;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $column
     * @param int|null $mode
     * @param string $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|null
     */
    private function createExpressionForString(QueryBuilder $qb, string $column, ?int $mode, string $value)
    {
        // e.g. ?foo=
        if ('' === $value) {
            return null;
        }

        if (mb_strlen($value) > $this->maxStringLength) {
            $value = mb_substr($value, 0, $this->maxStringLength);
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
            $qb->setParameter($p, $value);

            return $qb->expr()->like($column, ':' . $p);
        }

        $p = 'p' . $qb->getParameters()->count();
        $qb->setParameter($p, $value);

        return $qb->expr()->eq($column, ':' . $p);
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
        // an empty string equals the current time...
        // e.g. ?created_at=
        if ('' === $value) {
            return null;
        }

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
