<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para monedas
$app->get('/lstpcont', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, del, al, abierto FROM periodocontable ORDER BY abierto DESC, del";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getpcont/:idpcont', function($idpcont){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, del, al, abierto FROM periodocontable WHERE id = ".$idpcont;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO periodocontable(del, al, abierto) VALUES('".$d->delstr."', '".$d->alstr."', ".$d->abierto.")";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE periodocontable SET del = '".$d->delstr."' , al = '".$d->alstr."', abierto = ".$d->abierto." WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM periodocontable WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->post('/validar', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT COUNT(id) AS abiertos FROM periodocontable WHERE abierto = 1 AND '".$d->fecha."' >= del AND '".$d->fecha."' <= al";
    //$data = $conn->query($query)->fetchAll(5);
    $data = $conn->query($query)->fetchColumn(0);
    print json_encode(['valida' => (int)$data]);
});


$app->run();