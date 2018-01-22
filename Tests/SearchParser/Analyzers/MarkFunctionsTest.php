<?php

namespace Tests\SearchParser\Analyzers;

use Avris\Bag\Bag;
use Closure;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExpAnalyzer;
use SearchParser\Pipelines\ExpPipeline\Expression;
use Tests\SearchParser\AbstractTestCase;

class MarkFunctionsTest extends AbstractTestCase
{
    const FUNC_START_MARK = ExpAnalyzer::FUNC_START_MARK;
    const FUNC_END_MARK = ExpAnalyzer::FUNC_END_MARK;

    public function expProvider()
    {
        return [
            [
                'id<=date2(date3())',
                [
                    'id<=date2(date3())' => [
                        4  => self::FUNC_START_MARK,
                        10 => self::FUNC_START_MARK,
                        16 => self::FUNC_END_MARK,
                        17 => self::FUNC_END_MARK,
                    ]
                ]
            ],
            [
                'id<=date2(date3(1))',
                [
                    'id<=date2(date3(1))' => [
                        4  => self::FUNC_START_MARK,
                        10 => self::FUNC_START_MARK,
                        17 => self::FUNC_END_MARK,
                        18 => self::FUNC_END_MARK,
                    ]
                ]
            ],
            [
                'id<=date2(date3(),date4())',
                [
                    'id<=date2(date3(),date4())' => [
                        4  => self::FUNC_START_MARK,
                        10 => self::FUNC_START_MARK,
                        16 => self::FUNC_END_MARK,
                        18 => self::FUNC_START_MARK,
                        24 => self::FUNC_END_MARK,
                        25 => self::FUNC_END_MARK,
                    ]
                ]
            ],

            [
                'id<=date2(date3(1),date4("A"))',
                [
                    'id<=date2(date3(1),date4("A"))' => [
                        4  => self::FUNC_START_MARK,
                        10 => self::FUNC_START_MARK,
                        17 => self::FUNC_END_MARK,
                        19 => self::FUNC_START_MARK,
                        28 => self::FUNC_END_MARK,
                        29 => self::FUNC_END_MARK,
                    ]
                ]
            ],

            [
                'id<=date2(date3(1,2,3),date4(1,2,"A"))',
                [
                    'id<=date2(date3(1,2,3),date4(1,2,"A"))' => [
                        4  => self::FUNC_START_MARK,
                        10 => self::FUNC_START_MARK,
                        21 => self::FUNC_END_MARK,
                        23 => self::FUNC_START_MARK,
                        36 => self::FUNC_END_MARK,
                        37 => self::FUNC_END_MARK,
                    ]
                ]
            ],

            [
                'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))',
                [
                    'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))' => [
                        4  => self::FUNC_START_MARK,
                        16 => self::FUNC_START_MARK,
                        27 => self::FUNC_END_MARK,
                        29 => self::FUNC_START_MARK,
                        42 => self::FUNC_END_MARK,
                        43 => self::FUNC_END_MARK,
                    ]
                ]
            ],
            [
                'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A")) OR (date5() AND date6(date7()))',
                [
                    'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))' => [
                        4  => self::FUNC_START_MARK,
                        16 => self::FUNC_START_MARK,
                        27 => self::FUNC_END_MARK,
                        29 => self::FUNC_START_MARK,
                        42 => self::FUNC_END_MARK,
                        43 => self::FUNC_END_MARK,
                    ],
                    'OR (date5() AND date6(date7()))'              => [] // 子语句 analyzer 在另外一个 parser 中处理
                ]
            ],
            [
                'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A")) OR id:date5() AND id:date6(date7())',
                [
                    'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))' => [
                        4  => self::FUNC_START_MARK,
                        16 => self::FUNC_START_MARK,
                        27 => self::FUNC_END_MARK,
                        29 => self::FUNC_START_MARK,
                        42 => self::FUNC_END_MARK,
                        43 => self::FUNC_END_MARK,
                    ],
                    'OR id:date5()'                                => [
                        51 => self::FUNC_START_MARK,
                        57 => self::FUNC_END_MARK,
                    ],
                    'AND id:date6(date7())'                        => [
                        66 => self::FUNC_START_MARK,
                        72 => self::FUNC_START_MARK,
                        78 => self::FUNC_END_MARK,
                        79 => self::FUNC_END_MARK,
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider expProvider
     * @param $exp
     * @param $exceptedResult
     */
    public function testAnalyzeResults($exp, $exceptedResult)
    {
        $exp = new Expression($exp);
        $ast = new Bag();
        $callback = Closure::bind(function (Expression $exp, Bag $ast) use ($exceptedResult) {
            $this->assertEquals($exceptedResult, $ast->all());
        }, $this, $this);
        $analyzer = new ExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }
}
