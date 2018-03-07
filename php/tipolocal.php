<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de locales
$app->get('/lsttiposlocales', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descripcion, esrentable, orden FROM tipolocal ORDER BY orden, descripcion");
});

$app->get('/gettipolocal/:idtipo', function($idtipo){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descripcion, esrentable, orden FROM tipolocal WHERE id = ".$idtipo);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO tipolocal(descripcion, esrentable, orden) VALUES('$d->descripcion', $d->esrentable, $d->orden)");
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tipolocal SET descripcion = '$d->descripcion', esrentable = $d->esrentable, orden = $d->orden WHERE id = ".$d->id);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM tipolocal WHERE id = ".$d->id);
});

$app->run();