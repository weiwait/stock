<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:21
 */

use db\Checked;
use Illuminate\Database\Capsule\Manager;

include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();

$bought = Manager::table('boughts')->where(['status' => 0])->get()->map(function ($v) {
    return (array) $v;
})->toArray();



foreach ($bought as $key => $item) {
    $bought[$key]['buy_time'] = date('Y-m-d', strtotime($item['buy_time']));
}

header('Access-Control-Allow-Origin:*');
echo json_encode($bought);
