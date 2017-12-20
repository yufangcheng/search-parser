<?php

namespace SearchParser\Pipelines\ExpPipeline\Middlewares;

use Illuminate\Database\Eloquent\Builder;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\AbstractCase;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Closure;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Expression;

class EscapeQuotedStringMiddleware extends AbstractMiddleware
{
    public function handle(Builder $builder, CaseInterface $case, Closure $next)
    {
        /**
         * @var AbstractCase $case
         */
        $value = $case->value;

        if ($like = $this->isLikeString($value)) {
            $value = $this->escapePercents($value);
            $value = $this->escapeUnderlines($value);
            $value = $this->unescape($value);
        } else {
            $value = stripslashes($value);
        }

        $this->setCaseValue($case, [
            'value' => $value,
            'like'  => $like
        ]);

        return $next($builder, $case);
    }

    protected function isLikeString($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $value = new Expression($value);

        foreach ($value as $char) {
            if ($char === '*' && !checkIsEscapeChar($value->getString(), $value->key())) {
                return true;
            }
        }

        return false;
    }

    protected function setCaseValue(CaseInterface $case, array $params)
    {
        /**
         * @var AbstractCase $case
         */
        call_user_func(Closure::bind(function (array $params) {
            foreach ($params as $name => $value) {
                $this->{$name} = $value;
            }
        }, $case, $case), $params);
    }

    protected function escapePercents($value)
    {
        return $this->escapeSpecificChar($value, '%');
    }

    protected function escapeUnderlines($value)
    {
        return $this->escapeSpecificChar($value, '_');
    }

    protected function unescape($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $value = new Expression($value);
        $chars = [];
        $slashes = [];

        foreach ($value as $char) {
            if ($char === '\\') {
                if (count($slashes) % 2 === 1) {
                    array_pop($slashes);
                } else {
                    $slashes[] = $char;
                }
            } else if ($char === '*') {
                if (count($slashes) % 2 === 1) {
                    array_pop($chars);
                    array_pop($slashes);
                } else {
                    $char = '%';
                }
            } else {
                if (count($slashes) % 2 === 1) {
                    array_pop($chars);
                    array_pop($slashes);
                }
            }

            $chars[] = $char;
        }

        return implode('', $chars);
    }

    protected function escapeSpecificChar($value, $char)
    {
        if (is_string($value) && is_string($char) && strlen($char) > 0) {
            return addcslashes($value, $char);
        }

        return $value;
    }
}
