<?php

namespace SearchParser;

use Avris\Bag\Bag;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inno\Lib\SearchParser\Pipelines;
use Inno\Lib\SearchParser\Pipelines\AbstractPipeline;
use Inno\Lib\SearchParser\Exceptions;

class SearchParser
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var Request
     */
    protected $request;

    protected $pipelines = [
        Pipelines\FilterPipeline::class,
        Pipelines\OrderPipeline::class,
        Pipelines\ExpPipeline\ExpPipeline::class,
        Pipelines\PaginationPipeline::class,
        Pipelines\WithPipeline::class
    ];

    /**
     * SearchParser constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function parse(Builder $builder)
    {
        $this->builder = $builder;

        $pipeline = array_reduce(
            array_reverse((array)$this->pipelines),
            $this->pipelinesFrame(),
            $this->afterPipelinesProcessed()
        );

        return $pipeline($this, $payload = new Bag());
    }

    /**
     * 管道调用帧
     *
     * @return Closure
     */
    protected function pipelinesFrame()
    {
        return function ($prevCarry, $pipeline) {
            return function (SearchParser $parser, Bag $payload) use ($prevCarry, $pipeline) {
                if ($pipeline instanceof Closure) {
                    return $pipeline($parser, $payload, $prevCarry);
                }

                if (is_string($pipeline)) {
                    $pipeline = new $pipeline;
                }

                if ($pipeline instanceof AbstractPipeLine) {
                    return $pipeline->handle($parser, $payload, $prevCarry);
                }

                throw new Exceptions\SearchParserInvalidPipelineException(sprintf(
                    'Failed to invoke the pipeline with an invalid name or object.'
                ));
            };
        };
    }

    /**
     * 所有管道执行完毕后执行此最后步骤
     *
     * @return Closure
     */
    protected function afterPipelinesProcessed()
    {
        return function () {
            return $this->getBuilder();
        };
    }

    public function getBuilder()
    {
        return $this->builder;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
