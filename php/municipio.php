<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para municipios
$app->get('/lstmunicipios', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, CONCAT(nomdepto,' - ',nombre) AS descripcion FROM municipio ";
    $query.= "WHERE habilitado = 1 ";
    $query.= "ORDER BY nomdepto, nombre";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/lstallmunicipios', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, codigo, nombre, depto, nomdepto, habilitado FROM municipio ";
    $query.= "ORDER BY habilitado DESC, nomdepto, nombre";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getmunicipio/:idmunicipio', function($idmunicipio){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, CONCAT(nomdepto,' - ',nombre) as descripcion from municipio WHERE id=".$idmunicipio." order by nomdepto,nombre";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO municipio(codigo, nombre, depto, nomdepto, habilitado) ";
    $query.= "VALUES('".$d->codigo."', '".$d->nombre."', ".$d->depto.", '".$d->nomdepto."', ".$d->habilitado.")";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE municipio SET codigo = '".$d->codigo."', nombre = '".$d->nombre."', depto = ".$d->depto.", nomdepto = '".$d->nomdepto."', ";
    $query.= "habilitado  = ".$d->habilitado." ";
    $query.= "WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM municipio WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();