<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:21
 */


use Weiwait\HighOpen;


include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();

//$highOpen = new HighOpen();
//$highOpen->index();

set_time_limit(3600);

$before = microtime(true);

$times = getopt('t:');
$test = new \Weiwait\Test();
$stocks = \db\StockSz::all()->toArray();
$stocks = array_chunk($stocks, 300)[$times['t']];
//$profits = [];
foreach ($stocks as $key => $stock) {
    echo "{$key} --- ";
    $tmp = $test->test($stock['code']);
    if ($tmp) {
//        $profits[] = $test->test($stock['code']);
        $tmp['date'] = date('Y-m-d H:i:s');
        \db\Checked::query()->insert($tmp);
    }
}


$after = microtime(true);

echo ($after - $before);

//usort($profits, function ($first, $second) {
//    return $second['profit2'] <=> $first['profit2'];
//});
//
//file_put_contents('checked', json_encode($profits));


//print_r($profits);
