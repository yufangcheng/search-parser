<?php

namespace SearchParser\Pipelines;

use SearchParser\SearchParser;
use Avris\Bag\Bag;
use Avris\Bag\Set;
use Closure;

abstract class AbstractPipeline
{
    abstract public function handle(SearchParser $parser, Bag $payload, Closure $next);

    abstract protected function getRequestParams(SearchParser $parser);

    protected function getRelationsPayload(Bag $payload)
    {
        if ($payload->has('relations') && $payload->get('relations') instanceof Bag) {
            $relationFields = $payload->get('relations');
        } else {
            $payload->set('relations', $relationFields = new Bag());
        }

        return $relationFields;
    }

    protected function getRelationPayload(Bag $relationFields, $relation)
    {
        if ($relationFields->has($relation) && $relationFields->get($relation) instanceof Set) {
            $relationData = $relationFields->get($relation);
        } else {
            $relationFields->set($relation, $relationData = new Set());
        }

        return $relationData;
    }

    protected function parseFieldRelation($field)
    {
        $relation = null;

        if (is_string($field) && stripos($field, '.')) {
            $explode = array_filter(explode('.', $field));
            $field = array_pop($explode);
            $relation = implode('.', $explode);
        }

        return [$relation, $field];
    }
}
