<?php

namespace SearchParser\Pipelines\ExpPipeline\ExpAnalyzers;

use Avris\Bag\Bag;
use Closure;
use SearchParser\Exceptions\Analyze;
use SearchParser\Pipelines\ExpPipeline\Expression;

class ConvertToSimpleExpAnalyzer extends AbstractAnalyzer
{
    const RULE_PARAM = '/\s*(?<params>\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*/';
    const RULE_EXP_CLOSED_INTERVAL = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?\[\s*(\*|\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s+TO\s+(\*|\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*\]/i';
    const RULE_EXP_LEFT_CLOSED_RIGHT_OPEN_INTERVAL = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?\[\s*(\*|\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s+TO\s+(\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*\}/i';
    const RULE_EXP_OPEN_INTERVAL = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?\{\s*(\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s+TO\s+(\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*\}/i';
    const RULE_EXP_LEFT_OPEN_RIGHT_CLOSED_INTERVAL = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?\{\s*(\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s+TO\s+(\*|\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*\]/i';
    const RULE_EXP_SET = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?<(\s*(?:\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*(?:,\s*(?:\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*)*)>/i';
    const RULE_EXP_CONTAIN = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?\|(\s*(?:\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*(?:,\s*(?:\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))\s*)*)\|/i';
    const RULE_EXP_LIKE = '/(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*~\s*(?:(NOT)\s+)?(\-?\d+|".*?"|[a-z]\w*\(\s*(?:(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\))/i';

    public function analyze(Expression $exp, Bag $ast, Closure $next)
    {
        $expString = $exp->getString();
        $rules = $this->getExpRules();

        while (true) {
            $matchedAtLeastOnce = false;

            foreach ($rules as $ruleName => $rule) {
                $matches = [];

                if (preg_match($rule, $expString, $matches)) {
                    $matchedAtLeastOnce = true;
                    $expString = $this->invokeProcess(
                        $this->getRuleProcessName($ruleName),
                        $expString,
                        $matches
                    );
                }
            }

            if (!$matchedAtLeastOnce) {
                break;
            }
        }

        return $next(new Expression($expString), $ast);
    }

    protected function getRuleProcessName($ruleName)
    {
        $rules = $this->getExpRules();

        if (!isset($rules[$ruleName])) {
            throw new Analyze\AnalyzeMissingStateException(sprintf(
                "Missing rule which name is %s.",
                $ruleName
            ));
        }

        $segments = explode('_', $ruleName);
        array_shift($segments);
        array_shift($segments);
        $segments[] = 'PROCESS';
        $process = lineToHump(implode('_', $segments));

        return $process;
    }

    protected function getExpRules()
    {
        return $this->getConfigs('RULE_EXP_');
    }

    protected function getFullFieldName($relation, $field)
    {
        $fullField = implode('.', array_filter([$relation, $field], function ($str) {
            return !checkIsBlank($str);
        }));

        return $fullField;
    }

    protected function invokeProcess($process, $exp, $expData)
    {
        if (!method_exists($this, $process)) {
            throw new Analyze\AnalyzeMissingProcessException(sprintf(
                "Process %s is missing in analyzer %s.",
                $process,
                __CLASS__
            ));
        }

        return $this->{$process}($exp, $expData);
    }

    protected function closedIntervalProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $from, $to) = $expData;

        $from = trim($from);
        $to = trim($to);
        $fullField = $this->getFullFieldName($fieldRelation, $field);

        if ($from === '*' && $to === '*') {
            $replace = '';
        } else if ($from === '*') {
            $template = strtoupper($negation) === 'NOT' ? '%s %s>%s' : '%s %s<=%s';
            $replace = trim(sprintf($template, $relation, $fullField, $to));
        } else if ($to === '*') {
            $template = strtoupper($negation) === 'NOT' ? '%s %s<%s' : '%s %s>=%s';
            $replace = trim(sprintf($template, $relation, $fullField, $from));
        } else {
            $template = strtoupper($negation) === 'NOT' ? '%s (%s<%s OR %s>%s)' : '%s %s>=%s AND %s<=%s';
            $replace = trim(sprintf($template, $relation, $fullField, $from, $fullField, $to));
        }

        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }

    protected function leftClosedRightOpenIntervalProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $from, $to) = $expData;

        $from = trim($from);
        $to = trim($to);
        $fullField = $this->getFullFieldName($fieldRelation, $field);

        if ($from === '*') {
            $template = strtoupper($negation) === 'NOT' ? '%s %s>=%s' : '%s %s<%s';
            $replace = trim(sprintf($template, $relation, $fullField, $to));
        } else {
            $template = strtoupper($negation) === 'NOT' ? '%s (%s<%s OR %s>=%s)' : '%s %s>=%s AND %s<%s';
            $replace = trim(sprintf($template, $relation, $fullField, $from, $fullField, $to));
        }

        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }

    protected function openIntervalProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $from, $to) = $expData;

        $from = trim($from);
        $to = trim($to);
        $fullField = $this->getFullFieldName($fieldRelation, $field);

        if (strtoupper($negation) === 'NOT') {
            $template = '%s (%s<=%s OR %s>=%s)';
        } else {
            $template = '%s %s>%s AND %s<%s';
        }

        $replace = trim(sprintf($template, $relation, $fullField, $from, $fullField, $to));
        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }

    protected function leftOpenRightClosedIntervalProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $from, $to) = $expData;

        $from = trim($from);
        $to = trim($to);
        $fullField = $this->getFullFieldName($fieldRelation, $field);

        if ($to === '*') {
            $template = strtoupper($negation) === 'NOT' ? '%s %s<=%s' : '%s %s>%s';
            $replace = trim(sprintf($template, $relation, $fullField, $from));
        } else {
            $template = strtoupper($negation) === 'NOT' ? '%s (%s<=%s OR %s>%s)' : '%s %s>%s AND %s<=%s';
            $replace = trim(sprintf($template, $relation, $fullField, $from, $fullField, $to));
        }

        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }

    protected function setProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $values) = $expData;

        $fullField = $this->getFullFieldName($fieldRelation, $field);

        // 清除每个值的多余空格

        $params = [];

        if (preg_match_all(self::RULE_PARAM, $values, $params)) {
            $values = array_map(function ($value) {
                return trim($value);
            }, array_get($params, 'params', []));
        }

        if (count($values) === 1) {
            $template = '%s %s:%s';
        } else {
            $template = '%s %s:|%s';
        }

        $replace = trim(sprintf(
            $template, $relation, $fullField, trim($negation . ' ' . implode(',', $values))
        ));
        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }

    protected function containProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $values) = $expData;

        $fullField = $this->getFullFieldName($fieldRelation, $field);

        // 清除每个值的多余空格

        $params = [];

        if (preg_match_all(self::RULE_PARAM, $values, $params)) {
            $values = array_map(function ($value) {
                return trim($value);
            }, array_get($params, 'params', []));
        }

        $template = '%s %s:{%s';
        $replace = trim(sprintf(
            $template, $relation, $fullField, trim($negation . ' ' . implode(',', $values))
        ));
        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }

    protected function likeProcess($exp, $expData)
    {
        list($case, $relation, $fieldRelation, $field, $negation, $value) = $expData;

        $fullField = $this->getFullFieldName($fieldRelation, $field);
        $template = '%s %s:%s';
        $replace = trim(sprintf(
            $template, $relation, $fullField, trim($negation . ' ' . $value)
        ));
        $exp = trim(str_replace($case, $replace, $exp));

        return $exp;
    }
}
