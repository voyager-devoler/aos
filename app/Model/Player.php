<?php
class Model_Player extends Model_Abstract
{
    public $id;
    public $name;
    public $coins;
    public $clan_id;
    public $crews;
    protected $_tablename = 'players';
    
    public function createMessage($text, $time = 0, $position = '', $battle_id = 0)
    {
        return dbLink::getDB()->query('insert into messages (player_id, time, text, position, battle_id) values (?d, ?, ?, ?, ?d)', $this->id, $time, $text, $position, $battle_id);
    }
    
    public function makeNewCoinsProductionEvent($island_id = 0, $remains = 0)
    {
        if ($island_id == 0)
            $finish_time = date('Y-m-d H:i:s', strtotime("+ ".(Model_Settings::get()->increase_coins_limit/Model_Settings::get()->increase_coins_per_minute)."minutes"));
        else
            $finish_time = '2020-01-01 00:00:00';
        dbLink::getDB()->query('insert into events (player_id, type, obj_id, start_time, finish_time, params) values (?d, "res_prod", ?d, NOW(), ?, ?d)', $this->id, $island_id, $finish_time, $remains);
    }
    
    public function increaseCoins(int $coins)
    {
        if ($this->coins + $coins < 0)
            throw new InsufficientResourceException('Insufficient coins');
        $this->setRowValue('coins', $this->coins + $coins);
        if (__CLASS__ != 'Model_CurrentPlayer')
            $this->updateRow();
        return $this->coins;
    }
    
    public function increaseCurrentCrews(int $crews)
    {
        if ($this->crews + $crews > $this->crew_limit)
            throw new InsufficientResourceException('Crew limit reached');
        $this->setRowValue('crews', $this->crews + $crews);
        if (__CLASS__ != 'Model_CurrentPlayer')
            $this->updateRow();
        return $this->crews;
    }
}

