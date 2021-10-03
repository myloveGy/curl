<?php

include __DIR__ . '/../../vendor/autoload.php';

use jinxing\curl\Helper;

// 响应为json
header('content-type:application/json; charset=UTF-8');
$action = Helper::getValue($_GET, 'action', 'index');
switch ($action) {
    // 默认
    case 'index':
        exit(json_encode([
            'time'    => date('Y-m-d H:i:s'),
            'method'  => Helper::getValue($_SERVER, 'REQUEST_METHOD'),
            'request' => $_REQUEST,
        ]));
    // 响应xml
    case 'xml':
        header('content-type:application/xml; charset=UTF-8');
        $date   = date('Y-m-d H:i:s');
        $method = Helper::getValue($_SERVER, 'REQUEST_METHOD');

        exit(<<<xml
<xml>
    <time>$date</time>
    <method>$method</method>
</xml> 
xml
        );
    // 上传文件
    case 'file':
        exit(json_encode([
            'time' => date('Y-m-d H:i:s'),
            'file' => $_FILES,
        ]));
    // 测试重新
    case 'retry':
        $number   = Helper::getValue($_REQUEST, 'number');
        $filename = __DIR__ . '/../' . Helper::getValue($_REQUEST, 'filename', 'retry.num') . '.log';

        // 重试了几次
        $retry = file_exists($filename) ? file_get_contents($filename) : 0;
        if ($retry < $number) {
            sleep(6);
            $retry++;
        }

        file_put_contents($filename, $retry);
        exit(json_encode([
            'time'    => date('Y-m-d H:i:s'),
            'method'  => Helper::getValue($_SERVER, 'REQUEST_METHOD'),
            'request' => $_REQUEST,
        ]));
    case 'empty':
        exit('');
}

