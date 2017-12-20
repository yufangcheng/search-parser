<?php

namespace SearchParser\Pipelines\ExpPipeline;

use Inno\Lib\SearchParser\Exceptions;

class Expression implements \Iterator
{
    protected $chars;

    protected $position;

    public function __construct($exp)
    {
        if (is_scalar($exp) || is_null($exp)) {
            $exp = (string)$exp;
        }

        if (!is_string($exp)) {
            throw new Exceptions\SearchParserInitializeExpressionFailedException(sprintf(
                "Failed to initialize expression with non-string.",
                __CLASS__
            ));
        }

        $this->chars = $this->stringToArray(trim($exp));
        $this->rewind();
    }

    public function getString()
    {
        $chars = array_map(function ($char) {
            return is_scalar($char) ? $char : ' ';
        }, $this->chars);

        return implode('', $chars);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->chars[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->chars[$this->position]);
    }

    public function prepend($char)
    {
        array_unshift($this->chars, $char);
    }

    public function append($char)
    {
        $this->chars[] = $char;
    }

    public function length($realLength = true)
    {
        if ($realLength) {
            return count(array_filter($this->chars, function ($v) {
                return !is_null($v) && !checkIsBlank($v);
            }));
        }

        return count($this->chars);
    }

    public function detect($range)
    {
        if (is_numeric($range)) {
            $range = (int)$range;
        }

        if (is_int($range) && $range > 0 && !$this->isLastPosition()) {
            $segment = array_slice(
                $this->chars, $this->position + 1, $range
            );

            if (count($segment) > 1 || !is_null(current($segment))) {
                return implode('', $segment);
            }
        }

        return null;
    }

    public function isLastPosition()
    {
        return $this->position >= count($this->chars) - 1 || $this->position > 0 && is_null($this->current());
    }

    protected function stringToArray($str)
    {
        return preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}
