<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para los tipos de configuración de las cuentas contables por empresa
$app->get('/lsttipoipc', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descripcion FROM tipoipc ORDER BY descripcion");
});

$app->get('/gettipoipc/:idtipoipc', function($idtipoipc){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descripcion FROM tipoipc WHERE id = $idtipoipc");
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO tipoipc(descripcion) VALUES('$d->descripcion')");
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tipoipc SET descripcion = '$d->descripcion' WHERE id = $d->id");
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM tipoipc WHERE id = $d->id");
});

$app->run();