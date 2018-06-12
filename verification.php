<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/6/12
 * Time: 15:49
 */


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Weiwait\Test;

include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();

$checked = \db\Checked::query()->orderByDesc('profit2')->get()->toArray();

foreach ($checked as $key => $item) {
    $currentTrading = Test::currentTrading($item['code']);
    $checked[$key]['practical'] = $currentTrading[5];
    if ($item['predicted_value'] > $currentTrading[5]) {
        $checked[$key]['desirable'] = 1;
    } else {
        $checked[$key]['desirable'] = 0;
    }
}


print_r($checked);





