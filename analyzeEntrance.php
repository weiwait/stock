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

error_reporting(E_ALL);
ini_set('display_errors', 1);
$before = microtime(true);

//$times = getopt('t:');
$hasData = false;
if (!empty(getopt('d:'))) {
    $hasData = true;
}
$analyze = new \Weiwait\Analyze();
//$stocks = array_chunk($stocks, 300)[$times['t']];
$data = [];

if (!$hasData) {
    $stocks = \db\StockSz::all()->toArray();
    foreach ($stocks as $key => $stock) {
        $tmp = $analyze->index($stock['code']);
        if ($tmp) {
            $data[] = $tmp;
        }
    }
    usort($data, function ($first, $second) {
        return $first['符合'] <=> $second['符合'];
    });

    $data = array_chunk($data, count($data) / 2)[0];

    usort($data, function ($first, $second) {
        return $second['安全'] <=> $first['安全'];
    });

    $data = array_chunk($data, count($data) / 2)[0];

    file_put_contents('first-filtered', json_encode($data));
} else {
    $data = file_get_contents('first-filtered');
    $data = json_decode($data, true);
}

$data = $analyze->filterOfTrendOfOpeningPrice($data);
file_put_contents('filter-of-trend-opening-price', json_encode($data));


//$data = file_get_contents('filter-of-trend-opening-price');
//$data = json_decode($data, true);

$pre = $analyze->preOrder($data);

//$analyze->check($pre);


$after = microtime(true);

echo date('i:s', ($after - $before));
