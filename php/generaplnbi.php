<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/generar', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT z.tipo, z.cuenta, LPAD(@row := @row + 1, 20, ' ') AS contador, z.nombre, z.monto, ";
    $query.= "CONCAT('PLANILLA DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', ";
    $query.= "LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto ";
    $query.= "FROM (";
    $query.= "SELECT 1 AS tipo, LPAD(TRIM(b.cuentabanco), 10, ' ') AS cuenta, RPAD(CONCAT(TRIM(b.nombre), ' ', IFNULL(TRIM(b.apellidos), '')), 100, ' ') AS nombre, LPAD(a.liquido, 25,' ') AS monto ";
    $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido <> 0 AND b.cuentabanco IS NOT NULL ";
    //$query.= "AND b.formapago = 3 ";
    $query.= "ORDER BY c.descripcion, b.nombre, b.apellidos, a.fecha";
    $query.= ") z, (SELECT @row:= 0) r";

    print $db->doSelectASJson($query);
});

$app->get('/gettxt/:idempresa/:fdelstr/:falstr/:nombre', function($idempresa, $fdelstr, $falstr, $nombre) use($app, $db){
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.$nombre.'.txt"');

    $url = 'http://localhost:5489/api/report';
    //$data = ['template' => ['shortid' => 'B1BikIhjG'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];
    $data = ['template' => ['shortid' => 'BJty9IhoM'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    print iconv('UTF-8','Windows-1252', $respuesta);
});

function generand($d, $db, $empleados, $total) {

};

$app->post('/generand', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT a.nomempresa AS empresa, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, '$d->notadebito' AS notadebito, ";
    $query.= "CONCAT('PLANILLA DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', ";
    $query.= "LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto ";
    $query.= "FROM empresa a WHERE a.id = $d->idempresa";
    //print $query;
    $generales = $db->getQuery($query)[0];

    $query = "SELECT z.tipo, z.cuenta, @row := @row + 1 AS contador, z.nombre, z.monto, z.cuentacontable ";
    $query.= "FROM (";
    $query.= "SELECT 3 AS tipo, TRIM(b.cuentabanco) AS cuenta, TRIM(CONCAT(TRIM(b.nombre), ' ', IFNULL(TRIM(b.apellidos), ''))) AS nombre, a.liquido AS monto, b.cuentapersonal AS cuentacontable ";
    $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido <> 0 AND b.cuentabanco IS NOT NULL ";
    //$query.= "AND b.formapago = 3 ";
    $query.= "ORDER BY c.descripcion, b.nombre, b.apellidos, a.fecha";
    $query.= ") z, (SELECT @row:= 0) r";
    //print $query;
    $empleados = $db->getQuery($query);
    $cntEmpleados = count($empleados);

    if($cntEmpleados > 0){
        $qSuma = "SELECT SUM(y.monto) FROM ($query) y";
        $suma = $db->getOneField($qSuma);
        $empleados[] = ['tipo' => 'TOTAL:', 'cuenta' => '', 'contador' => '', 'nombre' => '', 'monto' => number_format($suma, 2)];

        generand($d, $db, $empleados, $suma);

        for($i = 0; $i < $cntEmpleados; $i++){
            $empleado = $empleados[$i];
            $empleado->monto = number_format((float)$empleado->monto, 2);
        }
    }

    print json_encode(['generales' => $generales, 'empleados' => $empleados]);

});



$app->run();