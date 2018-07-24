<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/7/6
 * Time: 20:36
 */

namespace Weiwait;


use db\Checked;
use db\Stock;
use db\StockMarket;
use db\StockSz;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager;

class Analyze
{
    public function index($code, $prefixCode)
    {
//        $stocks = StockMarket::allRecordOfStock($code, 2018, 2018);
        $stocks = Manager::table('stock_markets_2018_3')->where(['stock_code' => $code])->orderBy('date', 'asc')->get()->map(function ($v) {
            return (array) $v;
        })->toArray();
        if (empty($stocks)) {
            Stock::query()->where(['code' => $code])->delete();
            return false;
        }
//        if (count($stocks) < Manager::table('stock_markets_2018_2')->where(['stock_code' => '000001'])->count('*')) {
//            StockSz::query()->where(['code' => $code])->delete();
//            return false;
//        }


        $t = 0;
        $f = 0;
        $y = 0;
        $n = 0;
        $min = $stocks[0]['minimum_price'];
        unset($stocks[0]);
        foreach ($stocks as $item) {
            if ($item['maximum_price'] > $min) {
                if ((10000 / $min) * ($item['maximum_price'] - $min) > 500) {
                    $y++;
                } else {
                    $n++;
                }
                $t++;
            } else {
                $f++;
            }
            $min = $item['minimum_price'];
        }
        return ['符合' => $t, '不符合' => $f, '总数' => count($stocks), '安全' => $y, '危险' => $n, 'code' => $code, 'prefix_code' => $prefixCode];
    }

    public function filterOfTrendOfOpeningPrice($data)
    {
        foreach ($data as $key => $item) {
            $records = Manager::table('stock_markets_2018_3')->where(['stock_code' => $item['code']])->orderBy('date', 'desc')->take(3)->get()->map(function ($v) {
                return (array) $v;
            })->toArray();
            if ($records[0]['closing_price'] > $records[0]['opening_price'] && $records[1]['closing_price'] > $records[1]['opening_price']) {
//                echo $key.'-';
                echo (10000 / $records[1]['minimum_price']) * ($records[0]['maximum_price'] - $records[1]['minimum_price']) . "\r";
//                print_r($records);

            } else {
                unset($data[$key]);
            }
        }
        if (empty($data)) {
            die('nothing');
        }
        return $data;
    }

    public function preOrder($data)
    {
        foreach ($data as $key => $item) {
            $records = Manager::table('stock_markets_2018_3')->where(['stock_code' => $item['code']])->orderBy('date', 'desc')->take(3)->get()->map(function ($v) {
            return (array) $v;
        })->toArray();
            $openingPrice = intval(self::currentTrading($item['prefix_code']));
            if (!$openingPrice > 0) {
                continue;
            }
            $min = $records[0]['minimum_price'] / $records[0]['opening_price'] * $openingPrice;
            $min -= ($openingPrice - $min) * 0.5;
            $max = $records[0]['maximum_price'] / $records[0]['opening_price'] * $openingPrice;
            $max -= ($max - $openingPrice) * 0.5;
//            echo $openingPrice . "\n" . $min . "\n" . $max . "\n";
            $pre = [
                'date' => date('Y-m-d'),
                'code' => $item['code'],
                'prefix_code' => $item['prefix_code'],
                'predicted_value' => round($min, 2),
                'opening_price' => $openingPrice,
                'max_predicted_value' => round($max, 2),
                'profit' => (10000 / $min) * ($max - $min)
            ];
            Checked::query()->insert($pre);
        }
    }

    public function check($pre)
    {
        foreach ($pre as $item) {
            $records = Manager::table('stock_markets_2018_2')->where(['stock_code' => $item['code']])->orderBy('date', 'desc')->take(3)->get()->map(function ($v) {
                return (array) $v;
            })->toArray();
            if ($records[0]['minimum_price'] < $item['min'] && $records[0]['maximum_price'] > $item['max']) {
                echo "re{$records[0]['minimum_price']}\n";
                echo "it{$item['min']}\n";
                echo "re{$records[0]['maximum_price']}\n";
                echo "it{$item['max']}\n";
            }
//            echo $records[0]['minimum_price'] . '-' . $item['min'] . "---" . $records[0]['maximum_price'] . '-' . $item['max'] . "\n";
        }
    }

    //当天当时股票信息
    public static function currentTrading($stockCode)
    {
//        $records = Manager::table('stock_markets_2018_2')->where(['stock_code' => $stockCode])->orderBy('date', 'desc')->take(3)->get()->map(function ($v) {
//            return (array) $v;
//        })->toArray();
//
//        return $records[1]['opening_price'];


//http://money.finance.sina.com.cn/quotes_service/api/json_v2.php/CN_MarketData.getKLineData?symbol=sz000001&scale=5&ma=5&datalen=1023

        $client = new Client();
        try {
            $result = $client->request('GET', "http://hq.sinajs.cn/list={$stockCode}");
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
            return $data[1];
        }
        return false;
    }
}