<?php

namespace SearchParser\Pipelines\OrderPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use Closure;

abstract class AbstractMiddleware
{
    abstract public function handle(Builder $builder, $field, $direction, Closure $next);
}
