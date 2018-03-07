<?php
ini_set('memory_limit', '3072M');
ini_set('max_execution_time', 28800);
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'text/html');
$app->response->headers->set('Cache-Control', 'no-cache');

$app->get('/updcxc', function(){
    $db = new dbcpm();

    $query = "UPDATE factura SET pagada = 1 WHERE fecha < '2017-09-01'";
    $db->doQuery($query);

    $query = "SELECT id, idempresa, mes, anio, TRIM(serie) AS serie, TRIM(numero) AS numero, numeroanterior, valor, isr, iva, saldo, apagar, abonado FROM cxcagosto";
    $facturaspend = $db->getQuery($query);
    $cntFactsPend = count($facturaspend);
    if($cntFactsPend > 0){
        for($i = 0; $i < $cntFactsPend; $i++){
            $factura = $facturaspend[$i];
            $query = "SELECT id FROM factura WHERE TRIM(serie) = '$factura->serie' AND TRIM(numero) = '$factura->numero' LIMIT 1";
            $idfactura = (int)$db->getOneField($query);
            if($idfactura > 0){
                //Marcar como pendiente
                $query = "UPDATE factura SET pagada = 0 WHERE id = $idfactura";
                $db->doQuery($query);
                echo "Factura '$factura->serie'-'$factura->numero' marcada como pendiente...<br/>";
                //Halar quien es el cliente para el recibo
                $query = "SELECT idcliente FROM factura WHERE id = $idfactura";
                $idcliente = (int)$db->getOneField($query);
                //Revisar si ya existe recibos creados para esta factura, revisar el total abonado y agregar recibo si hay diferencia contra lo que viene en el xls.
                $query = "SELECT IF(SUM(monto) IS NULL, 0.00, SUM(monto)) FROM detcobroventa WHERE idfactura = $idfactura";
                $yaAbonado = (float)$db->getOneField($query);
                $aAbonar = (float)$factura->abonado - $yaAbonado;
                if($aAbonar > 0){
                    //Insertar recibo con el monto $aAbonar
                    $query = "INSERT INTO recibocli(idempresa, fecha, fechacrea, idcliente, espropio, idtranban) VALUES(";
                    $query.= "$factura->idempresa, DATE(NOW()), DATE(NOW()), $idcliente, 1, 0";
                    $query.= ")";
                    $db->doQuery($query);
                    $lastid = $db->getLastId();
                    $query = "INSERT INTO detcobroventa(idfactura, idrecibocli, monto) VALUES($idfactura, $lastid, $aAbonar)";
                    $db->doQuery($query);
                    echo "Recibo No. $lastid para la factura '$factura->serie'-'$factura->numero' agregado con un monto de ".number_format($aAbonar, 2)."...<br/>";
                }
            }
        }
    }
    echo "Actualización de cuentas por cobrar terminada...";
});

$app->run();