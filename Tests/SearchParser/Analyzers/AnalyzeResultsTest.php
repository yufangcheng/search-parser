<?php

namespace Tests\SearchParser\Analyzers;

use Inno\Lib\Tests\SearchParser\AbstractTestCase;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Expression;
use Avris\Bag\Set;
use Closure;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ExpAnalyzer;

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
        $ast = new Set();
        $callback = Closure::bind(function (Expression $exp, Set $ast) use ($exceptedResult) {
            $this->assertEquals($exceptedResult, $ast->all());
        }, $this, $this);
        $analyzer = new ExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }
}
