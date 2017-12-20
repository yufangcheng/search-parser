<?php

namespace SearchParser\Pipelines\ExpPipeline;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Resolvers\AbstractResolver;
use Inno\Lib\SearchParser\Exceptions;

class ExpQueryBuilder
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var AbstractResolver|null
     */
    protected $firstResolver;

    /**
     * @var AbstractResolver|null
     */
    protected $lastResolver;

    public function __construct(Builder $builder, Request $request)
    {
        $this->builder = $builder;
        $this->request = $request;
    }

    /**
     * @param AbstractResolver $resolver
     */
    protected function appendResolver(AbstractResolver $resolver)
    {
        if ($this->lastResolver instanceof AbstractResolver) {
            $this->lastResolver->setNextResolver($resolver);
        } else {
            $this->firstResolver = $resolver;
        }

        $this->lastResolver = $resolver;
    }

    protected function flushResolvers()
    {
        $this->firstResolver = null;
        $this->lastResolver = null;
    }

    /**
     * @return Builder
     */
    public function buildQuery()
    {
        $resolver = $this->firstResolver;

        do {
            if ($resolver instanceof AbstractResolver) {
                $resolver->assemble($this->builder);
            } else {
                break;
            }
        } while ($resolver = $resolver->getNextResolver());

        $this->flushResolvers();

        return $this->builder;
    }

    /**
     * @param $resolver
     * @param $arguments
     * @return AbstractResolver
     */
    public function __call($resolver, $arguments)
    {
        $reflector = new \ReflectionClass($resolver);

        if (!$reflector->isInstantiable()) {
            throw new Exceptions\SearchParserInvalidResolverException(
                sprintf("Could not create %s resolver instance.", $resolver)
            );
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            throw new Exceptions\SearchParserInvalidResolverException(
                sprintf("Could not create %s resolver instance.", $resolver)
            );
        }

        /**
         * @var AbstractResolver $resolver
         */
        $resolver = $reflector->newInstanceArgs($arguments);
        $this->appendResolver($resolver);

        return $resolver;
    }
}
