<?php
ob_start();
require_once(__DIR__.DIRECTORY_SEPARATOR.'includes\Env.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'includes\DB.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'includes\Action.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'includes\ApiLimit.php');
use Kvnc\Env;
use Kvnc\DB;
use Kvnc\Action;
use Kvnc\ApiLimit;
set_time_limit(180);
(new Env(__DIR__ . '/.env'))->init();

$env = getenv();
$settings['database'] = array(
    'host'      =>  $env['DATABASE_HOST'],
    'type'      =>  $env['DATABASE_DRIVER'],
    'username'  =>  $env['DATABASE_USER'],
    'database'  =>  $env['DATABASE_DBNAME'],
    'password'  =>  $env['DATABASE_PASSWORD'],
    'port'      =>  $env['DATABASE_PORT'],
    'charset'   =>  $env['DATABASE_CHARSET'],
    'prefix'    =>  $env['DATABASE_PREFIX'],
    'collation' =>  $env['DATABASE_COLLATION'],
);

$db  = new DB($settings['database']);  
$limitter = new ApiLimit(30,60);
$action = new Action($db);
