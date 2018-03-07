<?php

phpinfo();
/*
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->get('/lstmodulos', function () {
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('modulo',['id', 'descmodulo']);
    print json_encode($data);
});
$app->run();
*/