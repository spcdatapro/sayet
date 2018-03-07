<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de reembolso
$app->get('/lsttiposreem', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctiporeembolso FROM tiporeembolso ORDER BY desctiporeembolso");
});

$app->get('/gettiporeem/:idtiporeem', function($idtiporeem){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctiporeembolso FROM tiporeembolso WHERE id = ".$idtiporeem);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO tiporeembolso(desctiporeembolso) VALUES('".$d->desctiporeembolso."')");
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tiporeembolso SET desctiporeembolso = '".$d->desctiporeembolso."' WHERE id = ".$d->id);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM tiporeembolso WHERE id = ".$d->id);
});

$app->run();