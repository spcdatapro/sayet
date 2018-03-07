<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para tipos de servicios de venta
$app->get('/lsttsventa', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctiposervventa FROM tiposervicioventa ORDER BY desctiposervventa");
});

$app->get('/gettsventa/:idtipo', function($idtipo){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctiposervventa FROM tiposervicioventa WHERE id = ".$idtipo);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO tiposervicioventa(desctiposervventa) VALUES('".$d->desctiposervventa."')";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE tiposervicioventa SET desctiposervventa = '".$d->desctiposervventa."' WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM tiposervicioventa WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->run();