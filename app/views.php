<?php
class views
{
    public function getUserData(Model_CurrentPlayer $player)
    {
        $fleets = $player->getFleetsData();
        $fleets_data = array();
        foreach ($fleets as $fleet) /* @var $fleet Model_Fleet */
        {
            $fleets_data[] = array(
                'id' => $fleet->id,
                'position' => $fleet->position,
                'move_mode' => $fleet->move_mode,
                
            );
        }
        return array(
            'id'=>$player->id,
            'name'=>$player->name,
            'coins'=>$player->coins,
            'crew_limit'=>$player->crew_limit,
            'crews'=>$player->crews,
            'fleets'=>$player->getFleetsData()
        );
    }
}

