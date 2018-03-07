<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de adjunto
$app->get('/lsttipoadjunto', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipo_adjunto',['id', 'nombre'],['ORDER' => 'nombre']);
    print json_encode($data);
});

$app->get('/gettipoadjunto/:idtipoadjunto', function($idtipoadjunto){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tipo_adjunto',['id', 'nombre'],['id' => $idtipoadjunto, 'ORDER' => 'nombre']);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tipo_adjunto(nombre) VALUES('".$d->nombre."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipo_adjunto SET nombre = '".$d->nombre."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipo_adjunto WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();