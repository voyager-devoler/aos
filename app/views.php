<?php
class views
{
    public function getUserData(Model_CurrentPlayer $player)
    {
        $fleets = $player->getFleets();
        $fleets_data = array();
        foreach ($fleets as $fleet) /* @var $fleet Model_Fleet */
        {
            $fleets_data[] = array(
                'id' => $fleet->id,
                'position' => $fleet->position,
                'move_mode' => $fleet->move_mode,
                'capture_mode' => $fleet->capture_mode,
//                'path' => $fleet->getPath()
            );
        }

        return array(
            'id'=>$player->id,
            'name'=>$player->name,
            'coins'=>$player->coins,
            'crew_limit'=>$player->crew_limit,
            'crews'=>$player->crews,
            'fleets'=>$fleets_data,
            'ships'=>array_values($player->getAllShips())    
        );
    }
    
    public function getEquipments(array $equipment_data)
    {
        foreach ($equipment_data as $id => $equipment)
        {
            $effects = explode(',',$equipment['effect']);
            $formated = array();
            foreach ($effects as $effect)
            {
                list($prop,$val) = explode(':',$effect);
                $formated[$prop] = $val;
            }
            $equipment_data[$id]['effect'] = $formated;
        }
        return $equipment_data;
    }
    
    public function getFleets(array $fleets)
    {
        $resp = [];
        foreach ($fleets as $fleet) 
        {
            $resp[] = [
                'id'=>$fleet['fleet']->id,
                'player_id'=>$fleet['fleet']->player_id,
                'position'=>$fleet['fleet']->position,
                'player_name'=>$fleet['pname'],
                'icon'=>$fleet['fleet']->getHullTypeIcon()
            ];
        }
        return $resp;
    }
    
    public function getBattle(array $battle_data)
    {
        $battle_data['sides'] = json_decode($battle_data['sides'], true);
        $battle_data['fleets'] = json_decode($battle_data['fleets'], true);
        $battle_data['volleys'] = json_decode($battle_data['volleys'], true);
        return $battle_data;
    }
}

