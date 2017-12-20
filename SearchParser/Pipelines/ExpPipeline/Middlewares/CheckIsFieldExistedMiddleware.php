<?php

namespace SearchParser\Pipelines\ExpPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Closure;
use Inno\Lib\SearchParser\Exceptions\SearchParserInvalidCaseException;

class CheckIsFieldExistedMiddleware extends AbstractMiddleware
{
    public function handle(Builder $builder, CaseInterface $case, Closure $next)
    {
        $table = $this->getTableName($builder, $case);
        /**
         * @var \Illuminate\Database\Connection $connection
         */
        $connection = $builder->getConnection();
        $schema = $connection->getSchemaBuilder();
        $columns = $schema->getColumnListing($table);

        if (!in_array($case->field, $columns)) {
            throw new SearchParserInvalidCaseException(sprintf(
                "Field `%s` does not exists in table `%s`.",
                $case->field,
                $table
            ));
        }

        return $next($builder, $case);
    }

    protected function getTableName(Builder $builder, CaseInterface $case)
    {
        if ($case->fieldRelation) {
            return $case->getTable(
                $builder->getRelation($case->fieldRelation)
            );
        }

        return $case->getTable($builder);
    }
}
