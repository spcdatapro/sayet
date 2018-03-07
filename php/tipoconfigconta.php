<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para los tipos de configuración de las cuentas contables por empresa
$app->get('/lsttipoconfigconta', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipoconfigconta',['id', 'desctipoconfconta'], ['ORDER' => 'desctipoconfconta']);
    print json_encode($data);
});

$app->get('/gettipoconfigconta/:idtipoconf', function($idtipoconf){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipoconfigconta',['id', 'desctipoconfconta'], ['id'=>$idtipoconf]);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tipoconfigconta(desctipoconfconta) VALUES('".$d->desctipoconfconta."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipoconfigconta SET desctipoconfconta = '".$d->desctipoconfconta."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipoconfigconta WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();