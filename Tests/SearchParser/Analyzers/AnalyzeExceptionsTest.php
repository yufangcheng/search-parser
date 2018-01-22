<?php

namespace Tests\SearchParser\Analyzers;

use Tests\SearchParser\AbstractTestCase;
use SearchParser\Pipelines\ExpPipeline\Expression;
use Avris\Bag\Set;
use Closure;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExpAnalyzer;

class AnalyzeExceptionsTest extends AbstractTestCase
{
    /**
     * @expectedException \SearchParser\Exceptions\Analyze\AnalyzeMissingStateException
     */
    public function testSearchParserAnalyzeMissingStateException()
    {
        $analyzer = new ExpAnalyzer;

        call_user_func(Closure::bind(function () {
            $stateValues = array_keys($this->getAllStates());
            $invalidStateValue = array_sum($stateValues);
            $this->getStateProcessName($invalidStateValue);
        }, $analyzer, $analyzer));
    }

    /**
     * @expectedException \SearchParser\Exceptions\Analyze\AnalyzeMissingProcessException
     */
    public function testSearchParserAnalyzeMissingProcessException()
    {
        $analyzer = new ExpAnalyzer;

        call_user_func(Closure::bind(function () {
            $invalidProcess = md5(time());
            $exp = new Expression('id:1');
            $this->invokeProcess($invalidProcess, $exp);
        }, $analyzer, $analyzer));
    }

    public function invalidKeyExpProvider()
    {
        return [
            [':"mike"'],
            ['123:"321"']
        ];
    }

    /**
     * @dataProvider invalidKeyExpProvider
     * @expectedException \SearchParser\Exceptions\Analyze\AnalyzeInvalidKeyException
     * @param $invalidKeyExp
     */
    public function testSearchParserAnalyzeInvalidKeyException($invalidKeyExp)
    {
        $exp = new Expression($invalidKeyExp);
        $ast = new Set();
        $callback = function (Expression $exp, Set $ast) {
            // do nothing
        };
        $analyzer = new ExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }

    public function invalidValueExpProvider()
    {
        return [
            ['(id:1)AND'],
            ['id:null'],
            ['id:NOT3'],
            ['id:3a'],
            ['name:"mike"a'],
        ];
    }

    /**
     * @dataProvider invalidValueExpProvider
     * @expectedException \SearchParser\Exceptions\Analyze\AnalyzeInvalidValueException
     * @param $invalidValueExp
     */
    public function testSearchParserAnalyzeInvalidValueException($invalidValueExp)
    {
        $exp = new Expression($invalidValueExp);
        $ast = new Set();
        $callback = function (Expression $exp, Set $ast) {
            // do nothing
        };
        $analyzer = new ExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }

    public function invalidRelationExpProvider()
    {
        return [
            ['id:1 RELATION'],
            ['id:1 AND1'],
        ];
    }

    /**
     * @dataProvider invalidRelationExpProvider
     * @expectedException \SearchParser\Exceptions\Analyze\AnalyzeInvalidRelationException
     * @param $invalidRelationExp
     */
    public function testSearchParserAnalyzeInvalidRelationException($invalidRelationExp)
    {
        $exp = new Expression($invalidRelationExp);
        $ast = new Set();
        $callback = function (Expression $exp, Set $ast) {
            // do nothing
        };
        $analyzer = new ExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }
}
