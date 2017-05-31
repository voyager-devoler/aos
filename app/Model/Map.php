<?php
class Model_Map
{
    static public function getCellType($position)
    {
        list($x,$y) = explode(',', $position);
        return dbLink::getDB()->selectCell('select content from map where x=?d and y=?d',$x,$y);
    }
    
    static public function getAllMap()
    {
        $types = array('sea'=>0,'shallow'=>1,'land'=>2);
        $map = dbLink::getDB()->select('select * from map');
        foreach ($map as $id=>$cell)
        {
            $map[$id]['x'] = (int)$map[$id]['x'];
            $map[$id]['y'] = (int)$map[$id]['y'];
            $map[$id]['content'] = $types[$map[$id]['content']];
        }
        return array('width'=>50, 'height'=>50, 'map'=>$map);
    }
}

