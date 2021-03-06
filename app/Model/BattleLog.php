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
        $attacker_fleet->prepare4Battle();
        $defender_fleet->prepare4Battle();
        $this->attacker_fleet = clone($attacker_fleet);
        $this->attacker_fleet->cloneShips();
        $this->defender_fleet = clone($defender_fleet);
        $this->defender_fleet->cloneShips();
        $this->time = $time;
        $this->place = $place;
    }
    
    public function addVolley($attacker_volley, $defender_volley)
    {
        $this->volleys[] = array((int)$this->attacker_fleet->player_id => $attacker_volley, $this->defender_fleet->player_id => $defender_volley);
    }
    
    public function addCapture(Model_Fleet $attacker_fleet, array $ships)
    {
        $this->volleys[] = [$attacker_fleet->player_id => ['capture' => $ships]];
    }
    
    public function save()
    {
        $attacker_name = dbLink::getDB()->selectCell('select name from players where id=?d', $this->attacker_fleet->player_id);
        $defender_name = dbLink::getDB()->selectCell('select name from players where id=?d', $this->defender_fleet->player_id);
        $sides = json_encode(array('a'=>['id'=>$this->attacker_fleet->player_id,'name'=>$attacker_name], 'd'=>['id'=>$this->defender_fleet->player_id,'name'=>$defender_name]));
        return dbLink::getDB()->query('insert into battles (sides,time,position,fleets,volleys,result) values (?,?,?,?,?,?)',
                $sides,
                $this->time,
                $this->place,
                json_encode(['a'=>$this->attacker_fleet->getFleetData(),'d'=>$this->defender_fleet->getFleetData()]),
                json_encode($this->volleys),
                '');
    }
    
    public function getBattleData($id)
    {
        return dbLink::getDB()->selectRow('select sides,time,position,fleets,volleys from battles where id=?d', $id);
    }
}

