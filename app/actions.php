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
        $ships_data = dbLink::getDB()->select('select * from ships where id in (?a)', $new_fleet_data['ship_ids']);
        $ships = array();
        foreach ($ships_data as $ship_data)
        {
            $ships[$ship_data['id']] = new Model_Ship($ship_data);
        }
        $fleet = new Model_Fleet(array('player_id' => Model_CurrentPlayer::getInstance()->id, 'position' => Model_Settings::get()->portal_in, 'ships' => $ships));
        $this->createPath(array('fleet_id'=>$fleet, 'path'=>$new_fleet_data['path']));
        return $fleet->id;
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
        
    }
    
    public function getEquipments()
    {
        
    }
    
    public function getSettings()
    {
        return Model_Settings::get()->getAll();
    }

    public function getMap()
    {
        return Model_Map::getAllMap();
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

