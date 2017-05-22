<?php
die ('The map already filled.');
require_once '../config.php';
for ($x=0;$x<50;$x++)
    for ($y=0;$y<50;$y++)
        dbLink::getDB()->query("insert into map values (?d,?d,?)",$x,$y,'sea');
