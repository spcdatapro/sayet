<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/conciliacion', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conciliacion = new stdClass();

    //Datos del banco
    $query = "SELECT a.nombre, b.simbolo, FORMAT($d->saldobco, 2) AS saldobco, a.nocuenta, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, c.nomempresa AS empresa ";
    $query.= "FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.id = $d->idbanco";
	//print $query;
    $conciliacion->banco = $db->getQuery($query)[0];

    //Documentos no operados
    $query = "SELECT COUNT(a.id) AS cantidad, b.descripcion, FORMAT(SUM(a.monto), 2) AS sumatipo, b.orden ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.tipotrans IN(SELECT abreviatura FROM tipomovtranban WHERE suma = 1) AND (";
    //$query.= "(a.operado = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr') OR ";
    $query.= "(a.operado = 0 AND a.fecha <= '$d->falstr') OR ";
    //$query.= "(a.operado = 1 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= "(a.operado = 1 AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= ") ";
    $query.= "GROUP BY a.tipotrans ";
    $query.= "UNION ";
    $query.= "SELECT 0 AS cantidad, descripcion, 0.00 AS sumatipo, orden ";
    $query.= "FROM tipomovtranban WHERE suma = 1 AND abreviatura NOT IN(SELECT b.abreviatura ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.tipotrans IN(SELECT abreviatura FROM tipomovtranban WHERE suma = 1) AND (";
    //$query.= "(a.operado = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr') OR ";
    $query.= "(a.operado = 0 AND a.fecha <= '$d->falstr') OR ";
    //$query.= "(a.operado = 1 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')) ";
    $query.= "(a.operado = 1 AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')) ";
    $query.= "GROUP BY a.tipotrans) ";
    $query.= "ORDER BY 4";
	//print $query;
    $conciliacion->suman = $db->getQuery($query);

    $query = "SELECT FORMAT(SUM(a.monto), 2) ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.tipotrans IN(SELECT abreviatura FROM tipomovtranban WHERE suma = 1) AND (";
    //$query.= "(a.operado = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr') OR ";
    $query.= "(a.operado = 0 AND a.fecha <= '$d->falstr') OR ";
    //$query.= "(a.operado = 1 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= "(a.operado = 1 AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= ")";
    $conciliacion->totsuman = $db->getOneField($query);

    $query = "SELECT COUNT(a.id) AS cantidad, b.descripcion, FORMAT(SUM(a.monto), 2) AS sumatipo, b.orden ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.tipotrans IN(SELECT abreviatura FROM tipomovtranban WHERE suma = 0) AND (";
    //$query.= "(a.operado = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr') OR ";
    $query.= "(a.operado = 0 AND a.fecha <= '$d->falstr') OR ";
    //$query.= "(a.operado = 1 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= "(a.operado = 1 AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= ") ";
    $query.= "GROUP BY a.tipotrans ";
    $query.= "UNION ";
    $query.= "SELECT 0 AS cantidad, descripcion, 0.00 AS sumatipo, orden ";
    $query.= "FROM tipomovtranban WHERE suma = 0 AND abreviatura NOT IN(SELECT b.abreviatura ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.tipotrans IN(SELECT abreviatura FROM tipomovtranban WHERE suma = 0) AND (";
    //$query.= "(a.operado = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr') OR ";
    $query.= "(a.operado = 0 AND a.fecha <= '$d->falstr') OR ";
    //$query.= "(a.operado = 1 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')) ";
    $query.= "(a.operado = 1 AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')) ";
    $query.= "GROUP BY a.tipotrans) ";
    $query.= "ORDER BY 4";
    $conciliacion->restan = $db->getQuery($query);

    $query = "SELECT FORMAT(SUM(a.monto), 2) ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.tipotrans IN(SELECT abreviatura FROM tipomovtranban WHERE suma = 0) AND (";
    //$query.= "(a.operado = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr') OR ";
    $query.= "(a.operado = 0 AND a.fecha <= '$d->falstr') OR ";
    //$query.= "(a.operado = 1 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= "(a.operado = 1 AND a.fecha <= '$d->falstr' AND a.fechaoperado > '$d->falstr')";
    $query.= ")";
    $conciliacion->totrestan = $db->getOneField($query);

    $conciliacion->saldo = $db->getOneField("SELECT FORMAT(".((float)$d->saldobco + (float)str_replace(',', '', $conciliacion->totsuman) - (float)str_replace(',', '', $conciliacion->totrestan)).", 2)");

    print json_encode($conciliacion);
});

$app->run();