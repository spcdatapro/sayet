<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/correlativo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $correlativo = new stdClass();

    //Datos del banco
    $query = "SELECT a.nombre, b.simbolo, a.nocuenta, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, c.nomempresa AS empresa ";
    $query.= "FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.id = $d->idbanco";
    $correlativo->banco = $db->getQuery($query)[0];

    //Documentos
    $query = "SELECT DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, CONCAT(a.tipotrans, a.numero) AS documento, ";
    $query.= "IF(b.suma = 1, IF(a.anulado = 0, FORMAT(a.monto, 2), NULL), NULL) AS credito, ";
    $query.= "IF(b.suma = 0, IF(a.anulado = 0, FORMAT(a.monto, 2), NULL), NULL) AS debito, ";
    $query.= "a.beneficiario, a.concepto ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
    $query.= $d->tipo != '' ? "AND a.tipotrans = '$d->tipo' " : '';
    $query.= "ORDER BY a.fecha, a.numero";
    $correlativo->docs = $db->getQuery($query);

    //Sumatorias
    $query = "SELECT FORMAT(SUM(IF(b.suma = 1, a.monto, 0.00)), 2) AS credito, FORMAT(SUM(IF(b.suma = 0, a.monto, 0.00)), 2) AS debito ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
    $query.= $d->tipo != '' ? "AND a.tipotrans = '$d->tipo' " : '';
    $correlativo->sumas = $db->getQuery($query)[0];

    print json_encode($correlativo);
});

$app->run();