<?php
class Model_ShipTypes
{
    protected $_types;
    protected static $_instance;

    /**
     * 
     * @return Model_ShipTypes
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
        $this->_types = dbLink::getDB()->select('select id as ARRAY_KEY, name, hull, cost, crew, cells, speed, low_sinking, visibility, can_use_gun24, can_use_big_gunpowder from ship_types'); 
    }
    
    public function getHull(int $type)
    {
        return $this->_types[$type]['hull'];
    }
    
    public function getName(int $type)
    {
        return $this->_types[$type]['name'];
    }
    
    public function getCost(int $type)
    {
        return $this->_types[$type]['cost'];
    }
    
    public function getCrew(int $type)
    {
        return $this->_types[$type]['crew'];
    }
    
    public function getCells(int $type)
    {
        return $this->_types[$type]['cells'];
    }
    
    public function getSpeed(int $type)
    {
        return $this->_types[$type]['speed'];
    }
    
    public function getLowSinkingAbility(int $type)
    {
        return $this->_types[$type]['low_sinking'];
    }
    
    public function getVisibility(int $type)
    {
        return $this->_types[$type]['visibility'];
    }
    
    public function getAllHullsData()
    {
        return $this->_types;
    }
}


