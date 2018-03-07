<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para modulos
$app->get('/lstmodulos', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('modulo',['id', 'descmodulo'], ['ORDER' => 'descmodulo']);
    //print $data;
    print json_encode($data);
});

$app->post('/cmod', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO modulo(descmodulo) VALUES('".$d->descmodulo."')";
    $ins = $conn->query($query);
});

$app->post('/umod', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE modulo SET descmodulo = '".$d->descmodulo."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/dmod', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM modulo WHERE id = ".$d->id;
    $del = $conn->query($query);
});


//API para menus
$app->get('/lstmenu/:idmodulo', function($idmodulo){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('menu',['id', 'idmodulo', 'descmenu'], ['idmodulo'=>$idmodulo, 'ORDER' => 'descmenu']);
    //print $data;
    print json_encode($data);
});

$app->post('/cmnu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO menu(idmodulo, descmenu) VALUES(".$d->idmodulo.", '".$d->descmenu."')";
    $ins = $conn->query($query);
});

$app->post('/umnu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE menu SET idmodulo = ".$d->idmodulo.", descmenu = '".$d->descmenu."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/dmnu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM menu WHERE id = ".$d->id;
    $del = $conn->query($query);
});

//API para items
$app->get('/lstitems/:idmenu', function($idmenu){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('itemmenu',['id', 'idmenu', 'descitemmenu', 'url'], ['idmenu'=>$idmenu, 'ORDER' => 'descitemmenu']);
    //print $data;
    print json_encode($data);
});

$app->post('/citem', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO itemmenu(idmenu, descitemmenu, url) VALUES(".$d->idmenu.", '".$d->descitemmenu."', '".$d->url."')";
    $ins = $conn->query($query);
});

$app->post('/uitem', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE itemmenu SET idmenu = ".$d->idmenu.", descitemmenu = '".$d->descitemmenu."', url = '".$d->url."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/ditem', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM itemmenu WHERE id = ".$d->id;
    $del = $conn->query($query);
});


$app->run();