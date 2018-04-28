<?php


use Phinx\Migration\AbstractMigration;

class StockCrawlerCycleMigration extends AbstractMigration
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
        $this->table('stock_stock_crawler_cycles')
            ->addColumn('ephemeral_data', 'text')
            ->addColumn('year', 'integer', ['limit' => 4])
            ->addColumn('status', 'boolean')
            ->create();
    }
}
