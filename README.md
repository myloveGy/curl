Curl 
====

只包含一个`Curl`处理类，不依赖其他库

## 安装

使用composer安装

```bash
composer require jinxing/curl
```

## 使用说明

### `Curl` 类的使用

#### 1. 发送`get`请求

```php
$curl = new Curl();
$curl->get('http://localhost/', ['username' => 'test']);
$response = $curl->getBody();
```

#### 2. 发送`post`请求

```php
$curl = new Curl();
$curl->post('http://localhost/', ['username' => 'test']);
$response = $curl->getBody();
```

#### 3. 重试

>第三个参数设置间隔时间、以毫秒计算、默认为0

##### 1. 当存在curl错误时候，重试2次, 隔1秒重试1次

```php
$curl = new Curl();
$curl->get('http://localhost/', ['username' => 'test'])->retry(2, false, 1000);
$response = $curl->getBody();
```

##### 2. 当存在curl错误、或者响应为空的时候，重试2次, 隔1秒重试1次

```php
$curl = new Curl();
$curl->get('http://localhost/', ['username' => 'test'])->retry(2, true, 1000);
$response = $curl->getBody();
```
##### 3. 指定条件进行重试

当存在错误的时候重试2次
```php
$curl = new Curl();
$curl->get('http://localhost/', ['username' => 'test'])->whenRetry(2, function ($ch) {
    // 存在错误、重试
    return $ch->getError();
});
$response = $curl->getBody();
```

##### 4. 不需要暂停等待(没有间隔时间)
```php
$response = (new Curl())->get('http://localhost')->retry(2)->getBody();
```

#### 4. 初始化设置属性

初始化设置属性
```php
$curl = new Curl([
    'timeout' => 30,    // 设置超时时间
    'isJson'  => true,  // 发送json请求
    'isAjax'  => true,  // ajax 请求
    'headers' => ['Content-type: text/html'], // 设置http header 信息
    // 其他
    'referer'     => 'username:username', // 在HTTP请求头中"Referer: "的内容。
    'sslVerify'   => true,                // 使用ssl证书
    'sslCertFile' => './index',           // 一个包含 PEM 格式证书的文件名
    'sslKeyFile'  => './index',           // 包含 SSL 私钥的文件名
    
    // 默认curl options 配置
    'options' => [
         CURLOPT_USERAGENT      => 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)',   // 用户访问代理 User-Agent
         CURLOPT_HEADER         => 0,
         CURLOPT_FOLLOWLOCATION => 0, // 跟踪301
         CURLOPT_RETURNTRANSFER => 1, // 返回结果
         CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, // 默认使用IPV4
    ],
    
    // 其他curl options配置，优先级最高
    'curlOptions' => [
        CURLOPT_TIMEOUT => 50,
    ],
]);
```

使用链式调用
```php
$curl = new Curl();
$curl->setTimeout(30)
->setIsJson(true)
->setIsAjax(true)
->setHeaders('Content-type: text/html') // 允许传递数组 setHeaders(['X-Requested-With: XMLHttpRequest', 'X-Prototype-Version: 1.5.0'])
->setReferer('username:username')
->setSslVerify(true)
->setSslCertFile('./index')
->setSslKeyFile('./index')
->setOptions(CURLOPT_TIMEOUT, 50); // 允许传递数组 setOptions([CURLOPT_TIMEOUT => 50])
```

#### Curl 其他方法

|方法名称    |返回值| 方法说明 |
|---------------|-------------|----------|
|`delete($url)`|`Curl`|发送delete请求|
|`put($url, $data)`|`Curl`|发送put请求|
|`request($url, $method = 'GET', $data = '')`|`Curl`|发送请求|
|`reset()`|`Curl`|重置Curl类|
|`getBody()`|`mixed`|获取请求响应结果|
|`getError()`|`int`|获取curl错误码|
|`getErrorInfo()`|`string`|获取curl错误信息|
|`getInfo()`|`string`|获取`curl_getinfo`返回信息|
|`getStatusCode()`|`string`|获取请求状态码|
|`getRequestTime()`|`string`|获取请求时间|
|`toArray()`|`array`|curl类请求相关信息数组|


>所有私有属性，都可以通过get + 属性名称 方法获取例如： getIsAjax()、getIsJson()、getOptions() 等等
