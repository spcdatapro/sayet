<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de cliente
$app->get('/lsttiposcliente', function(){
    $db = new dbcpm();
    $query = "SELECT id, desctipocliente FROM tipocliente ORDER BY desctipocliente";
    print $db->doSelectASJson($query);
});

$app->get('/gettipocliente/:idtipocliente', function($idtipocliente){
    $db = new dbcpm();
    $query = "SELECT id, desctipocompra FROM tipocompra WHERE id = ".$idtipocliente;
    print $db->doSelectASJson($query);
});

$app->run();