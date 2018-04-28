<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/2/28
 * Time: 12:08
 */

namespace Weiwait;


use db\StockMarket;

class HighOpen
{
    public $week = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

    public function index()
    {
        $data = $this->allRecordOfStock('000001', 2016, 2017);
        foreach ($data as $key => $item) {
            $data[$key]['date'] .= '    ' . $this->week[date('w', strtotime($data[$key]['date']))];
        }
//        print_r($data);die;
        $previous = 0;
        $rice = [];
        $gruel = [];
        $minimums= [];

        foreach ($data as $key => $item) {
            if (!$previous) {
                $previous = $item['opening_price'];
                continue;
            }

            $priceDifference = $item['opening_price'] - $previous;
            if ($priceDifference > 0.6) {
                $profit = $data[$key + 1]['maximum_price'] - $item['opening_price'];
                $minimums[] = $data[$key]['minimum_price'];
                if ($profit > 0) {
                    $rice[] = [
                        'first' => $data[$key - 1],
                        'current' => $data[$key],
                        'second' => $data[$key + 1],
                        'profit' => $profit,
                        'minimum_price_difference' => $data[$key]['opening_price'] - $data[$key]['minimum_price'],
                        'maximum_price_difference' =>  $data[$key + 1]['maximum_price'] - $data[$key]['opening_price']
                    ];
                } else {
                    $gruel[] = [
                        'first' => $data[$key - 1],
                        'current' => $data[$key],
                        'second' => $data[$key + 1],
                        'profit' => $profit,
                        'minimum_price_difference' => $data[$key]['opening_price'] - $data[$key]['minimum_price'],
                        'maximum_price_difference' =>  $data[$key + 1]['maximum_price'] - $data[$key]['opening_price']
                    ];
                }
            }
            $previous = $item['opening_price'];
        }





        $result = ['rice' => $rice, 'gruel' => $gruel];
        print_r($minimums);
        print_r($result);
    }

    private function allRecordOfStock($stockCode, $start, $end)
    {
        $data = [];

        $stockMarket = new StockMarket;
        for ($year = $start; $year <= $end; $year++) {
            $stockMarket->table = "stock_markets_{$year}";
            $data = array_merge($data, $stockMarket->newQuery()->where(['stock_code' => $stockCode])->orderBy('date', 'asc')->get()->toArray());
        }

        return $data;
    }
}