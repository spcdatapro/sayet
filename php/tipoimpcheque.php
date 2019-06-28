<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para tipos de impresión de cheques
$app->get('/lsttiposimp', function(){
    $db = new dbcpm();
    $query = "SELECT id, descripcion, formato, impresora, pagewidth, pageheight FROM tipoimpresioncheque ORDER BY descripcion";
    print $db->doSelectASJson($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE tipoimpresioncheque SET impresora = '$d->impresora', pagewidth = $d->pagewidth, pageheight = $d->pageheight WHERE id = $d->id";
    $db->doQuery($query);
});

//API para campos de cheques
$app->get('/lstcampos/:formato', function($formato){
    $db = new dbcpm();
    $query = "SELECT id, formato, nombre, campo, superior, izquierda, ancho, alto, tamletra, tipoletra, ajustelinea ";
    $query.= "FROM etiqueta ";
    $query.= "WHERE formato = '$formato' ";
    $query.= "ORDER BY campo";
    print $db->doSelectASJson($query);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE etiqueta SET superior = $d->superior, izquierda = $d->izquierda, ancho = $d->ancho, alto = $d->alto, tamletra = $d->tamletra, tipoletra = '$d->tipoletra', ajustelinea = $d->ajustelinea ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->run();