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
use jinxing\curl\Helper;
use PHPUnit\Framework\TestCase;

/**
 * Class TestCurl
 * @covers  \jinxing\curl\Curl
 * @uses    \jinxing\curl\BaseObject
 * @uses    \jinxing\curl\Helper
 * @package Tests
 */
class CurlTest extends TestCase
{
    /**
     * @var string 请求地址
     */
    private $api = 'http://0.0.0.0:8080/index.php';

    /**
     * @var Curl
     */
    private $curl;

    public function setUp()
    {
        $this->curl = new Curl([
            'loggerFunc' => function ($content) {
                file_put_contents(
                    __DIR__ . '/' . date('Ymd') . '.log',
                    json_encode($content) . PHP_EOL,
                    FILE_APPEND
                );
            },
        ]);
    }

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

        $this->assertEquals(true, $curl->getIsJson());
        $this->assertEquals(true, $curl->getIsAjax());
        $this->assertEquals('username:username', $curl->getReferer());
        $this->assertEquals(30, $curl->getTimeout());
        $this->assertEquals(true, $curl->getSslVerify(), true);
        $this->assertEquals('./index', $curl->getSslCertFile());
        $this->assertEquals('./index', $curl->getSslKeyFile());

        // 重置
        $curl->reset();
        $this->assertEquals([], $curl->getHeaders());
        $this->assertEquals(false, $curl->getIsAjax());
        $this->assertEquals(null, $curl->getReferer());
        $this->assertEquals(5, $curl->getTimeout());
        $this->assertEquals(false, $curl->getSslVerify());
        $this->assertEquals('', $curl->getSslCertFile());
        $this->assertEquals('', $curl->getSslKeyFile());
    }

    public function testSetAttribute()
    {
        $curl = new Curl();

        // 链式调用
        $curl->setTimeout(30)
            ->setIsAjax(true)
            ->setIsJson(true)
            ->setReferer('username:username')
            ->setSslVerify(true)
            ->setSslCertFile('./index')
            ->setSslKeyFile('./index');

        $this->assertEquals(30, $curl->getTimeout());
        $this->assertEquals(true, $curl->getIsAjax());
        $this->assertEquals(true, $curl->getIsJson());
        $this->assertEquals('username:username', $curl->getReferer());
        $this->assertEquals(true, $curl->getSslVerify());
        $this->assertEquals('./index', $curl->getSslCertFile());
        $this->assertEquals('./index', $curl->getSslKeyFile());
        $this->assertEquals(0, $curl->getRetryNumber());
    }

    public function testDefault()
    {
        $curl = new Curl();
        $this->assertEquals(false, $curl->getIsJson());
        $this->assertEquals(false, $curl->getIsAjax());
        $this->assertEquals(null, $curl->getReferer());
        $this->assertEquals(5, $curl->getTimeout());
        $this->assertEquals(false, $curl->getSslVerify());
        $this->assertEquals('', $curl->getSslCertFile());
        $this->assertEquals('', $curl->getSslKeyFile());
    }

    public function testToArray()
    {
        $array = $this->curl->toArray();
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('method', $array);
        $this->assertCount(7, $array);
    }

    public function testGetInfo()
    {
        $this->assertEmpty($this->curl->getInfo());
    }

    public function testCurlGet()
    {
        $response = $this->curl->get($this->api, [
            'username' => 'jinxing.liu',
            'date'     => date('Y-m-d H:i:s'),
        ]);

        $this->assertTrue(Helper::isJson($response));
        $response = json_decode($response, true);

        $this->assertArrayHasKey('method', $response);
        $this->assertEquals('GET', Helper::getValue($response, 'method'));

        $response = $this->curl->get(
            $this->api . '?name=123',
            ['a' => 123],
            [CURL_TIMECOND_IFMODSINCE => 2]
        );

        $this->assertTrue(Helper::isJson($response));
        $response = json_decode($response, true);
        $this->assertArrayHasKey('method', $response);
        $this->assertEquals('GET', Helper::getValue($response, 'method'));

        // 会抛出错误
        try {
            $this->curl->get('');
        } catch (\Exception $e) {

        }
    }

    public function testCurlPost()
    {
        $response = $this->curl->post($this->api, ['username' => 789]);
        $this->assertTrue(Helper::isJson($response));
        $response = json_decode($response, true);
        $this->assertArrayHasKey('method', $response);
        $this->assertEquals('POST', Helper::getValue($response, 'method'));
        $this->assertEquals(789, Helper::getValue($response, 'request.username'));
    }

    public function testCurlDelete()
    {
        $response = $this->curl->delete($this->api);
        $this->assertTrue(Helper::isJson($response));
        $response = json_decode($response, true);
        $this->assertArrayHasKey('method', $response);
        $this->assertEquals('DELETE', Helper::getValue($response, 'method'));
    }

    public function testCurlPut()
    {
        $response = $this->curl->put($this->api, ['data' => 123]);
        $this->assertTrue(Helper::isJson($response));
        $response = json_decode($response, true);
        $this->assertArrayHasKey('method', $response);
        $this->assertEquals('PUT', Helper::getValue($response, 'method'));
    }

    public function testCurlMulti1()
    {
        $response = $this->curl->multi([$this->api, $this->api], [CURLOPT_RETURNTRANSFER => 1]);
        $this->assertCount(2, $response);
        $this->assertEquals(null, $this->curl->getBody());
    }

    public function testCurlXml()
    {
        $response = $this->curl
            ->post($this->api . '?action=xml', ['name' => 'jinxing.liu']);
        $this->assertEquals(false, is_array($response));
        $this->assertEquals(true, Helper::isXml($response));
    }

    public function testCurlIsJson()
    {
        $response = $this->curl->setIsJson(true)->post($this->api, ['name' => 'jinxing.liu']);
        $response = json_decode($response, true);
        $this->assertEquals(true, $this->curl->getIsJson());
        $this->assertArrayHasKey('time', $response);
    }

    public function testCurl()
    {
        $curl = new Curl([
            'isJson'     => true,
            'isAjax'     => true,
            'referer'    => 'username:username',
            'timeout'    => 3,
            'loggerFunc' => function ($data) {
                file_put_contents(
                    __DIR__ . '/' . date('Ymd') . '.log',
                    json_encode($data) . PHP_EOL,
                    FILE_APPEND
                );
            },
        ]);

        $curl->setOptions(CURLOPT_USERAGENT, '');
        $response = $curl->post($this->api, [
            'username' => 'test',
            'age'      => 15,
        ]);
        $response = json_decode($response, true);
        $curl->setSSLFile(__DIR__ . '/20200321.log', __DIR__ . '/20200321.log');
        $this->assertArrayHasKey('time', $response);
        $this->assertArrayHasKey(CURLOPT_USERAGENT, $curl->getOptions());
        $this->assertEquals('', Helper::getValue($curl->getOptions(), CURLOPT_USERAGENT));
        $this->assertEquals('username:username', Helper::getValue($curl->getOptions(), CURLOPT_REFERER));
        $this->assertEquals(3, Helper::getValue($curl->getOptions(), CURLOPT_TIMEOUT));
        $this->assertEquals([
            'X-Requested-With: XMLHttpRequest',
            'X-Prototype-Version: 1.5.0',
            'Content-Type: application/json',
        ], Helper::getValue($curl->getOptions(), CURLOPT_HTTPHEADER));

        $this->assertEquals($curl->getStatusCode(), $curl->getInfo('http_code'));
        $this->assertEquals($curl->getRequestTime(), $curl->getInfo('total_time'));
    }

    public function testRetry()
    {
        // 需要请求地址响应错误、curl_error 存在
        $response = $this->curl->retry(3)->get($this->api);
        $this->assertEquals(1, $this->curl->getRetryNumber());
        $this->assertNotEmpty($response);
    }

    public function testRetryTime()
    {
        @unlink(__DIR__ . '/retry.num.log');
        // 需要请求地址响应错误、curl_error 存在
        $response = $this->curl
            ->setTimeout(5)
            ->retry(5, false, 2)
            ->get($this->api . '?action=retry', ['number' => 2]);
        $this->assertEquals(3, $this->curl->getRetryNumber());
        $this->assertNotEmpty($response);
    }

    public function testIsEmptyRetry()
    {
        // 需要请求地址响应为空、curl_error 不存在
        $response = $this->curl->retry(2, true)->get($this->api . '?action=empty');
        $this->assertEquals(2, $this->curl->getRetryNumber());
        $this->assertEmpty($this->curl->getError());
        $this->assertEmpty($response);
    }

    public function testWhenRetry()
    {
        // 设置为空时候
        $this->curl->whenRetry(1, 'hhhh');

        @unlink(__DIR__ . '/retry.num.log');

        // 需要请求地址响应为空、curl_error 不存在
        $this->curl->setTimeout(5)
            ->whenRetry(3, function ($ch) {
                /* @var $ch Curl */
                return empty($ch->getBody()) || $ch->getError();
            })->get($this->api . '?action=retry&number=3');

        $this->assertEquals(3, $this->curl->getRetryNumber());
    }

    public function testWhenRetryTime()
    {
        // 需要请求地址响应为空、curl_error 不存在
        $this->curl->whenRetry(3, function ($ch) {
            /* @var $ch Curl */
            return empty($ch->getBody()) || $ch->getError();
        }, 2)
            ->get($this->api . '?action=empty', ['number' => 3]);

        $this->assertEquals(3, $this->curl->getRetryNumber());
    }

    public function testWhenRetry2()
    {
        @unlink(__DIR__ . '/retry.num.log');

        // 需要请求地址响应错误、curl_error 存在
        $this->curl->whenRetry(2, function ($ch) {
            /* @var $ch Curl */
            return $ch->getError();
        })->get($this->api . '?action=retry&number=5');

        $this->assertEquals(2, $this->curl->getRetryNumber());
        $this->assertNotEmpty($this->curl->getError());

    }

    public function testRetry1()
    {
        // 需要请求地址响应错误、curl_error 存在
        $this->curl->retry(2)->get($this->api);
        $this->assertEquals(1, $this->curl->getRetryNumber());
    }

    public function testCurlHeaders()
    {
        $this->curl->setIsJson(true)
            ->setIsAjax(true)
            ->post($this->api, [
                'username' => 'jinxing.liu',
                'date'     => date('Y-m-d H:i:s'),
            ]);

        $this->assertArrayHasKey('Content-Type', $this->curl->getHeaders(true));
        $this->assertCount(3, $this->curl->getHeaders());

        $this->curl->setHeaders([])
            ->setIsJson(false)
            ->setIsAjax(false)
            ->get($this->api);
        $this->assertCount(0, $this->curl->getHeaders());

        // 再次修改headers
        $this->curl->setIsAjax(true)
            ->setIsJson(true)
            ->setHeaders('Accept-Language: zh,zh-CN;q=0.9')
            ->get($this->api);
        $this->curl
            ->reset()
            ->get($this->api);
    }

    public function testCurlMultiHeaders()
    {
        $this->curl->setIsAjax(true)
            ->setIsJson(true)
            ->setHeaders(['Content-Type: text/html'])
            ->setHeaders(['Content-Type: text/html'])
            ->get('http://localhost/index/index');
        $accessHeaders  = $this->curl->getHeaders(true);
        $requestHeaders = $this->curl->getHeaders();
        $this->assertArrayHasKey('Content-Type', $accessHeaders);
        $this->assertCount(4, $requestHeaders);
        $this->assertCount(2, Helper::getValue($accessHeaders, 'Content-Type'));
    }

    public function testFile()
    {
        $response = $this->curl->post($this->api . '?action=file', [
            'image' => new \CURLFile(__DIR__ . '/HelperTest.php'),
            'data'  => 1,
        ]);

        $response = json_decode($response, true);
        $this->assertArrayHasKey('file', $response);
        $this->assertArrayHasKey('image', $response['file']);

        $response1 = $this->curl->post($this->api, [
            'data' => 1,
            'date' => date('Y-m-d'),
        ]);

        $response1 = json_decode($response1, true);
        $this->assertArrayHasKey('request', $response1);
        $this->assertNotEmpty($response1['request']);
    }

    public function testSetSSLFile()
    {
        $this->curl->setSSLFile(__DIR__ . '/mocks/index.php', __DIR__ . '/mocks/index.php');
        $this->assertEquals(__DIR__ . '/mocks/index.php', $this->curl->getSslCertFile());
        $this->assertEquals(__DIR__ . '/mocks/index.php', $this->curl->getSslKeyFile());
        $this->assertEquals(true, $this->curl->getSslVerify());
        $this->curl->get('https://www.baidu.com');
    }

    public function testNotLog()
    {
        $response = $this->curl->setLoggerFunc(null)->post($this->api);
        $response = json_decode($response, true);
        $this->assertArrayHasKey('time', $response);
    }

    public function testToAccessArray()
    {
        $this->curl->setHeaders(['', 'username']);
        $this->assertCount(1, $this->curl->getHeaders(true));
    }
}