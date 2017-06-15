<?php
class Model_Fleet extends Model_Abstract
{
    public $id;
    public $player_id;
    public $move_mode;
    public $capture_mode;
    public $position;
    public $time_for_position;
    /** @var Model_Ship|Array */
    protected $_ships;
    protected $_path = [];
    protected $_tablename = 'fleets';
    
    public function __construct($fleet_data) {
        if (is_array($fleet_data))
        {
            $ships = $fleet_data['ships'];
            $this->position = $fleet_data['position'];
            unset ($fleet_data['ships']);
            $this->_dbRawRowValues = $fleet_data;
            $this->id = $this->insertRow();
            foreach ($ships as $ship) /* @var $ship Model_Ship */
            {
                $this->addShip($ship);
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
            //if ($this->player_id == Model_CurrentPlayer::getInstance()->id)
            {
                $this->_path = dbLink::getDB()->select('select id,params as position, finish_time as arrived from events where obj_id = ?d and type="fleet_move"',$this->id);
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
        dbLink::getDB()->query('update events set processed=2 where type="fleet_move" and obj_id=?d',$this->id);
    }
    
    public function createPath(array $path)
    {
        $oneSquareTime = (int)(Model_Settings::get()->base_movement_time/$this->getSpeed());
        $time = date('Y-m-d H:i:s');
        $prev_cell = $this->position;
        foreach ($path as $cell)
        {
            if (!Model_Settings::get()->isConnected($prev_cell, $cell))
            {
                throw new ClientNotFatalException('Wrong path: '.$prev_cell.'->'.$cell);
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
            $next_time = date('Y-m-d H:i:s',strtotime($time." + ".$oneSquareTime." seconds"));
            $this->_path[] = [
                'id'=>dbLink::getDB()->query('insert into events (type, player_id, obj_id, start_time, finish_time, params) values ("fleet_move",?d,?,?,?,?)',  Model_CurrentPlayer::getInstance()->id,$this->id,$time,$next_time,$cell),
                'position'=>$cell,
                'arrived'=>$next_time
                    ];
            $time = $next_time;
        }
        return count($path)*$oneSquareTime;
    }
    
    public function canFire()
    {
        foreach ($this->_ships as $ship)
        {
            if ($ship->canFire())
                return true;
        }
        return false;
    }
    
    public function getShips()
    {
        return $this->_ships;
    }
    
    public function setMoveMode($mode)
    {
        $this->setRowValue('move_mode', $mode);
        return $this->updateRow();
    }
    
    public function setCaptureMode($mode)
    {
        $this->setRowValue('capture_mode', $mode);
        return $this->updateRow();
    }
    
    /**
     * возвращает массив живых кораблей текущей активной линии
     * @return array|Model_Ship
     */
    public function getAliveShips()
    {
        $alive = array();
        foreach ($this->_ships as $ship)
        {
            if ($ship->hull_strength > 0 && $ship->line == 1)
                $alive[] = $ship;
        }
        if (empty($alive))
        {
            foreach ($this->_ships as $ship)
            {
                if ($ship->hull_strength > 0)
                    $alive[] = $ship;
            }
        }
        return $alive;
    }
    
    /**
     * 
     * @return array|Model_Ship
     */
    protected function _getTargetShips()
    {
        $maxfirepower = -1;
        $maxhull = 0;
        $minhull = 1000000;
        $powerfull_ship = null;
        $strongest_ship = null;
        $weakest_ship = null;
        foreach ($this->getAliveShips() as $ship)
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
            if ($minhull > $ship->hull_strength)
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
        $volley->result_data['powerful'][$powerfull_ship->id] = $powerfull_ship->applyDamage($volley->damage4powerful, $volley->crit4powerfull);
        $volley->result_data['strongest'][$strongest_ship->id] = $strongest_ship->applyDamage($volley->damage4strongest, $volley->crit4strongest);
        $volley->result_data['weakest'][$weakest_ship->id] = $weakest_ship->applyDamage($volley->damage4weakest, $volley->crit4weakest);
        $all_num = count($this->getAliveShips());
        $critical_ships_id = array();
        for ($i=0; $i<$volley->crit4all; $i++)
        {
            $ship = $this->getAliveShips()[array_rand($this->getAliveShips())];
            if (!isset($critical_ships_id[$ship->id]))
                $critical_ships_id[$ship->id] = 1;
            else
                $critical_ships_id[$ship->id]++;
        }
        foreach ($this->_ships as $ship)
        {
            if (isset($critical_ships_id[$ship->id]))
                $critical = $critical_ships_id[$ship->id];
            else
                $critical = 0;
            $volley->result_data['all'][$ship->id] = $ship->applyDamage((int)($volley->damage4all/$all_num), $critical);
        }
        return $volley;
    }
    
    public function saveShipsState()
    {
        foreach ($this->_ships as $ship) /* @var $ship Model_Ship */
        {
            if (!$ship->saveState())
                unset ($this->_ships[$ship->id]);
        }
    }
    
    public function getPath()
    {
        return $this->_path;
    }
    
    public function prepare4Battle()
    {
        foreach ($this->_ships as $ship)
        {
            $ship->prepare4Battle();
        }
    }
    
    public function move(string $position, string $time)
    {
        if ($position == Model_Settings::get()->portal_out)
        {
            $this->move2Portal();
            return false;
        }
        $this->setRowValue('position', $position);
        $this->setRowValue('time_for_position', $time);
        $this->updateRow();
        return $position;
    }
    
    public function move2Portal()
    {
        foreach ($this->getShips() as $ship)
        {
            $ship->fleet_id = 0;
            $need_update_cargo = false;
            $coins = 0;
            foreach ($ship->equipments as $id=>$equipment)
            {
                if ($equipment == 6 && $ship->getCargoTypeByCell($id) == 1)
                {
                    $coins += $ship->getCargQuantityByCell($id);
                    $ship->clearCargoInCell($id);
                    $need_update_cargo = true;
                }
            }
            if ($need_update_cargo)
            {
                $ship->updateRow();
                $player = new Model_Player($this->player_id);
                $player->increaseCoins($coins);
            }
        }
        dbLink::getDB()->query('update ships set fleet_id = 0 where fleet_id = ?d', $this->id);
        $this->deleteRow();
    }
    
    public function getFleetData()
    {
        return [
            'id'=>$this->id,
            'player_id'=>$this->player_id,
            'position'=>$this->position,
            'move_mode'=>$this->move_mode,
            'capture_mode'=>$this->capture_mode,
            'ships'=>array_values($this->getShips())
        ];
    }
    
    public function cloneShips()
    {
        $ships = $this->_ships;
        unset($this->_ships);
        foreach($ships as $id=>$ship)
        {
            $this->_ships[$id] = clone($ship);
        }
    }
    
    public function getAllCrewsNum()
    {
        $crews = 0;
        foreach ($this->_ships as $ship)
        {
            if ($ship->hull_strength > 0 && !$ship->prize_ship)
            {
                $crews += Model_ShipTypes::getInstance()->getCrew($ship->hull_type);
            }
        }
        return $crews;
    }
    
    public function delCapturedShipFromFleet($id)
    {
        $this->_ships[$id]->kill(true);
        $this->delShipFromFleet($id);
    }
    
    public function delShipFromFleet($id)
    {
        unset($this->_ships[$id]);
    }
    
    public function captureShips(Model_Fleet $enemy_fleet)
    {
        $cships = [];
        foreach($enemy_fleet->getAliveShips() as $ship)
        {
            $enemy_fleet->delCapturedShipFromFleet($ship->id); // это стоит делать до смены хозяина, т.к. не туда начислится освободившийся экипаж (потом надо будет переделать)
            $ship->setRowValues(['fleet_id'=>$this->id, 'prize_ship'=>1, 'player_id'=>$this->player_id]);
            $this->_ships[$ship->id] = $ship;
            $cships[] = $ship;
        }
        return $cships;
    }
    
    public function addShip(Model_Ship $ship)
    {
        $this->_ships[$ship->id] = $ship; 
        $ship->setRowValue('fleet_id', $this->id);
        $ship->updateRow();
    }
    
    public function addAllShipsFromOtherFleet(Model_Fleet $other_fleet)
    {
        foreach ($other_fleet->getShips() as $ship)
        {
            $other_fleet->delShipFromFleet($ship->id);
            $this->addShip($ship);
        }
    }
    
    public function getHullTypeIcon()
    {
        $hullCrews = [];
        foreach ($this->_ships as $ship)
        {
            if ($ship->prize_ship)
                $crew = 1;
            else
                $crew = Model_ShipTypes::getInstance()->getCrew($ship->hull_type);
            if (isset($hullCrews[$ship->hull_type]))
                $hullCrews[$ship->hull_type] += $crew;
            else 
                $hullCrews[$ship->hull_type] = $crew;
        }
        return array_keys($hullCrews, max($hullCrews))[0];
    }
}

