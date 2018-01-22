<?php

namespace SearchParser\Pipelines\ExpPipeline\ExpAnalyzers;

use Avris\Bag\Bag;
use Avris\Bag\Set;
use Closure;
use SearchParser\Pipelines\ExpPipeline\Expression;
use SearchParser\Pipelines\ExpPipeline\Functions;

class ExecuteFunctionsAnalyzer extends AbstractAnalyzer
{
    protected $functionManager;

    public function analyze(Expression $exp, Bag $ast, Closure $next)
    {
        $cases = new Set;

        foreach ($ast as $atomicExp => $functionMarks) {
            $cases->add($this->execute($exp, $atomicExp, $functionMarks));
        }

        return $next($exp, $cases);
    }

    protected function getFunctionManager()
    {
        if (!$this->functionManager) {
            $this->functionManager = new Functions();
        }

        return $this->functionManager;
    }

    private function execute(Expression $exp, $atomicExp, array $functionMarks)
    {

        $functions = $this->buildFunctionsInvokeStack($functionMarks);
        $frame = array_reduce(
            $functions,
            $this->functionsFrame(),
            $this->afterFunctionsExecuted($atomicExp)
        );

        return $frame($exp, $results = new Set());
    }

    private function buildFunctionsInvokeStack(array $functionMarks)
    {
        $functions = [];
        $stack = [];
        $depth = 0;

        /**
         * 识别层级，生成 ['5,6'， ['1,2', '3,4']] 这样的数据
         * 然后利用管道生成 1,2 和 3,4 范围内函数的结果，按序代入 5,6 范围内的函数
         */
        foreach ($functionMarks as $offset => $mark) {
            if ($mark === ExpAnalyzer::FUNC_END_MARK) {
                $prevMark = array_pop($stack);

                if (!isset($functions[$depth]) || !is_array($functions[$depth])) {
                    $functions[$depth] = [];
                }

                $functions[$depth][] = [key($prevMark), $offset];
                --$depth;
            } else {
                $stack[] = [$offset => $mark];
                ++$depth;
            }
        }

        ksort($functions);

        return $functions;
    }

    private function functionsFrame()
    {
        return function ($prevCarry, $mark) {
            return function (Expression $exp, Set $results) use ($prevCarry, $mark) {
                return $this->getFunctionManager()->call($exp, $mark, $results, $prevCarry);
            };
        };
    }


    private function afterFunctionsExecuted($atomicExp)
    {
        return function (Expression $exp, Set $results) use ($atomicExp) {
            foreach ($results as $result) {
                $atomicExp = Functions::takeFunctionResultsPlaces($atomicExp, $result);
            }

            return $atomicExp;
        };
    }
}
