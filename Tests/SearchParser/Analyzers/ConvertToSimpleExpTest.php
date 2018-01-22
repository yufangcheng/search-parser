<?php

namespace Tests\SearchParser\Analyzers;

use Avris\Bag\Bag;
use Closure;
use SearchParser\Pipelines\ExpPipeline\ExpAnalyzers\ConvertToSimpleExpAnalyzer;
use SearchParser\Pipelines\ExpPipeline\Expression;
use Tests\SearchParser\AbstractTestCase;

class ConvertToSimpleExpTest extends AbstractTestCase
{
    public function complexExpProvider()
    {
        return [
            // closed interval
            ['id:[* TO *]', ''],
            ['id:[* TO 1]', 'id<=1'],
            ['id:[* TO "Z"]', 'id<="Z"'],
            ['id:NOT [* TO 1]', 'id>1'],
            ['id:NOT [* TO "Z"]', 'id>"Z"'],
            ['id:[1 TO *]', 'id>=1'],
            ['id:["A" TO *]', 'id>="A"'],
            ['id:NOT [1 TO *]', 'id<1'],
            ['id:NOT ["A" TO *]', 'id<"A"'],
            ['id:[1 TO 10]', 'id>=1 AND id<=10'],
            ['id:[1 TO "Z"]', 'id>=1 AND id<="Z"'],
            ['id:["A" TO 10]', 'id>="A" AND id<=10'],
            ['id:["A" TO "Z"]', 'id>="A" AND id<="Z"'],
            ['id:NOT [1 TO 10]', '(id<1 OR id>10)'],
            ['id:NOT [1 TO "Z"]', '(id<1 OR id>"Z")'],
            ['id:NOT ["A" TO 10]', '(id<"A" OR id>10)'],
            ['id:NOT ["A" TO "Z"]', '(id<"A" OR id>"Z")'],
            // closed interval with function
            ['id:[* TO date2()]', 'id<=date2()'],
            ['id:[* TO date2(1)]', 'id<=date2(1)'],
            ['id:[* TO date2("A")]', 'id<=date2("A")'],
            ['id:NOT [* TO date2()]', 'id>date2()'],
            ['id:NOT [* TO date2(1)]', 'id>date2(1)'],
            ['id:NOT [* TO date2("A")]', 'id>date2("A")'],
            ['id:[date1() TO *]', 'id>=date1()'],
            ['id:[date1(1) TO *]', 'id>=date1(1)'],
            ['id:[date1("A") TO *]', 'id>=date1("A")'],
            ['id:NOT [date1() TO *]', 'id<date1()'],
            ['id:NOT [date1(1) TO *]', 'id<date1(1)'],
            ['id:NOT [date1("A") TO *]', 'id<date1("A")'],
            ['id:[date1() TO date2()]', 'id>=date1() AND id<=date2()'],
            ['id:[date1(1) TO date2("A","B")]', 'id>=date1(1) AND id<=date2("A","B")'],
            ['id:[date1(1,2) TO date2("A")]', 'id>=date1(1,2) AND id<=date2("A")'],
            ['id:NOT [date1() TO date2()]', '(id<date1() OR id>date2())'],
            ['id:NOT [date1("A","B") TO date2(1,2)]', '(id<date1("A","B") OR id>date2(1,2))'],
            ['id:NOT [date1(1,2) TO date2("A","B")]', '(id<date1(1,2) OR id>date2("A","B"))'],
            ['id:1 OR id:NOT [date1(1,2) TO date2("A","B")]', 'id:1 OR (id<date1(1,2) OR id>date2("A","B"))'],
            // left closed right open interval
            ['id:[* TO 10}', 'id<10'],
            ['id:[* TO "Z"}', 'id<"Z"'],
            ['id:NOT [* TO 10}', 'id>=10'],
            ['id:NOT [* TO "Z"}', 'id>="Z"'],
            ['id:[1 TO 10}', 'id>=1 AND id<10'],
            ['id:[1 TO "Z"}', 'id>=1 AND id<"Z"'],
            ['id:["A" TO 10}', 'id>="A" AND id<10'],
            ['id:["A" TO "Z"}', 'id>="A" AND id<"Z"'],
            ['id:NOT [1 TO 10}', '(id<1 OR id>=10)'],
            ['id:NOT [1 TO "Z"}', '(id<1 OR id>="Z")'],
            ['id:NOT ["A" TO 10}', '(id<"A" OR id>=10)'],
            ['id:NOT ["A" TO "Z"}', '(id<"A" OR id>="Z")'],
            // left closed right open interval with function
            ['id:[* TO date2()}', 'id<date2()'],
            ['id:[* TO date2(1)}', 'id<date2(1)'],
            ['id:[* TO date2("A")}', 'id<date2("A")'],
            ['id:NOT [* TO date2()}', 'id>=date2()'],
            ['id:NOT [* TO date2(1)}', 'id>=date2(1)'],
            ['id:NOT [* TO date2("A")}', 'id>=date2("A")'],
            ['id:[date1() TO date2()}', 'id>=date1() AND id<date2()'],
            ['id:[date1(1) TO date2("A","B")}', 'id>=date1(1) AND id<date2("A","B")'],
            ['id:[date1(1,2) TO date2("A")}', 'id>=date1(1,2) AND id<date2("A")'],
            ['id:NOT [date1() TO date2()}', '(id<date1() OR id>=date2())'],
            ['id:NOT [date1("A","B") TO date2(1,2)}', '(id<date1("A","B") OR id>=date2(1,2))'],
            ['id:NOT [date1(1,2) TO date2("A","B")}', '(id<date1(1,2) OR id>=date2("A","B"))'],
            ['id:1 OR id:NOT [date1(1,2) TO date2("A","B")}', 'id:1 OR (id<date1(1,2) OR id>=date2("A","B"))'],
            // open interval
            ['id:{1 TO 10}', 'id>1 AND id<10'],
            ['id:{"A" TO 10}', 'id>"A" AND id<10'],
            ['id:{1 TO "Z"}', 'id>1 AND id<"Z"'],
            ['id:{"A" TO "Z"}', 'id>"A" AND id<"Z"'],
            ['id:NOT {1 TO 10}', '(id<=1 OR id>=10)'],
            ['id:NOT {"A" TO 10}', '(id<="A" OR id>=10)'],
            ['id:NOT {1 TO "Z"}', '(id<=1 OR id>="Z")'],
            ['id:NOT {"A" TO "Z"}', '(id<="A" OR id>="Z")'],
            // open interval with function
            ['id:{date1() TO date2()}', 'id>date1() AND id<date2()'],
            ['id:{date1(1) TO date2("A","B")}', 'id>date1(1) AND id<date2("A","B")'],
            ['id:{date1(1,2) TO date2("A")}', 'id>date1(1,2) AND id<date2("A")'],
            ['id:NOT {date1() TO date2()}', '(id<=date1() OR id>=date2())'],
            ['id:NOT {date1("A","B") TO date2(1,2)}', '(id<=date1("A","B") OR id>=date2(1,2))'],
            ['id:NOT {date1(1,2) TO date2("A","B")}', '(id<=date1(1,2) OR id>=date2("A","B"))'],
            ['id:1 OR id:NOT {date1(1,2) TO date2("A","B")}', 'id:1 OR (id<=date1(1,2) OR id>=date2("A","B"))'],
            // left open right closed interval
            ['id:{1 TO *]', 'id>1'],
            ['id:{"A" TO *]', 'id>"A"'],
            ['id:NOT {1 TO *]', 'id<=1'],
            ['id:NOT {"A" TO *]', 'id<="A"'],
            ['id:{1 TO 10]', 'id>1 AND id<=10'],
            ['id:{"A" TO 10]', 'id>"A" AND id<=10'],
            ['id:{1 TO "Z"]', 'id>1 AND id<="Z"'],
            ['id:{"A" TO "Z"]', 'id>"A" AND id<="Z"'],
            ['id:NOT {1 TO 10]', '(id<=1 OR id>10)'],
            ['id:NOT {"A" TO 10]', '(id<="A" OR id>10)'],
            ['id:NOT {1 TO "Z"]', '(id<=1 OR id>"Z")'],
            ['id:NOT {"A" TO "Z"]', '(id<="A" OR id>"Z")'],
            // left open right closed interval with function
            ['id:{date1() TO *]', 'id>date1()'],
            ['id:{date1(1) TO *]', 'id>date1(1)'],
            ['id:{date1("A") TO *]', 'id>date1("A")'],
            ['id:NOT {date1() TO *]', 'id<=date1()'],
            ['id:NOT {date1(1) TO *]', 'id<=date1(1)'],
            ['id:NOT {date1("A") TO *]', 'id<=date1("A")'],
            ['id:{date1() TO date2()]', 'id>date1() AND id<=date2()'],
            ['id:{date1(1) TO date2("A","B")]', 'id>date1(1) AND id<=date2("A","B")'],
            ['id:{date1(1,2) TO date2("A")]', 'id>date1(1,2) AND id<=date2("A")'],
            ['id:NOT {date1() TO date2()]', '(id<=date1() OR id>date2())'],
            ['id:NOT {date1("A","B") TO date2(1,2)]', '(id<=date1("A","B") OR id>date2(1,2))'],
            ['id:NOT {date1(1,2) TO date2("A","B")]', '(id<=date1(1,2) OR id>date2("A","B"))'],
            ['id:1 OR id:NOT {date1(1,2) TO date2("A","B")]', 'id:1 OR (id<=date1(1,2) OR id>date2("A","B"))'],
            // set
            ['id:<1,2,3>', 'id:|1,2,3'],
            ['id:NOT <1,2,3>', 'id:|NOT 1,2,3'],
            ['id:<1,2,"3">', 'id:|1,2,"3"'],
            ['id:NOT <1,2,"3">', 'id:|NOT 1,2,"3"'],
            ['id:<1>', 'id:1'],
            ['id:NOT <1>', 'id:NOT 1'],
            ['id:NOT <"3">', 'id:NOT "3"'],
            // set with function
            ['id:<1,"2", date()>', 'id:|1,"2",date()'],
            ['id:<1,"2", date(1)>', 'id:|1,"2",date(1)'],
            ['id:<1,"2", date("A")>', 'id:|1,"2",date("A")'],
            ['id:<1,"2", date(1,2,"A")>', 'id:|1,"2",date(1,2,"A")'],
            ['id:NOT <1,"2", date()>', 'id:|NOT 1,"2",date()'],
            ['id:NOT <1,"2", date(1)>', 'id:|NOT 1,"2",date(1)'],
            ['id:NOT <1,"2", date("A")>', 'id:|NOT 1,"2",date("A")'],
            ['id:NOT <1,"2", date(1,2,"A")>', 'id:|NOT 1,"2",date(1,2,"A")'],
            ['id:<date()>', 'id:date()'],
            ['id:<date(1)>', 'id:date(1)'],
            ['id:<date("A")>', 'id:date("A")'],
            ['id:<date(1,2,"A")>', 'id:date(1,2,"A")'],
            ['id:NOT <date()>', 'id:NOT date()'],
            ['id:NOT <date(1)>', 'id:NOT date(1)'],
            ['id:NOT <date("A")>', 'id:NOT date("A")'],
            ['id:NOT <date(1,2,"A")>', 'id:NOT date(1,2,"A")'],
            ['id:1 OR id:NOT <date(1,2,"A")>', 'id:1 OR id:NOT date(1,2,"A")'],
            // json contain
            ['id:|1,2,3|', 'id:{1,2,3'],
            ['id:NOT |1,2,3|', 'id:{NOT 1,2,3'],
            ['id:|1, 2, 3 |', 'id:{1,2,3'],
            ['id:NOT |1, 2, 3 |', 'id:{NOT 1,2,3'],
            ['id:|1|', 'id:{1'],
            ['id:NOT |1|', 'id:{NOT 1'],
            ['id:|1,2,"3"|', 'id:{1,2,"3"'],
            ['id:NOT |1,2,"3"|', 'id:{NOT 1,2,"3"'],
            // json contain with function
            ['id:|1,"2", date()|', 'id:{1,"2",date()'],
            ['id:|1,"2", date(1)|', 'id:{1,"2",date(1)'],
            ['id:|1,"2", date("A")|', 'id:{1,"2",date("A")'],
            ['id:|1,"2", date(1,2,"A")|', 'id:{1,"2",date(1,2,"A")'],
            ['id:NOT |1,"2", date()|', 'id:{NOT 1,"2",date()'],
            ['id:NOT |1,"2", date(1)|', 'id:{NOT 1,"2",date(1)'],
            ['id:NOT |1,"2", date("A")|', 'id:{NOT 1,"2",date("A")'],
            ['id:NOT |1,"2", date(1,2,"A")|', 'id:{NOT 1,"2",date(1,2,"A")'],
            ['id:|date()|', 'id:{date()'],
            ['id:|date(1)|', 'id:{date(1)'],
            ['id:|date("A")|', 'id:{date("A")'],
            ['id:|date(1,2,"A")|', 'id:{date(1,2,"A")'],
            ['id:NOT |date()|', 'id:{NOT date()'],
            ['id:NOT |date(1)|', 'id:{NOT date(1)'],
            ['id:NOT |date("A")|', 'id:{NOT date("A")'],
            ['id:NOT |date(1,2,"A")|', 'id:{NOT date(1,2,"A")'],
            ['id:1 OR id:NOT |date(1,2,"A")|', 'id:1 OR id:{NOT date(1,2,"A")'],
            // like
            ['id~"abc"', 'id:"abc"'],
            ['id~NOT "abc"', 'id:NOT "abc"'],
            ['id~"ab*"', 'id:"ab*"'],
            ['id~NOT "ab*"', 'id:NOT "ab*"'],
            ['id~"ab\*"', 'id:"ab\*"'],
            ['id~NOT "ab\*"', 'id:NOT "ab\*"'],
            // like with function
            ['id~date()', 'id:date()'],
            ['id~NOT date()', 'id:NOT date()'],
            ['id~date(1)', 'id:date(1)'],
            ['id~NOT date(1)', 'id:NOT date(1)'],
            ['id~date("A")', 'id:date("A")'],
            ['id~NOT date("A")', 'id:NOT date("A")'],
            ['id~date(1,2,"A")', 'id:date(1,2,"A")'],
            ['id~NOT date(1,2,"A")', 'id:NOT date(1,2,"A")'],
            ['id:1 OR id~NOT date(1,2,"A")', 'id:1 OR id:NOT date(1,2,"A")'],
            // complex
            [
                '(id:1 OR name:"zh") OR (age:20 OR (id:<1,2,3>)) AND (id:[1 TO 5] OR id:[1 TO 5} OR id:{1 TO 5} OR id:{1 TO 5]) OR org:|1,2| OR id~NOT date(1,2,"A")',
                '(id:1 OR name:"zh") OR (age:20 OR (id:|1,2,3)) AND (id>=1 AND id<=5 OR id>=1 AND id<5 OR id>1 AND id<5 OR id>1 AND id<=5) OR org:{1,2 OR id:NOT date(1,2,"A")'
            ],
        ];
    }

    /**
     * @dataProvider complexExpProvider
     * @param $exp
     * @param $exceptedResult
     */
    public function testAnalyzeResults($exp, $exceptedResult)
    {
        $exp = new Expression($exp);
        $ast = new Bag();
        $callback = Closure::bind(function (Expression $exp) use ($exceptedResult) {
            $this->assertEquals($exceptedResult, $exp->getString());
        }, $this, $this);
        $analyzer = new ConvertToSimpleExpAnalyzer;
        $analyzer->analyze($exp, $ast, $callback);
    }
}
