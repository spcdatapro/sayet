<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/catalogo/:idempresa', function($idempresa){
    $db = new dbcpm();

    //Datos de empresa
    $query = "SELECT TRIM(a.nomempresa) AS empresa, TRIM(a.abreviatura) AS abreviaempre, DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha FROM empresa a WHERE a.id = $idempresa";
    $generales = $db->getQuery($query)[0];

    //Cuentas contables
    $query = "SELECT a.id, a.codigo, a.nombrecta, a.tipocuenta, IF(a.tipocuenta = 0, '', 'Sí') AS esdetotales ";
    $query.= "FROM cuentac a ";
    $query.= "WHERE a.idempresa = $idempresa ";
    $query.= "ORDER BY a.codigo";
    $cuentas = $db->getQuery($query);

    print json_encode(['generales' => $generales, 'cuentas' => $cuentas]);
});

$app->run();