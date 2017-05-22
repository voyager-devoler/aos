<?php
class Model_Fleet extends Model_Abstract
{
    public $id;
    public $player_id;
    public $move_mode;
    public $capture_mode;
    public $position;
    /** @var Model_Ship|Array */
    protected $_ships;
    
    public function __construct($fleet_data) {
        if (is_array($fleet_data))
        {
            $this->_ships = $fleet_data['ships'];
            unset ($fleet_data['ships']);
            $this->_dbRawRowValues = $fleet_data;
            $fleet_id = $this->insertRow();
            foreach ($this->_ships as $ship) /* @var $ship Model_Ship */
            {
                $ship->setRowValue('fleet_id', $fleet_id);
                $ship->updateRow();
            }
        }
        else
        {
            parent::__construct($fleet_data);
            $ships_data = dbLink::getDB()->select('select * from ships where fleet_id = ?d', $this->id);
            foreach ($ships_data as $ship_data)
            {
                $this->_ships[$ship_data['id']] = new Model_Ship($ship_data);
            }
        }
    }
    
    public function getSpeed()
    {
        $speed = 100;
        foreach ($this->_ships as $ship) /* @var $ship Model_Ship */
        {
            if ($ship->getSpeed() < $speed)
                $speed = $ship->getSpeed();
        }
        return $speed;
    }
    
    public function getLowSinkingAbility()
    {
        foreach ($this->_ships as $ship)
        {
            if (!$ship->getLowSinkingAbility())
                return false;
        }
        return true;
    }
    
    public function deleteCurrentPath()
    {
        dbLink::getDB()->query('delete from events where type="fleet_move" and obj_id=?d',$this->id);
    }
    
    public function createPath(array $path)
    {
        $oneSquareTime = (int)(Model_Settings::get()->base_movement_time/$this->getSpeed());
        $time = date('Y-m-d H:s:i');
        $prev_cell = $this->position;
        foreach ($path as $cell)
        {
            if (!Model_Settings::get()->isConnected($prev_cell, $cell))
            {
                throw new ClientNotFatalException('Wrong path');
            }
            $cellType = Model_Map::getCellType($cell);
            if ($cellType == 'land')
            {
                throw new ClientNotFatalException("Can't build a path through the land");
            }    
            if ($cellType == 'shallow' && !$this->getLowSinkingAbility())
            {
                throw new ClientNotFatalException('Wrong path for this fleet');
            }
            $prev_cell = $cell;
        }
        $this->deleteCurrentPath();
        foreach ($path as $cell)
        {
            $next_time = strtotime($time." + ".$oneSquareTime." seconds");
            dbLink::getDB()->query('insert into events (type, player_id, obj_id, start_time, finish_time, params) values ("fleet_move",?d,?,?,?)',  Model_CurrentPlayer::getInstance()->id,$this->id,$time,$next_time,$cell);
            $time = $next_time;
        }
        return count($path)*$oneSquareTime;
    }
    
    public function canFire()
    {
        $canfire = false;
        foreach ($this->_ships as $ship)
        {
            if ($ship->canFire())
                return true;
        }
    }
    
    public function getShips()
    {
        return $this->_ships;
    }
    
    public function getAliveShips()
    {
        $alive = array();
        foreach ($this->_ships as $ship)
        {
            if ($ship->hull_strength > 0)
                $alive[] = $ship;
        }
        return $alive;
    }
    
    /**
     * 
     * @return array of Model_Ship
     */
    protected function _getTargetShips()
    {
        $maxfirepower = -1;
        $maxhull = 0;
        $minhull = null;
        $powerfull_ship = null;
        $strongest_ship = null;
        $weakest_ship = null;
        foreach ($this->_ships as $ship)
        {
            if ($maxfirepower < $ship->getFireRate(true))
            {
                $maxfirepower = $ship->getFireRate(true);
                $powerfull_ship = $ship;
            }
            if ($maxhull < $ship->hull_strength)
            {
                $maxhull = $ship->hull_strength;
                $strongest_ship = $ship;
            }
            if (!is_null($minhull) && $minhull > $ship->hull_strength && $ship->hull_strength > 0)
            {
                $minhull = $ship->hull_strength;
                $weakest_ship = $ship;
            }
        }
        return array ($powerfull_ship, $strongest_ship, $weakest_ship);
    }


    public function applyDamage(Model_Volley $volley)
    {
        list ($powerfull_ship, $strongest_ship, $weakest_ship) = $this->_getTargetShips(); 
        $powerfull_ship->applyDamage($volley->damage4powerful, $volley->crit4powerfull);
        $strongest_ship->applyDamage($volley->damage4strongest, $volley->crit4strongest);
        $weakest_ship->applyDamage($volley->damage4weakest, $volley->crit4weakest);
        $all_num = count($this->getAliveShips());
        $critical_ships_id = array();
        for ($i=0; $i<$volley->crit4all; $i++)
        {
            $ship = $this->getAliveShips()[array_rand($this->getAliveShips())];
            $critical_ships_id[$ship->id]++;
        }
        foreach ($this->_ships as $ship)
        {
            if (isset($critical_ships_id[$ship->id]))
                $critical = $critical_ships_id[$ship->id];
            else
                $critical = 0;
            $volley->result_data[$ship->id] = $ship->applyDamage((int)$volley->damage4all/$all_num, $critical);
        }
    }
    
    public function getShipsDataAsArray()
    {
        $data = array();
        foreach ($this->_ships as $ship)
        {
            
        }
    }
    
    public function saveShipsState()
    {
        foreach ($this->_ships as $ship) /* @var $ship Model_Ship */
        {
            if (!$ship->saveState())
                unset ($this->_ships[$ship->id]);
        }
    }
}
