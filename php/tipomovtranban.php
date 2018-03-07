<?php

require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para monedas
$app->get('/lsttiposmov', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, abreviatura, descripcion, CONCAT('(', abreviatura, ') ', descripcion) AS abreviadesc FROM tipomovtranban";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/gettipomov/:idtipomov', function($idtipomov){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, abreviatura, descripcion, CONCAT('(', abreviatura, ') ', descripcion) AS abreviadesc FROM tipomovtranban WHERE id = ".$idtipomov;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getbyabrevia/:qabrevia', function($qabrevia){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, abreviatura, descripcion, CONCAT('(', abreviatura, ') ', descripcion) AS abreviadesc FROM tipomovtranban WHERE abreviatura = '".$qabrevia."'";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getbysuma/:suma', function($suma){
    $db = new dbcpm();
    $query = "SELECT id, abreviatura, descripcion, CONCAT('(', abreviatura, ') ', descripcion) AS abreviadesc FROM tipomovtranban WHERE suma = ".$suma;
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tipomovtranban(abreviatura, descripcion) VALUES('".$d->abreviatura."', '".$d->descripcion."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipomovtranban SET abreviatura = '".$d->abreviatura."' , descripcion = '".$d->descripcion."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipomovtranban WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();