<?php
set_time_limit ( 5 );
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

// Главный конфиг проекта
define('ROOT_DIR', dirname(__FILE__).'/'); // Корневая папка для файлов проекта
define('LOG_DIR', ROOT_DIR . 'log/'); // Папка для логов
define('TIMEFORMAT','Y-m-d H:i:s');

$paths = array(
     '.',
     ROOT_DIR . 'libs/',
     ROOT_DIR . 'app/'
);
set_include_path(implode(PATH_SEPARATOR, $paths));

ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR.'/php-errors.log');
ini_set('html_errors', 1);

if (strpos($_SERVER['REQUEST_URI'], 'handler.php')!==FALSE) // или если ip = заданному где-то - то для данного юзера тоже включится логгер даже на боевом
{
    register_shutdown_function('devFinalizer');
    ob_start();
}


function __autoload($class_name)
{
    $path = str_replace("_", "/", $class_name);
    require_once $path.".php";
    return;
}

function devFinalizer()
{
    $response = ob_get_contents();
    ob_flush();
    dbLink::getDB()->query('INSERT INTO dev_logger (ip,post,response) VALUES(INET_ATON(?),?,?)',$_SERVER['REMOTE_ADDR'],file_get_contents('php://input'),$response);
}
?>
