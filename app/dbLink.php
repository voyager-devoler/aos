<?php

class SQLException extends Exception {}

/**
 * Класс для коннекта к базе
 */
require_once 'DbSimple/Generic.php';
class dbLink {
    static protected $db=null;

    static protected function _getConnectLine()
    {
        return config_settings::getDBConnectString();
    }

    /**
     *
     * @return DbSimple_Mysql 
     */
    public static function getDB()
    {
            if (is_null(static::$db))
            {
                    static::$db=DbSimple_Generic::connect(static::_getConnectLine());
                    static::$db->setErrorHandler('databaseErrorHandler');
                    //self::$db->setLogger('myLogger');
                    //static::$db->query('SET CHARACTER SET utf8');//кодировочку не забываем...
                    static::$db->query('SET NAMES "utf8"');
                    static::$db->query('SET time_zone = "+'.(2+date('I')).':00"');
            }
            return static::$db;
    }

    private function  __construct()  {}

    private function __clone() {}
}

// Код обработчика ошибок SQL.
function databaseErrorHandler($message, $info)
{
    // Если использовалась @, ничего не делать.
    if (!error_reporting()) return;
    // Выводим подробную информацию об ошибке.
    throw new SQLException('SQL Error: '.$message."\n".print_r($info, true), 502);
//    echo "SQL Error: $message<br><pre>";
//    print_r($info);
//    echo "</pre>";
//    exit();
}

function myLogger($db, $sql)
{
  // Находим контекст вызова этого запроса.
  $caller = $db->findLibraryCaller();
  $tip = "at ".@$caller['file'].' line '.@$caller['line'];

  echo "<xmp title=\"$tip\">";
  print_r($sql);
  echo "</xmp>";
}
?>
