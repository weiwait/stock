<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/6/12
 * Time: 11:30
 */


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();

//$highOpen = new HighOpen();
//$highOpen->index();

set_time_limit(3600);

$test = new \Weiwait\Test();
$stocks = \db\Stock::all()->toArray();

$client = new Client();

foreach ($stocks as $item) {
    try {
        $result = $client->request('GET', "http://hq.sinajs.cn/list=sz{$item['code']}");
    } catch (GuzzleException $e) {
        continue;
    }

    $code = $result->getStatusCode();
    if (200 == $code) {
        $data = $result->getBody();
        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
        $data = explode('"', $data);
        $data = explode(',', $data[1]);
        foreach ($data as $key => $value) {
            if ($key > 9 && $key < 30 && $key % 2 == 0) {
                $data[$key] = substr($value, 0, -2);
            }
        }
        if (!empty($data[1])) {
            if ($data[1] > 0) {
                echo "$data[1]\n";
                \Illuminate\Database\Capsule\Manager::table('stocks_sz')->insert($item);
            }
        }
    }
}

