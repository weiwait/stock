<?php


use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class StockMarketMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->table('stock_stock_markets_2016')
            ->addColumn('stock_code', 'string', ['limit' => 20])
            ->addColumn('year', 'integer', ['limit' => 4])
            ->addColumn('quarter', 'integer', ['limit' => 1])
            ->addColumn('date', 'string', ['limit' => 20])
            ->addColumn('opening_price', 'float')
            ->addColumn('maximum_price', 'float')
            ->addColumn('closing_price', 'float')
            ->addColumn('minimum_price', 'float')
            ->addColumn('trading_stocks', 'integer')
            ->addColumn('transaction_amount', 'integer')
            ->create();
    }
}
