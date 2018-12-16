<?php
/**
 * Created by PhpStorm.
 * User: all
 * Date: 2018/11/2
 * Time: 14:12
 */

include 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

new db\DatabaseManager\DatabaseManager();

ini_set('memory_limit', '1024M');
println('准备就绪');
$file = "C:\Users\all\Desktop\stock\沪深Ａ股20181102close.xls";//日期需要更改

$reader = IOFactory::createReader('Xls');

$spreadsheet = $reader->load($file);
println('已加载文件');
$sheet = $spreadsheet->getSheet(0);

$rows = $sheet->getHighestRow();
$columns = $sheet->getHighestColumn();
if ('CY' != $columns) {
    echo 'The data is incomplete!';die;
}

for ($i = 0; $i <= intval($rows/1000); $i++) {//MySQL单次限制占位符65535,所以分批写入
    println("进行第{$i}次循环");
    $data = [];
    for ($row = 2+$i*1000; $row < 2+($i+1)*1000; $row++) {
        if (0 == intval($sheet->getCell("L{$row}")->getValue())) {
            continue;
        }
        $data[] = [
            'date' => '2018-11-02',
            'code' => substr(trim($sheet->getCell("A{$row}")->getValue()), 2, 6),
            'name' => trim($sheet->getCell("B{$row}")->getValue()),
            'increase' => trim($sheet->getCell("C{$row}")->getValue()),
            'rise_and_fall' => trim($sheet->getCell("E{$row}")->getValue()),
            'op' => trim($sheet->getCell("L{$row}")->getValue()),
            'map' => trim($sheet->getCell("M{$row}")->getValue()),
            'mip' => trim($sheet->getCell("N{$row}")->getValue()),
            'cp' => trim($sheet->getCell("D{$row}")->getValue()),
            'yp' => trim($sheet->getCell("O{$row}")->getValue()),
            'turnover_rate' => trim($sheet->getCell("K{$row}")->getValue()),
            'quantity_ratio' => trim($sheet->getCell("R{$row}")->getValue()),
            'amplitude' => trim($sheet->getCell("U{$row}")->getValue()),
            'total' => trim($sheet->getCell("H{$row}")->getValue()),
            'total_amount' => trim($sheet->getCell("Q{$row}")->getValue()),
            'inside' => trim($sheet->getCell("W{$row}")->getValue()),
            'outside' => trim($sheet->getCell("X{$row}")->getValue()),
            'in_external_ratio' => trim($sheet->getCell("Y{$row}")->getValue()),
            'strong_weak_degree' => trim($sheet->getCell("AJ{$row}")->getValue()),
            'days_rise' => trim($sheet->getCell("AN{$row}")->getValue()),
            '3day_increase' => trim($sheet->getCell("AO{$row}")->getValue()),
            'opr' => trim($sheet->getCell("AU{$row}")->getValue()),
            'mapr' => trim($sheet->getCell("AV{$row}")->getValue()),
            'mipr' => trim($sheet->getCell("AW{$row}")->getValue()),
            'average_increase' => trim($sheet->getCell("AX{$row}")->getValue()),
            'entity_increase' => trim($sheet->getCell("AY{$row}")->getValue()),
            'return_wave' => trim($sheet->getCell("AZ{$row}")->getValue()),
            'attack_wave' => trim($sheet->getCell("BA{$row}")->getValue()),
            'now_average_difference' => trim($sheet->getCell("BB{$row}")->getValue()),
        ];
    }

    println('准备写入数据库');
    $turnover = \Illuminate\Database\Capsule\Manager::table('stock_turnover')->insert($data);
}


function println($str) {
    echo $str . "\n";
}
