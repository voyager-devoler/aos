<?php
class Model_BattleLog
{
    protected static $_instance;
    
    /** @var Model_Fleet */
    public $attacker_fleet;
    /** @var Model_Fleet */
    public $defender_fleet;
    /** @var Model_Volley|Array */
    public $volleys = array();
    public $time;
    public $place;
    
    protected function __construct() {}
    
    /**
     * 
     * @return Model_BattleLog
     */
    public static function getInstance()
    {
        if (is_null(static::$_instance))
        {
            static::$_instance = new static;
        }
        return static::$_instance;
    }
    
    public function initBattle(Model_Fleet $attacker_fleet, Model_Fleet $defender_fleet, $time, $place)
    {
        $this->attacker_fleet = clone($attacker_fleet);
        $this->defender_fleet = clone($defender_fleet);
        $this->time = $time;
        $this->place = $place;
    }
    
    public function addVolley($attacker_volley, $defender_volley)
    {
        $this->volleys[] = array((int)$this->attacker_fleet->player_id => $attacker_volley, $this->defender_fleet->player_id => $defender_volley);
    }
    
    public function save()
    {
        $sides = json_encode(array('a'=>$this->attacker_fleet->player_id, 'd'=>$this->defender_fleet->player_id));
        
    }
}

