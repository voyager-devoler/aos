<?php
class Model_Player extends Model_Abstract
{
    public $id;
    public $name;
    public $clan_id;
    protected $_tablename = 'players';
    
    public function createMessage($text)
    {
        
    }
}

