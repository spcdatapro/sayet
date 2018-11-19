<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/reemsinctaliq', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al";
    $generales = $db->getQuery($query)[0];

    $queryComp = "
        SELECT d.nomempresa AS empresa, d.abreviatura AS abreviaempresa, c.id AS reembolso, a.id, a.idempresa, a.idreembolso, d.ordensumario, a.fechaingreso, a.serie, a.documento, a.conceptomayor, c.idbeneficiario, a.totfact
        FROM compra a        
        INNER JOIN reembolso c ON c.id = a.idreembolso
        INNER JOIN empresa d ON d.id = c.idempresa
        WHERE a.idreembolso > 0 AND a.fechaingreso >= '$d->fdelstr' AND a.fechaingreso <= '$d->falstr' 
        ORDER BY d.ordensumario, a.fechaingreso";
    $query = "SELECT DISTINCT idempresa, empresa, abreviaempresa FROM($queryComp) z ORDER BY ordensumario";
    $reembolsos = $db->getQuery($query);
    $cntReembolsos = count($reembolsos);
    for($i = 0; $i < $cntReembolsos; $i++){
        $empresa = $reembolsos[$i];
        $empresa->comprassinliq = [];
        $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$empresa->idempresa." AND idtipoconfig = 5");
        $query = "SELECT reembolso, id, DATE_FORMAT(fechaingreso, '%d/%m/%Y') AS fechaingreso, serie, documento, conceptomayor, idbeneficiario, FORMAT(totfact, 2) AS monto ";
        $query.= "FROM ($queryComp) z WHERE idempresa = $empresa->idempresa ORDER BY fechaingreso";
        $compras = $db->getQuery($query);
        $cntCompras = count($compras);
        for($j = 0; $j < $cntCompras; $j++){
            $compra = $compras[$j];
            //$query = "SELECT (SUM(debe) - SUM(haber)) FROM detallecontable WHERE origen = 2 AND idorigen = $compra->id";
            //$montoliquida = $db->getOneField($query);

            //Caso especial de Francisco Coc que tiene su propia cuenta de liquidación
            $ctaliqesp = 0;
            if((int)$compra->idbeneficiario === 3){
                $query = "SELECT id FROM cuentac WHERE idempresa = $empresa->idempresa AND nombrecta LIKE 'FRANC%COC%' AND codigo LIKE '11202%'";
                $ctaliqesp = (int)$db->getOneField($query);
            }
            //Fin de caso especial

            if($ctaporliquidar > 0 || $ctaliqesp > 0){
                $query = "SELECT COUNT(idcuenta) FROM detallecontable WHERE origen = 2 AND idorigen = $compra->id AND (idcuenta = $ctaporliquidar";
                $query.= $ctaliqesp === 0 ? '' : " OR idcuenta = $ctaliqesp";
                $query.= ")";
                $noexiste = (int)$db->getOneField($query) === 0;
                if($noexiste) {
                    $empresa->comprassinliq[] = [
                        'reembolso' => $compra->reembolso,
                        'fechaingreso' => $compra->fechaingreso,
                        'serie' => $compra->serie,
                        'numero' => $compra->documento,
                        'monto' => $compra->monto,
                        'concepto' => $compra->conceptomayor
                    ];
                }
            }
        }
    }

    print json_encode(['generales' => $generales, 'reembolsos' => $reembolsos]);
});

$app->run();