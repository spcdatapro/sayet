<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptdetcontventas', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT TRIM(nomempresa) AS empresa, abreviatura AS abreviaempresa, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al FROM empresa WHERE id = $d->idempresa";
    $generales = $db->getQuery($query)[0];

    $query = "SELECT a.id AS idfactura, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempresa, c.siglas AS tipofactura, a.nit, a.nombre, a.serie, a.numero, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, ";
    $query.= "a.conceptomayor, FORMAT(a.iva, 2) AS iva, FORMAT(a.total, 2) AS totalneto, FORMAT(a.subtotal, 2) AS total, FORMAT(a.retisr, 2) AS retisr, FORMAT(a.retiva, 2) AS retiva, FORMAT(a.tipocambio, 4) AS tipocambio, ";
    $query.= "IF(a.anulada = 0, '', 'ANULADA') AS estatus ";
    $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipofactura c ON c.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
    $query.= "AND a.anulada = 0 ";
    $query.= "ORDER BY a.fecha, a.numero";
    //print $query;
    $facturas = $db->getQuery($query);
    $cntFacturas = count($facturas);
    for($i = 0; $i < $cntFacturas; $i++){
        $factura = $facturas[$i];
        $query = "SELECT z.idorigen, z.idcuenta, y.codigo, y.nombrecta, IF(z.debe <> 0, FORMAT(z.debe, 2), '') AS debe, IF(z.haber <> 0, FORMAT(z.haber, 2), '') AS haber, z.conceptomayor ";
        $query.= "FROM detallecontable z INNER JOIN cuentac y ON y.id = z.idcuenta WHERE z.origen = 3 AND z.idorigen = $factura->idfactura";
        //print $query;
        $factura->detcont = $db->getQuery($query);
        if(count($factura->detcont) > 0){
            $query = "SELECT FORMAT(SUM(z.debe), 2) AS totdebe, FORMAT(SUM(z.haber), 2) AS tothaber ";
            $query.= "FROM detallecontable z INNER JOIN cuentac y ON y.id = z.idcuenta WHERE origen = 3 AND z.idorigen = $factura->idfactura ";
            //print $query;
            $sumas = $db->getQuery($query)[0];
            $factura->detcont[] = [
                'idorigen' => '',
                'idcuenta' => '',
                'codigo' => '',
                'nombrecta' => 'Total de partida:',
                'debe' => $sumas->totdebe,
                'haber' => $sumas->tothaber,
                'conceptomayor' => ''
            ];
        }
    }

    print json_encode(['generales' => $generales, 'ventas' => $facturas]);
});

$app->run();