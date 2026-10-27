<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/ivaventa', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if(!isset($d->ordenalfa)) { $d->ordenalfa = 1; }

    $info = new stdClass();

    $query = "SELECT nombre AS mes, $d->anio AS anio, DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha, 0.00 AS totalbase, 0.00 AS totaliva, 0.00 AS totalretiva FROM mes WHERE id = $d->mes";
    $info->generales = $db->getQuery($query)[0];

    $query = "SELECT DISTINCT a.idempresa, TRIM(b.nomempresa) AS nomempresa, TRIM(b.abreviatura) AS abreviatura, b.ordensumario ";
    $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query.= "WHERE a.anulada = 0 AND a.idtipofactura <> 9 ";
    $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio " : '';
    $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
    $query.= $d->cliente != '' ? "AND a.nombre = '$d->cliente' " : '';
    $query.= "AND a.retiva > 0 ";
    $query.= "UNION ";
    $query.= "SELECT a.id AS idempresa, TRIM(a.nomempresa) AS nomempresa, TRIM(a.abreviatura) AS abreviatura, a.ordensumario FROM empresa a WHERE a.id = 8 ";
    $query.= "UNION ALL SELECT a.id AS idempresa, TRIM(a.nomempresa) AS nomempresa, TRIM(a.abreviatura) AS abrevitura, a.ordensumario FROM empresa a INNER JOIN retiva_venta b ON b.idempresa = a.id ";
    $query.= "WHERE ";
    $query.= $d->fdelstr == '' || $d->falstr == '' ? "MONTH(b.fecha) = $d->mes AND YEAR(b.fecha) = $d->anio " : '';
    $query.= $d->fdelstr != '' && $d->falstr != '' ? "b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' " : '';
    $query.= "ORDER BY 4";
    $info->empresas = $db->getQuery($query);
    $cntEmp = count($info->empresas);

    for($i = 0; $i < $cntEmp; $i++){
        $empresa = $info->empresas[$i];
        $query = "SELECT a.id AS idfactura, a.serie, a.numero, a.nombre, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, ";
        $query.= "FORMAT(ROUND(((a.subtotal - round(a.totdescuento*1.12,2)) + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2), 2) AS base, FORMAT(a.iva, 2) AS iva, FORMAT(a.retiva, 2) AS retiva, a.noformiva, ";
        $query.= "IF(a.porretiva = 0, ROUND((a.retiva / a.iva * 100), 2), a.porretiva) AS porretiva ";
        $query.= "FROM factura a ";
        $query.= "WHERE a.anulada = 0 AND a.idtipofactura <> 9 ";
        $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio " : '';
        $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
        $query.= $d->cliente != '' ? "AND a.nombre = '$d->cliente' " : '';
        $query.= "AND a.retiva > 0 AND a.idempresa = $empresa->idempresa ";
        $query.="UNION ALL SELECT a.id AS idfactura, IFNULL(b.serie, 'INSERTADO') AS serie, IFNULL(b.numero, '') AS numero, c.nombre, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, 
        0.00 AS baase, 0.00 AS iva, a.monto AS retiva, a.formulario AS noformiva, 0.00 AS porretiva 
        FROM retiva_venta a LEFT JOIN factura b ON a.idfactura = a.id INNER JOIN cliente c ON a.idcliente = c.id WHERE a.idempresa = $empresa->idempresa ";
        $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio " : '';
        $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
        $query.= "ORDER BY ".((int)$d->ordenalfa === 1 ? '4 , ' : '')."2 , 3 , 5";

        if((int)$empresa->idempresa == 8){
            $query = "SELECT a.idtipofactura, a.id AS idfactura, a.serie, a.numero, a.nombre, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, ";
            $query.= "FORMAT(ROUND(((a.subtotal - round(a.totdescuento*1.12,2)) + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2), 2) AS base, FORMAT(a.iva, 2) AS iva, FORMAT(a.iva, 2) AS retiva, a.noformiva, ";
            $query.= "IF(a.porretiva = 0, ROUND((a.retiva / a.iva * 100), 2), a.porretiva) AS porretiva ";
            $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa ";
            $query.= "WHERE a.anulada = 0 AND a.idtipofactura <> 9 ";
            $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio " : '';
            $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
            $query.= $d->cliente != '' && (int)$empresa->idempresa != 8 ? "AND a.nombre = '$d->cliente' " : '';
            $query.= "AND a.idempresa = $empresa->idempresa AND a.idcliente = 108 ";
            $query.= "ORDER BY ".((int)$d->ordenalfa === 1 ? 'a.nombre, ' : '')."a.serie, a.numero, a.fecha";
        }

        $empresa->facturas = $db->getQuery($query);

        if(count($empresa->facturas) > 0){
            $query = "SELECT 0 AS idfactura, '' AS serie, '' AS numero, '' AS nombre, 'TOTALES POR EMPRESA:' AS fecha, ";
            $query.= "FORMAT(SUM(ROUND(((a.subtotal - round(a.totdescuento*1.12,2)) + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2)), 2) AS base, FORMAT(SUM(a.iva), 2) AS iva, ";
            $query.= (int)$empresa->idempresa != 8 ? "FORMAT(SUM(a.retiva), 2) AS retiva, " : "FORMAT(SUM(a.iva), 2) AS retiva, ";
            $query.= "'' AS noformiva ";
            $query.= "FROM factura a ";
            $query.= "WHERE a.anulada = 0 AND a.idtipofactura <> 9 ";
            $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio " : '';
            $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
            $query.= $d->cliente != '' && (int)$empresa->idempresa != 8 ? "AND a.nombre = '$d->cliente' " : '';
            $query.= "AND a.idempresa = $empresa->idempresa ";
            $query.= (int)$empresa->idempresa != 8 ? "AND a.retiva > 0 " : "AND a.idcliente = 108 ";
            $suma = $db->getQuery($query)[0];
            $query = "SELECT IFNULL(monto, 0) FROM retiva_venta WHERE idempresa = $empresa->idempresa ";
            $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND MONTH(fecha) = $d->mes AND YEAR(fecha) = $d->anio " : '';
            $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND fecha >= '$d->fdelstr' AND fecha <= '$d->falstr' " : '';
            $insertado = (int)$db->getOneField($query);
            $insertado = $insertado > 0 ? $insertado : 0;

            $suma->retiva += $insertado;
            $empresa->facturas[] = $suma;
        }
    }

    $query = "SELECT FORMAT(SUM(ROUND(((a.subtotal - round(a.totdescuento*1.12,2)) + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2)), 2) AS totbase, FORMAT(SUM(a.iva), 2) AS totiva, FORMAT(SUM(a.retiva), 2) AS totretiva ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.anulada = 0 AND a.idtipofactura <> 9 ";

    $query.= $d->fdelstr == '' || $d->falstr == '' ? "AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio " : '';
    $query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
    $query.= $d->cliente != '' ? "AND a.nombre = '$d->cliente' " : '';

    $query.= "AND ((a.retiva > 0) OR (a.idempresa = 8 AND a.idcliente = 108))";
    $sumas = $db->getQuery($query)[0];

    $query = "SELECT IFNULL(monto, 0) FROM retiva_venta WHERE ";
    $query.= $d->fdelstr == '' || $d->falstr == '' ? "MONTH(fecha) = $d->mes AND YEAR(fecha) = $d->anio " : '';
    $query.= $d->fdelstr != '' && $d->falstr != '' ? "fecha >= '$d->fdelstr' AND fecha <= '$d->falstr' " : '';
    $insertado = (int)$db->getOneField($query);

    $info->generales->totalbase = $sumas->totbase;
    $info->generales->totaliva = $sumas->totiva;
    $info->generales->totalretiva = $sumas->totretiva + $insertado;

    print json_encode($info);
});


$app->run();