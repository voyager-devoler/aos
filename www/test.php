<?php
$a = array(1,2,3);
foreach ($a as $aa)
{
    echo '-'.$aa.'-<br>';
    foreach ($a as $aaa)
    {
        echo '+'.$aaa;
    }
    echo '<br>';
    
}

class obj
{
    public $prop;
}
$a = new obj();
$b = new obj();
$c = new obj();

$a->prop = 1;
$b->prop = 3;
$c->prop = 2;

$objs = array($a,$b,$c);
foreach ($objs as $o)
{
    if ($o->prop == 3)
        $result = $o;
}
$result->prop = 5;
echo $b->prop;