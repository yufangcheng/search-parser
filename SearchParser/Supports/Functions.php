<?php

namespace SearchParser\Supports;

use SearchParser\Exceptions\NotSupportedFunctionException;

class Functions
{
    public static function date($str = null)
    {
        static $nowTime;

        if (!isset($nowTime)) {
            $nowTime = date('Y-m-d H:i:s');
        }

        $dateTime = new DateTime($nowTime);

        if (!is_string($str) && is_scalar($str)) {
            $str = (string)$str;
        }

        if (is_string($str) && !empty($str)) {
            $map = [
                'd' => 'day',
                'w' => 'weeks',
                'm' => 'month',
                'y' => 'year',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            ];
            $operators = ['+', '-'];
            $segments = array_map(function ($value) {
                return trim($value);
            }, explode(',', $str));

            foreach ($segments as $segment) {
                if (!in_array($segment[0], $operators)) {
                    $segment = '+' . $segment;
                }

                if (array_has($map, $modifier = $segment[strlen($segment) - 1])) {
                    $shift = sprintf('%s %s', substr($segment, 0, -1), $map[$modifier]);
                    $dateTime->modify($shift);
                }
            }
        }

        return $dateTime->format('Y-m-d');
    }

    public static function current_user()
    {
        throw new NotSupportedFunctionException(
            "Method %s is not supported.",
            __METHOD__
        );
    }

    public static function current_org()
    {
        throw new NotSupportedFunctionException(
            "Method %s is not supported.",
            __METHOD__
        );
    }

    public static function current_date()
    {
        static $date;

        if (!isset($date)) {
            $date = (string)date('Y-m-d');
        }

        return $date;
    }

    public static function current_month()
    {
        static $month;

        if (!isset($month)) {
            $month = (int)date('n');
        }

        return $month;
    }

    public static function current_year()
    {
        static $year;

        if (!isset($year)) {
            $year = (int)date('Y');
        }

        return $year;
    }
}
