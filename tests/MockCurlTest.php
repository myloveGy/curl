<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use jinxing\curl\mocks\MockCurl;
use jinxing\curl\mocks\MockResponse;

/**
 * Class MockCurlTest
 * @covers  \jinxing\curl\mocks\MockCurl
 * @covers  \jinxing\curl\mocks\MockResponse
 *
 * @uses    \jinxing\curl\Curl
 * @uses    \jinxing\curl\Helper
 * @uses    \jinxing\curl\BaseObject
 *
 * @package Tests
 */
class MockCurlTest extends TestCase
{
    public function testRun()
    {
        $curl = new MockCurl([
            'loggerFunc' => function ($content) {
                file_put_contents(
                    __DIR__ . '/' . date('Ymd') . '.log',
                    json_encode($content) . PHP_EOL,
                    FILE_APPEND
                );
            },
        ], [
            '/index' => new MockResponse([
                'body' => json_encode(['code' => 200, 'msg' => 'success']),
            ]),
        ]);

        $response = $curl->post('https://www.baidu.com/index', ['username' => '12345']);
        $response = json_decode($response, true);

        $this->assertArrayHasKey('code', $response);
        $this->assertArrayHasKey('msg', $response);
        $this->assertEquals(200, $response['code']);

        // 请求其他路径
        $response = $curl->setUriResponse('/username', new MockResponse([
            'body'     => json_encode(['username' => 'jinxing.liu']),
            'httpCode' => 500,
            'error'    => 'CURL ERROR',
            'errno'    => 7,
        ]))
            ->get('https://www.baidu.com/username', [
                'username' => 'jinxing.liu',
            ]);

        $response = json_decode($response, true);
        $this->assertEquals(7, $curl->getError());
        $this->assertEquals('CURL ERROR', $curl->getErrorInfo());
        $this->assertEquals(500, $curl->getStatusCode());
        $this->assertArrayHasKey('username', $response);
    }

    public function testRequest()
    {
        $curl = new MockCurl();
        try {
            $curl->request('');
        } catch (\Exception $e) {
            $this->assertEquals(true, $e instanceof \RuntimeException);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testOptions()
    {
        $curl     = new MockCurl([], ['/' => new MockResponse(['body' => 'username'])]);
        $response = $curl->request('https://www.baidu.com/', 'GET', '', [CURLOPT_TIMEOUT => 100]);
        $this->assertEquals('username', $response);
    }

    public function testMatchUri()
    {
        $curl = new MockCurl([], [
            'user/username' => new MockResponse(['body' => 1]),
            'user/age'      => new MockResponse(['body' => 2]),
            'user/*'        => new MockResponse(['body' => 'username']),
            '*'             => new MockResponse(['body' => '******']),
        ]);

        // 匹配到*
        $response = $curl->matchUri('/');
        $this->assertNotEmpty($response);
        $this->assertEquals('******', $response->getBody());

        // 匹配到user/*
        $response = $curl->matchUri('/user/test');
        $this->assertNotEmpty($response);
        $this->assertEquals('username', $response->getBody());

        // 匹配到user/age
        $response = $curl->matchUri('/user/age');
        $this->assertEquals(2, $response->getBody());


        // 出现错误
        try {
            new MockCurl([], ['/' => null]);
        } catch (\Exception $exception) {
            $this->assertEquals(true, $exception instanceof \InvalidArgumentException);
        }
    }

    public function testHeaders()
    {
        $curl = new MockCurl([], [
            '/users' => new MockResponse([
                'body'    => '',
                'headers' => [
                    'Content-Type: application/json; charset=utf-8',
                    'X-Very-Signature: zKy0r/UhMdQqAgmxNIDwj+H71ewXesqsjo1a2eQsZQLSJySbgQVaSXZsmG+VsWOBDuVGFa4IMHjvHsi7/X7R8G2thCP+hYw3hwN2OJ0ihVmqvQOFI5xHAG75d74j5TmAwJZzfWZUvrEZyaFRBMCSxLMkswHUeLFf5I44ZuC/gEQtf29AOr4vFUhg7n2dLehLwiO8QiKDxtNvjIFc/U7+yS+ctnjwEj32X2zUzJpX1RNUqAi9cnJ/YCDHjhvAftL7181kApDexRYDvZVioyJ/PLmd5MrSKUTXPCZH+3rAHDxW40BKXlwLa4DdPtWwkfcX5Wr0rsQGXrsVMHmLmfbgQA==',
                ],
            ]),
        ]);

        $this->assertEmpty($curl->get('/users'));
        $responseHeaders = $curl->getResponseHeaders(true);
        $this->assertCount(2, $responseHeaders);
        $this->assertArrayHasKey('Content-Type', $responseHeaders);
        $this->assertArrayHasKey('X-Very-Signature', $responseHeaders);
    }
}