<?php

namespace SearchParser\Pipelines;

use Inno\Lib\SearchParser\SearchParser;
use Avris\Bag\Bag;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class OrderPipeline extends AbstractPipeline
{
    public function handle(SearchParser $parser, Bag $payload, Closure $next)
    {
        $orders = $this->getRequestParams($parser);
        $nonRelationFields = [];
        $relationFields = $this->getRelationsPayload($payload);

        foreach ($orders as $order) {
            list($field, $direction) = array_pad(
                $this->parseOrder($order), 2, null
            );
            list($relation, $field) = array_pad(
                $this->parseFieldRelation($field), 2, null
            );

            if (!$field) {
                continue;
            }

            if (!$direction) {
                $direction = 'asc';
            }

            if ($relation) {
                $relationData = $this->getRelationPayload($relationFields, $relation);
                $relationData->add($this->addRelationFieldsOrders($field, $direction));
            } else {
                $nonRelationFields[] = [$field => $direction];
            }
        }

        $builder = $parser->getBuilder();
        $this->addNonRelationFieldsOrders($builder, $nonRelationFields);

        return $next($parser, $payload);
    }

    protected function getRequestParams(SearchParser $parser)
    {
        $request = $parser->getRequest();
        $order = $request->get(getConfig('sort'), null);

        return array_filter(preg_split('/\s*,\s*/', $order));
    }

    protected function addRelationFieldsOrders($field, $direction)
    {
        return function (Relation $query) use ($field, $direction) {
            $table = $query->getRelated()->getTable();
            $query->orderBy($table . '.' . $field, $direction);
        };
    }

    protected function addNonRelationFieldsOrders(Builder $builder, array $nonRelationFields)
    {
        if (!empty($nonRelationFields)) {
            foreach ($nonRelationFields as $field) {
                $builder->orderBy(key($field), current($field));
            }
        }
    }

    protected function parseOrder($order)
    {
        $field = $order;
        $direction = 'asc';

        if (is_string($order)) {
            $explode = array_filter(preg_split('/\s+/', $order));
            $field = array_shift($explode);
            $direction = implode('', $explode);
        }

        return [$field, $direction];
    }
}
