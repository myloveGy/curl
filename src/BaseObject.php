<?php

namespace jinxing\curl;

/**
 * Class BaseObject 基础类
 * 实现 init 方法和 __call 拦截get属性、属性方法
 * @method array getGuarded() 获取不能设置的属性
 * @package jinxing\curl
 */
class BaseObject
{
    /**
     * @var array 不允许赋值的属性
     */
    protected $guarded = [];

    /**
     * 初始化对象
     *
     * @param array $config
     *
     * @return $this
     */
    public function init(array $config = [])
    {
        foreach ($config as $attribute => $value) {
            $attribute = lcfirst(Helper::studlyCase($attribute));
            // 特殊属性不允许设置
            if (in_array($attribute, $this->guarded, true) || $attribute === 'guarded') {
                continue;
            }

            // 存在的属性，设置
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }

        return $this;
    }

    /**
     * 运行方法
     *
     * @param $name
     * @param $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        // 方法前缀和属性名称
        $prefix    = substr($name, 0, 3);
        $attribute = lcfirst(substr($name, 3));

        // 存在属性才处理
        if (property_exists($this, $attribute) && in_array($prefix, ['get', 'set'], true)) {
            // 获取指定属性直接返回
            if ($prefix === 'get') {
                return $this->$attribute;
            }

            // 设置属性
            if ($attribute !== 'guarded' && !in_array($attribute, $this->guarded, true)) {
                $this->$attribute = $arguments[0];
                return $this;
            }
        }

        throw new \BadFunctionCallException(get_called_class() . ' does not exist method: ' . $name);
    }
}