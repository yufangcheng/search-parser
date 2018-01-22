<?php

namespace SearchParser\Pipelines\ExpPipeline\ExpAnalyzers;

use Avris\Bag\Bag;
use Closure;
use SearchParser\Exceptions\Analyze;
use SearchParser\Pipelines\ExpPipeline\Expression;

class ExpAnalyzer extends AbstractAnalyzer
{
    const FUNC_START_MARK = '(';
    const FUNC_END_MARK = ')';

    protected $charStack = [];
    protected $functionMarks = [];
    protected $collectEnabled = false;
    protected $operators = [
        self::OPT_EQUAL,
        self::OPT_IN,
        self::OPT_CONTAIN,
        self::OPT_GREAT,
        self::OPT_GREAT_EQUAL,
        self::OPT_LOWER,
        self::OPT_LOWER_EQUAL,
        self::OPT_LIKE,
    ];
    protected $relations = [
        self::RELATION_AND,
        self::RELATION_OR,
        self::RELATION_TO,
    ];

    public function analyze(Expression $exp, Bag $ast, Closure $next)
    {
        if ($exp->length() === 0) {
            return $next($exp, $ast);
        }

        $this->wrapExpWithNull($exp);
        $state = self::STATE_INITIAL;

        do {
            $state = $this->invokeProcess(
                $this->getStateProcessName($state),
                $exp
            );
            $this->recordChar($exp->current());

            if ($this->collectEnabled || $state === self::STATE_EXP_END) {
                $this->collectEnabled = false;
                $this->collectCharFromRecords($ast);
            }

            $exp->next();

        } while ($exp->valid());

        // 如果遍历完整个字符串状态不是预期的结束状态则抛错

        if ($state !== self::STATE_EXP_END) {
            throw new Analyze\AnalyzeInvalidExpressionException(sprintf(
                'Invalid expression %s.',
                $exp->getString()
            ));
        }

        return $next(new Expression($exp), $ast);
    }

    protected function getStateProcessName($stateValue)
    {
        $states = $this->getAllStates();

        if (!isset($states[$stateValue])) {
            throw new Analyze\AnalyzeMissingStateException(sprintf(
                "Missing state which state value is %s.",
                $stateValue
            ));
        }

        $state = $states[$stateValue];
        $segments = explode('_', $state);
        array_shift($segments);
        $segments[] = 'PROCESS';
        $process = lineToHump(implode('_', $segments));

        return $process;
    }

    protected function getAllStates()
    {
        return array_flip($this->getConfigs('STATE_'));
    }

    protected function wrapExpWithNull(Expression $exp)
    {
        $exp->prepend(null);
        $exp->append(null);
        $exp->rewind();
    }

    protected function invokeProcess($process, Expression $exp)
    {
        if (!method_exists($this, $process)) {
            throw new Analyze\AnalyzeMissingProcessException(sprintf(
                "Process %s is missing in analyzer %s.",
                $process,
                __CLASS__
            ));
        }

        return $this->{$process}($exp);
    }

    protected function recordChar($char)
    {
        if (is_string($char)) {
            $this->charStack[] = $char;
        }
    }

    protected function collectCharFromRecords(Bag $collection)
    {
        if (is_array($this->charStack) && !checkIsBlank($atomicExp = implode('', $this->charStack))) {
            $collection->set(
                trim($atomicExp),
                $this->collectFunctionMarks()
            );
        }

        $this->charStack = [];
    }

    protected function collectFunctionMarks()
    {
        $marks = $this->functionMarks;
        ksort($marks);
        $this->functionMarks = [];

        return $marks;
    }

    /**
     * @param Expression $exp
     * @return int
     */
    protected function initialProcess(Expression $exp)
    {
        $nextChar = $exp->detect(1);

        switch (true) {
            /**
             * 当前状态遇到 ":" 以及 "~" 会认为 key 是空值并抛出异常
             * 只有遇到合法的 key 之后遇到才是正常的
             */
            case in_array($nextChar, $this->operators):
                throw new Analyze\AnalyzeInvalidKeyException(
                    "Invalid empty key name."
                );
            /**
             * 极少数情况第一个遇到的就是个 scope 表达式
             */
            case $nextChar === '(':
                return self::STATE_SCOPE_START;
            /**
             * 其余非空字符就认为是 key 开始了
             */
            case !checkIsBlank($nextChar):
                return self::STATE_KEY_START;
        }

        return self::STATE_INITIAL;
    }

    /**
     * STATE_INITIAL 状态转为 STATE_SCOPE_START 状态的条件限制第一个字符只可能为 "("
     *
     * @param Expression $exp
     * @return int
     */
    protected function scopeStartProcess(Expression $exp)
    {
        static $leftBracketStack = [];
        static $quoteWrapped = null;

        $char = $exp->current();

        if ($char === '"' && !checkIsEscapeChar($exp->getString(), $exp->key())) {
            $quoteWrapped = is_null($quoteWrapped) ?: null;
        }

        if ($quoteWrapped) {
            return self::STATE_SCOPE_START;
        }

        if ($char === '(') {
            $leftBracketStack[] = $char;
        } else if ($char === ')' && array_pop($leftBracketStack) === '(' && empty($leftBracketStack)) {
            $nextChar = $exp->detect(1);
            switch (true) {
                /**
                 * value 可能处于整个表达式的最后
                 * 也就是说当前 value 字符可能就是整个表达式的最后一个字符
                 * 下一个字符是 null 也认为整个表达式结束
                 */
                case $exp->isLastPosition():
                case is_null($nextChar):
                    return self::STATE_EXP_END;
                /**
                 * scope value 结束后紧跟一个空格
                 * 接下来可能会遇到 relation
                 */
                case checkIsBlank($nextChar):
                    $this->collectEnabled = true;

                    return self::STATE_CHECK_RELATION_TYPE;
                /**
                 * 以上条件都不满足则抛出异常
                 */
                default:
                    throw new Analyze\AnalyzeInvalidValueException(
                        "Invalid scope value."
                    );
            }
        }

        return self::STATE_SCOPE_START;
    }

    /**
     * 因为 STATE_INITIAL 状态转为 STATE_KEY_START 状态的条件
     * 决定了 key 的第一个字符不可能为 "(" 、空字符以及操作符
     *
     * @param Expression $exp
     * @return int
     */
    protected function keyStartProcess(Expression $exp)
    {
        static $keyStack = [];

        $keyStack[] = $exp->current();

        /**
         * 判断后面是否紧跟着一个 operator
         * 如果是则检查 operator 类型
         */
        foreach ($this->operators as $operator) {
            if (in_array($exp->detect(strlen($operator)), $this->operators)) {
                $key = trim(implode('', $keyStack));
                $keyStack = [];

                if (!preg_match(self::RULE_KEY_WITH_RELATION, $key)) {
                    throw new Analyze\AnalyzeInvalidKeyException(sprintf(
                        "Invalid key name %s.",
                        $key
                    ));
                }

                return self::STATE_CHECK_OPERATOR_TYPE;
            }
        }

        return self::STATE_KEY_START;
    }

    protected function checkOperatorTypeProcess(Expression $exp)
    {
        static $operatorStack = [];

        $operatorStack[] = $exp->current();
        $unfinishedOperator = implode('', $operatorStack);
        $availableOperators = $this->matchOperators($unfinishedOperator);

        /**
         * 能够匹配到多个操作符，继续匹配
         */
        if (count($availableOperators) > 1) {
            $unfinishedOperator .= $exp->detect(1);

            if (!empty($this->matchOperators($unfinishedOperator))) {
                return self::STATE_CHECK_OPERATOR_TYPE;
            }
        }

        $operatorStack = [];

        if (empty($availableOperators)) {
            throw new Analyze\AnalyzeInvalidOperatorException(sprintf(
                'Invalid operator start with %s.',
                $unfinishedOperator
            ));
        }

        return $this->checkWhetherHasNotProcess($exp);
    }

    private function matchOperators($unfinishedOperator)
    {
        $availableOperators = [];

        foreach ($this->operators as $operator) {
            if (stripos($operator, $unfinishedOperator) !== false) {
                $availableOperators[] = $operator;
            }
        }

        return $availableOperators;
    }

    private function checkWhetherHasNotProcess(Expression $exp)
    {
        $nextChar = $exp->detect(1);
        $assumedNotKeyword = $exp->detect(strlen(self::OPT_NOT));

        switch (true) {
            case strtoupper($assumedNotKeyword) === strtoupper(self::OPT_NOT):
                return self::STATE_NOT_OPERATOR_START;
            case checkIsBlank($nextChar):
                return self::STATE_CHECK_WHETHER_HAS_NOT;
            default:
                return $this->checkValueTypeProcess($exp);
        }
    }

    /**
     * 忽略空字符，根据下一个遇到的非空字符决定将要转成的状态
     * 不识别的非空字符会抛出一个异常
     *
     * @param Expression $exp
     * @return int
     */
    protected function checkValueTypeProcess(Expression $exp)
    {
        static $functionScopeDepth = 0;

        $nextChar = $exp->detect(1);

        switch (true) {
            case is_numeric($nextChar):
            case $nextChar === '-':
                return self::STATE_NUMERIC_VALUE_START;
            case $nextChar === '"':
                return self::STATE_QUOTED_VALUE_START;
            /**
             * 函数命名规则要求第一个字符必须是字母，不区分大小写
             * 如果是函数，则跳转到函数的 value type process
             */
            case preg_match('/[a-z]/i', $nextChar):

                ++$functionScopeDepth;
                // 记录下一个字符是一个函数的起始位置
                $this->markFunctionStart($exp->key());

                return self::STATE_FUNC_VALUE_START;
            case $nextChar === ')' && $functionScopeDepth > 0:

                // 记录下一个字符是一个函数的结束位置
                $this->markFunctionEnd($exp->key());

                if (--$functionScopeDepth > 0) {
                    return self::STATE_CHECK_IS_MULTIPLE_FUNC;
                }

                return self::STATE_CHECK_IS_MULTIPLE_VALUE;
            case checkIsBlank($nextChar):
                return self::STATE_CHECK_VALUE_TYPE;
            default:
                throw new Analyze\AnalyzeInvalidValueException(sprintf(
                    "Invalid value start with %s.",
                    $nextChar
                ));
        }
    }

    private function markFunctionStart($offset)
    {
        $this->functionMarks[$offset] = self::FUNC_START_MARK;
    }

    private function markFunctionEnd($offset)
    {
        $this->functionMarks[$offset] = self::FUNC_END_MARK;
    }

    /**
     * @param Expression $exp
     * @return int
     */
    protected function notOperatorStartProcess(Expression $exp)
    {
        static $operatorStack = [];

        $operatorStack[] = strtoupper($exp->current());

        if (count($operatorStack) === strlen(self::OPT_NOT)) {
            $operatorStack = [];

            if (!checkIsBlank($exp->detect(1))) {
                throw new Analyze\AnalyzeInvalidValueException(
                    "Invalid operator with non-blank followed."
                );
            }

            return self::STATE_CHECK_VALUE_TYPE;
        }

        return self::STATE_NOT_OPERATOR_START;
    }

    /**
     * STATE_CHECK_VALUE_TYPE 状态转为 STATE_NUMERIC_VALUE_START 状态的条件限制第一个字符只可能为数字
     *
     * @param Expression $exp
     * @return int
     */
    protected function numericValueStartProcess(Expression $exp)
    {
        static $isFloat = false;

        $state = self::STATE_NUMERIC_VALUE_START;
        $nextChar = $exp->detect(1);

        switch (true) {
            /**
             * value 可能处于整个表达式的最后
             * 也就是说当前 value 字符可能就是整个表达式的最后一个字符
             * 下一个字符是 null 也认为整个表达式结束
             */
            case $exp->isLastPosition():
            case is_null($nextChar):
                $state = self::STATE_EXP_END;
                break;
            /**
             * 可能后面紧跟一个逗号，如果这样的话直接进行 value type 的处理
             */
            case $nextChar === ',':
                $state = self::STATE_CHECK_VALUE_TYPE;
                break;
            case $nextChar === ')':
                return $this->checkValueTypeProcess($exp);
            /**
             * 空格是结束 value 的标志
             * 接下来可能会遇到 relation 或者另外一个 value
             */
            case checkIsBlank($nextChar):
                $state = self::STATE_CHECK_IS_MULTIPLE_VALUE;
                break;
            /**
             * 下一个字符仍旧是数字的话维持状态不变
             */
            case is_numeric($nextChar):
                break;
            /**
             * 下一个字符是点则数字是浮点数，而且接下来不应该再遇到点
             */
            case !$isFloat && $nextChar === '.':
                $isFloat = true;
                break;
            /**
             * 以上条件都不满足则抛出异常
             */
            default:
                $isFloat = false;
                throw new Analyze\AnalyzeInvalidValueException(
                    "Invalid numeric value."
                );
        }

        if ($state !== self::STATE_NUMERIC_VALUE_START) {
            $isFloat = false;
        }

        return $state;
    }

    /**
     * STATE_CHECK_VALUE_TYPE 状态转为 STATE_QUOTED_VALUE_START 状态的条件限制第一个字符只可能为双引号
     * 直到遇到下一个非转义的双引号结束 quoted value 之前不会做状态的转变
     *
     * @param Expression $exp
     * @return int
     */
    protected function quotedValueStartProcess(Expression $exp)
    {
        static $quoted = null;

        if ($exp->current() === '"' && !checkIsEscapeChar($exp->getString(), $exp->key())) {
            $quoted = !is_null($quoted);
        }

        // $quoted 为真值意味着字符串已经结束，字符串被双引号套住了
        if ($quoted) {
            $quoted = null;
            $nextChar = $exp->detect(1);
            switch (true) {
                /**
                 * value 可能处于整个表达式的最后
                 * 也就是说当前 value 字符可能就是整个表达式的最后一个字符
                 * 下一个字符是 null 也认为整个表达式结束
                 */
                case $exp->isLastPosition():
                case is_null($nextChar):
                    return self::STATE_EXP_END;
                /**
                 * 可能后面紧跟一个逗号，如果这样的话直接进行 value 类型的检查
                 */
                case $nextChar === ',':
                    return self::STATE_CHECK_VALUE_TYPE;
                case $nextChar === ')':
                    return $this->checkValueTypeProcess($exp);
                /**
                 * quoted value 结束后紧跟一个空格
                 * 接下来可能会遇到 relation 或者另外一个 value
                 */
                case checkIsBlank($nextChar):
                    return self::STATE_CHECK_IS_MULTIPLE_VALUE;
                /**
                 * 以上条件都不满足则抛出异常
                 */
                default:
                    throw new Analyze\AnalyzeInvalidValueException(
                        "Invalid quoted value."
                    );
            }
        }

        return self::STATE_QUOTED_VALUE_START;
    }

    protected function funcValueStartProcess(Expression $exp)
    {
        static $funcNameStack = [];

        if ($exp->current() === '(') {
            $funcNameStack = [];

            return $this->checkValueTypeProcess($exp);
        } else {
            $funcNameStack[] = $exp->current();
            $funcName = trim(implode('', $funcNameStack));

            if (!preg_match(self::RULE_FUNCTION_NAME, $funcName)) {
                throw new Analyze\AnalyzeInvalidFunctionNameException(sprintf(
                    "Invalid function name %s.",
                    $funcName
                ));
            }
        }

        return self::STATE_FUNC_VALUE_START;
    }

    protected function checkIsMultipleFuncProcess(Expression $exp)
    {
        $nextChar = $exp->detect(1);

        switch (true) {
            /**
             * 遇到逗号，说明函数的参数多余 1 个
             */
            case $nextChar === ',':
                return self::STATE_CHECK_VALUE_TYPE;
            /**
             * 下一个字符是空格仍旧维持现有状态
             */
            case checkIsBlank($nextChar):
                return self::STATE_CHECK_IS_MULTIPLE_FUNC;
            /**
             * 以上条件都不满足则尝试检查 value 类型
             */
            case $nextChar === ')':
                return $this->checkValueTypeProcess($exp);
            default:
                return self::STATE_CHECK_VALUE_TYPE;
        }
    }

    protected function checkIsMultipleValueProcess(Expression $exp)
    {
        $nextChar = $exp->detect(1);

        switch (true) {
            /**
             * value 可能处于整个表达式的最后
             * 也就是说当前 value 字符可能就是整个表达式的最后一个字符
             * 下一个字符是 null 也认为整个表达式结束
             */
            case $exp->isLastPosition():
            case is_null($nextChar):
                return self::STATE_EXP_END;
            /**
             * 遇到逗号，说明 value 是一个 set
             */
            case $nextChar === ',':
                return self::STATE_CHECK_VALUE_TYPE;
            /**
             * 下一个字符是空格仍旧维持现有状态
             */
            case checkIsBlank($nextChar):
                return self::STATE_CHECK_IS_MULTIPLE_VALUE;
            /**
             * 以上条件都不满足则尝试检查 relation 类型
             */
            default:
                $this->collectEnabled = true;

                return $this->checkRelationTypeProcess($exp);
        }
    }

    /**
     * @param Expression $exp
     * @return int
     */
    protected function checkRelationTypeProcess(Expression $exp)
    {
        switch (true) {
            /**
             * 可能遇到 AND
             */
            case strtoupper($exp->detect(strlen(self::RELATION_AND))) === strtoupper(self::RELATION_AND):
                return self::STATE_AND_RELATION_START;
            /**
             * 可能遇到 OR
             */
            case strtoupper($exp->detect(strlen(self::RELATION_OR))) === strtoupper(self::RELATION_OR):
                return self::STATE_OR_RELATION_START;
            /**
             * 无法匹配任何 relation 则抛错
             */
            default:
                throw new Analyze\AnalyzeInvalidRelationException(sprintf(
                    "Invalid relation start with %s.",
                    $nextChar = $exp->detect(1)
                ));
        }
    }

    /**
     * @param Expression $exp
     * @return int
     */
    protected function andRelationStartProcess(Expression $exp)
    {
        $state = $this->checkStateByMatchingRelation($exp, self::RELATION_AND);

        return is_null($state) ? self::STATE_AND_RELATION_START : $state;
    }

    /**
     * @param Expression $exp
     * @return int
     */
    protected function orRelationStartProcess(Expression $exp)
    {
        $state = $this->checkStateByMatchingRelation($exp, self::RELATION_OR);

        return is_null($state) ? self::STATE_OR_RELATION_START : $state;
    }

    /**
     * @param Expression $exp
     * @param $relation
     * @return int|null
     */
    private function checkStateByMatchingRelation(Expression $exp, $relation)
    {
        static $relationStack = [];

        $relationStack[] = strtoupper($exp->current());

        if (count($relationStack) === strlen($relation)) {
            $relationStack = [];

            if (!checkIsBlank($exp->detect(1))) {
                throw new Analyze\AnalyzeInvalidRelationException(
                    "Invalid relation with non-blank followed."
                );
            }

            return self::STATE_INITIAL;
        }

        return null;
    }
}
