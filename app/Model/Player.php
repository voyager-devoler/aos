<?php
class Model_Player extends Model_Abstract
{
    public $id;
    public $name;
    public $clan_id;
    protected $_tablename = 'players';
    
    public function createMessage($text)
    {
        return dbLink::getDB()->query('insert into messages (player_id, time, text) values (?d, NOW(), ?)', $this->id, $text);
    }
    
    public function makeNewCoinsProductionEvent($island_id = 0, $remains = 0)
    {
        if ($island_id == 0)
            $finish_time = date('Y-m-d H:i:s', strtotime("+ ".(Model_Settings::get()->increase_coins_limit/Model_Settings::get()->increase_coins_per_minute)."minutes"));
        else
            $finish_time = '2020-01-01 00:00:00';
        dbLink::getDB()->query('insert into events (player_id, type, obj_id, start_time, finish_time, params) values (?d, "res_prod", ?d, NOW(), ?, ?d)', $this->id, $island_id, $finish_time, $remains);
    }
}

