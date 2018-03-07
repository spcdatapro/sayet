<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de compra
$app->get('/lsttiposcompra', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, desctipocompra FROM tipocompra ORDER BY desctipocompra";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->run();