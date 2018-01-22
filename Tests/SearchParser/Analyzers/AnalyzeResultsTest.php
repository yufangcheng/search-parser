<?php

namespace Tests\SearchParser\Analyzers;

use Avris\Bag\Bag;
use Closure;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExpAnalyzer;
use SearchParser\Pipelines\ExpPipeline\Expression;
use Tests\SearchParser\AbstractTestCase;

class AnalyzeResultsTest extends AbstractTestCase
{
    public function expProvider()
    {
        return [
            [
                ' (id:1 OR id:2)',
                [
                    '(id:1 OR id:2)'
                ]
            ], [
                '((id:NOT 1 OR id:2))',
                [
                    '((id:NOT 1 OR id:2))'
                ]
            ], [
                '((id:1) OR id:NOT 2)',
                [
                    '((id:1) OR id:NOT 2)'
                ]
            ], [
                '(name:"(mike" OR id:2)',
                [
                    '(name:"(mike" OR id:2)'
                ]
            ], [
                '(name:"mike)" OR id:2)',
                [
                    '(name:"mike)" OR id:2)'
                ]
            ], [
                'name:"a\\"bc"',
                [
                    'name:"a\\"bc"'
                ]
            ], [
                'name:NOT "a\\"bc"',
                [
                    'name:NOT "a\\"bc"'
                ]
            ], [
                'age:28',
                [
                    'age:28'
                ]
            ], [
                'age:NOT 28',
                [
                    'age:NOT 28'
                ]
            ], [
                'email~"*@qq.com"',
                [
                    'email~"*@qq.com"'
                ]
            ], [
                'email~NOT "*@qq.com"',
                [
                    'email~NOT "*@qq.com"'
                ]
            ], [
                'id>1',
                [
                    'id>1'
                ]
            ], [
                'id>NOT 1',
                [
                    'id>NOT 1'
                ]
            ], [
                'id>=1',
                [
                    'id>=1'
                ]
            ], [
                'id>=NOT 1',
                [
                    'id>=NOT 1'
                ]
            ], [
                'id < 1',
                [
                    'id < 1'
                ]
            ], [
                'id  <  NOT  1',
                [
                    'id  <  NOT  1'
                ]
            ], [
                'id    <=    1',
                [
                    'id    <=    1'
                ]
            ], [
                'id     <=     NOT     1',
                [
                    'id     <=     NOT     1'
                ]
            ], [
                'id:|1,2,3',
                [
                    'id:|1,2,3'
                ]
            ], [
                'price:1.00',
                [
                    'price:1.00'
                ]
            ], [
                'price:-1.1',
                [
                    'price:-1.1'
                ]
            ], [
                'price:-1.00',
                [
                    'price:-1.00'
                ]
            ], [
                '  id>90 AND id<95 OR id:|90,92,94 OR (age>=20 AND age<=30) AND id: NOT 93 AND email~"*@163.com"   OR   (name:"hjr")  ',
                [
                    'id>90',
                    'AND id<95',
                    'OR id:|90,92,94',
                    'OR (age>=20 AND age<=30)',
                    'AND id: NOT 93',
                    'AND email~"*@163.com"',
                    'OR   (name:"hjr")'
                ]
            ],
            [
                'dynamic_fields:{"1","2"',
                [
                    'dynamic_fields:{"1","2"'
                ]
            ],
            [
                'dynamic_fields:{"1",2',
                [
                    'dynamic_fields:{"1",2'
                ]
            ],
            [
                'dynamic_fields:{1',
                [
                    'dynamic_fields:{1'
                ]
            ],
            [
                'id<=date2()',
                [
                    'id<=date2()'
                ]
            ],
            [
                'id<=date2(1)',
                [
                    'id<=date2(1)'
                ]
            ],
            [
                'id<=date2(1,2)',
                [
                    'id<=date2(1,2)'
                ]
            ],
            [
                'id<=date2("ABC")',
                [
                    'id<=date2("ABC")'
                ]
            ],
            [
                'id<=date2("ABC","DEF")',
                [
                    'id<=date2("ABC","DEF")'
                ]
            ],
            [
                'id<=date2(date3())',
                [
                    'id<=date2(date3())'
                ]
            ],
            [
                'id<=date2(date3(1))',
                [
                    'id<=date2(date3(1))'
                ]
            ],
            [
                'id<=date2(date3(),date4())',
                [
                    'id<=date2(date3(),date4())'
                ]
            ],
            [
                'id<=date2(date3(1),date4("A"))',
                [
                    'id<=date2(date3(1),date4("A"))'
                ]
            ],
            [
                'id<=date2(date3(1,2,3),date4(1,2,"A"))',
                [
                    'id<=date2(date3(1,2,3),date4(1,2,"A"))'
                ]
            ],
            [
                'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))',
                [
                    'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))'
                ]
            ],
            [
                'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A")) OR (name:"zhangsan" OR age:20) OR id:3',
                [
                    'id<=date2(1,"A",date3(1,2,3),date4(1,2,"A"))',
                    'OR (name:"zhangsan" OR age:20)',
                    'OR id:3'
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
            $this->assertEquals($exceptedResult, $ast->keys());
        }, $this, $this);
        $analyzer = new ExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }
}
