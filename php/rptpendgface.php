<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/pendientes', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT a.nomempresa AS empresa, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy ";
    $query.= "FROM empresa a WHERE a.id = $d->idempresa";
    $generales = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.idempresa, b.abreviatura AS empresa, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.nombre, a.nit, TRIM(a.conceptomayor) AS concepto, ";
    $query.= "FORMAT(a.tipocambio, 2) AS tipocambio, FORMAT(a.subtotal, 2) AS total, FORMAT(a.iva, 2) AS iva, FORMAT(a.retisr, 2) AS retisr, FORMAT(a.retiva, 2) AS retiva, FORMAT(a.total, 2) AS totalneto ";
    $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query.= "WHERE a.fecha >= '2017-09-01' AND (a.serie IS NULL OR a.numero IS NULL) AND b.congface = 1 AND a.idempresa = $d->idempresa ";
    $query.= "ORDER BY a.fecha, a.nombre";
    $pendientes = $db->getQuery($query);

    $generales->cantpendientes = count($pendientes);

    print json_encode(['generales' => $generales, 'pendientes' => $pendientes]);

});

$app->run();