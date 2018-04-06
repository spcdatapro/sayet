<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/lstestpres', function(){
    $db = new dbcpm();
    $query = "SELECT id, descestatuspresup FROM estatuspresupuesto ORDER BY id";
    print $db->doSelectASJson($query);
});

$app->run();