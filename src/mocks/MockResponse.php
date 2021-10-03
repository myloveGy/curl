<?php

namespace jinxing\curl\mocks;

use jinxing\curl\BaseObject;

/**
 * Class MockCurl 模拟Curl 响应类信息
 * @method string getBody() curl 响应内容
 * @method integer getErrno() curl 响应错误码
 * @method string getError() curl 错误信息
 * @method array getHeaders() curl 响应header信息
 * @package jinxing\curl
 */
class MockResponse extends BaseObject
{
    /**
     * @var string curl 响应内容
     */
    protected $body = '';

    /**
     * @var string curl 错误信息
     */
    protected $error = '';

    /**
     * @var int curl 错误code
     */
    protected $errno = 0;

    /**
     * @var array curl info 信息
     */
    protected $info = [];

    /**
     * @var int 响应code
     */
    protected $httpCode = 200;

    /**
     * @var array 响应header头信息
     */
    protected $headers = [];

    public function __construct(array $config = [])
    {
        $this->init($config);
    }

    public function getInfo()
    {
        if (!isset($this->info['http_code'])) {
            $this->info['http_code'] = $this->httpCode;
        }

        return $this->info;
    }
}