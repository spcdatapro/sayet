<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/asistelibros', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    /*
    $query = "SELECT a.fecha AS fechaord,
$d->establecimiento AS establecimiento, 'V' AS compraventa, c.siglas AS documento, a.serie, a.numero AS numerodocumento, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechadocumento, TRIM(a.nit) AS nit, TRIM(a.nombre) AS nombre, 'L' AS tipotransaccion,
'' AS tipooperacion, IF(a.anulada = 0, 'E', 'A') AS estadodocumento, '' AS noordced, 0 AS noregistroced, '' AS tipodocexpo, 0 AS nodocexpo,
0 AS bien, 0 AS bienexpo,
IF(a.anulada = 0, IF(a.idtipoventa = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND(a.subtotal, 2), 0), 0), 0) AS servicio,
0 AS servicioexpo, 0 AS exentobien, 0 AS exentobienexpo, 0 AS exentoservicio, 0 AS exentoservicioexpo,
IF(a.retiva <> 0, 'CRIVA', '') AS tipoconstancia, a.noacciva AS noconstancia, ROUND(a.retiva, 2) AS valorconstancia,
'' AS peqcontbienes, '' AS peqcontservicios, '' AS peqcontbienexpo, '' AS peqcontserviciosexpo, ROUND(a.iva, 2) AS valorivadocumento, ROUND(a.subtotal, 2) AS totaldocumento
FROM factura a LEFT JOIN contrato b ON b.id = a.idcontrato LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente
WHERE a.idtipoventa <> 5 AND c.id <> 5 AND a.idempresa = $d->idempresa AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio
UNION
SELECT a.fechafactura AS fechaord,
$d->establecimiento AS establecimiento, 'C' AS compraventa, c.siglas AS documento, a.serie, a.documento AS numerodocumento, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechadocumento, TRIM(b.nit) AS nit, TRIM(b.nombre) AS nombre, 'L' AS tipotransaccion,
'' AS tipooperacion, '' AS estadodocumento, '' AS noordced, '' AS noregistroced, '' AS tipodocexpo, '' AS nodocexpo,
IF(a.idtipocompra = 3, IF(a.idtipofactura <> 7, round(a.totfact * a.tipocambio, 2), 0), 0) + IF(a.idtipocompra IN(1, 4), IF(a.idtipofactura <> 7, round(a.totfact * a.tipocambio, 2), 0), 0) AS bien, 0 AS bienexpo,
IF(a.idtipocompra = 2, IF(a.idtipofactura <> 7, round(a.totfact * a.tipocambio, 2), 0), 0) AS servicio, 0 AS servicioexpo,
IF(b.pequeniocont = 0, IF(a.idtipocompra IN(1, 3, 4), a.idp + a.noafecto, 0), 0) AS exentobien, 0 AS exentobienexpo,
IF(b.pequeniocont = 0, IF(a.idtipocompra IN(2), a.idp + a.noafecto, 0), 0) AS exentoservicio, 0 AS exentoservicioexpo,
'' AS tipoconstancia, '' AS noconstancia, '' AS valorconstancia,
IF(b.pequeniocont = 1 AND a.idtipocompra IN(1, 3, 4), ROUND(a.totfact * a.tipocambio, 2), 0) AS peqcontbienes,
IF(b.pequeniocont = 1 AND a.idtipocompra IN(2), ROUND(a.totfact * a.tipocambio, 2), 0) AS peqcontservicios, 0 AS peqcontbienexpo, 0 AS peqcontserviciosexpo,
IF(b.pequeniocont = 0, ROUND(a.iva * a.tipocambio, 2), '') AS valorivadocumento, ROUND(a.totfact * a.tipocambio, 2) AS totaldocumento
FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN tipocompra d ON d.id = a.idtipocompra
WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = $d->idempresa AND a.idreembolso = 0 AND a.mesiva = $d->mes AND YEAR(a.fechafactura) = $d->anio AND a.iva <> 0 AND b.pequeniocont = 0
UNION
SELECT a.fechafactura,
$d->establecimiento AS establecimiento, 'C' AS compraventa, c.siglas AS documento, a.serie, a.documento AS numerodocumento, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechadocumento, TRIM(a.nit) AS nit, TRIM(a.proveedor) AS nombre, 'L' AS tipotransaccion,
'' AS tipooperacion, '' AS estadodocumento, '' AS noordced, '' AS noregistroced, '' AS tipodocexpo, '' AS nodocexpo,
IF(a.idtipocompra = 3, IF(a.idtipofactura <> 7, round(a.totfact * a.tipocambio,2), 0), 0) + IF(a.idtipocompra IN(1, 4), IF(a.idtipofactura <> 7, round(a.totfact * a.tipocambio,2), 0), 0) AS bien, 0 AS bienexpo,
IF(a.idtipocompra = 2, IF(a.idtipofactura <> 7, round(a.totfact * a.tipocambio,2), ''), '') AS servicio, '' AS servicioexpo,
IF(b.pequeniocont = 0, IF(a.idtipocompra IN(1, 3, 4), a.idp + a.noafecto, ''), '') AS exentobien, '' AS exentobienexpo,
IF(b.pequeniocont = 0, IF(a.idtipocompra IN(2), a.idp + a.noafecto, ''), '') AS exentoservicio, '' AS exentoservicioexpo,
'' AS tipoconstancia, '' AS noconstancia, '' AS valorconstancia,
IF(b.pequeniocont = 1 AND a.idtipocompra IN(1, 3, 4), ROUND(a.totfact * a.tipocambio, 2), '') AS peqcontbienes,
IF(b.pequeniocont = 1 AND a.idtipocompra IN(2), ROUND(a.totfact * a.tipocambio, 2), '') AS peqcontservicios, '' AS peqcontbienexpo, '' AS peqcontserviciosexpo,
IF(b.pequeniocont = 0, ROUND(a.iva * a.tipocambio, 2), '') AS valorivadocumento, ROUND(a.totfact * a.tipocambio, 2) AS totaldocumento
FROM compra a INNER JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN proveedor b ON b.id = a.idproveedor LEFT JOIN tipocompra d ON d.id = a.idtipocompra
WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = $d->idempresa AND a.idreembolso > 0 AND a.mesiva = $d->mes AND YEAR(a.fechafactura) = $d->anio AND a.iva <> 0 AND b.pequeniocont = 0
ORDER BY 1";
    */


    /*
    1|V|FCE|FACE-63-FEA-001|1700000001|02/01/2017|3535591-3|ALTURISA GUATEMALA,|L||E||0|||0|0|11320.96|0|0|0|0.00|0|CRIVA|37113|181.94|||||1212.96|11320.96
    1|V|FCE|FACE-63-FEA-001|1700000005|02/01/2017|4002521-7|BODEGAS FRIAS, S. A.|L||E||0|||0|0|5234.88|0|0|0|0.00|0||||||||560.88|5234.88
    */

    $query = "SELECT a.fecha AS fechaord,
$d->establecimiento AS establecimiento, 'V' AS compraventa, c.siglas AS documento, a.serie, a.numero AS numerodocumento, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechadocumento, 
IF(a.anulada = 0, TRIM(a.nit), 0) AS nit, 
IF(a.anulada = 0, TRIM(a.nombre), 'ANULADA') AS nombre, 
'L' AS tipotransaccion,
'' AS tipooperacion, IF(a.anulada = 0, 'E', 'A') AS estadodocumento, '' AS noordced, 0 AS noregistroced, '' AS tipodocexpo, '' AS nodocexpo,
IF(a.anulada = 0, 0, '') AS bien, IF(a.anulada = 0, 0, '') AS bienexpo,
IF(a.anulada = 0, ROUND(a.subtotal, 2), '') AS servicio,
IF(a.anulada = 0, 0, '') AS servicioexpo, IF(a.anulada = 0, 0, '') AS exentobien, IF(a.anulada = 0, 0, '') AS exentobienexpo, IF(a.anulada = 0, 0, '') AS exentoservicio, IF(a.anulada = 0, 0, '') AS exentoservicioexpo,
IF(a.anulada = 0, IF(a.retiva <> 0, 'CRIVA', ''), '') AS tipoconstancia, IF(a.anulada = 0, a.noacciva, '') AS noconstancia, IF(a.anulada = 0, ROUND(a.retiva, 2), '') AS valorconstancia,
'' AS peqcontbienes, '' AS peqcontservicios, '' AS peqcontbienexpo, '' AS peqcontserviciosexpo, IF(a.anulada = 0, ROUND(a.iva, 2), '') AS valorivadocumento, IF(a.anulada = 0, ROUND(a.subtotal, 2), 0.00) AS totaldocumento
FROM factura a LEFT JOIN contrato b ON b.id = a.idcontrato LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente
WHERE a.idtipoventa <> 5 AND c.id <> 5 AND a.idempresa = $d->idempresa AND a.mesiva = $d->mes AND YEAR(a.fecha) = $d->anio
UNION
SELECT a.fechafactura AS fechaord,
$d->establecimiento AS establecimiento, 'C' AS compraventa, c.siglas AS documento, a.serie, a.documento AS numerodocumento, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechadocumento, TRIM(b.nit) AS nit, TRIM(b.nombre) AS nombre, 'L' AS tipotransaccion,
'' AS tipooperacion, '' AS estadodocumento, '' AS noordced, '' AS noregistroced, '' AS tipodocexpo, '' AS nodocexpo,
IF(a.idtipocompra = 3, IF(a.idtipofactura <> 7, round((a.totfact - (a.noafecto + a.idp)) * a.tipocambio, 2), 0), 0) + IF(a.idtipocompra IN(1, 4), IF(a.idtipofactura <> 7, round((a.totfact - (a.noafecto + a.idp)) * a.tipocambio, 2), 0), 0) AS bien, 0 AS bienexpo,
IF(a.idtipocompra = 2, IF(a.idtipofactura <> 7, round((a.totfact - (a.noafecto + a.idp)) * a.tipocambio, 2), 0), 0) AS servicio, 0 AS servicioexpo,
0 AS exentobien, 0 AS exentobienexpo, 0 AS exentoservicio, 0 AS exentoservicioexpo,
'' AS tipoconstancia, '' AS noconstancia, '' AS valorconstancia, '' AS peqcontbienes, '' AS peqcontservicios, '' AS peqcontbienexpo, '' AS peqcontserviciosexpo,
IF(b.pequeniocont = 0, ROUND(a.iva * a.tipocambio, 2), '') AS valorivadocumento, ROUND((a.totfact - (a.noafecto + a.idp)) * a.tipocambio, 2) AS totaldocumento
FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN tipocompra d ON d.id = a.idtipocompra
WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = $d->idempresa AND a.idreembolso = 0 AND a.mesiva = $d->mes AND YEAR(a.fechafactura) = $d->anio AND a.iva <> 0 AND b.pequeniocont = 0
UNION
SELECT a.fechafactura,
$d->establecimiento AS establecimiento, 'C' AS compraventa, c.siglas AS documento, a.serie, a.documento AS numerodocumento, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechadocumento, TRIM(a.nit) AS nit, TRIM(a.proveedor) AS nombre, 'L' AS tipotransaccion,
'' AS tipooperacion, '' AS estadodocumento, '' AS noordced, '' AS noregistroced, '' AS tipodocexpo, '' AS nodocexpo,
IF(a.idtipocompra = 3, IF(a.idtipofactura <> 7, round((a.totfact - (a.noafecto + a.idp)) * a.tipocambio,2), 0), 0) + IF(a.idtipocompra IN(1, 4), IF(a.idtipofactura <> 7, round((a.totfact - (a.noafecto + a.idp)) * a.tipocambio,2), 0), 0) AS bien, 0 AS bienexpo,
IF(a.idtipocompra = 2, IF(a.idtipofactura <> 7, round((a.totfact - (a.noafecto + a.idp)) * a.tipocambio,2), 0), 0) AS servicio, 0 AS servicioexpo,
0 AS exentobien, 0 AS exentobienexpo, 0 AS exentoservicio, 0 AS exentoservicioexpo,
'' AS tipoconstancia, '' AS noconstancia, '' AS valorconstancia, '' AS peqcontbienes, '' AS peqcontservicios, '' AS peqcontbienexpo, '' AS peqcontserviciosexpo,
IF(b.pequeniocont = 0, ROUND(a.iva * a.tipocambio, 2), '') AS valorivadocumento, ROUND((a.totfact - (a.noafecto + a.idp)) * a.tipocambio, 2) AS totaldocumento
FROM compra a INNER JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN proveedor b ON b.id = a.idproveedor LEFT JOIN tipocompra d ON d.id = a.idtipocompra
WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = $d->idempresa AND a.idreembolso > 0 AND a.mesiva = $d->mes AND YEAR(a.fechafactura) = $d->anio AND a.iva <> 0 AND (b.pequeniocont = 0 OR b.pequeniocont IS NULL) 
ORDER BY 1, 4, 5, 6, 9";

    $documentos = $db->getQuery($query);
    $cntDocs = count($documentos);
    for($i = 0; $i < $cntDocs; $i++){
        $doc = $documentos[$i];
		$doc->valorivadocumento = '';
        if($doc->compraventa == 'V'){
            $doc->noconstancia = (int)$doc->noconstancia != 0 ? $doc->noconstancia : '';
            $doc->valorconstancia = (float)$doc->valorconstancia != 0 ? $doc->valorconstancia : '';
            /*$doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';
            $doc-> = (float)$doc-> != 0 ? $doc-> : '';*/
        }
    }

    //print $db->doSelectASJson($query);
    print json_encode($documentos);
});

$app->get('/gettxt/:establecimiento/:idempresa/:mes/:anio/:nombre', function($establecimiento, $idempresa, $mes, $anio, $nombre) use($app){
    $db = new dbcpm();
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($nombre).'.asl"');

    //$url = 'http://52.35.3.1:5489/api/report';
    $url = 'http://localhost:5489/api/report';
    //$data = ['template' => ['shortid' => 'Bk--4KiDz'], 'data' => ['idempresa' => "$idempresa", 'establecimiento' => "$establecimiento", 'mes' => "$mes", 'anio' => "$anio"]];
    $data = ['template' => ['shortid' => 'SkeEL5jvz'], 'data' => ['idempresa' => "$idempresa", 'establecimiento' => "$establecimiento", 'mes' => "$mes", 'anio' => "$anio"]];
    //print json_encode($data);

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    print iconv('UTF-8','Windows-1252', $respuesta);
});

$app->run();