<?php
class ClientIllegalCommandException extends Exception {}
require_once '../config.php';

$data = file_get_contents('php://input');
$in_array = json_decode($data, true);

$packet = new Model_Packet($in_array);
$response = $packet->execute();

header('Content-type:application/json');
echo json_encode($response);