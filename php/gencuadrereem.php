<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/generar', function(){
    $db = new dbcpm();

    $query = "SELECT id, idempresa, idreembolso FROM compra WHERE idreembolso > 0 AND cuadrada = 0 ORDER BY idempresa, idreembolso";
    $compras = $db->getQuery($query);
    $cntCompras = count($compras);
    for($i = 0; $i < $cntCompras; $i++){
        $compra = $compras[$i];
        $query = "SELECT (SUM(debe) - SUM(haber)) FROM detallecontable WHERE origen = 2 AND idorigen = $compra->id";
        $montoliquida = $db->getOneField($query);
        $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$compra->idempresa." AND idtipoconfig = 5");
        if($ctaporliquidar > 0){
            $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= "2, $compra->id, $ctaporliquidar, 0.00, $montoliquida, 'Reembolso No. $compra->idreembolso'";
            $query.= ")";
            print $query;
            //$db->doQuery($query);
        }
    }
});

$app->run();