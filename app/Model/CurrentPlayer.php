<?php
class Model_CurrentPlayer extends Model_Player
{
    public $coins;
    public $crew_limit;
    public $crews;
    /** @var Model_Ship|Array */
    protected $_ships = array();
    /** @var Model_Fleet|Array */
    protected $_fleets = array();
    protected static $_device_id = null;


    public static function init($device_id)
    {
        self::$_device_id = $device_id;
    }
    
    /**
     * 
     * @return Model_CurrentPlayer
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
    
    public function __construct() {
        if (is_null(self::$_device_id))
        {
            throw new Exception("Device id haven't initialised");
        }
        $this->id = dbLink::getDB()->selectCell('select id from '.$this->_tablename.' where device_id=?',self::$_device_id);
        if (empty($this->id))
        {
            $this->name = 'new_user';
            $this->coins = Model_Settings::get()->start_coins;
            $this->crew_limit = Model_Settings::get()->start_crew_limit;
            $this->setRowValues(array('name'=>$this->name, 'coins'=>$this->coins, 'crew_limit'=>$this->crew_limit,'device_id'=>self::$_device_id));
            $this->id = $this->insertRow();
            $first_ship_id = $this->createNewShip(1, '', array(6,6,6,6));
            $this->_makeNewCoinsProductionEvent();
            Model_Packet::setNeedRequest('setUsername');
        }
        parent::__construct($this->id);
        $ships = dbLink::getDB()->select('select * from ships where player_id = ?d', $this->id); // на самом деле нет смысла все корабли создавать 2 раза, но пока пусть будет так
        foreach ($ships as $ship)
        {
            $this->_ships[$ship['id']] = new Model_Ship($ship);
        }
        $fleets = dbLink::getDB()->selectCol('select id from fleets where player_id = ?d', $this->id);
        foreach ($fleets as $fleet)
        {
            $this->_fleets[$fleet] = new Model_Fleet($fleet);
        }
    }
    
    public function save()
    {
        if (is_array($this->_dbRawRowValues))
            $this->updateRow();
    }
    
    public function increaseCoins(int $coins)
    {
        if ($this->coins + $coins < 0)
            throw new InsufficientResourceException('Insufficient coins');
        $this->setRowValue('coins', $this->coins + $coins);
        return $this->coins;
    }
    
    public function increaseCurrentCrews(int $crews)
    {
        if ($this->crews + $crews > $this->crew_limit)
            throw new InsufficientResourceException('Crew limit reached');
        $this->setRowValue('crews', $this->crews + $crews);
        return $this->crews;
    }
    
    public function createNewShip($type,$name,array $equipments)
    {
        if (count($equipments) != Model_ShipTypes::getInstance()->getCells($type))
            throw new InsufficientResourceException('Wrong cells number for equipments');
        $this->increaseCurrentCrews(Model_ShipTypes::getInstance()->getCrew($type));
        $cost = Model_ShipTypes::getInstance()->getCost($type);
        foreach ($equipments as $equipment)
        {
            $cost+=Model_Equipments::getInstance()->getCost($equipment);
        }
        $this->increaseCoins(-$cost);
        if ($name=='')
        {
            $names = explode("\n", file_get_contents('../ships.txt'));
            $name = trim($names[mt_rand(0, count($names)-1)]);
        }
        $ship = new Model_Ship(array('player_id'=>$this->id,'hull_type'=>$type, 'hull_strength'=>Model_ShipTypes::getInstance()->getHull($type), 'equipments'=>implode(',',$equipments),'name'=>$name));
        return $ship->id;
    }
    
    public function createFleet(array $new_fleet_data)
    {
        $ships_data = dbLink::getDB()->select('select * from ships where id in (?a)', $new_fleet_data['ship_ids']);
        $ships = array();
        foreach ($ships_data as $ship_data)
        {
            $ships[$ship_data['id']] = new Model_Ship($ship_data);
        }
        $fleet = new Model_Fleet(array('player_id' => $this->id, 'position' => Model_Settings::get()->portal_in, 'ships' => $ships));
        $fleet->createPath($new_fleet_data['path']);
        return $fleet->id;
    }
    
    /**
     * 
     * @return Model_Fleet|Array
     */
    public function getFleets()
    {
        return $this->_fleets;    
    }
    
    public function getAllShips()
    {
        return $this->_ships;
    }
    
    public function collectCoins()
    {
        $last_collect = dbLink::getDB()->selectRow('select id, start_time from events where player_id=?d and type="res_prod" and processed=0 and obj_id=0', $this->id);
        if (empty($last_collect))
            throw new Exception ('The coins production events list is empty...');
        $coins = (int)((time()-strtotime($last_collect['start_time']))/60) * Model_Settings::get()->increase_coins_per_minute;
        if ($coins > Model_Settings::get()->increase_coins_limit)
            $coins = Model_Settings::get()->increase_coins_limit;
        dbLink::getDB()->query('update events set processed=1, finish_time=NOW() where player_id=?d and type="res_prod" and obj_id=0 and processed=0', $this->id);
        $this->_makeNewCoinsProductionEvent();
        return $this->increaseCoins($coins);
    }
    
    protected function _makeNewCoinsProductionEvent()
    {
        dbLink::getDB()->query('insert into events (player_id, type, obj_id, start_time, finish_time) values (?d, "res_prod", 0, NOW(), ?)', $this->id, date('Y-m-d H:i:s', strtotime("+ ".(Model_Settings::get()->increase_coins_limit/Model_Settings::get()->increase_coins_per_minute)."minutes")));
    }
    
    public function move2FleetPortal($fleet_id)
    {
        $fleet = $this->_fleets[$fleet_id];
        foreach ($fleet->getShips() as $ship)
        {
            $ship->fleet_id = 0;
            $need_update_cargo = false;
            foreach ($ship->equipments as $id=>$equipment)
            {
                if ($equipment == 6 && $ship->getCargoTypeByCell($id) == 1)
                {
                    $this->increaseCoins($ship->getCargQuantityByCell($id));
                    $ship->clearCargoInCell($id);
                    $need_update_cargo = true;
                }
            }
            if ($need_update_cargo)
                $ship->updateRow();
        }
        dbLink::getDB()->query('update ships set fleet_id = 0 where fleet_id = ?d', $fleet->id);
        $fleet->deleteRow();
    }
    
    public function sellShip($id)
    {
        if (!isset($this->_ships[$id]))
            throw new ClientNotFatalException("Can't sell this ship");
        $ship = $this->_ships[$id];
        $cost = (int)(Model_ShipTypes::getInstance()->getCost($ship->hull_type)*$ship->hull_strength/Model_ShipTypes::getInstance()->getHull($ship->hull_type));
        foreach ($ship->equipments as $equipment)
        {
            $cost+=Model_Equipments::getInstance()->getCost($equipment);
        }
        $ship->kill();
        return $this->increaseCoins($cost);
    }
}


