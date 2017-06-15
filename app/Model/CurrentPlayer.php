<?php
class Model_CurrentPlayer extends Model_Player
{
    public $crew_limit;
    /** @var Model_Ship|Array */
    protected $_ships = array();
    /** @var Model_Fleet|Array */
    protected $_fleets = array();
    protected static $_device_id = null;


    public static function init($device_id)
    {
        self::$_device_id = $device_id;
    }
    
    public static function isCreated() // пока обработчик событий не в отдельном скрипте...
    {
        return !is_null(self::$_device_id);
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
            $this->makeNewCoinsProductionEvent();
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
        $this->makeNewCoinsProductionEvent();
        return $this->increaseCoins($coins);
    }
    
    public function sellShip($id)
    {
        if (!isset($this->_ships[$id]))
            throw new ClientNotFatalException("Can't sell this ship");
        $ship = $this->_ships[$id];
        $cost = (int)(Model_ShipTypes::getInstance()->getCost($ship->hull_type)*$ship->hull_strength/Model_ShipTypes::getInstance()->getHull($ship->hull_type));
        foreach ($ship->equipments as $equipment)
        {
            if ($equipment!=0)
                $cost+=Model_Equipments::getInstance()->getCost($equipment);
        }
        $ship->kill();
        return $this->increaseCoins($cost);
    }
    
    public function staffPrizeShip($id)
    {
        if (!isset($this->_ships[$id]))
            throw new ClientNotFatalException("You don't have ship id ({$id})");
        $ship = $this->_ships[$id];
        if (!$ship->prize_ship)
            throw new ClientNotFatalException("This is not a prize ship (id:{$id})");
        $this->increaseCurrentCrews(Model_ShipTypes::getInstance()->getCrew($ship->hull_type));
        $ship->setRowValue('prize_ship', 0);
        $ship->updateRow();
        return true;
    }
    
    public function embarkShip($ship_id, $cell_id, $resource_id)
    {
        if (!isset($this->_ships[$ship_id]))
            throw new ClientNotFatalException('Something is wrong...');
        $ship = $this->_ships[$ship_id];
        $fleet = $this->_fleets[$ship->fleet_id];
        $point_data = Model_Settings::get()->getResPointByPosition($fleet->position);
        if ($point_data === false)
            throw new ClientNotFatalException('Your ship is not in resource island position');
        $last_collect = dbLink::getDB()->selectRow('select id, start_time, params from events where player_id=?d and type="res_prod" and processed=0 and obj_id=?d', $this->id, $point_data['id']);
        $coins = (int)((time()-strtotime($last_collect['start_time']))/60) * $point_data['res'] + (int)$last_collect['params'];
        $remains = 0;
        if ($coins > Model_Settings::get()->cell_capacity)
        {
            $remains = $coins - Model_Settings::get()->cell_capacity;
            $coins = Model_Settings::get()->cell_capacity;
        }
        $ship->addCargo2Cell($cell_id, 1, $coins);
        $ship->updateRow();
        dbLink::getDB()->query('update events set processed=1 where id=?d', $last_collect['id']);
        $this->makeNewCoinsProductionEvent($point_data['id'], $remains);
        return ['quantity'=>$coins, 'remains'=>$remains];
    }
    
    public function createNewFletFromOld(array $ships_id)
    {
        $fleet_id = null;
        $ships = [];
        foreach ($ships_id as $ship_id)
        {
            if (!isset($this->_ships[$ship_id]))
                throw new ClientNotFatalException("Incorrect ship id {$ship_id}");
            if (is_null($fleet_id))
                $fleet_id = $this->_ships[$ship_id]->fleet_id;
            elseif ($fleet_id != $this->_ships[$ship_id]->fleet_id)
                throw new ClientNotFatalException("All ships must be from the same fleet.");
            $ships[] = $this->_ships[$ship_id];
        }
        if (!isset($this->_fleets[$fleet_id]))
            throw new ClientNotFatalException("Incorrect fleet");
        $fleet = $this->_fleets[$fleet_id];
        foreach ($ships as $ship)
        {
            $fleet->delShipFromFleet($ship->id);
        }
        $newfleet = new Model_Fleet(array('player_id' => $this->id, 'position' => $fleet->position, 'ships' => $ships)); // новые fleet_id присвоятся кораблям автоматом
        $this->_fleets[$newfleet->id] = $newfleet;
        return $newfleet->id;
    }
    
    public function mergeFleets(array $fleets_id)
    {
        $position = null;
        foreach ($fleets_id as $fleet_id)
        {
            if (!isset($this->_fleets[$fleet_id]))
                throw new ClientNotFatalException("Incorrect fleet id {$fleet_id}");
            if (is_null($position))
            {
                $fleet = $this->_fleets[$fleet_id];
                $position = $fleet->position;
            }
            elseif($position != $this->_fleets[$fleet_id]->position)
                throw new ClientNotFatalException("All fleets must be in the same position.");
        }
        foreach ($fleets_id as $fleet_id)
        {
            if ($fleet_id == $fleet->id)
                continue;
            $fleet->addAllShipsFromOtherFleet($this->_fleets[$fleet_id]);
            $this->_fleets[$fleet_id]->deleteCurrentPath();
            $this->_fleets[$fleet_id]->deleteRow();
            unset($this->_fleets[$fleet_id]);
        }
        $fleet->deleteCurrentPath();
        return $fleet->id;
    }
      
    public function markMessageAsRead($id)
    {
        return dbLink::getDB()->query('update messages set `read` = 1 where id in (?a)', $id);
    }
    
    public function increaseMaxCrews()
    {
        $this->increaseCoins(-Model_Settings::get()->getIncCrewsCosts());
        return $this->setRowValue('crew_limit', $this->crew_limit + Model_Settings::get()->getIncCrewsNum());
    }
}


