<?php

namespace SearchParser\Pipelines;

use SearchParser\SearchParser;
use Avris\Bag\Bag;
use Closure;

class PaginationPipeline extends AbstractPipeline
{
    public function handle(SearchParser $parser, Bag $payload, Closure $next)
    {
        list($start, $rows) = $this->getRequestParams($parser);
        $builder = $parser->getBuilder();
        $builder->take((int)$rows);
        $builder->skip((int)$start);

        return $next($parser, $payload);
    }

    protected function getRequestParams(SearchParser $parser)
    {
        $request = $parser->getRequest();
        $start = $request->get(getConfig('start'), 0);
        $rows = $request->get(
            getConfig('rows'),
            getConfig('pageSize', 20)
        );

        return [$start, $rows];
    }
}
