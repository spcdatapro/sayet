<?php

require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de documentos adjuntos de proyectos
$app->get('/lsttiposdocproy', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, descripcion FROM tipodocproy ORDER BY descripcion";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/gettipodocproy/:idtipodocproy', function($idtipodocproy){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, descripcion FROM tipodocproy WHERE id = ".$idtipodocproy;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tipodocproy(descripcion) VALUES('".$d->descripcion."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipodocproy SET descripcion = '".$d->descripcion."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipodocproy WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();