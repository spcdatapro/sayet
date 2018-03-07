<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para razones de anulacion
$app->get('/lstrazones', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, razon FROM razonanulacion ORDER BY razon");
});

$app->get('/getrazon/:idrazon', function($idrazon){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, razon FROM razonanulacion WHERE id = ".$idrazon);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO razonanulacion(razon) VALUES('".$d->razon."')";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE razonanulacion SET razon = '".$d->razon."' WHERE id = ".$d->id);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM razonanulacion WHERE id = ".$d->id);
});

$app->run();