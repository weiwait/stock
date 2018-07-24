<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/7/9
 * Time: 15:06
 */


use db\Checked;
use Illuminate\Database\Capsule\Manager;

include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();

$pre = Checked::query()->where(["date" => date('Y-m-d')])->get()->toArray();

//foreach ($pre as $item) {
//    $a[] = $item['code'];
//}
//
//$a = array_unique($a);
//
//print_r($a);die;

foreach ($pre as $key => $item) {
    if ($item['opening_price'] < 2 || $item['opening_price'] > 13) {
        unset($pre[$key]);
    }
}

usort($pre, function ($first, $second) {
    return $second['profit'] <=> $first['profit'];
});
//usort($pre, function ($first, $second) {
//    return $first['opening_price'] <=> $second['opening_price'];
//});

$records = Manager::table('stock_markets_2018_3')->where(['date' => '2018-07-23'])->get()->map(function ($v) {
    return (array) $v;
})->toArray();

$r = [];
foreach ($records as $key => $item) {
    $r[$item['stock_code']] = $item;
}

foreach ($pre as $key => $item) {
    if (!array_key_exists($item['code'], $r)) {
        unset($pre[$key]);
        continue;
    }
    $pre[$key]['date'] = date('Y-m-d', strtotime($item['date']));
    $pre[$key]['yesterday_opening_price'] = $r[$item['code']]['opening_price'];
    $pre[$key]['yesterday_minimum_price'] = $r[$item['code']]['minimum_price'];
    $pre[$key]['yesterday_maximum_price'] = $r[$item['code']]['maximum_price'];
    $pre[$key]['yesterday_closing_price'] = $r[$item['code']]['closing_price'];
}


$pre = array_chunk($pre, 30)[0];

//$i = 0;
//foreach ($pre as $item) {
//    $records = Manager::table('stock_markets_2018_2')->where(['stock_code' => $item['code']])->orderBy('date', 'desc')->take(5)->get()->map(function ($v) {
//        return (array) $v;
//    })->toArray();
//    if ($records[1]['minimum_price'] < $item['predicted_value'] && $records[0]['maximum_price'] > $item['max_predicted_value']) {
//        $i++;
//        echo "{$records[1]['date']}\n";
//        echo "{$records[0]['date']}\n";
//        echo "re{$records[1]['minimum_price']}\n";
//        echo "it{$item['predicted_value']}\n";
//        echo "re{$records[0]['maximum_price']}\n";
//        echo "it{$item['max_predicted_value']}\n";
//        echo "{$item['profit']}\n";
//    }
//            echo $records[0]['minimum_price'] . '-' . $item['min'] . "---" . $records[0]['maximum_price'] . '-' . $item['max'] . "\n";
//}
//echo $i;


header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Expose-Headers: *');
header('Content-Type: application/json');
echo json_encode($pre);


