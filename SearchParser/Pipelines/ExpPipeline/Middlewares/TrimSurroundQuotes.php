<?php

namespace SearchParser\Pipelines\ExpPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Closure;

class TrimSurroundQuotes extends AbstractMiddleware
{
    public function handle(Builder $builder, CaseInterface $case, Closure $next)
    {
        call_user_func(Closure::bind(function ($value) {
            $this->value = $value;
        }, $case, $case), trim($case->value, '"'));

        return $next($builder, $case);
    }
}
