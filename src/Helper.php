<?php

namespace jinxing\curl;

/**
 * Class Helper 基础助手类
 *
 * @package jinxing\curl
 */
class Helper
{
    /**
     * 通过指定字符串拆分数组，然后各个元素首字母，最后拼接
     *
     * @param string       $strName 字符串
     * @param string|array $and     拆分的字符串(默认'_')
     *
     * @return string
     * @example $strName = 'user_name',$and = '_', return UserName
     */
    public static function studlyCase($strName, $and = ['_', '-'])
    {
        return str_replace(' ', '', ucwords(str_replace($and, ' ', $strName)));
    }

    /**
     * 获取IP地址
     *
     * @return string 返回字符串
     */
    public static function getIpAddress()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $strIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $strIpAddress = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $strIpAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $strIpAddress = getenv('HTTP_X_FORWARDED_FOR');
            } else if (getenv('HTTP_CLIENT_IP')) {
                $strIpAddress = getenv('HTTP_CLIENT_IP');
            } else {
                $strIpAddress = getenv('REMOTE_ADDR') ?: '';
            }
        }

        return $strIpAddress;
    }

    /**
     * 处理请求地址和请求参数
     *
     * @param string $url   请求地址
     * @param array  $query 请求参数
     *
     * @return string
     */
    public static function buildGetQuery($url, $query = [])
    {
        // query 为空，直接返回
        if (empty($query)) {
            return $url;
        }

        // 拼接请求参数
        $query = is_array($query) ? http_build_query($query) : (string)$query;

        // 判断不存在?
        if (strrpos($url, '?') === false) {
            $url .= '?';
        } else if (!in_array(substr($url, -1, 1), ['?', '&'], true)) {
            $url .= '&';
        }

        return $url . $query;
    }

    /**
     *
     * 检测一个字符串否为Json字符串
     *
     * @param string $string
     *
     * @return true|false
     *
     */
    public static function isJson($string)
    {
        if ($string && is_string($string) && in_array(substr(trim($string), 0, 1), ['{', '['], true)) {
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        }

        return false;
    }

    /**
     * 检测一个字符串是否为xml字符串
     *
     * @param $string
     *
     * @return boolean
     */
    public static function isXml($string)
    {
        return strtolower(substr($string, 0, 4)) === '<xml';
    }

    /**
     * 获取数组的值
     *
     * @param array|mixed     $array 提供值的数组
     * @param string|callable $key   获取的key(支持user.name)
     * @param null            $default
     *
     * @return mixed|null
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key   = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessible beforehand
            return $array->$key;
        } elseif (is_array($array)) {
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        }

        return $default;
    }
}