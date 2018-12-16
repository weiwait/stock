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

//$stocks = Manager::table('stocks_sz')->get()->toArray();
//foreach ($stocks as $item) {
//    \db\Stock::query()->insert(['name' => $item->name, 'code' => $item->code, 'prefix_code' => "sz{$item->code}"]);
//}



//当天当时股票信息
function currentTrading()
{
    $client = new Client();
    try {
        $result = $client->request('GET', "http://stock.gtimg.cn/data/index.php?appn=detail&action=data&c=sz002451&p=2");
    } catch (GuzzleException $e) {
        return false;
    }
    $code = $result->getStatusCode();
    if (200 == $code) {
        $data = $result->getBody();
        $data = explode('"', $data)[1];
        $data = explode('|', $data);
        $data2 = [];
        foreach ($data as $item) {
            $tmp = explode('/', $item);
            $data2[] = [
                'on_times' => $tmp[0],
            ];
        }
        echo $data;die;
        return $data;
    }
    return false;
}


//currentTrading();

$records = Manager::table('stock_markets_2018_3')->where(['date' => '2018-07-27'])->get()->toArray();
//echo count($records);
foreach ($records as $key => $item) {
    if ($item->opening_price > 6) {
        unset($records[$key]);
    }
    if ($item->opening_price < 3) {
        unset($records[$key]);
    }
}

$y = 0;
$n = 0;
foreach ($records as $item) {
    $records2 = Manager::table('stock_markets_2018_3')->where(['stock_code' => $item->stock_code])->orderBy('date', 'desc')->take(6)->get()->toArray();
    foreach ($records2 as $key => $item2) {
        if (!empty($records2[$key + 1])) {
//            if ($item2->maximum_price - $records2[$key + 1]->minimum_price > 0) {
//                echo (3000 / $records2[$key + 1]->minimum_price) * ($item2->maximum_price - $records2[$key + 1]->minimum_price)."\n";
//            }
            if ((3000 / $records2[$key + 1]->minimum_price) * ($item2->maximum_price - $records2[$key + 1]->minimum_price) > 300) {
                if ($item2->opening_price > $records2[$key + 1]->closing_price) {
                    $y++;
                } else {
                    $n++;
                }
            }
        }
    }
//    echo "\n";
}

echo $y;
echo "\n";
echo $n;

//echo count($records);
//print_r($records);
