<?php

namespace Tests\SearchParser;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Inno\Lib\SearchParser\SearchParser;
use Avris\Bag\Bag;
use Closure;

class SearchParserTest extends AbstractTestCase
{
    protected $parser;

    protected $request;

    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = new SearchParser($this->request);
    }

    public function testGetRequest()
    {
        $result = $this->parser->getRequest();
        $this->assertEquals($this->request, $result);
        $this->assertInstanceOf(Request::class, $result);
    }

    public function testGetBuilder()
    {
        $result = $this->parser->getBuilder();
        $this->assertNull($result);

        call_user_func(Closure::bind(function ($builder) {
            $this->builder = $builder;
        }, $this->parser, $this->parser), $builder = $this->getMockQueryBuilder());
        $result = $this->parser->getBuilder();
        $this->assertEquals($builder, $result);
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testFinalReturnIsABuilder()
    {
        //$this->markTestIncomplete();

        $mockBuilder = $this->getMockQueryBuilder();
        $return = $this->parser->parse($mockBuilder);

        $this->assertEquals($mockBuilder, $return);
        $this->assertInstanceOf(Builder::class, $mockBuilder);
    }

    protected function getMockPipeline($pipeline, $prevPipeline)
    {
        $mockPipeline = $this->getMockBuilder($pipeline)
            ->setMethods(['handle'])
            ->getMock();
        $mockPipeline->expects($this->once())
            ->method('handle')
            ->with(
                $this->isInstanceOf(SearchParser::class),
                $this->isInstanceOf(Bag::class),
                $this->isInstanceOf(Closure::class)
            )->will($this->returnValue($prevPipeline()));

        return $mockPipeline;
    }

    protected function getMockQueryBuilder()
    {
        $mockQueryBuilder = $this->getMockBuilder(\Illuminate\Database\Eloquent\Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mockQueryBuilder;
    }
}
