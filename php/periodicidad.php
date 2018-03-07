<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para periodicidad
$app->get('/lstperiodicidad', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descperiodicidad, dias FROM periodicidad ORDER BY dias");
});

/*

$app->get('/getperiodicidad/:idperiodicidad', function($idperiodicidad){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, descperiodicidad, dias FROM periodicidad WHERE id = ".$idperiodicidad);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO periodicidad(desctiposervventa) VALUES('".$d->desctiposervventa."')";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE periodicidad SET desctiposervventa = '".$d->desctiposervventa."' WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM periodicidad WHERE id = ".$d->id;
    $db->doQuery($query);
});
*/

$app->run();