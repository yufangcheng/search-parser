<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Middlewares;

class GreatCase extends AbstractCase
{
    static protected $preMiddlewares = [
        Middlewares\TrimSurroundQuotes::class,
    ];

    public function getSupportedValueTypes()
    {
        return [
            ValueTypes\NumericValueType::class,
            ValueTypes\DatetimeValueType::class
        ];
    }

    protected function buildLaravelQuery($builder)
    {
        if ($table = $this->getTable($builder)) {
            $builder->where(
                $table . '.' . $this->field,
                strtoupper($this->negation) === 'NOT' ? '<=' : '>',
                $this->value
            );
        }
    }
}
