<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/generar', function(){
    $db = new dbcpm();

    $query = "SELECT ";


});

$app->run();