<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para tipos de impresión de cheques
$app->get('/lsttiposimp', function(){
    $db = new dbcpm();
    $query = "SELECT id, descripcion, formato FROM tipoimpresioncheque ORDER BY descripcion";
    print $db->doSelectASJson($query);
});

$app->run();