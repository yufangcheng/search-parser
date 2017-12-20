<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Middlewares;

class ContainCase extends AbstractCase
{
    static protected $preMiddlewares = [
        Middlewares\SplitMultipleValueMiddleware::class,
    ];

    public function getSupportedValueTypes()
    {
        return [
            ValueTypes\NumericValueType::class,
            ValueTypes\QuotedStringValueType::class
        ];
    }

    protected function buildLaravelQuery($builder)
    {
        if ($table = $this->getTable($builder)) {
            $sql = trim(sprintf(
                "%s JSON_CONTAINS(%s, '%s')",
                strtoupper($this->negation) === 'NOT' ? 'NOT' : '',
                $table . '.' . $this->field,
                json_encode($this->value, JSON_UNESCAPED_UNICODE)
            ));
            $builder->whereRaw($sql);
        }
    }
}
