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
    $query.= "AND b.mediopago = 3 ";
    $query.= "ORDER BY c.descripcion, b.nombre, b.apellidos, a.fecha";
    $query.= ") z, (SELECT @row:= 0) r";

    print $db->doSelectASJson($query);
});

$app->get('/gettxt/:idempresa/:fdelstr/:falstr/:nombre', function($idempresa, $fdelstr, $falstr, $nombre) use($app, $db){
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.$nombre.'.txt"');

    $url = 'http://localhost:5489/api/report';
    $data = ['template' => ['shortid' => 'B1BikIhjG'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];
    //$data = ['template' => ['shortid' => 'BJty9IhoM'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    print iconv('UTF-8','Windows-1252', $respuesta);
});

function generand($d, $db, $empleados, $total, $generales){
    $query = "SELECT COUNT(*) FROM tranban WHERE idbanco = $d->idbanco AND tipotrans = 'B' AND esplanilla = 1 AND fechaplanilla = '$d->falstr'";
    $existe = (int)$db->getOneField($query) > 0;
    if(!$existe){
        $query = "INSERT INTO tranban(";
        $query.= "idbanco, tipotrans, numero, esplanilla, fechaplanilla, fecha, monto, beneficiario, concepto, tipocambio, idempresa";
        $query.= ") VALUES (";
        $query.= "$d->idbanco, 'B', $d->notadebito, 1, '$d->falstr', '$d->falstr', $total, 'PLANILLA EMPLEADOS', 'PLANILLA $generales->concepto', 1.00, $d->idempresa";
        $query.= ")";
        $db->doQuery($query);
        $lastId = (int)$db->getLastId();
        if($lastId > 0){
            $cntEmpleados = count($empleados);
            for($i = 0; $i < $cntEmpleados; $i++){
                $empleado = $empleados[$i];
                $query = "SELECT id FROM cuentac WHERE idempresa = $d->idempresa AND TRIM(codigo) = '".trim($empleado->cuentacontable)."'";
                $idcuentac = (int)$db->getOneField($query);
                if($idcuentac > 0){
                    $query = "INSERT INTO detallecontable(";
                    $query.= "origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado";
                    $query.= ") VALUES(";
                    $query.= "1, $lastId, $idcuentac, $empleado->monto, 0.00, 'PLANILLA $generales->concepto', 1, 0";
                    $query.= ")";
                    $db->doQuery($query);
                }
            }
            $query = "SELECT idcuentac FROM banco WHERE id = $d->idbanco";
            $idctabco = (int)$db->getOneField($query);
            if($idctabco > 0){
                $query = "INSERT INTO detallecontable(";
                $query.= "origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado";
                $query.= ") VALUES(";
                $query.= "1, $lastId, $idctabco, 0.00, $total, 'PLANILLA $generales->concepto', 1, 0";
                $query.= ")";
                $db->doQuery($query);
            }
            $query = "UPDATE empresa SET ndplanilla = $d->notadebito + 1 WHERE id = $d->idempresa";
            $db->doQuery($query);
        }
    }
};

$app->post('/generand', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT a.nomempresa AS empresa, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, '$d->notadebito' AS notadebito, ";
    $query.= "CONCAT('DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', ";
    $query.= "LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto, ";
    $query.= "CONCAT(a.abreviatura, 'PLA', DATE_FORMAT('$d->fdelstr', '%Y%m%d'), DATE_FORMAT('$d->falstr', '%Y%m%d')) AS archivo ";
    $query.= "FROM empresa a WHERE a.id = $d->idempresa";
    //print $query;
    $generales = $db->getQuery($query)[0];

    $query = "SELECT z.tipo, z.cuenta, @row := @row + 1 AS contador, z.nombre, z.monto, z.cuentacontable ";
    $query.= "FROM (";
    $query.= "SELECT 3 AS tipo, TRIM(b.cuentabanco) AS cuenta, TRIM(CONCAT(TRIM(b.nombre), ' ', IFNULL(TRIM(b.apellidos), ''))) AS nombre, a.liquido AS monto, b.cuentapersonal AS cuentacontable ";
    $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido <> 0 AND b.cuentabanco IS NOT NULL ";
    $query.= "AND b.mediopago = 3 ";
    $query.= "ORDER BY c.descripcion, b.nombre, b.apellidos, a.fecha";
    $query.= ") z, (SELECT @row:= 0) r";
    //print $query;
    $empleados = $db->getQuery($query);
    $cntEmpleados = count($empleados);

    if($cntEmpleados > 0){
        $qSuma = "SELECT SUM(y.monto) FROM ($query) y";
        $suma = $db->getOneField($qSuma);
        generand($d, $db, $empleados, $suma, $generales);
        $empleados[] = ['tipo' => 'TOTAL:', 'cuenta' => '', 'contador' => '', 'nombre' => '', 'monto' => number_format($suma, 2)];

        for($i = 0; $i < $cntEmpleados; $i++){
            $empleado = $empleados[$i];
            $empleado->monto = number_format((float)$empleado->monto, 2);
        }
    }

    print json_encode(['generales' => $generales, 'empleados' => $empleados]);

});

function generachq($d, $db, $empresa, $empleado){
    $query = "SELECT COUNT(*) FROM tranban WHERE idbanco = $empresa->idbanco AND tipotrans = 'C' AND esplanilla = 1 AND fechaplanilla = '$d->falstr' AND idempleado = $empleado->idempleado";
    $existe = (int)$db->getOneField($query) > 0;
    if(!$existe){
        $query = "INSERT INTO tranban(";
        $query.= "idbanco, tipotrans, numero, esplanilla, fechaplanilla, fecha, monto, beneficiario, concepto, tipocambio, idempresa, idempleado";
        $query.= ") VALUES (";
        $query.= "$empresa->idbanco, 'C', $empresa->correlativo, 1, '$d->falstr', '$d->falstr', $empleado->monto, '$empleado->nombre', 'PLANILLA $empleado->concepto', 1.00, $empresa->idempresa, $empleado->idempleado";
        $query.= ")";
        // print $query;
        $db->doQuery($query);
        $lastId = (int)$db->getLastId();
        if($lastId > 0){
            $query = "SELECT id FROM cuentac WHERE idempresa = $empresa->idempresa AND TRIM(codigo) = '".trim($empleado->cuentacontable)."'";
            $idcuentac = (int)$db->getOneField($query);
            if($idcuentac > 0){
                $query = "INSERT INTO detallecontable(";
                $query.= "origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado";
                $query.= ") VALUES(";
                $query.= "1, $lastId, $idcuentac, $empleado->monto, 0.00, 'PLANILLA $empleado->concepto', 1, 0";
                $query.= ")";
                $db->doQuery($query);
            }

            $query = "SELECT idcuentac FROM banco WHERE id = $empresa->idbanco";
            $idctabco = (int)$db->getOneField($query);
            if($idctabco > 0){
                $query = "INSERT INTO detallecontable(";
                $query.= "origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado";
                $query.= ") VALUES(";
                $query.= "1, $lastId, $idctabco, 0.00, $empleado->monto, 'PLANILLA $empleado->concepto', 1, 0";
                $query.= ")";
                $db->doQuery($query);
            }
            $query = "UPDATE banco SET correlativo = $empresa->correlativo + 1 WHERE id = $empresa->idbanco";
            $db->doQuery($query);
        }
        return $empresa->correlativo;
    }
    return 0;
};

$app->post('/generachq', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $cntEmpresas = count($d->empresas);
    $generados = [];
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $d->empresas[$i];
        $query = "SELECT z.tipo, z.cuenta, @row := @row + 1 AS contador, z.nombre, z.monto, z.cuentacontable, z.idempleado, z.concepto ";
        $query.= "FROM (";
        $query.= "SELECT 3 AS tipo, TRIM(b.cuentabanco) AS cuenta, TRIM(CONCAT(TRIM(b.nombre), ' ', IFNULL(TRIM(b.apellidos), ''))) AS nombre, a.liquido AS monto, b.cuentapersonal AS cuentacontable, b.id AS idempleado, ";
        $query.= "CONCAT('DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', ";
        $query.= "LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto ";
        $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto ";
        $query.= "WHERE a.idempresa = $empresa->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido <> 0 ";
        $query.= "AND b.mediopago = 1 ";
        $query.= "ORDER BY c.descripcion, b.nombre, b.apellidos, a.fecha";
        $query.= ") z, (SELECT @row:= 0) r";
        //print $query;
        $empleados = $db->getQuery($query);
        $cntEmpleados = count($empleados);

        $empresa->correlativo = 0;
        $cntBancos = count($empresa->bancos);
        $banco = '';
        for($j = 0; $j < $cntBancos; $j++){
            if((int)$empresa->idbanco === (int)$empresa->bancos[$j]->id){
                $empresa->correlativo = (int)$empresa->bancos[$j]->correlativo;
                $banco = $empresa->bancos[$j]->bancomoneda;
            }
        }

        $numeros = [];
        for($j = 0; $j < $cntEmpleados; $j++){
            $empleado = $empleados[$j];
            $numero = generachq($d, $db, $empresa, $empleado);
            if($numero > 0){
                $numeros[] = ['numero' => $numero, 'beneficiario' => $empleado->nombre, 'monto' => number_format((float)$empleado->monto, 2)];
                $empresa->correlativo++;
            }
        }
        if(count($numeros) > 0){
            $generados[] = ['empresa' => $empresa->empresa, 'banco' => $banco, 'cheques' => $numeros];
        }
    }
    print json_encode(['generados' => $generados]);
});

$app->run();