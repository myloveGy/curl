<?php

namespace jinxing\curl\mocks;

use jinxing\curl\Curl;
use jinxing\curl\Helper;

/**
 * Class MockCurl 模拟curl 测试使用
 * @package jinxing\Curl
 */
class MockCurl extends Curl
{
    /**
     * @var array mocks 数据
     */
    protected $mocks = [];

    public function __construct(array $config = [], array $mocks = [])
    {
        parent::__construct($config);
        foreach ($mocks as $uri => $response) {
            if (!($response instanceof MockResponse)) {
                throw new \InvalidArgumentException(get_called_class() . ' mocks uri:[' . $uri . '] It must be MockResponse');
            }

            // 处理路径问题
            $uri               = $uri == '*' ? $uri : '/' . ltrim($uri, '/');
            $this->mocks[$uri] = $response;
        }
    }

    /**
     * 发送请求信息
     *
     * @param string       $url     请求地址
     * @param string       $method  请求方法
     * @param string|array $data    请求数据
     * @param array        $options curl 数据信息
     *
     * @return $this
     */
    public function request($url, $method = 'GET', $data = '', $options = [])
    {
        // 检测 curl 请求地址
        if (!$url) {
            throw new \RuntimeException(get_called_class() . ' url is null: ' . __FILE__);
        }

        // 初始化设置
        $this->retryNumber = 1;
        $this->options     = $this->defaultOptions;
        $this->url         = $url;
        $this->method      = strtoupper($method);
        $this->ch          = curl_init();          // CURL

        $this->options[CURLOPT_CUSTOMREQUEST] = $this->method;                // 请求方法

        // 存在数据
        $this->setPostFields($data);

        // 准备记录日志信息
        $start        = microtime(true);
        $request_time = date('Y-m-d H:i:s');

        // 一次性设置
        $this->handleCurlOptions($this->ch, $url);

        // 附加单次使用的附加curl配置(优先级最高)
        if ($options && is_array($options)) {
            curl_setopt_array($this->ch, $options);
        }

        // 处理模拟请求数据
        $urls = parse_url($this->url);
        $path = Helper::getValue($urls, 'path', '/');
        /* @var $response MockResponse */
        $response = $this->matchUri($path) ?: new MockResponse();

        // 赋值
        $this->requestData     = $data;
        $this->body            = $response->getBody();
        $this->error           = $response->getErrno();
        $this->errorInfo       = $response->getError();
        $this->info            = $response->getInfo();
        $this->responseHeaders = $response->getHeaders();

        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }

        // 请求完成 存在日志处理、那么添加上去
        $this->log($start, $request_time);

        return $this->getBody();
    }

    /**
     * 设置uri 对应的mock数据
     *
     * @param string       $uri
     * @param MockResponse $response
     *
     * @return MockCurl
     */
    public function setUriResponse($uri, MockResponse $response)
    {
        $this->mocks[$uri] = $response;
        return $this;
    }

    /**
     * 匹配路径
     *
     * @param string $uri uri名称
     *
     * @return mixed|MockResponse
     */
    public function matchUri($uri)
    {
        // 第一步：全路径匹配 /user/username
        if (array_key_exists($uri, $this->mocks)) {
            return $this->mocks[$uri];
        } elseif ($uri === '/') {
            return Helper::getValue($this->mocks, '*');
        }

        // 第二步：路径匹配 /user/*
        $response = null;
        $length   = strlen($uri);
        foreach ($this->mocks as $key => $value) {
            if (
                substr($key, -2, 2) === '/*' &&
                strlen($key) < $length &&
                rtrim($key, '/*') === substr($uri, 0, strlen($key) - 2)
            ) {
                $response = $value;
            }
        }

        return $response ?: Helper::getValue($this->mocks, '*');
    }
}