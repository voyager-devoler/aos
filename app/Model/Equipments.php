<?php
class Model_Equipments
{
    protected $_equipments;
    protected static $_instance;

    /**
     * 
     * @return Model_Equipments
     */
    public static function getInstance()
    {
        if (is_null(static::$_instance))
        {
            static::$_instance = new static;
        }
        return static::$_instance;
    }
    
    protected function __construct() {
        $this->_equipments = dbLink::getDB()->select('select id as ARRAY_KEY, name, cost, effect from equipments'); 
    }
    
    public function getName(int $type)
    {
        return $this->_equipments[$type]['name'];
    }
    
    public function getCost(int $type)
    {
        return $this->_equipments[$type]['cost'];
    }
    
    protected function _getEffectRate(int $type, $need)
    {
        if ($type == 0)
            return 0;
        $effects = $this->_equipments[$type]['effect'];
        $effects = explode(',',$effects);
        foreach ($effects as $effect)
        {
            list($etype,$epower) = explode(':',$effect);
            if ($etype == $need)
                return $epower;
        }
        return 0;
    }
    
    public function getFireRate(int $type)
    {
        return $this->_getEffectRate($type, 'fire');
    }
    
    public function getCriticalRate(int $type)
    {
        return $this->_getEffectRate($type, 'critical');
    }
    
    public function getGunpowderRate(int $type)
    {
        return $this->_getEffectRate($type, 'gunpowder');
    }
    
    public function getSelectTargetAbility(int $type)
    {
        return $this->_getEffectRate($type, 'select_target');
    }
    
    public function getAllEquipmentsData()
    {
        return $this->_equipments;
    }
}


