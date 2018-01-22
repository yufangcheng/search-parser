<?php

namespace SearchParser\Pipelines\ExpPipeline;

use Avris\Bag\Set;
use Closure;
use SearchParser\Exceptions\InvalidFunctionClassException;
use SearchParser\Supports\Functions as FunctionsList;

class Functions
{
    const RULE_CATCH_FUNC_AND_PARAMS = '/^(?<func>[a-z]\w*)\(\s*(?<params>(?:\-?\d+|".*?")\s*(?:,\s*(?:\-?\d+|".*?"))*)?\s*\)$/';
    const RULE_CATCH_EACH_PARAM = '/\s*(?<params>\-?\d+|".*?")\s*/';

    public function call(Expression $exp, array $marks, Set $results, Closure $parentFunction)
    {
        foreach ($marks as $mark) {
            $function = $this->detectFunctionByBoundary($exp, $mark);
            $result = $this->searchResultFromExecutedFunctions($function, $results);

            if (is_null($result)) {
                if (!$results->isEmpty()) {
                    foreach ($results as $result) {
                        $function = self::takeFunctionResultsPlaces($function, $result);
                    }
                }

                try {
                    $result = $this->callFunction($function);
                    $results->add([$function => $result]);
                } catch (\Exception $e) {
                    throw new \Exception(sprintf(
                        "Error occurred when invoke function %s",
                        $function
                    ));
                }
            }
        }

        return $parentFunction($exp, $results);
    }

    public static function takeFunctionResultsPlaces($str, array $result)
    {
        $search = key($result);
        $replace = current($result);

        if (!is_int($replace) && !is_float($replace)) {
            $replace = "\"${replace}\"";
        }

        $str = str_replace($search, $replace, $str);

        return $str;
    }

    protected function detectFunctionByBoundary(Expression $exp, array $boundary)
    {
        list($from, $to) = $boundary;
        $function = substr($exp->getString(), $from, $to - $from + 1);

        return $function;
    }

    protected function callFunction($function)
    {
        list($functionName, $params) = $this->getFunctionNameAndParams($function);

        if (!$functionName) {
            throw new \Exception("Invalid function name.");
        }

        $params = $this->setParamsTypeForFunction($params);
        $result = $this->callCertainFunction($functionName, $params);

        return $result;
    }

    protected function callCertainFunction($function, array $params = null)
    {
        if ($class = getConfig('functionClass')) {
            if (
                $class !== FunctionsList::class &&
                !is_subclass_of($class, FunctionsList::class)
            ) {
                throw new InvalidFunctionClassException(sprintf(
                    "Registered function class must be a sub class of %s.",
                    FunctionsList::class
                ));
            }
        }

        return forward_static_call_array([$class, $function], $params);
    }

    private function getFunctionNameAndParams($signature)
    {
        $matches = [];
        $functionName = null;
        $paramsString = null;
        $params = [];

        if (preg_match(self::RULE_CATCH_FUNC_AND_PARAMS, $signature, $matches)) {
            $functionName = array_get($matches, 'func');
            $paramsString = array_get($matches, 'params');
        }

        if (
            $paramsString &&
            preg_match_all(self::RULE_CATCH_EACH_PARAM, $paramsString, $matches)
        ) {
            $params = array_get($matches, 'params', []);
        }

        return [$functionName, $params];
    }

    private function setParamsTypeForFunction(array $params)
    {
        foreach ($params as &$param) {
            switch (true) {
                case preg_match('/^".+"$/', trim($param)):
                    $param = (string)substr($param, 1, -1);
                    break;
                case preg_match('/^\d+$/', trim($param)):
                    $param = (int)$param;
                    break;
                case preg_match('/^\d+\.\d+$/', trim($param)):
                    $param = (float)$param;
                    break;
            }
        }

        return $params;
    }

    private function searchResultFromExecutedFunctions($function, Set $results)
    {
        foreach ($results as $result) {
            if (array_has($result, $function)) {
                return $result[$function];
            }
        }
    }
}
