<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API gasto contable
$app->get('/lstgastoscontables', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('gastocontable',['id', 'descgastoconta']);
    print json_encode($data);
});

$app->get('/getgastocontable/:idgstcnt', function($idgstcnt){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('gastocontable',['id', 'descgastoconta'], ['id'=>$idgstcnt, 'ORDER' => 'id']);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO gastocontable(descgastoconta) ";
    $query.= "VALUES('".$d->descgastoconta."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE gastocontable SET descgastoconta = '".$d->descgastoconta."' WHERE id = " .$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM gastocontable WHERE id = ".$d->id;
    $del = $conn->query($query);
});


$app->run();