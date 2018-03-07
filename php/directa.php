<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para partidas directas
$app->get('/lstdirectas/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT id, idempresa, fecha, concepto FROM directa WHERE idempresa = $idempresa ORDER BY fecha, id";
    print $db->doSelectASJson($query);
});

$app->get('/getdirecta/:iddirecta', function($iddirecta){
    $db = new dbcpm();
    $query = "SELECT id, idempresa, fecha, concepto FROM directa WHERE id = $iddirecta";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $concepto = $d->concepto == '' ? "NULL" : "'$d->concepto'";
    $query = "INSERT INTO directa(idempresa, fecha, concepto) VALUES($d->idempresa,'$d->fechastr', $concepto)";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $concepto = $d->concepto == '' ? "NULL" : "'$d->concepto'";
    $query = "UPDATE directa SET fecha = '$d->fechastr', concepto = $concepto WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM directa WHERE id = $d->id");
});

//API para impresion de partidas directas
$app->get('/print/:iddirecta', function($iddirecta) {
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $generales = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.idempresa, b.nomempresa, b.abreviatura, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.concepto ";
    $query.= "FROM directa a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query.= "WHERE a.id = $iddirecta";
    $pd = $db->getQuery($query);
    $directa = new  stdClass();
    if(count($pd) > 0){
        $directa = $pd[0];
        $query = "SELECT a.idcuenta, b.codigo, b.nombrecta, ";
        $query.= "IF(a.debe <> 0, FORMAT(a.debe, 2), '') AS debe, ";
        $query.= "IF(a.haber <> 0, FORMAT(a.haber, 2), '') AS haber, ";
        $query.= "TRIM(a.conceptomayor) AS conceptomayor, NULL AS valcuadre ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.origen = 4 AND a.idorigen = $iddirecta ";
        $query.= "ORDER BY a.id";
        $directa->detalle = $db->getQuery($query);
        if(count($directa->detalle) > 0) {
            $query = "SELECT FORMAT(SUM(a.debe), 2) AS totdebe, FORMAT(SUM(a.haber), 2) AS tothaber, ";
            $query.= "IF(SUM(a.debe) <> SUM(a.haber), 'Partida descuadrada', 'Partida cuadrada') AS cuadre, ";
            $query.= "IF(SUM(a.debe) <> SUM(a.haber), NULL, 1) AS valcuadre ";
            $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
            $query.= "WHERE a.origen = 4 AND a.idorigen = $iddirecta";
            $suma = $db->getQuery($query)[0];
            $directa->detalle[] = [
                'idcuenta' => '',
                'codigo' => '',
                'nombrecta' => 'Total:',
                'debe' => $suma->totdebe,
                'haber' => $suma->tothaber,
                'conceptomayor' => $suma->cuadre,
                'valcuadre' => $suma->valcuadre
            ];
        }
    }

    print json_encode(['generales' => $generales, 'directa' => $directa]);
});

$app->run();