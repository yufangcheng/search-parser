<?php

namespace SearchParser\Pipelines\ExpPipeline;

use Avris\Bag\Set;
use Inno\Lib\SearchParser\SearchParser;
use Inno\Lib\SearchParser\Pipelines\AbstractPipeline;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\ExpAnalyzers;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Resolvers;
use Avris\Bag\Bag;
use Closure;
use Inno\Lib\SearchParser\Exceptions;

class ExpPipeline extends AbstractPipeline
{
    /**
     * @var ExpQueryBuilder
     */
    protected $expQueryBuilder;

    protected $analyzers = [
        ExpAnalyzers\ConvertToSimpleExpAnalyzer::class,
        ExpAnalyzers\ExpAnalyzer::class
    ];

    protected $resolvers = [
        Resolvers\EqualResolver::class,
        Resolvers\SetResolver::class,
        Resolvers\ContainResolver::class,
        Resolvers\WildcardResolver::class,
        Resolvers\ScopeResolver::class,
        Resolvers\GreatEqualResolver::class,
        Resolvers\LowerEqualResolver::class,
        Resolvers\GreatResolver::class,
        Resolvers\LowerResolver::class
    ];

    public function handle(SearchParser $parser, Bag $payload, Closure $next)
    {
        $this->expQueryBuilder = $this->makeExpQueryBuilder($parser);
        /**
         * @var Expression $exp
         */
        $exp = $this->getRequestParams($parser);

        if ($exp instanceof Expression) {
            $this->parseExp($exp, $payload);
            $this->expQueryBuilder->buildQuery();
        }

        return $next($parser, $payload);
    }

    protected function getRequestParams(SearchParser $parser)
    {
        $request = $parser->getRequest();

        /**
         * 为了避免因浏览器对 URL 的长度限制导致 URL 被错误的截断
         * 优先从 header 头的 search 中取 q 表达式
         */
        $exp = $request->header('search');

        if (!$exp || checkIsBlank($exp)) {
            $exp = $request->get(getConfig('advancedSearch'), null);
        }

        if (is_string($exp) && !checkIsBlank($exp)) {
            return new Expression(trim($exp));
        }

        return null;
    }

    protected function parseExp(Expression $exp, Bag $payload)
    {
        $analyzer = array_reduce(
            array_reverse((array)$this->analyzers),
            $this->analyzersFrame(),
            $this->afterAnalyzersProcessed($payload)
        );

        return $analyzer($exp, $ast = new Set());
    }

    protected function analyzersFrame()
    {
        return function ($prevCarry, $analyzer) {
            return function (Expression $exp, Set $ast) use ($prevCarry, $analyzer) {
                if ($analyzer instanceof Closure) {
                    return $analyzer($exp, $ast, $prevCarry);
                }

                if (is_string($analyzer)) {
                    $analyzer = new $analyzer;
                }

                if ($analyzer instanceof ExpAnalyzers\AbstractAnalyzer) {
                    return $analyzer->analyze($exp, $ast, $prevCarry);
                }

                throw new Exceptions\SearchParserInvalidAnalyzerException(
                    'Failed to invoke the analyzer with a invalid name or object.'
                );
            };
        };
    }

    protected function afterAnalyzersProcessed(Bag $payload)
    {
        return function (Expression $exp, Set $ast) use ($payload) {
            foreach ($ast as $exp) {
                $resolverClass = $this->getAvailableResolver($exp);
                $resolver = $this->registerResolverToExpQueryBuilder($resolverClass, [
                    new Expression(trim($exp)), $payload
                ]);

                if ($resolver instanceof Resolvers\AbstractResolver) {
                    $resolver->resolve();
                }
            }
        };
    }

    protected function getAvailableResolver($exp)
    {
        try {
            foreach ($this->resolvers as $resolver) {
                if (forward_static_call([$resolver, 'checkIsExpAvailable'], $exp)) {
                    return $resolver;
                }
            }
        } catch (\Exception $e) {
            /**
             * 任何异常或者不做处理，任由下面抛错
             * 没有匹配到任何适用的 resolver 也将执行下面的抛错
             */
        }

        throw new Exceptions\SearchParserInvalidResolverException(sprintf(
            "There's no available resolvers for expression %s.",
            $exp
        ));
    }

    protected function registerResolverToExpQueryBuilder($resolverClass, array $params)
    {
        return call_user_func_array([$this->expQueryBuilder, $resolverClass], $params);
    }

    /**
     * 初始化一个 ExpQueryBuilder 对象
     *
     * @param SearchParser $parser
     * @return ExpQueryBuilder
     */
    protected function makeExpQueryBuilder(SearchParser $parser)
    {
        return $this->expQueryBuilder = new ExpQueryBuilder(
            $parser->getBuilder(),
            $parser->getRequest()
        );
    }
}
