<?php
set_time_limit(0);
//ini_set('memory_limit', '1536M');
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'text/html');
$app->response->headers->set('Cache-Control', 'no-cache');

$app->get('/generar', function(){
    $db = new dbcpm();

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><small>";

    $query = "
        SELECT a.id, a.idempresa, a.idreembolso, b.diferencia
        FROM compra a 
        INNER JOIN (
            SELECT idorigen, SUM(debe) AS debe, SUM(haber) AS haber, (SUM(debe) - SUM(haber)) AS diferencia FROM detallecontable WHERE origen = 2 GROUP BY idorigen HAVING (SUM(debe) - SUM(haber)) <> 0
        ) b ON a.id = b.idorigen
        WHERE a.idreembolso > 0 AND a.cuadrada = 0 AND a.fechaingreso >= '2018-01-01' AND a.fechaingreso <= '2018-03-31' 
        ORDER BY a.idempresa, a.idreembolso";
    $compras = $db->getQuery($query);
    $cntCompras = count($compras);
    for($i = 0; $i < $cntCompras; $i++){
        $compra = $compras[$i];
        $query = "SELECT (SUM(debe) - SUM(haber)) FROM detallecontable WHERE origen = 2 AND idorigen = $compra->id";
        $montoliquida = $db->getOneField($query);
        $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$compra->idempresa." AND idtipoconfig = 5");
        if($ctaporliquidar > 0){
            $noexiste = (int)$db->getOneField("SELECT COUNT(idcuenta) FROM detallecontable WHERE origen = 2 AND idorigen = $compra->id AND idcuenta = $ctaporliquidar") === 0;
            if($noexiste){
                $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
                $query.= "2, $compra->id, $ctaporliquidar, 0.00, $montoliquida, 'Reembolso No. $compra->idreembolso'";
                $query.= ")";
                echo $query."<br/>";
                $db->doQuery($query);
                $query = "UPDATE compra SET cuadrada = 1 WHERE id = $compra->id";
                echo $query."<br/>";
                $db->doQuery($query);
            }else{
                echo "<strong>La cuenta por liquidar de la empresa $compra->idempresa para la compra con ID $compra->id del ree/cc No. $compra->idreembolso ya existe. El descuedre es por otro motivo.</strong><br/>";
            }
        }else{
            echo "<strong>No se encontró la cuenta por liquidar de la empresa $compra->idempresa para la compra con ID $compra->id del ree/cc No. $compra->idreembolso</strong><br/>";
        }
    }

    echo "<p><strong>Terminamos generación de cuadre de reembolsos...</strong></p></small></body></html>";
});

$app->run();