<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases;

use SearchParser\Pipelines\ExpPipeline\Middlewares;

class WildcardCase extends AbstractCase
{
    static protected $preMiddlewares = [
        Middlewares\EscapeQuotedStringMiddleware::class,
        Middlewares\TrimSurroundQuotes::class,
    ];

    public function getSupportedValueTypes()
    {
        return [
            ValueTypes\QuotedStringValueType::class
        ];
    }

    protected function buildLaravelQuery($builder)
    {
        if ($table = $this->getTable($builder)) {
            $builder->where(
                $table . '.' . $this->field,
                strtoupper(trim($this->negation . ' LIKE')),
                $this->value
            );
        }
    }
}
