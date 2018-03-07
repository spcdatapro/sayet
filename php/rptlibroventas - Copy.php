<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/rptlibventas/:idempresa/:mes/:anio', function($idempresa, $mes, $anio){
    $db = new dbcpm();
    $query = "SELECT a.fecha AS fechafactura, c.siglas AS tipodocumento, a.serie, a.numero AS documento, a.nit AS nit, ";
    $query.= "IF(a.anulada = 0, TRIM(a.nombre), CONCAT(TRIM(a.nombre), ' (ANULADA)')) AS cliente, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa IN(1, 2, 4), IF(c.generaiva = 0 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS exento, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 4, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS activo, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 1, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS bien, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS servicio, ";
    $query.= "IF(a.anulada = 0, ROUND(a.iva, 2), 0.00) AS iva, IF(a.anulada = 0, ROUND(((a.subtotal - a.noafecto) + a.iva), 2), 0.00) AS totfact ";
    $query.= "FROM factura a LEFT JOIN contrato b ON b.id = a.idcontrato LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "WHERE a.idtipoventa <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.mesiva = ".$mes." AND YEAR(a.fecha) = ".$anio." ";
	/*
    $query.= "UNION ";
    $query.= "SELECT a.fecha AS fechafactura, c.siglas AS tipodocumento, a.serie, a.numero AS documento, a.nit, a.nombre AS cliente, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa IN(1, 2, 4), IF(c.generaiva = 0 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS exento, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 4, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS activo, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 1, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS bien, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS servicio, ";
    $query.= "IF(a.anulada = 0, ROUND(a.iva, 2), 0.00) AS iva, IF(a.anulada = 0, ROUND(((a.subtotal - a.noafecto) + a.iva), 2), 0.00) AS totfact ";
    $query.= "FROM factura a LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "WHERE a.idtipoventa <> 5 AND c.id <> 5 AND a.idcontrato = 0 AND a.idempresa = ".$idempresa." AND a.mesiva = ".$mes." AND YEAR(a.fecha) = ".$anio." ";
	*/
    $query.= "ORDER BY 1, 2, 3, 4";
    print $db->doSelectASJson($query);
});

$app->run();