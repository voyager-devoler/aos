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
        $fleet = new Model_Fleet($mode_info['id']);
        return $fleet->setMoveMode($mode_info['move_mode']);
    }
    
    public function getShipName()
    {
        $names = explode("\n", file_get_contents('../ships.txt'));
        return trim($names[mt_rand(0, count($names)-1)]);
    }
    
    public function setCaptureMode(array $capture_info) // fleet_id, mode
    {
        $fleet = new Model_Fleet($capture_info['id']);
        return $fleet->setCaptureMode($capture_info['capture_mode']);
    }
    
    public function setFireTactics(array $fire_info)
    {
        $ship = new Model_Ship($fire_info['id']);
        return $ship->setFireTactic($fire_info['fire_tactic']);
    }
    
    public function setLine(array $line_info)
    {
        $ship = new Model_Ship($line_info['id']);
        return $ship->setLine($line_info['line']);
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
        $fleets_id = dbLink::getDB()->selectCol('select fleets.id as ARRAY_KEY, name from fleets join players on fleets.player_id=players.id');
        $fleets = [];
        foreach ($fleets_id as $fid=>$pname)
        {
            $fleets[] = ['fleet'=>new Model_Fleet($fid),'pname'=>$pname];
        }
        return $fleets;
    }
    
    public function getUserData()
    {
        return Model_CurrentPlayer::getInstance();
    }
    
    public function repairShip($ship)
    {
        $ship = new Model_Ship($ship['id']);
        return $ship->repair();
    }
    
    public function embarkShip($embark_info) // ship_id, cargo, res
    {
        return Model_CurrentPlayer::getInstance()->embarkShip($embark_info['ship_id'], $embark_info['cell'], $embark_info['resource']);
    }
    
    public function sellShip($ship)
    {
        return Model_CurrentPlayer::getInstance()->sellShip($ship['id']);
    }
    
    public function collectCoins()
    {
        return Model_CurrentPlayer::getInstance()->collectCoins();
    }
    
    public function getBattle($bid)
    {
        return Model_BattleLog::getInstance()->getBattleData($bid['id']);
    }
    
    public function staffShip($ship)
    {
        return Model_CurrentPlayer::getInstance()->staffPrizeShip($ship['id']);
    }
    
    public function addShips2NewFleet($ships_id)
    {
        return Model_CurrentPlayer::getInstance()->createNewFletFromOld($ships_id['ids']);
    }
    
    public function mergeFleets($fleets_id)
    {
        return Model_CurrentPlayer::getInstance()->mergeFleets($fleets_id['ids']);
    }
    
    public function markAsRead($message)
    {
        return Model_CurrentPlayer::getInstance()->markMessageAsRead($message['id']);
    }
    
    public function getIslandOwner($island)
    {
        $owner = dbLink::getDB()->selectRow('select player_id, name from events as e join players as p on e.player_id=p.id where e.type="res_prod" and e.processed=0 and e.obj_id=?d', $island['id']);
        if (empty($owner))
            return ['player_id'=>0,'name'=>'nobody'];
        return $owner;
    }
    
    public function increaseMaxCrews()
    {
        return Model_CurrentPlayer::getInstance()->increaseMaxCrews();
    }
    
    public function equipCell($equip_data)
    {
        return Model_CurrentPlayer::getInstance();
    }
}

