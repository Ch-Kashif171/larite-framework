<?php

namespace Lumite\Database\Traits;

/**
 * Trait for pagination methods.
 */
trait PaginationTrait
{
    /**
     * @param int $limit
     * @param array $timestamp
     * @param array $hidden
     * @return array
     */
    public function paginate(int $limit, array $timestamp = [], array $hidden = [], string $pageName = 'page'): array
    {
        $pagination = [];

        $page = isset($_GET[$pageName]) && is_numeric($_GET[$pageName]) && $_GET[$pageName] > 0 ? (int)$_GET[$pageName] : 1;

        if (!empty($this->groupBy)) {
            $sql_statement = "SELECT count(*) as count FROM (SELECT 1 FROM {$this->table}"
                . $this->joins
                . $this->wheres
                . $this->groupBy
                . $this->having
                . ") as aggregate_table";
        } else {
            $sql_statement = "SELECT count(*) as count FROM {$this->table}"
                . $this->joins
                . $this->wheres
                . $this->groupBy
                . $this->having;
        }
        $count = $this->con->query($sql_statement);
        $total = $count->fetch(\PDO::FETCH_OBJ);
        $totalCount = (int)$total->count;
        $lastPage = (int) ceil($totalCount / $limit);

        if ($page > $lastPage && $lastPage > 0) {
            $page = $lastPage;
        }
        $offset = ($page - 1) * $limit;

        $limitClause = $this->limit ?: " LIMIT {$limit}";
        $offsetClause = $this->offset ?: " OFFSET {$offset}";

        $columns = $this->getColumns($timestamp, $hidden);

        $sql = "SELECT {$columns} FROM {$this->table}"
            . $this->joins
            . $this->wheres
            . $this->groupBy
            . $this->having
            . $this->orderBy
            . $limitClause
            . $offsetClause;
        $query = $this->con->query($sql);
        $result = $query->fetchAll(\PDO::FETCH_OBJ);

        $from = $totalCount > 0 ? $offset + 1 : 0;
        $to = $totalCount > 0 ? min($offset + $limit, $totalCount) : 0;

        $baseUrl = full_path();
        $pagination['data'] = $result;
        $pagination['current_page'] = $page;
        $pagination['per_page'] = $limit;
        $pagination['total'] = $totalCount;
        $pagination['last_page'] = $lastPage;
        $pagination['from'] = $from;
        $pagination['to'] = $to;
        $pagination['first_page_url'] = $baseUrl . '?' . $pageName . '=1';
        $pagination['last_page_url'] = $baseUrl . '?' . $pageName . '=' . $lastPage;
        $pagination['next_page_url'] = $page < $lastPage ? $baseUrl . '?' . $pageName . '=' . ($page + 1) : null;
        $pagination['prev_page_url'] = $page > 1 ? $baseUrl . '?' . $pageName . '=' . ($page - 1) : null;
        $pagination['path'] = $baseUrl;
        $pagination['pageName'] = $pageName;

        return $pagination;
    }

    /**
     * @param int $limit
     * @param array $timestamp
     * @param array $hidden
     * @return array
     */
    public function simplePaginate(int $limit, array $timestamp = [], array $hidden = [], string $pageName = 'page'): array
    {
        $pagination = $this->paginate($limit, $timestamp, $hidden, $pageName);
        $pagination['simple'] = true;
        return $pagination;
    }
}