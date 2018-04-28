<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/1/16
 * Time: 17:24
 */

namespace db;


use Illuminate\Database\Eloquent\Model;

class StockMarket extends Model
{
    public $table = 'stock_markets_2016';

    public $timestamps = false;

    public static function allRecordOfStock($stockCode, $start, $end)
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