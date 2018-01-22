<?php

namespace SearchParser\Pipelines\ExpPipeline\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use SearchParser\Pipelines\ExpPipeline\Expression;
use SearchParser\Pipelines\ExpPipeline\Cases\Contracts\CaseInterface;
use SearchParser\Pipelines\ExpPipeline\Middlewares\AbstractMiddleware;
use Closure;
use Avris\Bag\Bag;
use SearchParser\Exceptions;

abstract class AbstractResolver
{
    /**
     * 下一个 Resolver
     *
     * @var AbstractResolver $next
     */
    protected $next;

    /**
     * 当前 Resolver 范围内的表达式
     *
     * @var string $exp
     */
    protected $exp;

    /**
     * 分析表达式并捕获初始化对应 Case 所需要的值
     *
     * @var string $resolverReg
     */
    protected static $resolverReg = '/.*/';

    /**
     * 当前 Resolver 的关系
     *
     * @var string $resolverRelation
     */
    protected $resolverRelation;

    /**
     * 当前 Resolver 使用 $resolverReg 成员保存的正则表达式分析过后的数据
     *
     * @var array $resolvedData
     */
    protected $resolvedData;

    /**
     * @var mixed $caseInstance
     */
    protected $caseInstances;

    /**
     * 用于传递数据的荷载对象
     *
     * @var Bag
     */
    protected $payload;

    /**
     * AbstractResolver constructor.
     *
     * @param Expression $exp
     * @param Bag $payload
     */
    public function __construct(Expression $exp, Bag $payload)
    {
        $this->exp = $exp;
        $this->payload = $payload;
    }

    /**
     * 获取下一个 Resolver
     *
     * @return AbstractResolver
     */
    public function getNextResolver()
    {
        return $this->next;
    }

    /**
     * 设置下一个 Resolver
     *
     * @param AbstractResolver $resolver
     * @return AbstractResolver
     */
    public function setNextResolver(AbstractResolver $resolver)
    {
        return $this->next = $resolver;
    }

    /**
     * 使用当前 Resolver 的关系建立一个 Laravel 的 Scope 查询
     * 在回调函数中执行中间件，拼装查询条件等的处理
     *
     * @param Builder $builder
     */
    public function assemble(Builder $builder)
    {
        $caseInstances = $this->caseInstances;

        if (!is_array($caseInstances)) {
            $caseInstances = [$caseInstances];
        }

        if ($this->resolverRelation == 'OR') {
            $builder->orWhere(
                $this->assembleScopeCarry($caseInstances)
            );
        } else {
            $builder->where(
                $this->assembleScopeCarry($caseInstances)
            );
        }
    }

    /**
     * 经过中间件处理后调用 Case 的拼装功能
     * 拼装 Laravel 数据模型的查询条件
     *
     * @param array $caseInstances
     * @return Closure
     */
    protected function assembleScopeCarry(array $caseInstances)
    {
        return function (Builder $builder) use ($caseInstances) {
            foreach ($caseInstances as $caseInstance) {
                if (!($caseInstance instanceof CaseInterface)) {
                    throw new Exceptions\InvalidCaseException(sprintf(
                        'Invalid case instance of %s resolver.',
                        __CLASS__
                    ));
                }

                $middlewares = $caseInstance->getMiddlewares();
                $pipeline = array_reduce(
                    array_reverse((array)$middlewares),
                    $this->middlewaresFrame(),
                    $this->prepareAssembly($this->payload)
                );
                $pipeline($builder, $caseInstance);
            }
        };
    }

    /**
     * 中间件调用帧
     *
     * @return Closure
     */
    protected function middlewaresFrame()
    {
        return function ($prevCarry, $middleware) {
            return function (Builder $builder, CaseInterface $caseInstance) use (
                $prevCarry, $middleware
            ) {
                if ($middleware instanceof Closure) {
                    return $middleware($builder, $caseInstance, $prevCarry);
                }

                if (is_string($middleware)) {
                    $middleware = new $middleware;
                }

                if ($middleware instanceof AbstractMiddleware) {
                    return $middleware->handle($builder, $caseInstance, $prevCarry);
                }

                throw new Exceptions\InvalidMiddlewareException(sprintf(
                    'Invalid middleware %s.',
                    $middleware
                ));
            };
        };
    }

    /**
     * 中间件处理完后的步骤
     * 即执行 Case 的拼装
     *
     * @param $payload
     * @return Closure
     */
    protected function prepareAssembly(Bag $payload)
    {
        return function (Builder $builder, CaseInterface $caseInstance) use ($payload) {
            $caseInstance->assemble($builder, $payload);
        };
    }

    /**
     * 分析查询表达式
     *
     * @throws Exceptions\InvalidResolverException
     * @throws Exceptions\ResolveFailedException
     */
    public function resolve()
    {
        $this->resolvedData = $this->getResolvedDataFromExp();
        $this->resolverRelation = $this->getRelationFromResolvedData();
        $this->caseInstances = $this->getCaseInstance($this->resolvedData);
    }

    /**
     * 使用当前 Resolver 的 $resolverReg 成员保存的正则表达式分析当前 Resolver 范围的表达式
     * 返回正则表达式捕获到的数据
     *
     * @return array
     */
    protected function getResolvedDataFromExp()
    {
        $matches = [];
        $exp = $this->exp->getString();

        if (!preg_match(static::$resolverReg, $exp, $matches)) {
            throw new Exceptions\ResolveFailedException(sprintf(
                'Failed to initialize a case for %s resolver with invalid case string %s.',
                __CLASS__,
                $exp
            ));
        }

        return $matches;
    }

    /**
     * 从分析好的数据中获取 Resolver 的关系
     *
     * @return string
     */
    protected function getRelationFromResolvedData()
    {
        array_shift($this->resolvedData); // 我们不需要匹配到整个字符串

        return strtoupper(array_shift($this->resolvedData)) !== 'OR' ? 'AND' : 'OR';
    }

    /**
     * 获取当前 Resolver 对应的 Case 实例对象
     *
     * @param array $resolvedData
     * @return mixed
     * @throws Exceptions\InitializeCaseFailed
     */
    protected function getCaseInstance($resolvedData)
    {
        $caseClasses = $this->getCaseClasses();

        if ($caseClasses instanceof CaseInterface) {
            return $caseClasses;
        }

        if ($caseClasses instanceof Closure) {
            return call_user_func_array($caseClasses, $resolvedData);
        }

        if (is_string($caseClasses)) {
            return $this->initializeCase($caseClasses, $resolvedData);
        }

        if (!is_array($caseClasses)) {
            $caseClasses = [$caseClasses];
        }

        return $this->initializeMultipleCases($caseClasses, $resolvedData);
    }

    /**
     * 初始化多个 Case
     * 例如，闭合区间表达式由 GreatEqualCase 和 LowerEqualCase 组成，需要初始化多个 Case
     *
     * @param array $caseClasses
     * @param array $resolvedData
     * @throws Exceptions\InitializeCaseFailed
     * @return array
     */
    protected function initializeMultipleCases($caseClasses, $resolvedData)
    {
        $caseInstances = [];
        $nonValueParamsCount = count($resolvedData) - count($caseClasses);

        if ($nonValueParamsCount < 0) {
            throw new Exceptions\InitializeCaseFailed(sprintf(
                "The number of cases that supported by the resolver %s exceeds the number of resolved parameters.",
                __CLASS__
            ));
        }

        $nonValueParams = array_slice($resolvedData, 0, $nonValueParamsCount);
        $valueParams = array_slice($resolvedData, $nonValueParamsCount);

        foreach ($caseClasses as $caseClass) {
            if (is_null($caseValue = array_shift($valueParams))) {
                throw new Exceptions\InitializeCaseFailed(sprintf(
                    "Failed to initialize a case with empty value."
                ));
            }

            $initialParams = array_merge($nonValueParams, [$caseValue]);
            $caseInstances[] = $this->initializeCase($caseClass, $initialParams);
        }

        return $caseInstances;
    }

    /**
     * 初始化单个 Case
     *
     * @param string $caseClass
     * @param array $resolvedData
     * @throws Exceptions\InvalidCaseException
     * @return object
     */
    protected function initializeCase($caseClass, $resolvedData)
    {
        if ($caseClass && is_string($caseClass)) {
            $reflector = new \ReflectionClass($caseClass);
        }

        if (!isset($reflector) || !$reflector->isInstantiable()) {
            throw new Exceptions\InvalidCaseException(sprintf(
                "Could not create %s case instance because of %s isn't instantiable.",
                $caseClass
            ));
        }

        if (is_null($constructor = $reflector->getConstructor())) {
            throw new Exceptions\InvalidCaseException(sprintf(
                "Could not create %s case instance because of there's no constructor.",
                $caseClass
            ));
        }

        if (count($resolvedData) < count($constructor->getParameters())) {
            throw new Exceptions\InvalidCaseException(sprintf(
                "Could not create %s case instance because of missing initial parameters.",
                $caseClass
            ));
        }

        return $reflector->newInstanceArgs($resolvedData);
    }

    static public function checkIsExpAvailable($exp)
    {
        return preg_match(static::$resolverReg, $exp);
    }

    /**
     * 获取当前 Resolver 对应的 Case 类名
     * 支持的返回值：
     * 1.Case 类名字符串
     * 2.包含 Case 类名字符串的数组
     * 3.函数
     * 4.Case 类对象
     *
     * @return mixed
     */
    abstract protected function getCaseClasses();
}
