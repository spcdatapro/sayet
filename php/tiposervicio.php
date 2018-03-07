<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de servicios
$app->get('/lsttiposservicios', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tiposervicio',['id', 'descripcion'], ['ORDER' => 'descripcion']);
    print json_encode($data);
});

$app->get('/gettiposervicio/:idtipo', function($idtipo){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('tiposervicio',['id', 'descripcion'], ['id'=>$idtipo]);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO tiposervicio(descripcion) VALUES('".$d->descripcion."')";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tiposervicio SET descripcion = '".$d->descripcion."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tiposervicio WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();