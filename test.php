<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/1/25
 * Time: 11:50
 */


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager;

include_once 'vendor/autoload.php';


new db\DatabaseManager\DatabaseManager();
//
//$before = microtime(true);
//
//$db = new \Illuminate\Database\Capsule\Manager();
//for ($i = 1; $i < 2000; $i++) {
//    $db->table('stocks')->find($i);
//}
//
//$after = microtime(true);
//echo ($after - $before);

//$t = getopt('t:');
//if (empty($t)) {
//    $t = 'hello world';
//}
//print_r($t);

//$stocks = \db\Stock::all()->toArray();
//
//foreach ($stocks as $item) {
//    $data = currentTrading($item['code']);
//    if ($data) {
//        if (array_key_exists(6, $data)) {
//            if ($data[6] > 0) {
//                \Illuminate\Database\Capsule\Manager::table('stocks_sh')->insert(['name' => $item['name'], 'code' => $item['code']]);
//            }
//        }
//    }
//}

$stocks = Manager::table('stocks_sz')->get()->toArray();
foreach ($stocks as $item) {
    \db\Stock::query()->insert(['name' => $item->name, 'code' => $item->code, 'prefix_code' => "sz{$item->code}"]);
}



//当天当时股票信息
function currentTrading($stockCode)
{
    $client = new Client();
    try {
        $result = $client->request('GET', "http://hq.sinajs.cn/list=sh{$stockCode}");
    } catch (GuzzleException $e) {
        return false;
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
        return $data;
    }
    return false;
}


//currentTrading();
