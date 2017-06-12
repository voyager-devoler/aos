<?php
class Model_Volley
{
    public $damage4all = 0;
    public $damage4powerful = 0;
    public $damage4strongest = 0;
    public $damage4weakest = 0;
    public $crit4all = 0;
    public $crit4powerfull = 0;
    public $crit4strongest = 0;
    public $crit4weakest = 0;
    public $result_data;
    
    public function __construct(Model_Fleet $fleet) {
        
        foreach ($fleet->getAliveShips() as $ship) /* @var $ship Model_Ship */
        {
            list($damage,$critical) = $ship->fire();
            switch ($ship->fire_tactic)
            {
                case 0: 
                    $this->damage4all += $damage;
                    $this->crit4all += $critical;
                    break;
                case 1:
                    $this->damage4powerful += $damage;
                    $this->crit4powerfull += $critical;
                    break;
                case 2:
                    $this->damage4strongest += $damage;
                    $this->crit4strongest += $critical;
                    break;
                case 3:
                    $this->damage4weakest += $damage;
                    $this->crit4weakest += $critical;
            }
        }
    }
}

