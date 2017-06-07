<?php

class Model_Ship extends Model_Abstract
{
    public $id;
    public $player_id;
    public $fleet_id;
    public $hull_type;
    public $equipments;
    public $hull_strength;
    public $gunpowder_stock;
    public $prize_ship;
    public $fire_tactic;
    public $name;
    public $cargo;
    protected $_tablename = 'ships';
    
    public function __construct($ship_data)
    {
        if (is_array($ship_data))
        {
            if (!isset($ship_data['id']))
            {
                $this->_dbRawRowValues = $ship_data;
                $ship_data['id'] = $this->insertRow();
            }
            $this->_dbRawArray = $ship_data;
            $this->_assoc2properties();
        }
        else
        {
            parent::__construct($ship_data);
        }
        $this->equipments = explode(',',$this->equipments);
        $cargo_data = [];
        if (!empty($this->cargo))
        {
            $this->cargo = explode(';',$this->cargo);
            foreach($this->cargo as $cargo)
            {
                list($cell,$res,$num) = explode(',',$cargo);
                $cargo_data[] = ['cell'=>$cell,'resource'=>$res,'count'=>$num];
            }
        }
        $this->cargo = $cargo_data;
    }
    
    public function getCargoTypeByCell($cell)
    {
        foreach ($this->cargo as $cargo)
        {
            if ($cargo['cell'] == $cell)
                return $cargo['resource'];
        }
        return 0;
    }
    
    public function getCargQuantityByCell($cell)
    {
        foreach ($this->cargo as $cargo)
        {
            if ($cargo['cell'] == $cell)
                return $cargo['count'];
        }
        return 0;
    }
    
    public function clearCargoInCell($cell)
    {
        foreach ($this->cargo as $id=>$cargo)
        {
            if ($cargo['cell'] == $cell)
            {
                unset ($this->cargo[$id]);
                $this->setRowValue('cargo', $this->packCargoData(), true);
                return true;
            }
        }
        return false;
    }
    
    protected function packCargoData()
    {
        $cdata = [];
        foreach ($this->cargo as $cargo)
        {
            $cdata[] = $cargo['cell'].','.$cargo['resource'].','.$cargo['count'];
        }
        return implode(';',$cdata);
    }
    
    public function getFireRate($useGunepowderStock = false)
    {
        $fire_rate = 0;
        foreach ($this->equipments as $equipment)
        {
            $fire_rate += Model_Equipments::getInstance()->getFireRate($equipment);
        }
        if ($useGunepowderStock && $fire_rate > $this->gunpowder_stock)
        {
            $fire_rate = $this->gunpowder_stock;
        }
        return $fire_rate;
    }
    
    public function fire()
    {
        $firepower = 0;
        $critical = 0;
        foreach ($this->equipments as $equipment)
        {
            $fire = Model_Equipments::getInstance()->getFireRate($equipment);
            if ($fire > $this->gunpowder_stock)
                $fire = $this->gunpowder_stock;
            $this->gunpowder_stock -= $fire;
            if ($fire > 0)
            {
                $crit = mt_rand(0, 999);
                if ($crit <= Model_Equipments::getInstance()->getCriticalRate($equipment)*10)
                {
                    $critical ++;
                    $fire = 0;
                }
            }
            $firepower += $fire;
        }
        return array ($firepower, $critical);
    }


    public function getSpeed()
    {
        return Model_ShipTypes::getInstance()->getSpeed($this->hull_type);
    }
    
    public function getLowSinkingAbility()
    {
        return (bool)Model_ShipTypes::getInstance()->getLowSinkingAbility($this->hull_type);
    }
    
    public function canFire()
    {
        return $this->hull_strength>0 && $this->gunpowder_stock>0;
    }
    
    public function prepare4Battle()
    {
        $this->gunpowder_stock = 0;
        foreach ($this->equipments as $equipment)
        {
            $this->gunpowder_stock += Model_Equipments::getInstance()->getGunpowderRate($equipment);
        }
    }
    
    public function applyDamage($damage, $critical)
    {
        $crits = array();
        $this->hull_strength -= $damage;
        if ($this->hull_strength < 0)
            $this->hull_strength = 0;
        if ($this->hull_strength>0)
        {
            for ($i=0;$i<$critical;$i++)
            {
                $ekey = array_rand($this->equipments);
                if (Model_Equipments::getInstance()->getGunpowderRate($this->equipments[$ekey])>0) // CRASH!BOOM!BANG!
                {
                    $this->hull_strength = 0;
                }
                else
                {
                    $this->equipments[$ekey] = 0;
                }
                $crits[] = $ekey;
            }
        }
        return array('damage' => $damage, 'criticals' => $crits);
        
    }
    
    public function saveState()
    {
        if ($this->hull_strength == 0)
        {
            $this->kill();
            return false;
        }
        $this->setRowValue('hull_strength', $this->hull_strength);
        $this->setRowValue('equipments', implode(',',$this->equipments));
        $this->updateRow();
        return true;
    }
    
    public function kill()
    {
        $this->deleteRow();
        Model_CurrentPlayer::getInstance()->increaseCurrentCrews(-Model_ShipTypes::getInstance()->getCrew($this->hull_type));
    }
    
    public function repair()
    {
        if ($this->fleet_id != 0)
            throw new ClientNotFatalException("Can't repair this ship");
        $repair_cost = (int)(Model_ShipTypes::getInstance()->getCost($this->hull_type) * Model_Settings::get()->ship_repair_cost / 100);
        Model_CurrentPlayer::getInstance()->increaseCoins(-$repair_cost);
        $this->setRowValue('hull_strength', Model_ShipTypes::getInstance()->getHull($this->hull_type));
        $this->updateRow();
    }
  
}

