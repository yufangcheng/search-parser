<?php

namespace SearchParser\Pipelines;

use SearchParser\SearchParser;
use Avris\Bag\Bag;
use Avris\Bag\Set;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class WithPipeline extends AbstractPipeline
{
    public function handle(SearchParser $parser, Bag $payload, Closure $next)
    {
        $relations = $this->getRequestParams($parser);
        /**
         * @var Bag $relationsFromPipelines
         */
        $relationsFromPipelines = $payload->get('relations');
        $relations = array_diff($relations, $relationsFromPipelines->keys());

        $builder = $parser->getBuilder();

        $this->setRelationsFromWith($builder, $relations);
        $this->setRelationsFromPipelines($builder, $relationsFromPipelines->all());

        return $next($parser, $payload);
    }

    protected function getRequestParams(SearchParser $parser)
    {
        $request = $parser->getRequest();
        $with = $request->get(getConfig('with'), null);

        return array_filter(preg_split('/\s*,\s*/', $with));
    }

    protected function setRelationsFromWith(Builder $builder, $relations)
    {
        if (is_array($relations) && !empty($relations)) {
            $builder->with($relations);
        }
    }

    protected function setRelationsFromPipelines(Builder $builder, $relations)
    {
        if (is_array($relations) && !empty($relations)) {
            foreach ($relations as $relation => $set) {
                $builder->with([$relation => $this->scopeCarry($set)]);
            }
        }
    }

    protected function scopeCarry($set)
    {
        return function (Relation $query) use ($set) {
            if ($set instanceof Set) {
                foreach ($set as $callback) {
                    is_callable($callback) && $callback($query);
                }
            }
        };
    }
}
