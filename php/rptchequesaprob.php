<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/getcheques', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.numero, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.monto, REPLACE(b.nocuenta, '-', '') AS nocuenta, ";
    $query.= "SUBSTR(CONCAT(TRIM(limpiaString(c.abreviatura)), ' ', TRIM(limpiaString(a.beneficiario)), ' ', TRIM(limpiaString(a.concepto))), 1, 57) AS descripcion ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE a.tipotrans = 'C' AND a.anulado = 0 AND a.fecha = '$d->fechastr' AND UPPER(TRIM(b.nombre)) LIKE '%INDUSTRIAL%' AND b.idempresa <> 16 AND b.idmoneda = $d->idmoneda ";
    //"AND a.idbanco IN($d->bancos) ";
    $query.= (int)$d->idempresa == 0 ? "" : "AND b.idempresa = $d->idempresa ";
    $query.= "ORDER BY b.ordensumario, a.numero";
    //print $query;
    print $db->doSelectAsJSON($query);
});

$app->get('/gettxt/:idempresa/:fechastr/:idmoneda/:nombre', function($idempresa, $fechastr, $idmoneda, $nombre) use($app){
    $db = new dbcpm();
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/csv;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($nombre).'.csv"');

    //$url = 'http://104.197.209.57:5489/api/report';
    $url = 'http://localhost:5489/api/report';
    $data = ['template' => ['shortid' => 'B1ICfUfDb'], 'data' => ['idempresa' => "$idempresa", 'fechastr' => "$fechastr", 'idmoneda' => "$idmoneda"]];
    //print json_encode($data);

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    //print iconv('UTF-8','Windows-1252', preg_replace('/[^\P{C}\n]+/u', '', $respuesta));
	print iconv('UTF-8','Windows-1252', $respuesta);
});


$app->run();