<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:23
 */

namespace Weiwait;


use db\DatabaseManager\DatabaseManager;
use db\Stock;
use db\StockCrawlerCycle;
use db\StockMarket;
use Illuminate\Database\Capsule\Manager as Capsule;
use QL\Ext\CurlMulti;
use QL\QueryList;
use Symfony\Component\Config\Definition\Exception\Exception;

class PQuery
{
    private $response = '';
    private $urls = [];
    private $start = '';
    private $end = '';
    private $data = [];
    private $stockCodes = [];

    public function __construct()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->start = microtime(true);
//        $this->urls = [
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2018&jidu=1",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=1",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=2",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=3",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=4",
//        ];
//        $this->stockCode('http://quote.eastmoney.com/stocklist.html');
//        $this->response = $this->$execute();
//         = is_array($data) ? json_encode($data) : $data;
    }

    public function __toString()
    {
        return $this->response;
    }

    public function stockCode($url)
    {
        $phpQuery = new QueryList();
        $rules = [
            'stockCode' => ['#quotesearch', 'html', '', function ($match) {
                $data = QueryList::getInstance()->html($match)->find('li')->texts()->toArray();
                $data = array_filter($data);
                foreach ($data as $key => $item) {
                    $item = trim($item, ')');
                    $item = explode('(', $item);
                    $data[$key] = ['name' => $item[0], 'code' => $item[1]];
                }
                usort($data, function ($first, $second) {
                    return $first['code'] <=> $second['code'];
                });
                Stock::query()->insert($data);
            }]
        ];
        $stockCodes = $phpQuery->get($url)->rules($rules)->removeHead()->encoding('UTF-8')->query()->getData()->all();
//        $stockCodes = $stockCodes[0]['stockCode'];
//        if (count($stockCodes) > 1000) {
//            file_put_contents(__DIR__ . '/stockCodes', json_encode($stockCodes));
//            $this->stockCodes = $stockCodes;
//        }
    }

    public function single($url)
    {
        $pQuery = new QueryList();
        $rules = [
            'quarter' => ['#FundHoldSharesTable', 'html', '', function ($match) {
                try {
                    $query = QueryList::getInstance()->html($match);
                    $data = $query->find('div')->texts()->toArray();
                    $query->destruct();
                } catch (Exception $exception) {
                    return [];
                }
                array_splice($data, 0, 7);
                return $data;
            }],
        ];

        try {
            $data = $pQuery->get($url)->rules($rules)->query()->getData()->toArray();
            $pQuery->destruct();
        } catch (Exception $exception) {
            $data = [];
        }
        if ($data) {
            return $data[0]['quarter'];
        } else {
            return [];
        }
    }

    /**
     * @name CurlMulti multipleThread
     * @return array
     */
    public function multiple(Array $urls)
    {
        $pq = QueryList::getInstance();
        $pq->use(CurlMulti::class, 'multipleThread');
        $pq->rules([
            'quarter' => ['#FundHoldSharesTable', 'html', '', function ($match) {
                $data = QueryList::getInstance()->html($match)->find('div')->texts()->toArray();
                array_splice($data, 0, 7);
                return $data;
            }],
//            'stock' => ['.productviewcart', 'html', '', function ($match) {
//                $stock = QueryList::getInstance()->html($match)->find('button[title=Add to Cart]')->attrs('onclick');
//                $stock = array_reverse(explode(',', $stock))[0];
//                return intval(trim($stock, "'"));
//            }]
        /* @name curlMulti multipleThread */
        ])->multipleThread($urls)->success(function (QueryList $pq) {
            $this->data[] = ($pq->query()->getData()->first())['quarter'];
        })->start();
        $this->end = microtime(true);
//        echo 'executing: ' . round($this->end - $this->start, 3) . " seconds</br>";
        $data = [];
        foreach ($this->data as $item) {
            $data = array_merge($data, $item);
        }
        $data = array_chunk($data, 7);
        asort($data);
        $this->data = [];
//        $maximum = 0;
//        $minimum = 0;
//        foreach ($data as $oneDay) {
//            $maximum += $oneDay[2];
//            $minimum += $oneDay[4];
//        }
//        $total = count($data);
//        echo "maximumAverage: " . $maximum / $total . "\r";
//        echo "minimumAverage: " . $minimum / $total . "\r";
//        echo "persents: " . (($maximum / $total) - ($minimum / $total)) / ($minimum / $total) * 5000 . "\r";
//        print_r($data);die;
        return $data;
    }

    public function pickUpStock()
    {
        $year = 2017;
//        $year = date('Y');
//        $month = date('m');
//        $lastYear = $year - 1;
//        $quotient = 12 / $month;
//        if ($quotient > 3) {
//            $currentQuarter = 1;
//            $lastYearQuarter = 4;
//        } else if ($quotient > 1.7) {
//            $currentQuarter = 2;
//            $lastYearQuarter = 3;
//        } else if ($quotient > 1.2) {
//            $currentQuarter = 2;
//            $lastYearQuarter = 1;
//        } else {
//            $currentQuarter = 4;
//            $lastYearQuarter = 1;
//        }


        $stockCodes = Stock::query()->select(['code'])->get()->toArray();
        $stockCodes = array_column($stockCodes, 'code');
        $codes = StockMarket::query()->select(['stock_code'])->get()->toArray();
        $codes = array_column($codes, 'stock_code');
        $stockCodes = array_diff($stockCodes, $codes);
        $done = [];
        foreach ($stockCodes as $where => $item) {
//            $urls = [];
            if (!$done) {
                $crawlerCycle = StockCrawlerCycle::query()->find(7)->toArray();
                if ($crawlerCycle['ephemeral_data']) {
                    $done = json_decode($crawlerCycle['ephemeral_data'], true);
                    if (in_array($item, $done)) {
                        continue;
                    }
                }
            } else {
                if (in_array($item, $done)) {
                    continue;
                }
            }
            for ($i = 4; $i > 0; $i--) {
                $url = "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/{$item}.phtml?year={$year}&jidu={$i}";
                $data = $this->single($url);
                if ($data) {
                    $data = array_chunk($data, 7);
                    asort($data);
                    foreach ($data as $key => $value) {
                        $data[$key] = [
                            'stock_code' => $item,
                            'year' => $year,
                            'quarter' => $i,
                            'date' => $value[0],
                            'opening_price' => $value[1],
                            'maximum_price' => $value[2],
                            'closing_price' => $value[3],
                            'minimum_price' => $value[4],
                            'trading_stocks' => $value[5],
                            'transaction_amount' => $value[6],
                        ];
                    }
                    $result = StockMarket::query()->insert($data);
                    if (!$result) {
                        file_put_contents(__DIR__ . '/log', "code: {$item}, quarter: {$i}\r", FILE_APPEND);
                    }
                } else {
                    if (file_exists(__DIR__ . '/empty')) {
                        $empty = file_get_contents(__DIR__ . '/empty');
                        $empty = json_decode($empty, true);
                    } else {
                        $empty = [];
                    }
                    $empty[] = ['code' => $item, 'quarter' => $i];
                    $empty = json_encode($empty);
                    file_put_contents(__DIR__ . '/empty', $empty);
                }
                sleep(2.2);
            }
            $done[] = $item;
            $ephemeral = [
                'ephemeral_data' => json_encode($done)
            ];
            StockCrawlerCycle::query()->where(['id' => 7])->update($ephemeral);
            print_r("{$where}<>{$item}\t");
            sleep(3.3);
//            for ($i = $lastYearQuarter; $i > $currentQuarter; $i--) {
//                $urls[] = "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/{$item['code']}.phtml?year={$lastYear}&jidu={$i}";
//            }
//            $data = $this->multiple($urls);
        }
    }
}
