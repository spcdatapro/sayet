<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function queryTipoEmpresa($personal, $idmoneda = '1'){
    $query = "SELECT a.id, CONCAT(a.siglas, ' / ', a.nocuenta) AS empresa, c.simbolo AS moneda, c.eslocal ";
    $query.= "FROM banco a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "WHERE a.debaja = 0 AND b.propia = 1 AND b.espersonal = $personal AND c.id = $idmoneda ";
    $query.= "ORDER BY a.ordensumario";
    return $query;
}

function queryTipoEmpresaDos($grupo, $idmoneda = '1'){
    $query = "SELECT a.id, CONCAT(a.siglas, ' / ', a.nocuenta) AS empresa, c.simbolo AS moneda, c.eslocal ";
    $query.= "FROM banco a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "WHERE a.debaja = 0 AND a.gruposumario = $grupo AND b.propia = 1 AND c.id = $idmoneda ";
    $query.= "ORDER BY a.ordensumario";
    return $query;
}

function getSaldoAnterior($idbanco, $fecha, $eslocal){
    //$factor = $eslocal ? "1" : "a.tipocambio";
	$factor = "1";
    $query = "SELECT ";
    $query.= "(SELECT IF(SUM(a.monto * $factor) IS NULL, 0.00, SUM(a.monto * $factor)) AS ingresos FROM tranban a ";
    $query.= "WHERE a.idbanco = $idbanco AND a.fecha < '$fecha' AND a.tipotrans IN('D', 'R')) - ";
    $query.= "(SELECT IF(SUM(a.monto * $factor) IS NULL, 0.00, SUM(a.monto * $factor)) AS salidas FROM tranban a ";
    $query.= "WHERE a.idbanco = $idbanco AND a.fecha < '$fecha' AND a.tipotrans IN('C', 'B')) AS saldoanterior";
    return $query;
}

function getSumaTipoDoc($idbanco, $fecha, $eslocal, $tipodoc, $anulado = 0){
    //$factor = $eslocal ? "1" : "a.tipocambio";
	$factor = "1";
    $query = "SELECT IF(SUM(a.monto * $factor) IS NULL, 0.00, SUM(a.monto * $factor)) AS sumatoria ";
    $query.= "FROM tranban a ";
    $query.= "WHERE a.idbanco = $idbanco AND a.tipotrans = '$tipodoc' AND ";
    $query.= ($anulado == 0 ? "a.fecha" : "a.fechaanula")." = '$fecha'" ;
    return $query;
}

function generaData($d, $db, $empresas){
    $sumas = new stdClass();
    $sumas->saldoanterior = 0.00; $sumas->depositos = 0.00; $sumas->girados = 0.00; $sumas->anulados = 0.00; $sumas->credito = 0.00; $sumas->debito = 0.00; $sumas->saldoactual = 0.00;
    $cnt = count($empresas);
    for($i = 0; $i < $cnt; $i++){
        $empresa = $empresas[$i];
        $empresa->saldoanterior = round((float)$db->getOneField(getSaldoAnterior($empresa->id, $d->fechastr, ((int)$empresa->eslocal == 1))), 2);
        $sumas->saldoanterior += $empresa->saldoanterior;
        $empresa->depositos = round((float)$db->getOneField(getSumaTipoDoc($empresa->id, $d->fechastr, ((int)$empresa->eslocal == 1), 'D')), 2);
        $sumas->depositos += $empresa->depositos;
        $empresa->girados = round((float)$db->getOneField(getSumaTipoDoc($empresa->id, $d->fechastr, ((int)$empresa->eslocal == 1), 'C')), 2);
        $sumas->girados += $empresa->girados;
        //$empresa->anulados = round((float)$db->getOneField(getSumaTipoDoc($empresa->id, $d->fechastr, ((int)$empresa->eslocal == 1), 'C', 1)), 2);
		$empresa->anulados = 0.00;
        $sumas->anulados += $empresa->anulados;
        $empresa->credito = round((float)$db->getOneField(getSumaTipoDoc($empresa->id, $d->fechastr, ((int)$empresa->eslocal == 1), 'R')), 2);
        $sumas->credito += $empresa->credito;
        $empresa->debito = round((float)$db->getOneField(getSumaTipoDoc($empresa->id, $d->fechastr, ((int)$empresa->eslocal == 1), 'B')), 2);
        $sumas->debito += $empresa->debito;
        $empresa->saldoactual = round($empresa->saldoanterior + $empresa->depositos - $empresa->girados + $empresa->credito - $empresa->debito, 2);
        $sumas->saldoactual += $empresa->saldoactual;
    }

    array_push($empresas, [
        "id"=> "0", "empresa"=> "Sub-total:", "moneda"=> $db->getOneField("SELECT simbolo FROM moneda WHERE id = $d->idmoneda"), "eslocal"=> "1",
        "saldoanterior"=> $sumas->saldoanterior, "depositos"=> $sumas->depositos,
        "girados"=> $sumas->girados, "anulados"=> $sumas->anulados, "credito"=> $sumas->credito,
        "debito"=> $sumas->debito, "saldoactual"=> $sumas->saldoactual
    ]);

    return $empresas;
}

function sumaTodo($comercial, $personal){
    $liCom = count($comercial) - 1; $liPer = count($personal) - 1;
    //var_dump($comercial[$liCom]);
    return [
        "id"=> "0", "empresa"=> "GRAN TOTAL:", "moneda"=> "Q", "eslocal"=> "1",
        "saldoanterior"=> ($comercial[$liCom]["saldoanterior"] + $personal[$liPer]["saldoanterior"]),
        "depositos"=> ($comercial[$liCom]["depositos"] + $personal[$liPer]["depositos"]),
        "girados"=> ($comercial[$liCom]["girados"] + $personal[$liPer]["girados"]),
        "anulados"=> ($comercial[$liCom]["anulados"] + $personal[$liPer]["anulados"]),
        "credito"=> ($comercial[$liCom]["credito"] + $personal[$liPer]["credito"]),
        "debito"=> ($comercial[$liCom]["debito"] + $personal[$liPer]["debito"]),
        "saldoactual"=> ($comercial[$liCom]["saldoactual"] + $personal[$liPer]["saldoactual"])
    ];
}

function sumaTodoDos($grupos){
    $granTotal = [
        "id"=> "0", "empresa"=> "GRAN TOTAL:", "moneda"=> "Q", "eslocal"=> "1", "saldoanterior" => 0.00, "depositos" => 0.00, "girados" => 0.00, "anulados" => 0.00, "credito" => 0.00, "debito" => 0.00, "saldoactual" => 0.00
    ];
    $cnt = count($grupos);
    for($i = 0; $i < $cnt; $i++){
        //$idx = count($grupos[$i]) - 1;
        $subtot = $grupos[$i][count($grupos[$i]) - 1];
        $granTotal["saldoanterior"] += $subtot["saldoanterior"];
        $granTotal["depositos"] += $subtot["depositos"];
        $granTotal["girados"] += $subtot["girados"];
        $granTotal["anulados"] += $subtot["anulados"];
        $granTotal["credito"] += $subtot["credito"];
        $granTotal["debito"] += $subtot["debito"];
        $granTotal["saldoactual"] += $subtot["saldoactual"];
    }
    return $granTotal;
}

function removeZeros($arr){
    //var_dump($arr);
    $cntArr = count($arr) - 1;
    for($i = 0; $i < $cntArr; $i++){
        //var_dump($arr[$i]->saldoanterior);
        if($arr[$i]->saldoanterior == 0 && $arr[$i]->saldoactual == 0){ unset($arr[$i]); }
    }
    return $arr;
}

function removeZerosDos($arr){
    $cntArr = count($arr) - 1;
    $temp = [];
    for($i = 0; $i < $cntArr; $i++){
        if($arr[$i]->saldoanterior != 0 || $arr[$i]->saldoactual != 0){
            $temp[] = $arr[$i];
        }
    }
    $temp[] = $arr[$cntArr];
    return $temp;
}

$app->post('/sumarioold', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $sumario = new stdClass();

    $sumario->moneda = $db->getOneField("SELECT CONCAT(nommoneda, ' (', simbolo, ')') FROM moneda WHERE id = $d->idmoneda");
    $sumario->comercial = generaData($d, $db, $db->getQuery(queryTipoEmpresa(0, $d->idmoneda)));
    $sumario->personal = generaData($d, $db, $db->getQuery(queryTipoEmpresa(1, $d->idmoneda)));
    $sumario->grantotal = sumaTodo($sumario->comercial, $sumario->personal);

    if((int)$d->solomov == 1){
        $sumario->comercial = removeZeros($sumario->comercial);
        $sumario->personal = removeZeros($sumario->personal);
    }


    print json_encode($sumario);

});

// anterior
$app->post('/sumarioant', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $sumario = new stdClass();
    $sumario->moneda = $db->getOneField("SELECT CONCAT(nommoneda, ' (', simbolo, ')') FROM moneda WHERE id = $d->idmoneda");

    $query = "SELECT DISTINCT gruposumario FROM banco WHERE gruposumario > 0 AND idmoneda = $d->idmoneda ORDER BY gruposumario";
    $grupos = $db->getQuery($query);
    $cntGrupos = count($grupos);
    if($cntGrupos > 0){
        for($i = 0; $i < $cntGrupos; $i++){
            $grupo = $grupos[$i]->gruposumario;
            $sumario->grupos[] = generaData($d, $db,$db->getQuery(queryTipoEmpresaDos($grupo, $d->idmoneda)));
        }

        $sumario->grantotal = sumaTodoDos($sumario->grupos);

        $cntGrps = count($sumario->grupos);
        if((int)$d->solomov == 1 && $cntGrps > 0){
            for($i = 0; $i < $cntGrps; $i++){
                $sumario->grupos[$i] = removeZerosDos($sumario->grupos[$i]);
            }
        }
    }

    print json_encode($sumario);
});

// nuevo
$app->post('/sumario', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $separador = new StdClass;
    $totales = new StdClass;
    $monto_anterior = array();
    $monto_depositos = array();
    $monto_girados = array();
    $monto_credito = array();
    $monto_debito = array();
    $monto_actual = array();
    $primero = true;
    $sumario = array();

    $encabezado = new StdClass;
    $encabezado->moneda = $d->idmoneda == 3 ? 'Todas' : $db->getOneField("SELECT CONCAT(nommoneda, ' (', simbolo, ')') FROM moneda WHERE id = $d->idmoneda");
    // $encabezado->tipo = $d->tipo == 1 ? 'EMPRESA' : $d->tipo == 2 ? 'PERSONAL' : 'GENERAL';

    if ($d->tipo == 1) {
        $grupos = '1, 4';
        $encabezado->tipo = 'POR EMPRESA';
    } else if ($d->tipo == 2) {
        $grupos = '2, 3';
        $encabezado->tipo = 'PERSONAL';
    } else {
        $query = "SELECT GROUP_CONCAT(DISTINCT gruposumario) FROM banco WHERE gruposumario > 0 ";
        $query.= $d->idmoneda != 3 ?  "AND idmoneda = $d->idmoneda" : '';
        $grupos = $db->getOneField($query);
        $encabezado->tipo = 'GENERAL';
    }

    $query = "SELECT 
                a.id,
                a.gruposumario AS grupo,
                a.idmoneda, 
                CONCAT(IF(a.gruposumario = 3, 'Financiera Personal ', IF(a.gruposumario = 1, 'Empresarial ', IF(a.gruposumario = 4, 'Financiera ', 'Personal '))), c.nombre, ' (', c.simbolo, ')') AS nombre,
                CONCAT(a.siglas, ' / ', a.nocuenta) AS empresa,
                c.simbolo AS moneda,
                c.eslocal,
                SUM(IF(d.tipotrans IN ('D' , 'R')
                        AND d.fecha < '$d->fechastr',
                    d.monto,
                    IF(d.fecha < '$d->fechastr',
                        d.monto * - 1, 
                        NULL))) AS saldoanterior,
                SUM(IF(d.fecha = '$d->fechastr' AND d.tipotrans = 'D',
                    d.monto,
                    0)) AS depositos,
                SUM(IF(d.fecha = '$d->fechastr' AND d.tipotrans = 'C',
                    d.monto,
                    0)) AS girados,
                SUM(IF(d.fecha = '$d->fechastr' AND d.tipotrans = 'R',
                    d.monto,
                    0)) AS credito,
                SUM(IF(d.fecha = '$d->fechastr' AND d.tipotrans = 'B',
                    d.monto,
                    0)) AS debito,
                SUM(IF(d.tipotrans IN ('D' , 'R') 
                        AND d.fecha <= '$d->fechastr',
                    d.monto,
                    IF(d.fecha <= '$d->fechastr',
                        d.monto * - 1, 
                        NULL))) AS saldoactual
            FROM
                banco a
                    INNER JOIN
                (SELECT 
                    b.id, b.propia
                FROM
                    empresa b) b ON b.id = a.idempresa
                    INNER JOIN
                (SELECT 
                    c.id, c.simbolo, c.eslocal, c.nommoneda AS nombre
                FROM
                    moneda c) c ON c.id = a.idmoneda
                    INNER JOIN
                (SELECT 
                    d.idbanco, d.monto, d.tipotrans, d.fecha
                FROM
                    tranban d) d ON d.idbanco = a.id
            WHERE
                a.debaja = 0
                    AND a.gruposumario IN ($grupos)
                    AND b.propia = 1 ";
    $query.= $d->idmoneda != 3 ? "AND a.idmoneda = $d->idmoneda GROUP BY a.id ORDER BY a.gruposumario, a.idmoneda, a.ordensumario" : "GROUP BY a.id ORDER BY a.gruposumario, a.idmoneda, a.ordensumario";
    $data = $db->getQuery($query);
    
    $cntsCuentas = count($data);

    for ($i = 1; $i < $cntsCuentas; $i++) {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            $separador->grupo = $anterior->grupo;
            $separador->moneda = $anterior->nombre;
            $separador->idmoneda = $anterior->idmoneda;
            $separador->bancos = array();
            $primero = false;
        }
        // siempre empujar el monto anterior ya que fue validado anteriormente
        array_push($monto_anterior, $anterior->saldoanterior);
        array_push($monto_depositos, $anterior->depositos);
        array_push($monto_girados, $anterior->girados);
        array_push($monto_credito, $anterior->credito);
        array_push($monto_debito, $anterior->debito);
        array_push($monto_actual, $anterior->saldoactual);
        if ($d->solomov == 0 || $anterior->saldoactual != 0) {
            array_push($separador->bancos, $anterior);
        }

        // si no tienen el mismo separador
        if ($actual->idmoneda != $anterior->idmoneda || $actual->grupo != $anterior->grupo) {
            // generar variable de totales
            $totales->saldoanterior = round(array_sum($monto_anterior), 2);
            $totales->depositos = round(array_sum($monto_depositos), 2);
            $totales->girados = round(array_sum($monto_girados), 2);
            $totales->credito = round(array_sum($monto_credito), 2);
            $totales->debito = round(array_sum($monto_debito), 2);
            $totales->saldoactual = round(array_sum($monto_actual), 2);
            $separador->totales = $totales;

            // empujar a array global de recibo los recibos separados
            array_push($sumario, $separador);
            // limpiar variables 
            $totales = new StdClass;
            $monto_anterior = array();
            $monto_depositos = array();
            $monto_girados = array();
            $monto_credito = array();
            $monto_debito = array();
            $monto_actual = array();
            $separador = new StdClass;
            $separador->grupo = $actual->grupo;
            $separador->moneda = $actual->nombre;
            $separador->idmoneda = $actual->idmoneda;
            $separador->bancos = array();
        }
        // para empujar el ultimo dato
        if ($i+1 == $cntsCuentas) {
            array_push($monto_anterior, $actual->saldoanterior);
            array_push($monto_depositos, $actual->depositos);
            array_push($monto_girados, $actual->girados);
            array_push($monto_credito, $actual->credito);
            array_push($monto_debito, $actual->debito);
            array_push($monto_actual, $actual->saldoactual);
            if ($d->solomov == 0 || $actual->saldoactual != 0) {
                array_push($separador->bancos, $actual);
            }
            $totales->saldoanterior = round(array_sum($monto_anterior), 2);
            $totales->depositos = round(array_sum($monto_depositos), 2);
            $totales->girados = round(array_sum($monto_girados), 2);
            $totales->credito = round(array_sum($monto_credito), 2);
            $totales->debito = round(array_sum($monto_debito), 2);
            $totales->saldoactual = round(array_sum($monto_actual), 2);
            $separador->totales = $totales;
            array_push($sumario, $separador);

            // limpiar 
            $monto_anterior = array();
            $monto_depositos = array();
            $monto_girados = array();
            $monto_credito = array();
            $monto_debito = array();
            $monto_actual = array();
        }
    }

    $fecha_ant = date("Y-m-d", strtotime($d->fechastr . " -1 day"));

    $encabezado->tc = $db->getOneField("SELECT ROUND(tipocambio, 5) FROM tipocambio WHERE fecha = '$d->fechastr' LIMIT 1");
    $encabezado->tcant = $db->getOneField("SELECT ROUND(tipocambio, 5) FROM tipocambio WHERE fecha = '$fecha_ant' LIMIT 1");

    foreach ($sumario as $sum) {
        $tc = $sum->idmoneda == 1 ? 1.00 : $encabezado->tc;
        $tc_ant = $sum->idmoneda == 1 ? 1.00 : $encabezado->tcant;
        array_push($monto_anterior, $sum->totales->saldoanterior * $tc_ant);
        array_push($monto_depositos, $sum->totales->depositos * $tc);
        array_push($monto_girados, $sum->totales->girados * $tc);
        array_push($monto_credito, $sum->totales->credito * $tc);
        array_push($monto_debito, $sum->totales->debito * $tc);
        array_push($monto_actual, $sum->totales->saldoactual * $tc);
    }

    $encabezado->saldoanterior = number_format(array_sum($monto_anterior), 2, '.', ',');
    $encabezado->depositos = number_format(array_sum($monto_depositos), 2, '.', ',');
    $encabezado->girados = number_format(array_sum($monto_girados), 2, '.', ',');
    $encabezado->credito = number_format(array_sum($monto_credito), 2, '.', ',');
    $encabezado->debito = number_format(array_sum($monto_debito), 2, '.', ',');
    $encabezado->saldoactual = number_format(array_sum($monto_actual), 2, '.', ',');

    print json_encode(['encabezado' => $encabezado, 'sumario' => $sumario]);
});

$app->run();