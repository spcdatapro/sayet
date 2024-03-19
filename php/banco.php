<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para cuentas contables
$app->get('/lstbcos/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, b.id AS idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS nombrecta, ";
    $query.= "a.nombre, a.nocuenta, a.siglas, a.nomcuenta, a.idmoneda, a.idbancopais, CONCAT(c.nommoneda,' (',c.simbolo,')') AS descmoneda, ";
    $query.= "CONCAT(a.nombre, ' (', c.simbolo,')') AS bancomoneda, a.correlativo, c.tipocambio, CONCAT(a.nombre, ' (', c.simbolo,') (Sigue el No. ', a.correlativo,')') AS bancomonedacorrela, ";
    $query.= "a.idtipoimpresion, d.descripcion AS tipoimpresion, d.formato, c.eslocal AS monedalocal, a.debaja, a.gruposumario, a.ordensumario ";
    $query.= "FROM banco a INNER JOIN cuentac b ON b.id = a.idcuentac ";
    $query.= "INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "LEFT JOIN tipoimpresioncheque d ON d.id = a.idtipoimpresion ";
    $query.= "WHERE a.idempresa = ".$idempresa." ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/lstbcosactivos(/:idempresa)', function($idempresa = 0){
    $db = new dbcpm();
    $query = "SELECT a.id, e.id AS idempresa, e.nomempresa AS empresa, e.abreviatura AS abreviaempresa, b.id AS idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS nombrecta, ";
    $query.= "a.nombre, a.nocuenta, a.siglas, a.nomcuenta, a.idmoneda, CONCAT(c.nommoneda,' (',c.simbolo,')') AS descmoneda, ";
    $query.= "CONCAT(a.nombre, ' (', c.simbolo,')') AS bancomoneda, a.correlativo, c.tipocambio, CONCAT(a.nombre, ' (', c.simbolo,') (Sigue el No. ', a.correlativo,')') AS bancomonedacorrela, ";
    $query.= "a.idtipoimpresion, d.descripcion AS tipoimpresion, d.formato, c.eslocal AS monedalocal, a.debaja, a.gruposumario, a.ordensumario, a.idbancopais ";
    $query.= "FROM banco a INNER JOIN cuentac b ON b.id = a.idcuentac INNER JOIN moneda c ON c.id = a.idmoneda LEFT JOIN tipoimpresioncheque d ON d.id = a.idtipoimpresion LEFT JOIN empresa e ON e.id = a.idempresa ";
    $query.= "WHERE 1 = 1 AND a.debaja = 0 ";
    $query.= (int)$idempresa > 0 ? "AND a.idempresa = $idempresa " : '';
    $query.= "ORDER BY e.ordensumario, a.nombre";

    print $db->doSelectASJson($query);
});

$app->get('/lstbcosfltr/:idempresa', function($idempresa){
    $db = new dbcpm();

    $query = "SELECT a.id, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempre, a.idcuentac, a.idbancopais, d.codigo, d.nombrecta, a.nombre, a.nocuenta, a.siglas, ";
    $query.= "a.nomcuenta, a.correlativo, a.idmoneda, c.simbolo, c.nommoneda AS moneda ";
    $query.= "FROM banco a INNER JOIN empresa b on b.id = a.idempresa INNER JOIN moneda c ON c.id = a.idmoneda INNER JOIN cuentac d ON d.id = a.idcuentac ";
    $query.= (int)$idempresa > 0 ? "WHERE a.idempresa = $idempresa " : "";
    $query.= "ORDER BY b.nomempresa, a.nombre, a.nocuenta";
    print $db->doSelectASJson($query);
});

$app->get('/lstnombrebcosactivos(/:idempresa)', function($idempresa = 0){
    $db = new dbcpm();
    $query = "SELECT a.nombre FROM banco a ";
    $query.= "WHERE a.debaja = 0 ";
    $query.= (int)$idempresa > 0 ? "AND a.idempresa = $idempresa " : '';
    $query.= "GROUP BY a.nombre ORDER BY a.nombre";

    print $db->doSelectASJson($query);
});

$app->get('/getbco/:idbco', function($idbco){
    $db = new dbcpm();
    $query = "SELECT a.id AS idbanco, b.id AS idcuentac, b.nombrecta, a.nombre, a.nocuenta, a.siglas, a.nomcuenta, a.idempresa, a.idbancopais, ";
    $query.= "a.idmoneda, CONCAT(c.nommoneda,' (',c.simbolo,')') AS descmoneda, ";
    $query.= "CONCAT(a.nombre, ' (', c.simbolo,')') AS bancomoneda, a.correlativo, c.tipocambio, ";
    $query.= "a.idtipoimpresion, d.descripcion AS tipoimpresion, d.formato, c.eslocal AS monedalocal, a.debaja, a.gruposumario, a.ordensumario ";
    $query.= "FROM banco a INNER JOIN cuentac b ON b.id = a.idcuentac ";
    $query.= "INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "LEFT JOIN tipoimpresioncheque d ON d.id = a.idtipoimpresion ";
    $query.= "WHERE a.id = ".$idbco." ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getcorrelabco/:idbco', function($idbco){
    $db = new dbcpm();
    $query = "SELECT a.correlativo ";
    $query.= "FROM banco a ";
    $query.= "WHERE a.id = ".$idbco;
    print $db->doSelectASJson($query);
});

$app->get('/chkexists/:idbco/:ttrans/:num', function($idbco, $ttrans, $num){
    $db = new dbcpm();
    $data = $db->getQuery("SELECT idbanco, tipotrans, numero FROM tranban WHERE idbanco = ".$idbco." AND tipotrans = '".$ttrans."' AND numero = ".$num);
    print json_encode(['existe' => count($data) > 0 ? 1 : 0]);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->gruposumario)){ $d->gruposumario = 0; }
    if(!isset($d->ordensumario)){ $d->ordensumario = 0; }
    $db = new dbcpm();
    $query = "INSERT INTO banco(idempresa, idcuentac, nombre, nocuenta, siglas, nomcuenta, idmoneda, correlativo, idtipoimpresion, gruposumario, ordensumario, idbancopais) ";
    $query.= "VALUES($d->idempresa, $d->idcuentac, '$d->nombre', '$d->nocuenta', '$d->siglas', '$d->nomcuenta', $d->idmoneda, $d->correlativo, $d->idtipoimpresion, $d->gruposumario, $d->ordensumario, $d->idbancopais)";
    $db->doQuery($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->gruposumario)){ $d->gruposumario = 0; }
    if(!isset($d->ordensumario)){ $d->ordensumario = 0; }
    $db = new dbcpm();
    $query = "UPDATE banco SET idempresa = $d->idempresa, idcuentac = $d->idcuentac, idbancopais = $d->idbancopais, ";
    $query.= "nombre = '$d->nombre', siglas = '$d->siglas', nomcuenta = '$d->nomcuenta', idmoneda = $d->idmoneda, correlativo = $d->correlativo, idtipoimpresion = $d->idtipoimpresion, ";
    $query.= "debaja = $d->debaja, gruposumario = $d->gruposumario, ordensumario = $d->ordensumario ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM banco WHERE id = ".$d->id;
    $db->doQuery($query);
});

function conmovs($idbanco, $fdelstr, $falstr){
    $db = new dbcpm();
    return ((int)$db->getOneField("SELECT COUNT(id) AS cuantos FROM tranban WHERE idbanco = $idbanco AND fecha >= '$fdelstr' AND fecha <= '$falstr' AND anulado = 0") > 0);
};

$app->get('/ctassumario/:idmoneda/:fdelstr/:falstr/:tipo', function($idmoneda, $fdelstr, $falstr, $tipo){
    $db = new dbcpm();

    if ($tipo == 1) {
        $grupos = '1, 4';
    } else if ($tipo == 2) {
        $grupos = '2, 3';
    } else {
        $query = "SELECT GROUP_CONCAT(DISTINCT gruposumario) FROM banco WHERE gruposumario > 0 ";
        $query.= $idmoneda != 3 ?  "AND idmoneda = $idmoneda" : '';
        $grupos = $db->getOneField($query);
    }

    $enviar = [];
    $query = "SELECT a.id, CONCAT(a.siglas, ' / ', a.nocuenta) AS empresa, c.simbolo AS moneda, c.eslocal ";
    $query.= "FROM banco a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "WHERE a.debaja = 0 AND b.propia = 1 ";
    $query.= $idmoneda != 3 ? "AND c.id = $idmoneda " : "";
    $query.= isset($grupos) ? "AND a.gruposumario IN($grupos) " : "";
    $query.= "ORDER BY a.gruposumario, a.ordensumario";
    $cuentas = $db->getQuery($query);
    $cntCuentas = count($cuentas);
    for($i = 0; $i < $cntCuentas; $i++){
        $cuenta = $cuentas[$i];
        if(conmovs($cuenta->id, $fdelstr, $falstr)){
            $enviar[] = $cuenta;
        }
    }

    print json_encode($enviar);
});

function fNum($db, $numero){ return $db->getOneField("SELECT FORMAT($numero, 2)"); }

#API para reportes
$app->post('/rptestcta', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    //Datos del banco
    $query = "SELECT a.nombre, b.simbolo, a.nocuenta, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, c.nomempresa AS empresa ";
    $query.= "FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.id = $d->idbanco";
    $banco = $db->getQuery($query)[0];

    $query = "SELECT (SELECT IF(ISNULL(SUM(a.monto)), 0.00, SUM(a.monto)) FROM tranban a ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha < '$d->fdelstr' AND a.tipotrans IN('D','R')) - ";
    $query.= "(SELECT IF(ISNULL(SUM(a.monto)), 0.00, SUM(a.monto)) FROM tranban a ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha < '$d->fdelstr' AND a.tipotrans IN('C','B')) AS saldoinicial";
    $saldoinicial = (float)$db->getOneField($query);

    $query = "SELECT DATE_FORMAT(b.fecha, '%d/%m/%Y') AS fecha, d.abreviatura AS tipo, b.numero, b.beneficiario, ";
    $query.= "IF(b.anulado = 0, b.concepto, CONCAT('(ANULADO) ', b.concepto)) AS concepto, ";
    $query.= "IF(b.tipotrans IN('D', 'R'), b.monto, 0.00) AS credito, ";
    $query.= "IF(b.tipotrans IN('C', 'B'), b.monto, 0.00) AS debito, ";
    $query.= "0.00 AS saldo, c.id AS idbanco, ";
    $query.= "c.nombre AS banco, d.abreviatura, b.id AS idtran, ".((int)$d->resumen == 0 ? "''": '1')." AS resumen ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
    $query.= "WHERE c.id = ".$d->idbanco." AND fecha >= '".$d->fdelstr."' AND fecha <= '".$d->falstr."' ";
    $query.= "ORDER BY b.fecha, b.numero";
    $tran = $db->getQuery($query);

    $cant = count($tran);
    $tmp = $saldoinicial;
    $sumacredito = 0.00;
    $sumadebito = 0.00;
    for($x = 0; $x < $cant; $x++){
        $tmp = in_array($tran[$x]->abreviatura, ['D', 'R']) ? $tmp + (float)$tran[$x]->credito : $tmp - (float)$tran[$x]->debito;
        $tran[$x]->saldo = number_format((float)$tmp, 2);
        $sumacredito += (float)$tran[$x]->credito;
        $sumadebito += (float)$tran[$x]->debito;
        $tran[$x]->credito = (float)$tran[$x]->credito != 0 ? number_format((float)$tran[$x]->credito, 2) : '';
        $tran[$x]->debito = (float)$tran[$x]->debito != 0 ? number_format((float)$tran[$x]->debito, 2) : '';
    };

    $query = "SELECT b.id, CONCAT('(', b.abreviatura,') ', b.descripcion) AS tipo, COUNT(a.tipotrans) AS cantidad, FORMAT(SUM(a.monto), 2) AS monto, ";
    $query.= "IF(b.abreviatura IN ('D', 'R'), '( + )', '( - )') AS operacion, b.orden ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = ".$d->idbanco." AND a.fecha >= '".$d->fdelstr."' AND a.fecha <= '".$d->falstr."' ";
    $query.= "GROUP BY a.tipotrans ";
    $query.= "ORDER BY b.orden";
    $resumen = $db->getQuery($query);

    $query = "SELECT id, CONCAT('(', abreviatura,') ', descripcion) AS tipo, 0 AS cantidad, 0.00 AS monto, ";
    $query.= "IF(abreviatura IN ('D', 'R'), '( + )', '( - )') AS operacion, orden ";
    $query.= "FROM tipomovtranban WHERE id NOT IN(SELECT b.id FROM tranban a RIGHT JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' GROUP BY a.tipotrans) ORDER BY orden";
    $faltantes = $db->getQuery($query);

    if(count($faltantes) > 0){
        if(count($resumen) > 0){
            for($i = 0; $i < count($faltantes); $i++){
                $resumen[] = $faltantes[$i];
            }
            usort($resumen, function($a, $b){ $idpa = (int)$a->orden; $idpb = (int)$b->orden; return $idpa == $idpb ? 0 : ($idpa < $idpb ? -1 : 1); });
        }else{
            $resumen = $faltantes;
        }
    }

    $data = [
        'banco' => $banco,
        'saldoinicial' => number_format((float)$saldoinicial, 2),
        'tran' => $tran,
        'sumacredito' => (float)$sumacredito != 0 ? number_format((float)$sumacredito, 2) : '',
        'sumadebito' => (float)$sumadebito != 0 ? number_format((float)$sumadebito, 2) : '',
        'saldofinal' => number_format((float)$tmp, 2),
        'resumen' => $resumen
    ];
    
    print json_encode($data);
});

$app->get('/lstbcospais', function(){
    $db = new dbcpm();
    $query = "SELECT id, nombre FROM bancopais ";
    print $db->doSelectASJson($query);
});

$app->run();