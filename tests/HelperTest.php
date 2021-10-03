<?php
/**
 *
 * TestHelper.php
 *
 * Author: jinxing.liu
 * Create: 2019/11/12 16:15
 * Editor: created by PhpStorm
 */

namespace Tests;

use Tests\mocks\User;
use jinxing\curl\Helper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \jinxing\curl\Helper
 */
class HelperTest extends TestCase
{
    public function testStudlyCase()
    {
        $this->assertEquals('UserName', Helper::studlyCase('user_name'));
        $this->assertEquals('UserNameAge', Helper::studlyCase('user_name_age'));
        $this->assertEquals('User', Helper::studlyCase('user'));
        $this->assertEquals('User', Helper::studlyCase('User'));
    }

    public function testGetGetValue1()
    {
        $user = new User();
        $this->assertEquals('jinxing.liu', Helper::getValue($user, 'username'));
        $this->assertEquals('jinxing.liu@qq.com', Helper::getValue($user, 'email'));

        $user->email = '821901008@qq.com';
        $user->username = 'jinxing';

        $this->assertEquals('jinxing', Helper::getValue($user, 'username'));
        $this->assertEquals('821901008@qq.com', Helper::getValue($user, 'email'));
    }


    public function testGetValue()
    {
        $array = [
            'username' => 123,
            'userInfo' => [
                'name' => 456,
                'age'  => 789,
            ],
        ];


        // 获取数组
        $this->assertEquals(456, Helper::getValue($array, 'userInfo.name'));
        $this->assertEquals(123, Helper::getValue($array, 'username'));
        $this->assertEquals(null, Helper::getValue($array, 'age'));
        $this->assertEquals(7, Helper::getValue($array, 'age', 7));

        // 获取不存在的值
        $this->assertEquals('123', Helper::getValue($array, 'username-age', '123'));

        // key 为函数
        $this->assertEquals('123', Helper::getValue($array, function () {
            return '123';
        }));

        // 多层次调用(Key为数组)
        $this->assertNull(Helper::getValue($array, ['userInfo', 'age', 'name']));
    }

    /**
     * @dataProvider buildGetQueryProvider
     *
     * @param $url
     * @param $uri
     * @param $query
     */
    public function testBuildGetQuery($url, $uri, $query)
    {
        $this->assertEquals($url, Helper::buildGetQuery($uri, $query));
    }

    public function buildGetQueryProvider()
    {
        return [
            ['/index?username=123', '/index', ['username' => '123']],
            ['/index?u=123&a=1', '/index?u=123', ['a' => 1]],
            ['/index', '/index', []],
            ['/index?u=345&a=b', '/index?u=345&', ['a' => 'b']],
        ];
    }

    public function testIsJson()
    {
        $this->assertTrue(Helper::isJson(' {"username": 123} '));
        $this->assertFalse(Helper::isJson('[username]'));
        $this->assertTrue(Helper::isJson('["username", 123]'));
        $this->assertTrue(Helper::isJson('{}'));
        $this->assertFalse(Helper::isJson('{{{}}}'));
        $this->assertFalse(Helper::isJson('123'));
    }

    public function testIsXml()
    {
        $this->assertTrue(Helper::isXml('<xml'));
        $this->assertFalse(Helper::isXml('123'));
    }

    public function testGetIpAddress()
    {
        error_reporting(E_ALL & ~E_NOTICE);
        // 获取IP
        $this->assertEmpty(Helper::getIpAddress());

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $this->assertEquals('127.0.0.1', Helper::getIpAddress());
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['HTTP_CLIENT_IP'] = '127.0.0.2';
        $this->assertEquals('127.0.0.2', Helper::getIpAddress());
        unset($_SERVER);

        putenv('HTTP_X_FORWARDED_FOR=127.0.0.3');
        $this->assertEquals('127.0.0.3', Helper::getIpAddress());

        putenv('HTTP_X_FORWARDED_FOR=');
        putenv('HTTP_CLIENT_IP=127.0.0.4');
        $this->assertEquals('127.0.0.4', Helper::getIpAddress());
        putenv('HTTP_CLIENT_IP=');
        $this->assertEmpty(Helper::getIpAddress());
    }
}