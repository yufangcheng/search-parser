<?php

namespace Tests\SearchParser\Analyzers;

use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExecuteFunctionsAnalyzer;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExpAnalyzer;
use Tests\SearchParser\AbstractTestCase;

class BuildFunctionsInvokeStackTest extends AbstractTestCase
{
    const FUNC_START_MARK = ExpAnalyzer::FUNC_START_MARK;
    const FUNC_END_MARK = ExpAnalyzer::FUNC_END_MARK;

    public function functionMarksProvider()
    {
        return [
            [
                [
                    4  => self::FUNC_START_MARK,
                    10 => self::FUNC_START_MARK,
                    16 => self::FUNC_END_MARK,
                    17 => self::FUNC_END_MARK,
                ],
                [
                    1 => [
                        [4, 17],
                    ],
                    2 => [
                        [10, 16],
                    ]
                ]
            ],
            [
                [
                    1  => self::FUNC_START_MARK,
                    5  => self::FUNC_START_MARK,
                    10 => self::FUNC_START_MARK,
                    13 => self::FUNC_END_MARK,
                    15 => self::FUNC_END_MARK,
                    20 => self::FUNC_END_MARK,
                ],
                [
                    1 => [
                        [1, 20],
                    ],
                    2 => [
                        [5, 15],
                    ],
                    3 => [
                        [10, 13],
                    ]
                ]
            ],
            [
                [
                    1  => self::FUNC_START_MARK,
                    5  => self::FUNC_START_MARK,
                    10 => self::FUNC_END_MARK,
                    13 => self::FUNC_START_MARK,
                    17 => self::FUNC_END_MARK,
                    20 => self::FUNC_END_MARK,
                ],
                [
                    1 => [
                        [1, 20],
                    ],
                    2 => [
                        [5, 10],
                        [13, 17],
                    ]
                ]
            ],
            [
                [
                    1  => self::FUNC_START_MARK,
                    2  => self::FUNC_START_MARK,
                    3  => self::FUNC_START_MARK,
                    5  => self::FUNC_END_MARK,
                    7  => self::FUNC_START_MARK,
                    9  => self::FUNC_END_MARK,
                    10 => self::FUNC_END_MARK,
                    13 => self::FUNC_START_MARK,
                    17 => self::FUNC_END_MARK,
                    20 => self::FUNC_END_MARK,
                ],
                [
                    1 => [
                        [1, 20],
                    ],
                    2 => [
                        [2, 10],
                        [13, 17],
                    ],
                    3 => [
                        [3, 5],
                        [7, 9],
                    ]
                ]
            ],
            [
                [
                    1  => self::FUNC_START_MARK,
                    2  => self::FUNC_START_MARK,
                    3  => self::FUNC_START_MARK,
                    5  => self::FUNC_END_MARK,
                    7  => self::FUNC_START_MARK,
                    9  => self::FUNC_END_MARK,
                    10 => self::FUNC_END_MARK,
                    13 => self::FUNC_START_MARK,
                    14 => self::FUNC_START_MARK,
                    16 => self::FUNC_END_MARK,
                    21 => self::FUNC_END_MARK,
                    22 => self::FUNC_END_MARK,
                ],
                [
                    1 => [
                        [1, 22],
                    ],
                    2 => [
                        [2, 10],
                        [13, 21],
                    ],
                    3 => [
                        [3, 5],
                        [7, 9],
                        [14, 16],
                    ]
                ]
            ],
            [
                [
                    1  => self::FUNC_START_MARK,
                    2  => self::FUNC_START_MARK,
                    3  => self::FUNC_START_MARK,
                    5  => self::FUNC_END_MARK,
                    7  => self::FUNC_START_MARK,
                    9  => self::FUNC_END_MARK,
                    10 => self::FUNC_END_MARK,
                    13 => self::FUNC_START_MARK,
                    14 => self::FUNC_START_MARK,
                    16 => self::FUNC_END_MARK,
                    18 => self::FUNC_START_MARK,
                    20 => self::FUNC_END_MARK,
                    21 => self::FUNC_END_MARK,
                    22 => self::FUNC_END_MARK,
                ],
                [
                    1 => [
                        [1, 22],
                    ],
                    2 => [
                        [2, 10],
                        [13, 21],
                    ],
                    3 => [
                        [3, 5],
                        [7, 9],
                        [14, 16],
                        [18, 20],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider functionMarksProvider
     * @param $marks
     * @param $exceptedResult
     */
    public function testAnalyzeResults($marks, $exceptedResult)
    {
        $analyzer = new ExecuteFunctionsAnalyzer();
        $method = new \ReflectionMethod($analyzer, 'buildFunctionsInvokeStack');
        $method->setAccessible(true);
        $this->assertEquals($exceptedResult, $method->invoke($analyzer, $marks));
    }
}
