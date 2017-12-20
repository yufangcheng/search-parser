<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Middlewares;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Avris\Bag\Bag;
use Avris\Bag\Set;
use Closure;
use Inno\Lib\SearchParser\Exceptions;

abstract class AbstractCase implements CaseInterface
{
    protected $fieldRelation;
    protected $field;
    protected $negation;
    protected $value;

    protected $supportedValueTypes = [
        //
    ];

    static protected $preMiddlewares = [
        //
    ];

    static protected $postMiddlewares = [
        Middlewares\CheckIsFieldExistedMiddleware::class,
        Middlewares\ExplainValuesMiddleware::class,
    ];

    public function __construct($fieldRelation, $field, $negation, $value)
    {
        $this->fieldRelation = $fieldRelation;
        $this->field = $field;
        $this->negation = $negation;
        $this->value = $value;
    }

    /**
     * 获取 Case 会经过的中间件
     *
     * @return array
     */
    public function getMiddlewares()
    {
        $preMiddlewares = array_unique(array_merge(
            self::$preMiddlewares,
            static::$preMiddlewares
        ));
        $postMiddlewares = array_unique(array_merge(
            self::$postMiddlewares,
            static::$postMiddlewares
        ));

        return array_merge(
            $preMiddlewares,
            getConfig('middlewares'),
            $postMiddlewares
        );
    }

    /**
     * 分解字段上可能带有的关联关系
     *
     * @param $field
     * @return array
     */
    protected function parseRelation($field)
    {
        $relation = null;

        if (is_string($field) && stripos($field, '.')) {
            $explode = explode('.', $field);
            $field = array_pop($explode);
            $relation = implode('.', $explode);
        }

        return [$relation, $field];
    }

    /**
     * 在荷载 payload 上设置关联关系数据
     *
     * @param Bag $payload
     * @return Set|mixed
     */
    protected function setRelationData(Bag $payload)
    {
        if ($payload->has('relations')) {
            $relationFields = $payload->get('relations');
        } else {
            $payload->set('relations', $relationFields = new Bag());
        }

        if (!$relationFields->has($this->fieldRelation)) {
            $relationFields->set($this->fieldRelation, $relationData = new Set());
        } else {
            $relationData = $relationFields->get($this->fieldRelation);
        }

        return $relationData;
    }

    /**
     * 从不同类型的对象获取数据表名
     *
     * @param Builder|Relation $builder
     * @return null|string
     */
    public function getTable($builder)
    {
        $table = null;

        switch (true) {
            case $builder instanceof Builder:
                $table = $builder->getModel()->getTable();
                break;
            case $builder instanceof Relation:
                $table = $builder->getRelated()->getTable();
                break;
        }

        return $table;
    }

    /**
     * 不覆写则抛错
     */
    public function getSupportedValueTypes()
    {
        throw new Exceptions\SearchParserInvalidValueTypeException(sprintf(
            "The case %s doesn't support any value types.",
            __CLASS__
        ));
    }

    /**
     * 组装 Laravel 查询对象的条件的入口
     * 在这之前会执行 Case 对象设置中间件
     *
     * @param Builder $builder
     * @param Bag $payload
     */
    public function assemble(Builder $builder, Bag $payload)
    {
        if ($this->fieldRelation) {
            $relationData = $this->setRelationData($payload);
            $relationData->add(Closure::bind(function (Relation $query) {
                $this->buildLaravelQuery($query);
            }, $this));
        } else {
            $this->buildLaravelQuery($builder);
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }

    /**
     * 利用当前 Case 对象上的数据拼装 Laravel 查询对象的条件
     *
     * @param $builder
     * @return mixed
     */
    abstract protected function buildLaravelQuery($builder);
}
