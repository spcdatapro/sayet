<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de proyecto
$app->get('/lsttipoproyecto', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipo_proyecto',['id', 'descripcion'],['ORDER' => 'descripcion']);
    print json_encode($data);
});

$app->get('/gettipoproyecto/:idtipoproyecto', function($idtipoproyecto){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipo_proyecto',['id', 'descripcion'],['id' => $idtipoproyecto, 'ORDER' => 'descripcion']);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tipo_proyecto(descripcion) VALUES('".$d->descripcion."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipo_proyecto SET descripcion = '".$d->descripcion."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipo_proyecto WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();