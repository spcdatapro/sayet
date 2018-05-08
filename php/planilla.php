<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/empresas', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT DISTINCT a.idempresa, b.nomempresa AS empresa, b.ndplanilla, NULL as idbanco ";
    $query.= "FROM plnnomina a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN plnempleado c ON c.id = a.idplnempleado ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND c.mediopago = $d->mediopago ORDER BY b.ordensumario";
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $empresas[$i];
        $query = "SELECT a.id, b.id AS idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS nombrecta, ";
        $query.= "a.nombre, a.nocuenta, a.siglas, a.nomcuenta, a.idmoneda, CONCAT(c.nommoneda,' (',c.simbolo,')') AS descmoneda, ";
        $query.= "CONCAT(a.nombre, ' (', c.simbolo,')') AS bancomoneda, a.correlativo, c.tipocambio, CONCAT(a.nombre, ' (', c.simbolo,') (Sigue el No. ', a.correlativo,')') AS bancomonedacorrela, ";
        $query.= "a.idtipoimpresion, d.descripcion AS tipoimpresion, d.formato, c.eslocal AS monedalocal, a.debaja ";
        $query.= "FROM banco a INNER JOIN cuentac b ON b.id = a.idcuentac ";
        $query.= "INNER JOIN moneda c ON c.id = a.idmoneda ";
        $query.= "LEFT JOIN tipoimpresioncheque d ON d.id = a.idtipoimpresion ";
        $query.= "WHERE a.idempresa = ".$empresa->idempresa." ORDER BY a.nombre";
        $empresa->bancos = $db->getQuery($query);
    }
    print json_encode($empresas);
});

$app->post('/generado', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT COUNT(*) FROM tranban WHERE tipotrans = '$d->tipo' AND esplanilla = 1 AND fechaplanilla = '$d->falstr' AND anulado = 0";
    $generado = (int)$db->getOneField($query) > 0;
    print json_encode(['generado' => ($generado ? 1: 0)]);
});

$app->run();