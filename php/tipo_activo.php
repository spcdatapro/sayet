<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de activo
$app->get('/lsttipoactivo', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipo_activo',['id', 'descripcion'],['ORDER' => 'descripcion']);
    print json_encode($data);
});

$app->get('/gettipoactivo/:idtipoactivo', function($idtipoactivo){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipo_activo',['id', 'descripcion'],['id' => $idtipoactivo, 'ORDER' => 'descripcion']);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tipo_activo(descripcion) VALUES('".$d->descripcion."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipo_activo SET descripcion = '".$d->descripcion."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipo_activo WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();