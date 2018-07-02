<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$db = new dbcpm();

$app->get('/generar', function() use($db){

    $idempresa = 4;
    $serie = 'FACE-63-FEA-001';
    $numero = '180000000';
    $fecha = '2018-06-11';
    $insertadas = [];

    for($i = 410; $i <= 411; $i++){
        $query = "INSERT INTO factura(";
        $query.= "idempresa, idtipofactura, idcontrato, idcliente, serie, numero, ";
        $query.= "fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, iva, ";
        $query.= "total, noafecto, subtotal, totalletras, idmoneda, tipocambio, ";
        $query.= "retisr, retiva, totdescuento, nit, nombre, direccion, montocargoiva, montocargoflat, ";
        $query.= "anulada, idrazonanulafactura, fechaanula, esinsertada";
        $query.= ") VALUES (";
        $query.= "$idempresa, 1, 0, 0, '$serie', '".($numero.$i)."', ";
        $query.= "'$fecha', MONTH('$fecha'), '$fecha', 2, 'ANULADA', 0.00, ";
        $query.= "0.00, 0.00, 0.00, 'CERO QUETZALES CON CERO CENTAVOS', 1, 7.49, ";
        $query.= "0.00, 0.00, 0.00, 'ANULADA', 'ANULADA', 'ANULADA', 0.00, 0.00, ";
        $query.= "1, 6, '$fecha', 1";
        $query.= ")";
        //$db->doQuery($query);
        $lastid = $db->getLastId();

        $query = "INSERT INTO detfact(idfactura, cantidad, descripcion, preciounitario, preciotot, idtiposervicio, mes, anio, descuento, montoconiva, montoflatconiva) VALUES(";
        $query.= "$lastid, 1, 'ANULADA', 0.00, 0.00, 13, MONTH('$fecha'), YEAR('$fecha'), 0.00, 0.00, 0.00";
        $query.= ")";

        //$db->doQuery($query);
        $insertadas[] = ['id' => $lastid, 'numero' => $i];
    }

    print json_encode(['insertadas' => $insertadas]);

});

$app->run();