<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/existe/:idempresa/:anio', function($idempresa, $anio){
    $db = new dbcpm();
    $cont = (int)$db->getOneField("SELECT COUNT(id) FROM directa WHERE idempresa = $idempresa AND YEAR(fecha) = $anio AND tipocierre > 0 AND tipocierre <= 4");
    print json_encode(['existe' => ($cont > 0 ? 1 : 0)]);
});

function generaPartida($d, $db, $cuentas, $tipocierre, $fcierre){
    $tipos = [1 => 'cierre de balances', 2 => 'cierre de gastos', 3 => 'cierre de ingresos', 4 => 'apertura del ejercicio contable'];
    $query = "INSERT INTO directa (idempresa, fecha, tipocierre, concepto) VALUES($d->idempresa, '$d->falstr', $tipocierre, 'Partida de ".$tipos[$tipocierre].($tipocierre < 4 ? " al $fcierre" : "")."')";
    $db->doQuery($query);
    $idenc = $db->getLastId();
    foreach($cuentas as $cierre){
        $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= "4, $idenc, ".$cierre['idcuentac'].", ".$cierre['debe'].", ".$cierre['haber'].", ";
        $query.= "'Partida de ".$tipos[$tipocierre].($tipocierre < 4 ? " al $fcierre" : "")."'";
        $query.= ")";
        $db->doQuery($query);
    }
    return $idenc;
}

$app->post('/cierre', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->falstr = $d->anio.'-12-31';
    $fechaApertura = (string)((int)$d->anio + 1).'-01-01';
    $generadas = '';
    //Cálculo de saldos al final del período
    $db->doQuery("DELETE FROM calccierre");
    $db->doQuery("ALTER TABLE calccierre AUTO_INCREMENT = 1");
    $actpascap = [3, 4, 5, 2, 1]; //3 = Activo; 4 = Pasivo; 5 = Capital; 2 = Gastos; 1 = Ingresos
    $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8];
    foreach($actpascap AS $apc){
        $ctasapc = $db->getQuery("SELECT b.descripcion AS tipo, a.empiezancon FROM confrptcont a INNER JOIN tiporptconfcont b ON b.id = a.idtiporptconfcont WHERE b.id = $apc");
        foreach($ctasapc as $cta){
            $inicianCon = preg_split('/\D/', $cta->empiezancon, NULL, PREG_SPLIT_NO_EMPTY);
            foreach($inicianCon as $ini){
                $query = "INSERT INTO calccierre(idcuentac, codigo, nombrecta, tipo) ";
                $query.= "SELECT id, codigo, nombrecta, $apc FROM cuentac WHERE idempresa = ".$d->idempresa." AND tipocuenta = 0 AND codigo LIKE '".$ini."%' ORDER BY codigo";
                $db->doQuery($query);
                foreach($origenes as $k => $v){
                    $query = "UPDATE calccierre a INNER JOIN (".getSelectBalances($v, $d, $ini).") b ON a.idcuentac = b.idcuenta SET a.saldo = a.saldo + b.anterior";
                    $db->doQuery($query);
                }
            }
        }
    }

    $cierre = new stdClass();

    //CIERRE DE BALANCES
    //Cuentas de activo con saldo a fecha de cierre deben ir en el lado del HABER
    $query = "SELECT id, idcuentac, codigo, nombrecta, tipo, saldo FROM calccierre WHERE tipo = 3 AND saldo <> 0.00 ORDER BY codigo";
    $activo = $db->getQuery($query);
    $cntActivo = count($activo);
    $sumaActivo = 0.00;
    for($i = 0; $i < $cntActivo; $i++){
        $act = $activo[$i];
        $cierre->balances[] = ['idcuentac' => $act->idcuentac, 'codigo' => $act->codigo, 'nombrecta' => $act->nombrecta, 'debe' => 0.00, 'haber' => round((float)$act->saldo, 2)];
        $sumaActivo += (float)$act->saldo;
    }
    //Cuentas de pasivo o capital con saldo a fecha de cierre deben ir en el lado del DEBE con el saldo multiplicado por -1
    $query = "SELECT id, idcuentac, codigo, nombrecta, tipo, saldo FROM calccierre WHERE tipo IN(4, 5) AND saldo <> 0.00 ORDER BY tipo, codigo";
    $pascap = $db->getQuery($query);
    $cntPasCap = count($pascap);
    $sumaPasCap = 0.00;
    for($i = 0; $i < $cntPasCap; $i++){
        $pc = $pascap[$i];
        $cierre->balances[] = ['idcuentac' => $pc->idcuentac, 'codigo' => $pc->codigo, 'nombrecta' => $pc->nombrecta,
            'debe' => round((float)$pc->saldo * ((int)$pc->tipo == 4 ? -1 : 1), 2),
            'haber' => 0.00];
        $sumaPasCap += ((float)$pc->saldo * ((int)$pc->tipo == 4 ? -1 : 1));
    }

    $query = "SELECT id AS idcuentac, codigo, nombrecta, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fcierre, DATE_FORMAT('$fechaApertura', '%d/%m/%Y') AS fapertura ";
    $query.= "FROM cuentac WHERE id = (SELECT idcuentac FROM detcontempresa WHERE idempresa = $d->idempresa AND idtipoconfig = 27)";
    $cuadradora = $db->getQuery($query)[0];

    //Se agregega la cuenta cuadradora con monto en el DEBE = SUMATORIA DE CUENTAS DE ACTIVO - (SUMATORIA DE CUENTAS DE PASIVO + SUMATORIA DE CUENTAS DE CAPITAL)
    $cierre->balances[] = ['idcuentac' => $cuadradora->idcuentac, 'codigo' => $cuadradora->codigo, 'nombrecta' => $cuadradora->nombrecta, 'debe' => round($sumaActivo - $sumaPasCap, 2), 'haber' => 0.00];

    $generadas = generaPartida($d, $db, $cierre->balances, 1, $cuadradora->fcierre);

    //CIERRE DE GASTOS
    //Cuentas de gastos con saldo a fecha de cierre deben ir en el lado del HABER
    $query = "SELECT id, idcuentac, codigo, nombrecta, tipo, saldo FROM calccierre WHERE tipo = 2 AND saldo <> 0.00 ORDER BY codigo";
    $gastos = $db->getQuery($query);
    $cntGastos = count($gastos);
    $sumaGastos = 0.00;
    for($i = 0; $i < $cntGastos; $i++){
        $gasto = $gastos[$i];
        $cierre->gastos[] = ['idcuentac' => $gasto->idcuentac, 'codigo' => $gasto->codigo, 'nombrecta' => $gasto->nombrecta, 'debe' => 0.00, 'haber' => round((float)$gasto->saldo, 2)];
        $sumaGastos += (float)$gasto->saldo;
    }

    //Se agregega la cuenta cuadradora con monto en el DEBE = SUMATORIA DE CUENTAS DE GASTO
    $cierre->gastos[] = ['idcuentac' => $cuadradora->idcuentac, 'codigo' => $cuadradora->codigo, 'nombrecta' => $cuadradora->nombrecta, 'debe' => round($sumaGastos, 2), 'haber' => 0.00];

    $generadas.= ', '.generaPartida($d, $db, $cierre->gastos, 2, $cuadradora->fcierre);

    //CIERRE DE INGRESOS
    //Cuentas de ingresos con saldo a fecha de cierre deben ir en el lado del HABER
    $query = "SELECT id, idcuentac, codigo, nombrecta, tipo, saldo FROM calccierre WHERE tipo = 1 AND saldo <> 0.00 ORDER BY codigo";
    $ingresos = $db->getQuery($query);
    $cntIngresos = count($ingresos);
    $sumaIngresos = 0.00;
    for($i = 0; $i < $cntIngresos; $i++){
        $ingreso = $ingresos[$i];
        $cierre->ingresos[] = ['idcuentac' => $ingreso->idcuentac, 'codigo' => $ingreso->codigo, 'nombrecta' => $ingreso->nombrecta, 'debe' => round((float)$ingreso->saldo * -1, 2), 'haber' => 0.00];
        $sumaIngresos += ((float)$ingreso->saldo * -1);
    }

    //Se agregega la cuenta cuadradora con monto en el HABER = SUMATORIA DE CUENTAS DE INGRESO
    $cierre->ingresos[] = ['idcuentac' => $cuadradora->idcuentac, 'codigo' => $cuadradora->codigo, 'nombrecta' => $cuadradora->nombrecta, 'debe' => 0.00, 'haber' => round($sumaIngresos, 2)];

    $generadas.= ', '.generaPartida($d, $db, $cierre->ingresos, 3, $cuadradora->fcierre);

    //APERTURA DEL PERÍODO
    //Cuentas de activo con saldo a fecha de cierre deben ir en el lado del DEBE
    $query = "SELECT id, idcuentac, codigo, nombrecta, tipo, saldo FROM calccierre WHERE tipo = 3 AND saldo <> 0.00 ORDER BY codigo";
    $activo = $db->getQuery($query);
    $cntActivo = count($activo);
    $sumaActivo = 0.00;
    for($i = 0; $i < $cntActivo; $i++){
        $act = $activo[$i];
        $cierre->apertura[] = ['idcuentac' => $act->idcuentac, 'codigo' => $act->codigo, 'nombrecta' => $act->nombrecta, 'debe' => round((float)$act->saldo, 2), 'haber' => 0.00];
        $sumaActivo += (float)$act->saldo;
    }
    //Cuentas de pasivo o capital con saldo a fecha de cierre deben ir en el lado del HABER con el saldo multiplicado por -1
    $query = "SELECT id, idcuentac, codigo, nombrecta, tipo, saldo FROM calccierre WHERE tipo IN(4, 5) AND saldo <> 0.00 ORDER BY tipo, codigo";
    $pascap = $db->getQuery($query);
    $cntPasCap = count($pascap);
    $sumaPasCap = 0.00;
    for($i = 0; $i < $cntPasCap; $i++){
        $pc = $pascap[$i];
        $cierre->apertura[] = ['idcuentac' => $pc->idcuentac, 'codigo' => $pc->codigo, 'nombrecta' => $pc->nombrecta, 'debe' => 0.00, 'haber' => round((float)$pc->saldo * ((int)$pc->tipo == 4 ? -1 : 1), 2)];
        $sumaPasCap += ((float)$pc->saldo * ((int)$pc->tipo == 4 ? -1 : 1));
    }

    //Se agregega la cuenta cuadradora con monto en el HABER = VALOR ABSOLUTO(SUMATORIA DE CUENTAS DE ACTIVO - (SUMATORIA DE CUENTAS DE PASIVO + SUMATORIA DE CUENTAS DE CAPITAL))
    $cierre->apertura[] = ['idcuentac' => $cuadradora->idcuentac, 'codigo' => $cuadradora->codigo, 'nombrecta' => $cuadradora->nombrecta, 'debe' => 0.00, 'haber' => round(abs($sumaActivo - $sumaPasCap), 2)];

    //Se agregó este cambio para que la fecha de la partida de reapertura sea del primero de enero del siguiente año
    $d->falstr = $fechaApertura;
    $generadas.= ', '.generaPartida($d, $db, $cierre->apertura, 4, $cuadradora->fapertura);

    //print json_encode($cierre);
    print json_encode(['generadas' => $generadas]);

});

function getSelectBalances($cual, $d, $ini){
    $query = "";
    switch($cual){
        case 1:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN cuentac d ON d.id = a.idcuenta ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND ((b.anulado = 0 AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha <= '$d->falstr')) AND c.idempresa = ".$d->idempresa." AND d.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
        case 2:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND b.fechaingreso <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND b.idreembolso = 0 AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
        case 3:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND b.fecha <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
        case 4:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN directa b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 4 AND a.activada = 1 AND a.anulado = 0 AND b.fecha <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
        case 5:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN reembolso d ON d.id = b.idreembolso ";
            $query.= "WHERE a.origen = 2 AND a.anulado = 0 AND b.idreembolso > 0 AND b.fechaingreso <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
            /*
        case 6:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN contrato b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 6 AND a.activada = 1 AND a.anulado = 0 AND b.fechacontrato <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
            */
        case 7:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN reciboprov b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 7 AND a.activada = 1 AND a.anulado = 0 AND b.fecha <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
        case 8:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN recibocli b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 8 AND a.activada = 1 AND a.anulado = 0 AND b.fecha <= '$d->falstr' AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
            /*
        case 9:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN cuentac d ON d.id = a.idcuenta ";
            $query.= "WHERE a.origen = 9 AND a.activada = 1 AND ";
            $query.= "((b.anulado = 0 AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida <= '$d->falstr' AND b.fechaanula > '$d->falstr')) AND ";
            $query.= "c.idempresa = ".$d->idempresa." AND d.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            break;
            */
    }
    return $query;
}

$app->run();