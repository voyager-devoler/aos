<?php
class eventsHandler
{
    
    public function execute()
    {
        $events = dbLink::getDB()->select('select *, id as ARRAY_KEY from events where finish_time<=? and processed = 0 order by finish_time desc',date('Y-m-d H:i:s'));
        $events_check = array_column($events, 'processed', 'id');
        foreach ($events as $event)
        {
            if ($event['type'] == 'fleet_move')
            {
                if ($events_check[$event['id']] != 0)
                    continue;
                $fleet = new Model_Fleet($event['obj_id']);
                $new_position = $event['params'];
                $other_fleets_id = dbLink::getDB()->selectCol('select id from fleets where position=? order by move_mode,time_for_position', $new_position);
                $battleResult = null;
                foreach ($other_fleets_id as $other_fleet_id)
                {
                    $other_fleet = new Model_Fleet($other_fleet_id);
                    if ($other_fleet->player_id == $fleet->player_id || ($fleet->move_mode=='move' && $other_fleet->move_mode=='move'))
                        continue;
                    
                    Model_BattleLog::getInstance()->initBattle($fleet, $other_fleet, $event['finish_time'], $new_position);
                    $battleResult = $this->makeBattle($fleet, $other_fleet);
                    $player = new Model_Player($fleet->player_id);
                    $other_player = new Model_Player($other_fleet->player_id);
                    $player->createMessage("{$player->name}! Your fleet attacks {$other_player->name}'s fleet in the position ({$new_position})");
                    $other_player->createMessage("{$other_player->name}! Your fleet was attaked by {$player->name}'s fleet in position ({$new_position})");
                    if ($battleResult == 'ALoose')
                    {
                        $player->createMessage("{$player->name}! Your fleet was stoped in the position ({$new_position}) after battle with {$other_player->name}'s fleet");
                    }
                    if ($battleResult == 'ALost' || $battleResult == 'ALoose')
                    {
                        $looser_fleet = $fleet;
                    }
                    elseif ($battleResult == 'AWin')
                    {
                        $looser_fleet = $other_fleet;
                        $other_player->createMessage("Your fleet was defeated in ({$new_position}) by {$player->name}");
                    }
                    $looser_fleet->deleteCurrentPath();
                    if (dbLink::getDB()->query('update events set processed = 2 where type="fleet_move" and obj_id = ?d and id!=?d and processed=0',$looser_fleet->id, $event['id'])>0)
                    { // удалить необработанные события движения для проигравшего флота (если что-то удалилось из базы)
                        foreach ($events as $event4del)
                        {
                            if ($event4del['obj_id']==$looser_fleet->id)
                                $events_check[$event4del['id']] = 1;
                        }
                    }
                    if ($battleResult == 'ALost' || $battleResult == 'ALoose')
                        break;
                }
                if (is_null($battleResult) || $battleResult == 'AWin')
                {
                    $fleet->move($new_position, $event['finish_time']);
                    dbLink::getDB()->query('update events set processed = 1 where id=?d',$event['id']);
                }
            }
            elseif ($event['type'] == 'res_prod')
            {
                
            }
        }
    }
    
    public function makeBattle(Model_Fleet $attacker_fleet,Model_Fleet $defender_fleet)
    {
        while ($attacker_fleet->canFire() && $defender_fleet->canFire())
        {
            $attackerVolley = new Model_Volley($attacker_fleet);
            $defenderVolley = new Model_Volley($defender_fleet);
            Model_BattleLog::getInstance()->addVolley($attacker_fleet->applyDamage($defenderVolley), $defender_fleet->applyDamage($attackerVolley));
        }
        $close_combat_attacker = null;
        $close_combat_defender = null;
        if ($attacker_fleet->canFire())
        {
            $close_combat_attacker = $attacker_fleet;
            $close_combat_defender = $defender_fleet;
        }
        elseif ($defender_fleet->canFire())
        {
            $close_combat_attacker = $defender_fleet;
            $close_combat_defender = $attacker_fleet;
        }
        if (!is_null($close_combat_attacker))
        {
            if ($close_combat_attacker->capture_mode == 1) // пробуем захватить
            {
                if ($close_combat_attacker->getAllCrewsNum() > $close_combat_defender->getAllCrewsNum())
                {
                    Model_BattleLog::getInstance()->addCapture($close_combat_attacker, $close_combat_attacker->captureShips($close_combat_defender));
                }
            }
            else // пробуем добить
            {
                while ($close_combat_attacker->canFire() && count($close_combat_defender->getAliveShips())>0)
                {
                    $volley = new Model_Volley($close_combat_attacker);
                    $empty_volley = new Model_Volley($close_combat_defender);
                    Model_BattleLog::getInstance()->addVolley($close_combat_attacker->applyDamage($empty_volley),$close_combat_defender->applyDamage($volley));
                }
            }
        }
        $attacker_fleet->saveShipsState();
        $defender_fleet->saveShipsState();
        if (count($attacker_fleet->getAliveShips()) == 0)
            $attacker_fleet->deleteRow();
        if (count($defender_fleet->getAliveShips()) == 0)
            $defender_fleet->deleteRow();
        Model_BattleLog::getInstance()->save();
        if (count($attacker_fleet->getAliveShips()) == 0)
            return 'ALost';
        if (count($defender_fleet->getAliveShips()) == 0)
            return 'AWin';
        return 'ALoose';
        
        // TODO: также нужно обработать захват оставшихся в живых но не стреляющих кораблей
        // более того, если присоединение к флоту захваченных кораблей приведет к снижению его скорости -
        // нужно будет пересчитать время для всех последующих событий перемещения
    }
}

