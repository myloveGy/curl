<?php

namespace jinxing\curl;

/**
 * Class Curl 基础的curl 请求类
 *
 * @method int getTimeout() 获取设置超时时间
 * @method bool getIsAjax() 获取是否设置isAjax
 * @method bool getIsJson() 获取是否设置isJson
 * @method mixed getReferer() 获取设置的referer信息
 * @method bool getSslVerify() 获取是否设置sslVerify
 * @method string getSslCertFile() 获取设置的sslCertFile文件地址
 * @method string getSslKeyFile() 获取设置的sslKeyFile文件地址
 * @method int|mixed getError() 获取错误编号
 * @method null|string getErrorInfo() 获取错误信息
 * @method array getOptions() 获取curl设置的配置项
 * @method int getRetryNumber() 获取重试的次数
 * @method \Closure|null getLoggerFunc() 获取日志记录回调函数
 * @method array getCurlOptions() 获取curl的附加配置
 * @method Curl setTimeout(int $time) 设置超时时间
 * @method Curl setIsAjax(boolean $isAjax) 设置是否为ajax请求
 * @method Curl setIsJson(boolean $isJson) 设置是否为json请求(header 添加json, 请求数组会转json)
 * @method Curl setReferer(mixed $referer) 在HTTP请求头中"Referer: "的内容
 * @method Curl setSslVerify(boolean $sslVerify) 设置是否设置sslVerify
 * @method Curl setSslCertFile(string $certFile) 设置的sslCertFile文件地址
 * @method Curl setSslKeyFile(string $keyFile) 设置的sslKeyFile文件地址
 * @method Curl setCurlOptions(array $options) 设置的curl的附加配置，优先级最高
 * @method Curl setLoggerFunc($loggerFunc) 设置日志记录回调函数
 *
 * @package jinxing\Curl
 */
class Curl extends BaseObject
{
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PUT    = 'PUT';

    /**
     * @var bool 是否AJAX 请求
     */
    protected $isAjax = false;

    /**
     * @var bool 是否 json 请求
     */
    protected $isJson = false;

    /**
     * @var \Closure 日志处理回调函数
     */
    protected $loggerFunc;

    /**
     * @var array 请求header
     */
    protected $headers = [];

    /**
     * @var int 超时时间
     */
    protected $timeout = 5;

    /**
     * @var null
     */
    protected $referer = null;

    /**
     * @var bool 开启ssl
     */
    protected $sslVerify = false;

    /**
     * @var string ssl cert 文件地址
     */
    protected $sslCertFile = '';

    /**
     * @var string ssl key 文件地址
     */
    protected $sslKeyFile = '';

    /**
     * @var curl
     */
    protected $ch;

    /**
     * @var string|int|mixed 错误编号
     */
    protected $error;

    /**
     * @var string|mixed 错误信息
     */
    protected $errorInfo;

    /**
     * @var string|mixed 响应数据
     */
    protected $body;

    /**
     * @var array 响应头信息
     */
    protected $responseHeaders = [];

    /**
     * @var curl 句柄信息
     */
    protected $info;

    /**
     * @var string 请求地址
     */
    protected $url;

    /**
     * @var string 请求方法
     */
    protected $method;

    /**
     * @var array|mixed 请求数据
     */
    protected $requestData;

    /**
     * @var int 重试次数
     */
    protected $retryNumber = 0;

    /**
     * @var int 重试次数
     */
    protected $retry = 0;

    /**
     * @var \Closure 重试条件
     */
    protected $retryWhen;

    /**
     * @var int 重试暂停时间毫秒
     */
    protected $retryMilliseconds = 0;

    /**
     * @var array 不允许赋值的属性
     */
    protected $guarded = [
        'ch', 'error', 'errorInfo',                              // curl 相关
        'body', 'info', 'responseHeaders',                       // 响应相关
        'url', 'method', 'requestData', 'headers',               // 请求相关
        'retryNumber', 'retry', 'retryWhen', 'retryMilliseconds',// 重试相关
        'guarded', 'options',                                    // 默认选项
    ];

    /**
     * @var array 默认配置属性
     */
    protected $defaultOptions = [
        CURLOPT_USERAGENT      => 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)',   // 用户访问代理 User-Agent
        CURLOPT_HEADER         => 0,
        CURLOPT_FOLLOWLOCATION => 0,                    // 跟踪301
        CURLOPT_RETURNTRANSFER => 1,                    // 返回结果
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,    // 默认使用IPV4
    ];

    /**
     * @var array 配置项
     */
    protected $options = [];

    /**
     * @var array curl 额外参数，优先级最高
     */
    protected $curlOptions = [];

    /**
     * 允许在初始化的时候设置属性信息
     *
     * Curl constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->init($config);
    }

    /**
     * 发送get 请求
     *
     * @param string $url     请求地址
     * @param array  $params  请求参数
     * @param array  $options curl 配置信息
     *
     * @return array|Curl|string|null
     */
    public function get($url, $params = [], $options = [])
    {
        return $this->request($url, self::METHOD_GET, $params, $options);
    }

    /**
     * 发送post 请求
     *
     * @param string       $url     请求地址
     * @param array|string $data    请求数据
     * @param array        $options curl 配置信息
     *
     * @return array|string|mixed
     */
    public function post($url, $data = [], $options = [])
    {
        return $this->request($url, self::METHOD_POST, $data, $options);
    }

    /**
     * 发送 DELETE 请求
     *
     * @param string $url     请求地址
     * @param array  $options curl 配置信息
     *
     * @return string|mixed
     */
    public function delete($url, $options = [])
    {
        return $this->request($url, self::METHOD_DELETE, '', $options);
    }

    /**
     * 发送PUT 请求
     *
     * @param string $url     请求地址
     * @param array  $data    请求数据
     * @param array  $options curl 配置信息
     *
     * @return string|mixed
     */
    public function put($url, $data = [], $options = [])
    {
        return $this->request($url, self::METHOD_PUT, $data, $options);
    }

    /**
     * 重试次数 $this->retry(2)->get('http://localhost/index.php')
     *
     * @param integer $num          重试次数
     * @param bool    $emptyRetry   响应结果为空是否重试
     * @param int     $milliseconds 响应错误暂停多少毫秒
     *
     * @return $this
     */
    public function retry($num, $emptyRetry = false, $milliseconds = 0)
    {
        $this->retry             = $num;
        $this->retryMilliseconds = $milliseconds;
        $this->retryWhen         = function () use ($emptyRetry) {
            return ($this->retryNumber < $this->retry) && ($this->error || ($emptyRetry && empty($this->body)));
        };

        return $this;
    }

    /**
     * 自定义重试条件重试
     *
     * @param int      $num          重试次数
     * @param callable $when         自定义调用结构
     * @param int      $milliseconds 响应错误暂停多少毫秒
     *
     * @return $this
     */
    public function whenRetry($num, $when, $milliseconds = 0)
    {
        // 不是可调用结构
        if (!is_callable($when)) {
            return $this;
        }

        $this->retry             = $num;
        $this->retryMilliseconds = $milliseconds;
        $this->retryWhen         = function () use ($when) {
            return ($this->retryNumber < $this->retry) && $when($this);
        };

        return $this;
    }

    /**
     * 批量发送请求
     *
     * @param array $urls    请求地址
     * @param array $options curl 其他配置项(优先级高)
     *
     * @return array
     */
    public function multi($urls, $options = [CURLOPT_RETURNTRANSFER => 1])
    {
        $mh   = curl_multi_init();
        $conn = $contents = [];

        // 初始化
        foreach ($urls as $i => $url) {
            $conn[$i] = curl_init($url);
            $this->handleCurlOptions($conn[$i], $url);
            curl_setopt_array($conn[$i], $options);
            curl_multi_add_handle($mh, $conn[$i]);
        }

        // 执行
        do {
            curl_multi_exec($mh, $active);
        } while ($active);

        foreach ($urls as $i => $url) {
            $this->body   = curl_multi_getcontent($conn[$i]);
            $contents[$i] = $this->getBody();
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        }

        // 结束清理
        curl_multi_close($mh);
        $this->body = null;
        return $contents;
    }

    /**
     * 发送请求信息
     *
     * @param string       $url     请求地址
     * @param string       $method  请求方法
     * @param string|array $data    请求数据
     * @param array        $options curl 数据信息
     *
     * @return string|mixed
     */
    public function request($url, $method = 'GET', $data = '', $options = [])
    {
        // 检测 curl 请求地址
        if (!$url) {
            throw new \RuntimeException('curl url is null: ' . __FILE__);
        }

        // 初始化设置
        $this->retryNumber = 0;
        $this->options     = $this->defaultOptions;
        $retryWhenFunc     = $this->retryWhen;

        // 开始发送请求
        do {

            // 初始化CURL
            $method = strtoupper($method);

            // 如果是GET请求，需要处理 $data 信息
            if ($method === self::METHOD_GET) {
                $url  = Helper::buildGetQuery($url, $data);
                $data = '';
            }

            $this->ch                             = curl_init();            // CURL
            $this->options[CURLOPT_CUSTOMREQUEST] = $method;                // 请求方法

            // 存在数据、那么需要设置请求数据
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

            // 赋值
            $this->method      = $method;
            $this->url         = $url;
            $this->requestData = $data;
            $this->body        = curl_exec($this->ch);
            $this->error       = curl_errno($this->ch);
            $this->errorInfo   = curl_error($this->ch);
            $this->info        = curl_getinfo($this->ch);

            if (is_resource($this->ch)) {
                curl_close($this->ch);
            }

            // 请求完成 存在日志处理、那么添加上去
            $this->log($start, $request_time);

            // 存在等待时间
            if ($this->retryMilliseconds > 0) {
                usleep($this->retryMilliseconds * 1000);
            }

            $this->retryNumber++;
        } while ($retryWhenFunc instanceof \Closure && $retryWhenFunc());

        return $this->getBody();
    }

    /**
     * 设置请求头信息
     *
     * @param array|string $headers 设置的信息
     *
     * @return Curl
     */
    public function setHeaders($headers)
    {
        // 为空、那么就清空设置的headers
        if (empty($headers)) {
            $this->headers = [];
            return $this;
        }

        if (!is_array($headers)) {
            $headers = func_get_args();
        }

        foreach ($headers as $header) {
            if (in_array($header, $this->headers, true)) {
                continue;
            }

            $this->headers[] = $header;
        }

        return $this;
    }

    /**
     * 设置选项
     *
     * @param string|array $options 设置项
     * @param null|mixed   $value
     *
     * @return Curl
     */
    public function setOptions($options, $value = null)
    {
        if (!is_array($options)) {
            $options = [$options => $value];
        }

        // 设置选项
        foreach ($options as $option => $value) {
            $this->curlOptions[$option] = $value;
        }

        return $this;
    }

    /**
     * 设置SSL文件
     *
     * @param string $certFile 证书文件
     * @param string $keyFile  秘钥文件
     *
     * @return $this
     */
    public function setSSLFile($certFile, $keyFile)
    {
        $this->sslVerify = true;
        if (is_file($certFile)) {
            $this->sslCertFile = $certFile;
        }

        if (is_file($keyFile)) {
            $this->sslKeyFile = $keyFile;
        }

        return $this;
    }

    /**
     * 获取curl info 信息
     *
     * @param null $key 获取的字段信息
     *
     * @return mixed|null
     */
    public function getInfo($key = null)
    {
        if ($key !== null) {
            return isset($this->info[$key]) ? $this->info[$key] : null;
        }

        return $this->info;
    }

    /**
     * 获取状态码
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->getInfo('http_code');
    }

    /**
     * 获取请求时间
     *
     * @param string $timeKey
     *
     * @return mixed
     */
    public function getRequestTime($timeKey = 'total_time')
    {
        return $this->getInfo($timeKey);
    }

    /**
     * 获取请求内容信息
     *
     * @return array|mixed|string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 获取请求头信息
     *
     * @param bool $isAccess 是否处理为 key => value
     *
     * @return array
     */
    public function getHeaders($isAccess = false)
    {
        return $this->toAccessArray($this->headers, $isAccess);
    }

    /**
     * 获取响应头信息
     *
     * @param bool $isAccess 是否转为 key => value 数组
     *
     * @return array
     */
    public function getResponseHeaders($isAccess = false)
    {
        return $this->toAccessArray($this->responseHeaders, $isAccess);
    }

    /**
     * 获取整个请求的数组信息
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'url'          => $this->url,
            'method'       => $this->method,
            'request_data' => $this->requestData,
            'body'         => $this->body,
            'error'        => $this->error,
            'error_info'   => $this->errorInfo,
            'info'         => $this->info,
        ];
    }

    /**
     * 对象属性重置
     *
     * @return Curl
     */
    public function reset()
    {
        $this->headers           = [];
        $this->timeout           = 5;
        $this->isAjax            = false;
        $this->isJson            = false;
        $this->referer           = null;
        $this->sslVerify         = false;
        $this->sslCertFile       = '';
        $this->sslKeyFile        = '';
        $this->ch                = null;
        $this->error             = 0;
        $this->errorInfo         = '';
        $this->body              = null;
        $this->info              = [];
        $this->url               = '';
        $this->method            = null;
        $this->requestData       = null;
        $this->retryNumber       = 0;
        $this->options           = $this->defaultOptions;
        $this->curlOptions       = [];
        $this->retry             = 0;
        $this->retryMilliseconds = 0;
        $this->retryWhen         = null;
        $this->responseHeaders   = [];
        return $this;
    }

    /**
     * 设置默认选项
     *
     * @param resource $ch  curl 资源
     * @param string   $url 请求地址
     */
    protected function handleCurlOptions($ch, $url)
    {
        // 初始化默认值
        $this->responseHeaders = [];

        // 设置 referer
        if ($this->referer) {
            $this->options[CURLOPT_REFERER] = $this->referer;
        }

        $this->options[CURLOPT_URL]     = $url;           // 设置访问的url地址
        $this->options[CURLOPT_TIMEOUT] = $this->timeout; // 设置超时

        // 处理 response header 信息
        $this->options[CURLOPT_HEADERFUNCTION] = function () {
            $header  = Helper::getValue(func_get_args(), 1);
            $len     = strlen($header);
            $headers = explode(':', $header, 2);
            if (count($headers) < 2) {
                return $len;
            }

            $this->responseHeaders[] = trim($header);
            return $len;
        };

        // Https 关闭 ssl 验证
        if (substr($url, 0, 5) == 'https') {
            $this->options[CURLOPT_SSL_VERIFYPEER] = false;
            $this->options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        // 设置ajax
        if ($this->isAjax) {
            $this->setHeaders('X-Requested-With: XMLHttpRequest', 'X-Prototype-Version: 1.5.0');
        }

        // 设置 json 请求
        if ($this->isJson) {
            $this->setHeaders('Content-Type: application/json');
        }

        // 设置证书 使用证书：cert 与 key 分别属于两个.pem文件
        if ($this->sslVerify && $this->sslCertFile && $this->sslKeyFile) {
            // 默认格式为PEM，可以注释
            $this->options[CURLOPT_SSLCERTTYPE] = 'PEM';
            $this->options[CURLOPT_SSLCERT]     = $this->sslCertFile;

            // 默认格式为PEM，可以注释
            $this->options[CURLOPT_SSLKEYTYPE] = 'PEM';
            $this->options[CURLOPT_SSLKEY]     = $this->sslKeyFile;
        }

        // 设置HTTP header 信息
        if ($this->headers) {
            $this->options[CURLOPT_HTTPHEADER] = $this->headers;
        }

        // 存在curlOptions
        if ($this->curlOptions) {
            foreach ($this->curlOptions as $option => $value) {
                $this->options[$option] = $value;
            }
        }

        // 一次性设置属性
        curl_setopt_array($ch, $this->options);
    }

    /**
     * 将['Content-Type: application/json'] 转为 key => value 形式数组
     *
     * @param array $array
     * @param bool  $isAccess
     *
     * @return array
     */
    protected function toAccessArray(array $array, $isAccess = false)
    {
        // 不需要转换或者为空直接返回
        if ($isAccess === false || empty($array)) {
            return $array;
        }

        // 处理数据
        $arrayValues = [];
        foreach ($array as $header) {
            if (!is_string($header) || empty($header)) {
                continue;
            }

            $arrHeaders = explode(':', $header, 2);
            if (count($arrHeaders) < 2) {
                $arrayValues[] = $header;
                continue;
            }

            $attribute = trim($arrHeaders[0]);
            $value     = trim($arrHeaders[1]);
            if (isset($arrayValues[$attribute])) {
                if (is_string($arrayValues[$attribute])) {
                    $arrayValues[$attribute] = [$arrayValues[$attribute]];
                }

                $arrayValues[$attribute][] = $value;
            } else {
                $arrayValues[$attribute] = $value;
            }
        }

        return $arrayValues;
    }

    /**
     * 记录日志
     *
     * @param integer $start        开始毫秒数
     * @param string  $request_time 请求开始时间
     *
     * @return false|mixed
     */
    protected function log($start, $request_time)
    {
        if (empty($this->loggerFunc) || !($this->loggerFunc instanceof \Closure)) {
            return false;
        }

        // 请求完成 存在日志处理、那么添加上去
        $urls       = parse_url($this->url);
        $serverName = Helper::getValue($urls, 'host', $this->url);        // 请求host

        // 请求参数
        if ($query = Helper::getValue($urls, 'query', '')) {
            parse_str($query, $query);
        }

        // 日志完整信息
        $logs = [
            'request_time'     => $request_time,
            'request_method'   => $this->method,
            'proto'            => Helper::getValue($urls, 'scheme', 'http'),
            'server_name'      => $serverName,
            'request_uri'      => Helper::getValue($urls, 'path', $this->url),
            'request_ip'       => Helper::getIpAddress(),
            'request_query'    => $query,
            'request_header'   => $this->getHeaders(true),
            'request_body'     => $this->requestData,
            'host_name'        => gethostname(),
            'http_status'      => $this->getStatusCode(),
            'http_user_agent'  => Helper::getValue($_SERVER, 'HTTP_USER_AGENT'),
            'request_duration' => microtime(true) - $start,
            'response_time'    => date('Y-m-d H:i:s'),
            'response_header'  => $this->getResponseHeaders(true),
            'response_body'    => $this->body,
        ];

        // 存在错误
        if ($this->error) {
            $logs['http_error']      = 'curl error: ' . $this->error;
            $logs['http_error_desc'] = $this->errorInfo;
        }

        // 执行记录日志行为函数
        $function = $this->loggerFunc;
        return $function($logs);
    }

    /**
     * 设置 curl CURLOPT_POSTFIELDS 请求数据
     *
     * @param mixed $data 设置请求数据
     *
     * @return bool
     */
    protected function setPostFields($data)
    {
        // 为空直接返回
        if (empty($data)) {
            return false;
        }

        if (is_array($data) && $this->isJson) {
            $postFields = json_encode($data);
        } else {
            $postFields = $data;
        }

        $this->options[CURLOPT_POSTFIELDS] = $postFields;
        return true;
    }
}