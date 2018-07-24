<?php
/**
 * Created by PhpStorm.
 * User: all
 * Date: 2018/7/13
 * Time: 20:05
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Expose-Headers: *');
header('Content-Type: application/json');


use db\Checked;
use Illuminate\Database\Capsule\Manager;

include_once 'vendor/autoload.php';
new db\DatabaseManager\DatabaseManager();

$_POST['method']();

function complete() {
    $id = $_REQUEST['id'];

    $result = Manager::table('boughts')->where(['id' => $id])->update(['status' => 1]);
    echo json_encode(['result' => $result]);
}

function deletePreItem() {
    $id = $_REQUEST['id'];
    $result = Checked::query()->where(['id' => $id])->delete();
    echo json_encode(['result' => $result]);
}
