<?php

namespace SearchParser\Pipelines\ExpPipeline\ExpAnalyzers;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Expression;
use Avris\Bag\Set;
use Closure;

abstract class AbstractAnalyzer
{
    const RULE_KEY_WITH_RELATION = '/^((?:AND|OR)\s+)?\s*[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?\s*$/i';

    const OPT_EQUAL = ':';
    const OPT_IN = ':|';
    const OPT_CONTAIN = ':{';
    const OPT_GREAT = '>';
    const OPT_GREAT_EQUAL = '>=';
    const OPT_LOWER = '<';
    const OPT_LOWER_EQUAL = '<=';
    const OPT_LIKE = '~';
    const OPT_NOT = 'NOT';

    const RELATION_AND = 'AND';
    const RELATION_OR = 'OR';
    const RELATION_TO = 'TO';

    const STATE_INITIAL = 0;
    const STATE_KEY_START = 1;
    const STATE_SCOPE_START = 2;
    const STATE_CHECK_OPERATOR_TYPE = 3;
    const STATE_CHECK_WHETHER_HAS_NOT = 4;
    const STATE_CHECK_VALUE_TYPE = 5;
    const STATE_NUMERIC_VALUE_START = 6;
    const STATE_QUOTED_VALUE_START = 7;
    const STATE_NOT_OPERATOR_START = 8;
    const STATE_CHECK_IS_MULTIPLE_VALUE = 9;
    const STATE_CHECK_RELATION_TYPE = 10;
    const STATE_AND_RELATION_START = 11;
    const STATE_OR_RELATION_START = 12;
    const STATE_EXP_END = 13;

    protected function getConfigs($prefix)
    {
        $configs = [];
        $reflect = new \ReflectionClass($this);

        foreach ($reflect->getConstants() as $config => $configValue) {
            if (stripos($config, $prefix) === 0) {
                $configs[$config] = $configValue;
            }
        }

        return $configs;
    }

    abstract public function analyze(Expression $exp, Set $ast, Closure $next);
}
