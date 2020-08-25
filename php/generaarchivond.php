<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/generar', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT z.tipo, z.cuenta, LPAD(@row := @row + 1, 20, ' ') AS contador, z.nombre, z.monto, limpiaString(z.concepto) AS concepto
    FROM (
        SELECT 1 AS tipo, LPAD(TRIM(IFNULL(b.cuentabanco, ' ')), 10, ' ') AS cuenta, RPAD(TRIM(limpiaString(a.beneficiario)), 100, ' ') AS nombre, LPAD(a.monto, 25, ' ') AS monto, TRIM(a.concepto) AS concepto
        FROM tranban a
        INNER JOIN proveedor b ON b.id = a.idbeneficiario
        WHERE a.tipotrans = 'B' AND a.anulado = 0 AND a.esplanilla = 0 AND a.origenbene = 1 AND a.fecha = '$d->fechastr' AND a.idbanco = $d->idbanco
        UNION
        SELECT 1 AS tipo, LPAD(TRIM(IFNULL(b.cuentabanco, ' ')), 10, ' ') AS cuenta, RPAD(TRIM(limpiaString(a.beneficiario)), 100, ' ') AS nombre, LPAD(a.monto, 25, ' ') AS monto, TRIM(a.concepto) AS concepto
        FROM tranban a
        INNER JOIN beneficiario b ON b.id = a.idbeneficiario
        WHERE a.tipotrans = 'B' AND a.anulado = 0 AND a.esplanilla = 0 AND a.origenbene = 2 AND a.fecha = '$d->fechastr' AND a.idbanco = $d->idbanco
    ) z, (SELECT @row:= 0) r";    
    print $db->doSelectASJson($query);
});

$app->get('/gettxt/:fechastr/:idbanco', function($fechastr, $idbanco) use($app, $db){
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="notas_debito.txt"');

    $url = 'http://localhost:5489/api/report';    
    $data = ['template' => ['shortid' => 'SJBT_em7P'], 'data' => ['fechastr' => "$fechastr", 'idbanco' => "$idbanco"]];

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    print iconv('UTF-8','Windows-1252', $respuesta);
});

$app->run();