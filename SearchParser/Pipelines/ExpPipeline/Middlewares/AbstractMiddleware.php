<?php

namespace SearchParser\Pipelines\ExpPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Closure;

abstract class AbstractMiddleware
{
    abstract public function handle(Builder $builder, CaseInterface $case, Closure $next);
}
