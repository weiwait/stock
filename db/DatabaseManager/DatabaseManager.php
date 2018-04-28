<?php
/**
 * Created by PhpStorm.
 * User: MT
 * Date: 17.10.18
 * Time: 15:33
 */

namespace db\DatabaseManager;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

class DatabaseManager
{
    public function __construct()
    {
        $capsule = new Manager();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => 'weiwait.top',
            'database'  => 'stock',
            'username'  => 'root',
            'password'  => 'ljingwei5433',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'stock_',
        ]);


        $capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally via static methods... (optional)
        $capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $capsule->bootEloquent();

        return $capsule;
    }
}