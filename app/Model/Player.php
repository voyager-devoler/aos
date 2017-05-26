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
}

