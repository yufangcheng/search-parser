<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use Illuminate\Database\Eloquent\Builder;
use Closure;
use Illuminate\Http\Request;
use Inno\Lib\SearchParser\SearchParser;
use Avris\Bag\Bag;
use Inno\Lib\SearchParser\Exceptions;

class ScopeCase implements CaseInterface
{
    protected $value;

    /**
     * @var SearchParser $parser
     */
    protected $parser;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getMiddlewares()
    {
        return [function (Builder $builder, CaseInterface $case, Closure $next) {
            $request = Request::capture();
            $request[getConfig('advancedSearch')] = $this->value;
            $this->parser = new SearchParser($request);

            return $next($builder, $case);
        }];
    }

    public function getTable($builder)
    {
        return null;
    }

    public function getSupportedValueTypes()
    {
        throw new Exceptions\SearchParserInvalidValueTypeException(
            "Scope case doesn't support any value types."
        );
    }

    public function assemble(Builder $builder, Bag $payload)
    {
        $this->parser->parse($builder);
    }
}
