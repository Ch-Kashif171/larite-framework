<?php

namespace Lumite\Database;

use Lumite\Database\Contracts\DoctrineContract;
use Lumite\Database\Traits\AggregateTrait;
use Lumite\Database\Traits\ClauseTrait;
use Lumite\Database\Traits\JoinTrait;
use Lumite\Database\Traits\MutationTrait;
use Lumite\Database\Traits\PaginationTrait;
use Lumite\Database\Traits\RetrievalTrait;
use Lumite\Database\Traits\SelectTrait;
use Lumite\Database\Traits\WhereTrait;
use Lumite\Support\Traits\Internal\Queries;

/**
 * Main Query ORMQueryBuilder class implementing the contract and composing traits.
 */
class Doctrine implements DoctrineContract
{
    use Queries; // Assuming this sets $con, $table, etc.
    use SelectTrait;
    use RetrievalTrait;
    use AggregateTrait;
    use MutationTrait;
    use WhereTrait;
    use JoinTrait;
    use ClauseTrait;
    use PaginationTrait;

    protected bool $distinct = false;

    /**
     * @param string $columns
     * @return string
     */
    protected function buildSelectQuery(string $columns): string
    {
        $limitClause = $this->limit ?: '';
        $offsetClause = $this->offset ?: '';
        $takeClause = $this->take ?: '';
        $distinct = $this->distinct ? 'DISTINCT ' : '';

        return "SELECT {$distinct}{$columns} FROM {$this->table}"
            . $this->joins
            . $this->wheres
            . $takeClause
            . $this->groupBy
            . $this->having
            . $this->orderBy
            . $limitClause
            . $offsetClause;
    }

    public function getWheres(): string
    {
        return $this->wheres;
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

}
