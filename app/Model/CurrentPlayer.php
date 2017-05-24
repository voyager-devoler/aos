<?php
class Model_CurrentPlayer extends Model_Player
{
    public $coins;
    public $crew_limit;
    public $crews;
    /** @var Model_Ship|Array */
    protected $_ships = array();
    /** @var Model_Fleet|Array */
    protected $_fleets = array();
    protected static $_device_id = null;


    public static function init($device_id)
    {
        self::$_device_id = $device_id;
    }
    
    /**
     * 
     * @return Model_CurrentPlayer
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
    
    public function __construct() {
        if (is_null(self::$_device_id))
        {
            throw new Exception("Device id haven't initialised");
        }
        $this->id = dbLink::getDB()->selectCell('select id from '.$this->_tablename.' where device_id=?',self::$_device_id);
        if (empty($this->id))
        {
            $this->name = 'new_user';
            $this->coins = Model_Settings::get()->start_coins;
            $this->crew_limit = Model_Settings::get()->start_crew_limit;
            $this->setRowValues(array('name'=>$this->name, 'coins'=>$this->coins, 'crew_limit'=>$this->crew_limit,'device_id'=>self::$_device_id));
            $this->id = $this->insertRow();
            Model_Packet::setNeedRequest('setUsername');
        }
        parent::__construct($this->id);
        $ships = dbLink::getDB()->select('select * from ships where player_id = ?d', $this->id); // на самом деле нет смысла все корабли создавать 2 раза, но пока пусть будет так
        foreach ($ships as $ship)
        {
            $this->_ships[$ship['id']] = new Model_Ship($ship);
        }
        $fleets = dbLink::getDB()->select('select * from fleets where player_id = ?d', $this->id);
        foreach ($fleets as $fleet)
        {
            $this->_fleets[$fleet['id']] = new Model_Fleet($fleet);
        }
    }
    
    public function save()
    {
        if (is_array($this->_dbRawRowValues))
            $this->updateRow();
    }
    
    public function increaseCoins(int $coins)
    {
        if ($this->coins + $coins < 0)
            throw new InsufficientResourceException('Insufficient coins');
        $this->setRowValue('coins', $this->coins + $coins);
        return $this->coins;
    }
    
    public function increaseCurrentCrews(int $crews)
    {
        if ($this->crews + $crews > $this->crew_limit)
            throw new InsufficientResourceException('Crew limit reached');
        $this->setRowValue('crews', $this->crews + $crews);
        return $this->crews;
    }
    
    public function createNewShip($type,$name,array $equipments)
    {
        if (count($equipments) != Model_ShipTypes::getInstance()->getCells($type))
            throw new InsufficientResourceException('Wrong cells number for equipments');
        $this->increaseCurrentCrews(Model_ShipTypes::getInstance()->getCrew($type));
        $cost = Model_ShipTypes::getInstance()->getCost($type);
        foreach (explode(',',$equipments) as $equipment)
        {
            $cost+=Model_Equipments::getInstance()->getCost($equipment);
        }
        $this->increaseCoins(-$cost);
        if ($name=='')
        {
            $names = explode("\n", file_get_contents('../ships.txt'));
            $name = $names[mt_rand(0, count($names)-1)];
        }
        $ship = new Model_Ship(array('type'=>$type,'player_id'=>$this->id,'hull_type'=>$type, 'hull_strength'=>Model_ShipTypes::getInstance()->getHull($type), 'equipments'=>implode(',',$equipments),'name'=>$name));
        return $ship->id;
    }
    
    /**
     * 
     * @return Model_Fleet|Array
     */
    public function getFleets()
    {
        return $this->_fleets;    
    }
    
    public function getAllShips()
    {
        return $this->_ships;
    }
}


