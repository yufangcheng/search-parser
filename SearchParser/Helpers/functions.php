<?php

/**
 * 获取 Search Parser 的配置
 */
if (!function_exists('getConfig')) {
    function getConfig($key, $prefix = 'search_parser')
    {
        $keys = array_filter([$prefix, $key], function ($str) {
            return is_string($str) && !checkIsBlank($str);
        });

        return config(implode('.', $keys), $key);
    }
}

/**
 * 检查字符是否有转义
 *
 * @param $searchQuery
 * @param $offset
 * @return bool
 */
if (!function_exists('checkIsEscapeChar')) {
    function checkIsEscapeChar($str, $offset)
    {
        $slashes = [];

        for ($i = $offset; $i > 0; $i--) {
            if ($str[$i - 1] === '\\') {
                $slashes[] = $str[$i - 1];
            } else {
                break;
            }
        }

        return (bool)(count($slashes) % 2);
    }
}

/**
 * 用于计算脚本执行时间
 */
if (!function_exists('microtime_float')) {
    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());

        return ((float)$usec + (float)$sec);
    }
}

/**
 * 将下划线分隔或者中划线分隔的名称转换成驼峰形式
 */
if (!function_exists('lineToHump')) {
    function lineToHump($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, strtolower($str));

        return $str;
    }
}

/**
 * 检查字符或者字符串是否是空的
 */
if (!function_exists('checkIsBlank')) {
    function checkIsBlank($str)
    {
        return is_string($str) && strlen(trim($str)) === 0;
    }
}
