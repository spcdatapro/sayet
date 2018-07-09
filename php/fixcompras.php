<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/fix', function(){
    $db = new dbcpm();

    $qGen = "SELECT a.idempresa, a.id AS idfactura, a.serie, a.documento AS numero, a.fechaingreso AS fecha, b.id AS iddetcont, c.id AS idcuenta, c.codigo, c.nombrecta AS cuenta ";
    $qGen.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen INNER JOIN cuentac c ON c.id = b.idcuenta ";
    $qGen.= "WHERE a.idreembolso = 0 AND YEAR(a.fechaingreso) >= 2018 AND b.origen = 2 AND TRIM(c.codigo) = '1120299' ";
    $qGen.= "ORDER BY a.idempresa, a.fechaingreso";

    $query = "SELECT DISTINCT idempresa FROM ($qGen) z ORDER BY idempresa";
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $idempresa = $empresas[$i]->idempresa;
        $cxp = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND TRIM(codigo) = '2120199'");
        if($cxp > 0){
            $query = "SELECT idfactura, TRIM(serie) AS serie, TRIM(numero) AS numero, fecha, iddetcont FROM ($qGen) z WHERE idempresa = $idempresa";
            $compras = $db->getQuery($query);
            $cntCompras = count($compras);
            for($j = 0; $j < $cntCompras; $j++){
                $compra = $compras[$j];
                //Actualización de la compra
                $query = "UPDATE detallecontable SET idcuenta = $cxp WHERE id = $compra->iddetcont";
                $db->doQuery($query);
                $query = "INSERT INTO docsafectados (origen, idorigen, fechadoc, documento) VALUES(2, $compra->idfactura, '$compra->fecha', '".($compra->serie." ".$compra->numero)."')";
                $db->doQuery($query);
                //Actualización de la transacción bancaria, si es que hay una atada a la compra
                $query = "SELECT idtranban FROM detpagocompra WHERE esrecprov = 0 AND idcompra = $compra->idfactura";
                $idtranban = (int)$db->getOneField($query);
                if($idtranban > 0){
                    $query = "SELECT a.id AS idtranban, a.idbanco, a.fecha, a.tipotrans, a.numero, b.id AS iddetcont ";
                    $query.= "FROM tranban a INNER JOIN detallecontable b ON a.id = b.idorigen INNER JOIN cuentac c ON c.id = b.idcuenta ";
                    $query.= "WHERE a.id = $idtranban AND YEAR(a.fecha) >= 2018 AND b.origen = 1 AND TRIM(c.codigo) = '1120299'";
                    $transacciones = $db->getQuery($query);
                    $cntTransacciones = count($transacciones);
                    for($k = 0; $k < $cntTransacciones; $k++){
                        $transaccion = $transacciones[$k];
                        $query = "UPDATE detallecontable SET idcuenta = $cxp WHERE id = $transaccion->iddetcont";
                        $db->doQuery($query);
                        $query = "INSERT INTO docsafectados (origen, idorigen, fechadoc, documento, idbanco) VALUES(";
                        $query.= "1, $transaccion->idtranban, '$transaccion->fecha', '".($transaccion->tipotrans." ".$transaccion->numero)."', $transaccion->idbanco";
                        $query.= ")";
                        $db->doQuery($query);
                    }
                }
                //Actualización de transacciones bancarias que no sean liquidaciones y que estén atadas a la compra
                $query = "SELECT a.id AS idtranban, a.idbanco, a.fecha, a.tipotrans, a.numero, b.id AS iddetcont ";
                $query.= "FROM tranban a INNER JOIN detallecontable b ON a.id = b.idorigen INNER JOIN cuentac c ON c.id = b.idcuenta INNER JOIN doctotranban d ON a.id = d.idtranban ";
                $query.= "WHERE YEAR(a.fecha) >= 2018 AND b.origen = 1 AND TRIM(c.codigo) = '1120299' AND d.idtipodoc = 1 AND d.iddocto = $compra->idfactura";
                $transacciones = $db->getQuery($query);
                $cntTransacciones = count($transacciones);
                for($l = 0; $l < $cntTransacciones; $l++){
                    $transaccion = $transacciones[$l];
                    $query = "UPDATE detallecontable SET idcuenta = $cxp WHERE id = $transaccion->iddetcont";
                    $db->doQuery($query);
                    $query = "INSERT INTO docsafectados (origen, idorigen, fechadoc, documento, idbanco) VALUES(";
                    $query.= "1, $transaccion->idtranban, '$transaccion->fecha', '".($transaccion->tipotrans." ".$transaccion->numero)."', $transaccion->idbanco";
                    $query.= ")";
                    $db->doQuery($query);
                }
            }
        }
    }

    print json_encode('Proceso terminado con éxito...');
});

$app->run();