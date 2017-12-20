<?php

namespace SearchParser\Pipelines\ExpPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Closure;

class SplitMultipleValueMiddleware extends AbstractMiddleware
{
    public function handle(Builder $builder, CaseInterface $case, Closure $next)
    {
        call_user_func(Closure::bind(function (array $values) {
            $this->value = $values;
        }, $case, $case), $this->splitValues($case->value));

        return $next($builder, $case);
    }

    protected function splitValues($value)
    {
        //TODO 无法区分 '12, "a,b,c", 34'
        $values = preg_split('/\s*,\s*/', $value);

        foreach ($values as &$v) {
            $originValue = trim($v, '"');

            /**
             * 当没有被双引号包住，则是一个数字
             * 数字需要强制转一下类型为 float 或者 int，这对 JSON 是有意义的，因为 JSON 对双引号敏感
             */
            if (strlen($originValue) === strlen($v)) {
                $originValue = $this->castType($originValue);
            }

            $v = $originValue;
        }

        return $values;
    }

    protected function castType($number)
    {
        if (is_numeric($number) && is_string($number)) {
            if (stripos($number, '.')) {
                $number = (float)$number;
            } else {
                $number = (int)$number;
            }
        }

        return $number;
    }
}
