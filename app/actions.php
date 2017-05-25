<?php
class ClientNotFatalException extends Exception {}

class InsufficientResourceException extends ClientNotFatalException {}

class actions
{
    public function setUsername($name)
    {
        return Model_CurrentPlayer::getInstance()->setRowValue('name', $name['name']);
    }
    
    public function createShip(array $ship_info)
    {
        return Model_CurrentPlayer::getInstance()->createNewShip($ship_info['type'], $ship_info['name'], $ship_info['equipments']);
    }
    
    public function createFleet(array $new_fleet_data)
    {
        return Model_CurrentPlayer::getInstance()->createFleet($new_fleet_data);
    }
    
    public function addShips2Fleet(array $ship_ids)
    {
        
    }
    
    public function createPath(array $path_info) // fleet_id, path - и для смены пути тоже эта команда
    {
        $fleet = $path_info['fleet_id'];
        if (!is_object($fleet))
        {
            $fleet = new Model_Fleet($fleet);
        }
        return $fleet->createPath($path_info['path']);
    }
    
    public function setMoveMode(array $mode_info) // fleet_id, mode
    {
        
    }
    
    public function setCaptureMode(array $capture_info) // fleet_id, mode
    {
        
    }
    
    public function getHullTypes()
    {
        return Model_ShipTypes::getInstance()->getAllHullsData();
    }
    
    public function getEquipments()
    {
        return Model_Equipments::getInstance()->getAllEquipmentsData();
    }
    
    public function getSettings()
    {
        return Model_Settings::get()->getAll();
    }

    public function getMap()
    {
        return Model_Map::getAllMap();
    }
    
    public function getResources()
    {
        return dbLink::getDB()->select('select * from resources');
    }
    
    public function getFleets()
    {
        
    }
    
    public function getUserData()
    {
        return Model_CurrentPlayer::getInstance();
    }
    
    public function repairShip($ship_id)
    {
        
    }
    
    public function embarkShip($embark_info) // ship_id, cargo, island
    {
        
    }
    
    public function sellShip($ship_id)
    {
        
    }
    
    public function collectCoins()
    {
        
    }
}

