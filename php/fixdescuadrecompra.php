<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function fixValor($totfact, $totdh, $codigocta, $idfactura, $campo){
    $db = new dbcpm();
    $diferencia = $totfact - $totdh;

    $orFilt = '';
    foreach($codigocta as $cod){
        if($orFilt !== ''){ $orFilt.= ' OR '; }
        $orFilt.= "b.codigo LIKE '$cod%'";
    }

    $query = "SELECT a.id FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 2 AND a.idorigen = $idfactura AND ($orFilt)  LIMIT 1";
    $iddetcont = (int)$db->getOneField($query);
    if($iddetcont > 0){
        $query = "UPDATE detallecontable SET $campo = $campo + $diferencia WHERE id = $iddetcont";
        $db->doQuery($query);
    }
}

$app->post('/fix', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $idfactura = 0;
    $desde = '';
    if(isset($d->idfactura)){ $idfactura = (int)$d->idfactura; }
    if(isset($d->desde)){ $desde = trim($d->desde); }
    if(!isset($d->desde) && !isset($d->idfactura)){ $desde = '2018-01-01'; }

    $query = "SELECT a.idorigen, TRUNCATE(b.totfact, 2) AS totfact, SUM(a.debe) AS totdebe, SUM(a.haber) AS tothaber ";
    $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen ";
    $query.= "WHERE a.origen = 2 AND b.idreembolso = 0 ";
    $query.= $desde == '' ? '' : "AND b.fechaingreso >= '$desde' ";
    $query.= $idfactura > 0 ? "AND a.idorigen = $idfactura " : '';
    $query.= "GROUP BY a.idorigen ";
    $query.= "HAVING totdebe <> tothaber AND ABS(totdebe - tothaber) <= 0.05";
    $descuadres = $db->getQuery($query);
    $cntDescuadres = count($descuadres);
    for($i = 0; $i < $cntDescuadres; $i++){
        $descuadre = $descuadres[$i];
        $totfact = (float)$descuadre->totfact;
        $totdebe = (float)$descuadre->totdebe;
        $tothaber = (float)$descuadre->tothaber;
        if($totdebe !== $totfact){
            fixValor($totfact, $totdebe, ['512', '121'], $descuadre->idorigen, 'debe');
        }
        if($tothaber !== $totfact){
            fixValor($totfact, $tothaber, ['212', '11201'], $descuadre->idorigen, 'haber');
        }
    }
    print json_encode(['mensaje' => 'Detalle contable de facturas de compra corregido exitosamente...']);
});

$app->run();