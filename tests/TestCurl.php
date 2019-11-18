<?php
/**
 *
 * TestCurl.php
 *
 * Author: jinxing.liu
 * Create: 2019/11/12 16:15
 * Editor: created by PhpStorm
 */

namespace Tests;

use jinxing\curl\Curl;
use PHPUnit\Framework\TestCase;

class TestCurl extends TestCase
{
    public function testInit()
    {
        $curl = new Curl([
            'isJson'      => true,
            'isAjax'      => true,
            'timeout'     => 30,
            'referer'     => 'username:username',
            'sslVerify'   => true,
            'sslCertFile' => './index',
            'sslKeyFile'  => './index',
        ]);

        $this->assertEquals($curl->getIsJson(), true);
        $this->assertEquals($curl->getIsAjax(), true);
        $this->assertEquals($curl->getReferer(), 'username:username');
        $this->assertEquals($curl->getTimeout(), 30);
        $this->assertEquals($curl->getSslVerify(), true);
        $this->assertEquals($curl->getSslCertFile(), './index');
        $this->assertEquals($curl->getSslKeyFile(), './index');

        // 重置
        $curl->reset();
        $this->assertEquals($curl->getHeaders(), []);
        $this->assertEquals($curl->getIsAjax(), false);
        $this->assertEquals($curl->getReferer(), null);
        $this->assertEquals($curl->getTimeout(), 5);
        $this->assertEquals($curl->getSslVerify(), false);
        $this->assertEquals($curl->getSslCertFile(), '');
        $this->assertEquals($curl->getSslKeyFile(), '');
        var_dump($curl->toArray(), $curl->getOptions());
    }

    public function testSetAttribute()
    {
        $curl = new Curl();

        $curl->setTimeout(30);
        $this->assertEquals($curl->getTimeout(), 30);

        $curl->setIsAjax(true);
        $this->assertEquals($curl->getIsAjax(), true);

        $curl->setIsJson(true);
        $this->assertEquals($curl->getIsJson(), true);

        $curl->setReferer('username:username');
        $this->assertEquals($curl->getReferer(), 'username:username');

        $curl->setSslVerify(true);
        $this->assertEquals($curl->getSslVerify(), true);

        $curl->setSslCertFile('./index');
        $this->assertEquals($curl->getSslCertFile(), './index');

        $curl->setSslKeyFile('./index');
        $this->assertEquals($curl->getSslKeyFile(), './index');
    }

    public function testDefault()
    {
        $curl = new Curl();
        $this->assertEquals($curl->getIsJson(), false);
        $this->assertEquals($curl->getIsAjax(), false);
        $this->assertEquals($curl->getReferer(), null);
        $this->assertEquals($curl->getTimeout(), 5);
        $this->assertEquals($curl->getSslVerify(), false);
        $this->assertEquals($curl->getSslCertFile(), '');
        $this->assertEquals($curl->getSslKeyFile(), '');
    }

    public function testCurl()
    {
        $curl = new Curl([
            'isJson'  => true,
            'isAjax'  => true,
            'referer' => 'username:username',
            'timeout' => 3,
        ]);

        $curl->setOptions(CURLOPT_USERAGENT, '');
        $curl->get('http://localhost/index.php')->retry(2, true);

        $options = $curl->getOptions();
        $this->assertArrayHasKey(CURLOPT_USERAGENT, $options);
        $this->assertEquals($options[CURLOPT_USERAGENT], '');
        $this->assertEquals($options[CURLOPT_REFERER], 'username:username');
        $this->assertEquals($options[CURLOPT_TIMEOUT], 3);
        $this->assertEquals($options[CURLOPT_HTTPHEADER], [
            'X-Requested-With: XMLHttpRequest',
            'X-Prototype-Version: 1.5.0',
            'Content-Type: application/json',
        ]);

        $this->assertEquals($curl->getStatusCode(), $curl->getInfo('http_code'));
        $this->assertEquals($curl->getRequestTime(), $curl->getInfo('total_time'));
        var_dump($curl->getBody(), $curl->getRetryNumber());
    }

    public function testRetry()
    {
        $curl = new Curl();
        // 需要请求地址响应错误、curl_error 存在
        $curl->get('http://localhost/index.php')->retry(2);
        $this->assertEquals($curl->getRetryNumber(), 2);
        $this->assertNotEmpty($curl->getError());
        var_dump($curl->getError(), $curl->getErrorInfo());
    }

    public function testIsEmptyRetry()
    {
        $curl = new Curl();
        // 需要请求地址响应为空、curl_error 不存在
        $curl->get('http://localhost/index.php')->retry(2, true);
        $this->assertEquals($curl->getRetryNumber(), 2);
        $this->assertEmpty($curl->getError());
        $this->assertEmpty($curl->getBody());
        var_dump($curl->getBody(), $curl->getErrorInfo());
    }

    public function testWhenRetry()
    {
        $curl = new Curl();
        // 需要请求地址响应为空、curl_error 不存在
        $curl->get('http://localhost/index.php')->whenRetry(2, function ($ch) {
            /* @var $ch Curl */
            return empty($ch->getBody());
        }, 0);
        $this->assertEquals($curl->getRetryNumber(), 2);
        $this->assertEmpty($curl->getError());
        $this->assertEmpty($curl->getBody());
        var_dump($curl->getBody(), $curl->getErrorInfo());
    }

    public function testWhenRetry2()
    {
        $curl = new Curl();
        // 需要请求地址响应错误、curl_error 存在
        $curl->get('http://localhost/index.php')->whenRetry(2, function ($ch) {
            /* @var $ch Curl */
            return $ch->getError();
        }, 0);

        $this->assertEquals($curl->getRetryNumber(), 2);
        $this->assertNotEmpty($curl->getError());
        var_dump($curl->getError(), $curl->getErrorInfo());
    }

    public function testRetry1()
    {
        $curl = new Curl();
        // 需要请求地址响应错误、curl_error 存在
        $curl->get('http://localhost/index.php')->retry(2);
        var_dump($curl->getBody(), $curl->getError(), $curl->getErrorInfo(), $curl->getRetryNumber());
        $this->assertEquals($curl->getRetryNumber(), 1);
    }
}