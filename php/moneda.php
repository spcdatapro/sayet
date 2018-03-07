<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para monedas
$app->get('/lstmonedas', function(){
    $db = new dbcpm();
    $query = "SELECT id, nommoneda, simbolo, tipocambio, codgface, eslocal FROM moneda ORDER BY nommoneda";
    print $db->doSelectASJson($query);
});

$app->get('/getmoneda/:idmoneda', function($idmoneda){
    $db = new dbcpm();
    $query = "SELECT id, nommoneda, simbolo, tipocambio, codgface, eslocal FROM moneda WHERE id = $idmoneda";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO moneda(nommoneda, simbolo, tipocambio) VALUES('$d->nommoneda', '$d->simbolo', $d->tipocambio)";
    $db->doQuery($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE moneda SET nommoneda = '$d->nommoneda' , simbolo = '$d->simbolo', tipocambio = $d->tipocambio WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM moneda WHERE id = $d->id";
    $db->doQuery($query);
});

$app->run();