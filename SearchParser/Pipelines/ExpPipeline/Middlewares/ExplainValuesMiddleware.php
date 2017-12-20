<?php

namespace SearchParser\Pipelines\ExpPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Closure;
use Inno\Lib\SearchParser\Exceptions;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\ValueTypes;

class ExplainValuesMiddleware extends AbstractMiddleware
{
    public function handle(Builder $builder, CaseInterface $case, Closure $next)
    {
        call_user_func(Closure::bind(function ($value) {
            $this->value = $value;
        }, $case, $case), $this->explainValues($builder, $case));

        return $next($builder, $case);
    }

    protected function explainValues(Builder $builder, CaseInterface $case)
    {
        $values = $case->value;
        $multiple = true;

        if (!is_array($case->value)) {
            $values = [$case->value];
            $multiple = false;
        }

        foreach ($values as &$value) {
            $valueType = $this->getValueType($builder, $case, $value);
            $value = $valueType->explain();
        }

        return $multiple ? $values : current($values);
    }

    protected function getTableName(Builder $builder, CaseInterface $case)
    {
        if ($case->fieldRelation) {
            return $case->getTable(
                $builder->getRelation($case->fieldRelation)
            );
        }

        return $case->getTable($builder);
    }

    /**
     * 根据当前 Case 的支持，找到对应的 value type 并初始化
     *
     * @param Builder $builder
     * @param CaseInterface $case
     * @param $value
     * @return ValueTypes\AbstractValueType
     */
    protected function getValueType(Builder $builder, CaseInterface $case, $value)
    {
        try {
            foreach ($case->getSupportedValueTypes() as $supportedValueType) {
                if ($valueType = forward_static_call_array([$supportedValueType, 'match'], [
                    $builder, $this->getTableName($builder, $case), $case->field, $value
                ])) {
                    return $valueType;
                }
            }
        } catch (\Exception $e) {
            /**
             * 任何异常或者不做处理，任由下面抛错
             * 没有匹配到任何适用的 value type 也将执行下面的抛错
             */
        }

        throw new Exceptions\SearchParserInvalidValueTypeException(sprintf(
            "There's no available value type for value %s.",
            $value
        ));
    }
}
