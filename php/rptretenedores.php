<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/retenedores', function(){
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy";
    $generales = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.nombre, a.nombrecorto, b.facturara, b.nit, IF(b.retisr = 1, 'Sí', 'No') AS retisr, IF(b.retiva = 1, 'Sí', 'No') AS retiva ";
    $query.= "FROM cliente a INNER JOIN detclientefact b ON a.id = b.idcliente ";
    $query.= "WHERE b.fal IS NULL AND (b.retisr = 1 OR b.retiva = 1) ";
    $query.= "ORDER BY a.nombre, b.facturara";
    $retenedores = $db->getQuery($query);

    print json_encode([ 'generales' => $generales, 'retenedores' => $retenedores]);

});

$app->run();