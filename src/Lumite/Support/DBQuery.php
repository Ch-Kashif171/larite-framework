<?php

namespace Lumite\Support;

use Lumite\Database\QueryBuilder;
use Lumite\Exception\Handlers\DBException;
use Whoops\Exception\ErrorException;

/**
 * Class DBQuery
 */
class DBQuery
{
    /**
     * @param $table
     * @return QueryBuilder
     * @throws DBException
     */
    public static function table($table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    /**
     * @param $sql
     * @return bool
     * @throws ErrorException
     */
    public static function rawQuery($sql)
    {
        return QueryBuilder::rawQuery($sql);
    }

}
