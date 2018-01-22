<?php

namespace Tests\SearchParser\Analyzers;

use Avris\Bag\Bag;
use Avris\Bag\Set;
use Closure;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExecuteFunctionsAnalyzer;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExpAnalyzer;
use SearchParser\Pipelines\ExpPipeline\Expression;
use SearchParser\Pipelines\ExpPipeline\Functions;
use Tests\SearchParser\AbstractTestCase;

class ExecuteFunctionsTest extends AbstractTestCase
{
    const FUNC_START_MARK = ExpAnalyzer::FUNC_START_MARK;
    const FUNC_END_MARK = ExpAnalyzer::FUNC_END_MARK;

    public function functionMarksProvider()
    {
        return [
            [
                [
                    'id:date1(date2(date4(4),date5(5)),date3(date6(6),date7(7)))' => [
                        15 => self::FUNC_START_MARK,
                        22 => self::FUNC_END_MARK,
                        24 => self::FUNC_START_MARK,
                        31 => self::FUNC_END_MARK,
                        40 => self::FUNC_START_MARK,
                        47 => self::FUNC_END_MARK,
                        49 => self::FUNC_START_MARK,
                        56 => self::FUNC_END_MARK,
                    ]
                ],
                ['id:date1(date2("date4","date5"),date3("date6","date7"))']
            ],
            [
                [
                    'id:date1(date2(date4(4),date5(5)),date3(date6(6),date7(7)))' => [
                        9  => self::FUNC_START_MARK,
                        15 => self::FUNC_START_MARK,
                        22 => self::FUNC_END_MARK,
                        24 => self::FUNC_START_MARK,
                        31 => self::FUNC_END_MARK,
                        32 => self::FUNC_END_MARK,
                        34 => self::FUNC_START_MARK,
                        40 => self::FUNC_START_MARK,
                        47 => self::FUNC_END_MARK,
                        49 => self::FUNC_START_MARK,
                        56 => self::FUNC_END_MARK,
                        57 => self::FUNC_END_MARK,
                    ]
                ],
                ['id:date1("date2","date3")']
            ],
            [
                [
                    'id:date1(date2(date4(4),date5(5)),date3(date6(6),date7(7)))' => [
                        3  => self::FUNC_START_MARK,
                        9  => self::FUNC_START_MARK,
                        15 => self::FUNC_START_MARK,
                        22 => self::FUNC_END_MARK,
                        24 => self::FUNC_START_MARK,
                        31 => self::FUNC_END_MARK,
                        32 => self::FUNC_END_MARK,
                        34 => self::FUNC_START_MARK,
                        40 => self::FUNC_START_MARK,
                        47 => self::FUNC_END_MARK,
                        49 => self::FUNC_START_MARK,
                        56 => self::FUNC_END_MARK,
                        57 => self::FUNC_END_MARK,
                        58 => self::FUNC_END_MARK,
                    ]
                ],
                ['id:"date1"']
            ],
        ];
    }

    /**
     * @dataProvider functionMarksProvider
     * @param $data
     * @param $exceptedResult
     */
    public function testAnalyzeResultsStaged($data, $exceptedResult)
    {
        $exp = new Expression(key($data));
        $callback = Closure::bind(function (Expression $exp, Set $ast) use ($exceptedResult) {
            $this->assertEquals($exceptedResult, $ast->all());
        }, $this, $this);
        /**
         * @var Functions $functionManager
         */
        $functionManager = $this->getMockFunctionManager();
        /**
         * @var ExecuteFunctionsAnalyzer $analyzer
         */
        $analyzer = $this->getMockAnalyzer($functionManager);
        /*
        $analyzer = new class($functionManager) extends ExecuteFunctionsAnalyzer
        {
            public function __construct(Functions $functionManager)
            {
                $this->functionManager = $functionManager;
            }

            protected function getFunctionManager()
            {
                return $this->functionManager;
            }
        };
        */
        $analyzer->analyze($exp, new Bag($data), $callback);
    }

    protected function getMockFunctionManager()
    {
        $mock = $this->getMockBuilder(Functions::class)
            ->setMethods(['callCertainFunction'])
            ->getMock();
        $mock->expects($this->any())
            ->method('callCertainFunction')
            ->will($this->returnArgument(0));

        return $mock;
    }

    protected function getMockAnalyzer(Functions $functionManager)
    {
        $mock = $this->getMockBuilder(ExecuteFunctionsAnalyzer::class)
            ->setMethods(['getFunctionManager'])
            ->getMock();
        $mock->expects($this->any())
            ->method('getFunctionManager')
            ->willreturn($functionManager);

        return $mock;
    }
}
