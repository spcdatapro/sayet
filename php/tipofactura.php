<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de factura
$app->get('/lsttiposfact', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctipofact, generaiva, paracompra, paraventa, siglas FROM tipofactura ORDER BY desctipofact");
});

$app->get('/gettipofact/:idtipofact', function($idtipofact){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctipofact, generaiva, paracompra, paraventa, siglas FROM tipofactura WHERE id = ".$idtipofact);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO tipofactura(desctipofact, generaiva, siglas) VALUES('$d->desctipofact', $d->generaiva, '$d->siglas')");
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tipofactura SET desctipofact = '$d->desctipofact', generaiva = $d->generaiva, siglas = '$d->siglas' WHERE id = ".$d->id);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM tipofactura WHERE id = ".$d->id);
});

$app->run();