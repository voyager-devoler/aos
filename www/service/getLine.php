<?php
$lnum = $_GET['line'];
header('Content-type:application/json');
require_once '../../config.php';
$data = dbLink::getDB()->selectRow('SELECT post,response from dev_logger where id=?d',$lnum);
if (empty($data['post']))
    $data['post'] = 'null';
echo "{'request':{$data['post']},\n'answer':{$data['response']}}";
?>

