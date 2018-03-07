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

function removeZeros($arr){
    //var_dump($arr);
    $cntArr = count($arr) - 1;
    for($i = 0; $i < $cntArr; $i++){
        //var_dump($arr[$i]->saldoanterior);
        if($arr[$i]->saldoanterior == 0 && $arr[$i]->saldoactual == 0){ unset($arr[$i]); }
    }
    return $arr;
}

$app->post('/sumario', function(){
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

$app->run();