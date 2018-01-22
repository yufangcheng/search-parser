<?php

namespace SearchParser\Pipelines\OrderPipeline;

use SearchParser\SearchParser;
use SearchParser\Pipelines\AbstractPipeline;
use Avris\Bag\Bag;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;
use SearchParser\Pipelines\OrderPipeline\Middlewares\AbstractMiddleware;
use SearchParser\Exceptions;

class OrderPipeline extends AbstractPipeline
{
    static protected $middlewares = [
        //
    ];

    public function handle(SearchParser $parser, Bag $payload, Closure $next)
    {
        $orders = $this->getRequestParams($parser);

        if (is_array($orders)) {
            foreach ($orders as $order) {
                list($field, $direction) = $this->parseOrder($order);
                $middleware = array_reduce(
                    array_reverse($this->getMiddlewares()),
                    $this->middlewaresFrame(),
                    $this->afterMiddlewaresFrameProcessed($orders, $payload)
                );
                $middleware($parser->getBuilder(), $field, $direction);
            }
        }

        return $next($parser, $payload);
    }

    protected function getMiddlewares()
    {
        return array_merge(
            self::$middlewares,
            getConfig('middlewares.sort')
        );
    }

    protected function middlewaresFrame()
    {
        return function ($prevCarry, $middleware) {
            return function (Builder $builder, $field, $direction) use ($prevCarry, $middleware) {
                if ($middleware instanceof Closure) {
                    return $middleware($builder, $field, $direction, $prevCarry);
                }

                if (is_string($middleware)) {
                    $middleware = new $middleware;
                }

                if ($middleware instanceof AbstractMiddleware) {
                    return $middleware->handle($builder, $field, $direction, $prevCarry);
                }

                throw new Exceptions\InvalidMiddlewareException(sprintf(
                    'Invalid middleware %s.',
                    $middleware
                ));
            };
        };
    }

    protected function afterMiddlewaresFrameProcessed(array $orders, Bag $payload)
    {
        return function (Builder $builder, $field, $direction) use ($orders, $payload) {
            $nonRelationFields = [];
            $relationFields = $this->getRelationsPayload($payload);

            list($relation, $field) = array_pad(
                $this->parseFieldRelation($field), 2, null
            );

            if ($field) {
                if ($relation) {
                    $relationData = $this->getRelationPayload($relationFields, $relation);
                    $relationData->add($this->addRelationFieldsOrders($field, $direction));
                } else {
                    $nonRelationFields[] = [$field => $direction];
                }
            }

            $this->addNonRelationFieldsOrders($builder, $nonRelationFields);
        };
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
            $newDirection = implode('', $explode);

            if ($newDirection) {
                $direction = $newDirection;
            }
        }

        return [$field, $direction];
    }
}
