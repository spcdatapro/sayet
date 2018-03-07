<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para los tipos de configuración de las cuentas contables por empresa
$app->get('/lsttiposcomb', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descripcion, impuesto FROM tipocombustible ORDER BY descripcion");
});

$app->get('/gettipocomb/:idtipocomb', function($idtipocomb){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descripcion, impuesto FROM tipocombustible WHERE id = ".$idtipocomb);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO tipocombustible(descripcion, impuesto) VALUES('".$d->descripcion."', ".$d->impuesto.")");
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tipocombustible SET descripcion = '".$d->descripcion."', impuesto = ".$d->impuesto." WHERE id = ".$d->id);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM tipocombustible WHERE id = ".$d->id);
});

$app->run();