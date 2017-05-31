<?php
/**
 * Значения получаем по Settings::get()->variable
 *
 * @property string $island_2
 * @property string $island_1
 * @property string $portal_out
 * @property string $portal_in
 * @property int $cell_capacity
 * @property int $increase_coins_per_minute
 * @property int $increase_coins_limit
 * @property string $increase_crew_limit
 * @property int $start_coins
 * @property int $start_crew_limit
 * @property int $base_movement_time
 * @property int $ship_repair_cost
 * 
 */
class Model_Settings {
    private static $_item = null;
    private $_settingsData;

    private function  __construct()
    {
        $db = dbLink::getDB();
        $this->_settingsData = $db->selectCol('SELECT name as ARRAY_KEY, value FROM settings');
    }

    private function __clone()
    {
    }

    /**
     *
     * @return Model_Settings
     */
    public static function get()
    {
        if (is_null(self::$_item))
        {
            self::$_item = new self;
        }
        return self::$_item;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->_settingsData))
                throw new Exception ('Undefined setting name ['.$name.']');
        return $this->_settingsData[$name];
    }
    
    public function getIsland1Point()
    {
        $island = self::get()->island_1;
        list($point,$prod) = explode(':',$island);
        return explode(',',$point);
    }
    
        public function getIsland2Point()
    {
        $island = self::get()->island_2;
        list($point,$prod) = explode(':',$island);
        return explode(',',$point);
    }
    
    public function getPortalOutPoint()
    {
        return explode(',',self::get()->portal_out);
    }
    
    public function getPortalInPoint()
    {
        return explode(',',self::get()->portal_in);
    }
    
    public function isConnected($cell1,$cell2)
    {
        list($c1x, $c1y) = explode(',',$cell1);
        list($c2x, $c2y) = explode(',',$cell2);
        if ($c1y == $c2y && abs($c1x - $c2x) == 1)
            return true;
        $oddFactor = $c1y % 2;
        if (abs($c1y - $c2y) == 1)
        {
            if($c2x == $c1x - $oddFactor || $c2x == $c1x + 1 - $oddFactor)
            {
                return true;
            }
        }
        return false;
    }
    
    public function getAll()
    {
        return $this->_settingsData;
    }
}
?>


