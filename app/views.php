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
            );
        }
        $ships_in_port = array();
        foreach ($player->getAllShips() as $ship)
        {
            $ships_in_port[] = $ship;
        }
        return array(
            'id'=>$player->id,
            'name'=>$player->name,
            'coins'=>$player->coins,
            'crew_limit'=>$player->crew_limit,
            'crews'=>$player->crews,
            'fleets'=>$fleets_data,
            'ships'=>$ships    
        );
    }
}

