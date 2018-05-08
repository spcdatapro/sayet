<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para transacciones bancarias
$app->get('/lsttranbanc/:idbanco', function($idbanco){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto, ";
    $query.= "a.beneficiario, a.concepto, a.operado, a.anticipo, a.idbeneficiario, a.origenbene, a.anulado, a.fechaanula, a.tipocambio, a.impreso, a.fechaliquida, a.esnegociable, ";
    $query.= "CONCAT('OT: ', c.idpresupuesto, '-', c.correlativo, ' (', e.nombre,')') AS ot, a.iddetpresup, a.iddetpagopresup, a.idproyecto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco ";
    $query.= "LEFT JOIN detpresupuesto c ON c.id = a.iddetpresup LEFT JOIN presupuesto d ON d.id = c.idpresupuesto LEFT JOIN proveedor e ON e.id = c.idproveedor ";
    $query.= "WHERE a.idbanco = ".$idbanco." ";
    $query.= "ORDER BY a.fecha DESC, a.operado, b.nombre, a.tipotrans, a.numero";
    print $db->doSelectASJson($query);
});

$app->post('/lsttran', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto, ";
    $query.= "a.beneficiario, a.concepto, a.operado, a.anticipo, a.idbeneficiario, a.origenbene, a.anulado, a.fechaanula, a.tipocambio, a.impreso, a.fechaliquida, a.esnegociable, ";
    $query.= "CONCAT('OT: ', c.idpresupuesto, '-', c.correlativo, ' (', e.nombre,')') AS ot, a.iddetpresup, a.iddetpagopresup, a.idproyecto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco ";
    $query.= "LEFT JOIN detpresupuesto c ON c.id = a.iddetpresup LEFT JOIN presupuesto d ON d.id = c.idpresupuesto LEFT JOIN proveedor e ON e.id = c.idproveedor ";
    $query.= "WHERE a.idbanco = $d->idbanco ";
    $query.= $d->fdelstr != "" ? "AND a.fecha >= '$d->fdelstr' " : "";
    $query.= $d->falstr != "" ? "AND a.fecha <= '$d->falstr' " : "";
    $query.= "ORDER BY a.fecha DESC, a.operado, b.nombre, a.tipotrans, a.numero";
    print $db->doSelectASJson($query);
});

$app->get('/gettran/:idtran', function($idtran){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto, ";
    $query.= "a.beneficiario, a.concepto, a.operado, a.anticipo, a.idbeneficiario, a.origenbene, a.anulado, c.razon, a.fechaanula, a.tipocambio, d.simbolo AS moneda, a.impreso, a.fechaliquida, a.esnegociable, ";
    $query.= "CONCAT('OT: ', e.idpresupuesto, '-', e.correlativo, ' (', g.nombre,')') AS ot, a.iddetpresup, a.iddetpagopresup, a.idproyecto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco LEFT JOIN razonanulacion c ON c.id = a.idrazonanulacion LEFT JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "LEFT JOIN detpresupuesto e ON e.id = a.iddetpresup LEFT JOIN presupuesto f ON f.id = e.idpresupuesto LEFT JOIN proveedor g ON g.id = e.idproveedor ";
    $query.= "WHERE a.id = ".$idtran;
    print $db->doSelectASJson($query);
});

function insertaDetalleContable($d, $idorigen){
    $db = new dbcpm();
    $origen = 1;
    //Verificacion si la moneda es local o no
    $tc = 1.00;
    $query = "SELECT eslocal FROM moneda WHERE id = (SELECT idmoneda FROM banco WHERE id = $d->idbanco)";
    $noeslocal = (int)$db->getOneField($query) == 0;
    if($noeslocal){ $tc = (float)$d->tipocambio; };

    //Inicia inserción automática de detalle contable de transacción bancaria
    //Si es C o B, va de la cuenta por liquidar o de la cuenta de proveedores en el debe al banco en el haber
    $idempresa = (int)$db->getOneField("SELECT idempresa FROM banco WHERE id = ".$d->idbanco);
    $ctabco = (int)$db->getOneField("SELECT idcuentac FROM banco WHERE id = ".$d->idbanco);
    //$tc = (float)$db->getOneField("SELECT a.tipocambio FROM moneda a INNER JOIN banco b ON a.id = b.idmoneda WHERE b.id = ".$d->idbanco);
    $cuenta = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = ".((int)$d->origenbene === 2 ? 5 : 3));

    if($cuenta > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= "$origen, $idorigen, $cuenta, ".round((float)$d->monto * $tc, 2).", 0.00, '$d->concepto')";
        $db->doQuery($query);
    };

    if($ctabco > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= "$origen, $idorigen, $ctabco, 0.00, ".round((float)$d->monto * $tc, 2).", '$d->concepto')";
        $db->doQuery($query);
    };
};

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $ttsalida = ['C', 'B'];
    $tentrada = ['D', 'R'];
    $query = "INSERT INTO tranban(idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, anticipo, idbeneficiario, origenbene, tipocambio, esnegociable, iddetpresup, ";
    $query.= "iddetpagopresup, idproyecto) ";
    $query.= "VALUES(".$d->idbanco.", '".$d->tipotrans."', '".$d->fechastr."', ".$d->monto.", '".$d->beneficiario."', '".$d->concepto."', ";
    $query.= $d->numero.", ".$d->anticipo.", ".$d->idbeneficiario.", ".$d->origenbene.", ".$d->tipocambio.", $d->esnegociable, $d->iddetpresup, $d->iddetpagopresup, $d->idproyecto)";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    if(in_array($d->tipotrans, $ttsalida)){
        if($d->tipotrans === 'C'){ $db->doQuery("UPDATE banco SET correlativo = correlativo + 1 WHERE id = ".$d->idbanco); }
        //Inserta detalle contable
        insertaDetalleContable($d, $lastid);
        //Actualización de pago de OT, en caso de que tenga OT
        if((int)$d->iddetpresup > 0 && (int)$d->iddetpagopresup > 0){
            $query = "UPDATE detpagopresup SET pagado = 1, origen = 1, idorigen = $lastid WHERE id = $d->iddetpagopresup";
            $db->doQuery($query);
        }
    }elseif(in_array($d->tipotrans, $tentrada)){
        $ctabco = (int)$db->getOneField("SELECT idcuentac FROM banco WHERE id = ".$d->idbanco);
        if($ctabco > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= "1, ".$lastid.", ".$ctabco.", ".round(((float)$d->monto * (float)$d->tipocambio), 2).", 0.00, '".$d->concepto."')";
            $db->doQuery($query);
        };
    }
    print json_encode(['lastid' => $lastid]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE tranban SET tipotrans = '".$d->tipotrans."', ";
    $query.= "fecha = '".$d->fechastr."', monto = ".$d->monto.", beneficiario = '".$d->beneficiario."', concepto = '".$d->concepto."', ";
    $query.= "operado = ".$d->operado.", numero = ".$d->numero.", anticipo = ".$d->anticipo.", idbeneficiario = ".$d->idbeneficiario.", ";
    $query.= "origenbene = ".$d->origenbene.", tipocambio = ".$d->tipocambio.", esnegociable = $d->esnegociable, iddetpresup = $d->iddetpresup, ";
    $query.= "iddetpagopresup = $d->iddetpagopresup, idproyecto = $d->idproyecto ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);

    //$query = "DELETE FROM detallecontable WHERE origen = 1 AND idorigen = $d->id";
    $db->doQuery($query);
    $ttsalida = ['C', 'B'];
    $tentrada = ['D', 'R'];
    if(in_array($d->tipotrans, $ttsalida)){
        //if($d->tipotrans === 'C'){ $db->doQuery("UPDATE banco SET correlativo = correlativo + 1 WHERE id = ".$d->idbanco); }
        //Inserta detalle contable
        //insertaDetalleContable($d, $d->id);
        //Actualización de pago de OT, en caso de que tenga OT
        if((int)$d->iddetpresup > 0 && (int)$d->iddetpagopresup > 0){
            $query = "UPDATE detpagopresup SET pagado = 1, origen = 1, idorigen = $d->id WHERE id = $d->iddetpagopresup";
            $db->doQuery($query);
        }
    }
    /*
    elseif(in_array($d->tipotrans, $tentrada)){
        $ctabco = (int)$db->getOneField("SELECT idcuentac FROM banco WHERE id = ".$d->idbanco);
        if($ctabco > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= "1, ".$d->id.", ".$ctabco.", ".round(((float)$d->monto * (float)$d->tipocambio), 2).", 0.00, '".$d->concepto."')";
            $db->doQuery($query);
        };
    }
    */
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $tran = $db->getQuery("SELECT tipotrans, numero, idbanco FROM tranban WHERE id = $d->id")[0];
    if(trim($tran->tipotrans) == 'C'){ $db->doQuery("UPDATE banco SET correlativo = $tran->numero WHERE id = $tran->idbanco"); }
	$db->doQuery("UPDATE reembolso SET idtranban = 0 WHERE idtranban = $d->id AND esrecprov = 0");
    $db->doQuery("DELETE FROM doctotranban WHERE idtranban = $d->id");
    $db->doQuery("DELETE FROM detpagocompra WHERE idtranban = $d->id");
    $db->doQuery("DELETE FROM detallecontable WHERE origen = 1 AND idorigen = $d->id");
    $db->doQuery("DELETE FROM tranban WHERE id = $d->id");
});

$app->get('/aconciliar/:idbanco/:afecha/:qver', function($idbanco, $afecha, $qver){
    try{
        $db = new dbcpm();
        $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto, ";
        $query.= "a.beneficiario, IF(a.anulado = 0, a.concepto, CONCAT('(ANULADO) ', a.concepto)) AS concepto, a.operado, a.anticipo, a.idbeneficiario, ";
        $query.= "a.origenbene, a.anulado, a.fechaanula, a.tipocambio, a.impreso, a.esnegociable, a.idproyecto ";
        $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco ";
        $query.= "WHERE a.operado = $qver AND a.idbanco = ".$idbanco." ";
        $query.= $afecha != '0' ? "AND a.fecha <= '$afecha' " : "";
        $query.= "ORDER BY a.fecha, a.tipotrans, a.numero";
        print $db->doSelectASJson($query);
    }catch(Exception $e ){
        print json_encode([]);
    }
});

//Inicia Para impresión de cheques continuos
//Listar docunentos mediante correlativos
$app->get('/correlativodelal/:ndel/:nal/:idbanco', function($ndel,$nal,$idbanco){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query ="SELECT id , numero FROM tranban WHERE numero >= $ndel AND numero <= $nal AND tipotrans = 'C' AND idbanco = $idbanco AND impreso = 0 AND anulado = 0";
    print $db->doSelectASJson($query);
});

//Hace que el dato de impreso sea verdadero de los documentos listados
$app->post('/udoc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->ndel = $d->ndel == '' ? 'NULL' : "'".$d->ndel."'";
    $d->nal = $d->nal == '' ? 'NULL' : "'".$d->nal."'";
    $d->idbanco = $d->idbanco == '' ? 'NULL' : "'".$d->idbanco."'";
    $db->doQuery("UPDATE tranban SET impreso = 1 WHERE numero >= $d->ndel AND numero <= $d->nal AND idbanco = $d->idbanco AND tipotrans= 'C'" );
});
//Fin de para impresion de cheques continuos

$app->post('/o', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tranban SET operado = ".$d->operado.", fechaoperado = '$d->foperado' WHERE id = ".$d->id);
});

$app->post('/anula', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE tranban SET idrazonanulacion = ".$d->idrazonanulacion.", anulado = 1, fechaanula = '".$d->fechaanulastr."' WHERE id = ".$d->id);
    $db->doQuery("UPDATE detallecontable SET anulado = 1 WHERE origen = 1 AND idorigen = ".$d->id);
	$db->doQuery("DELETE FROM detpagocompra WHERE idtranban = ".$d->id);
	$db->doQuery("UPDATE reembolso SET idtranban = 0 WHERE idtranban = ".$d->id);    
});

$app->get('/lstbeneficiarios', function(){
    $db = new dbcpm();
    $query = "SELECT id, CONCAT(nombre, ' (', nit, ')') AS beneficiario, chequesa, 1 AS dedonde, concepto, 'Proveedor(es)' AS grupo FROM proveedor UNION ";
    $query.= "SELECT id, CONCAT(nombre, ' (', nit, ')') AS beneficiario, nombre AS chequesa, 2 AS dedonde, concepto, 'Beneficiario(s)' AS grupo FROM beneficiario ";
    $query.= "ORDER BY 4, 2";
    print $db->doSelectASJson($query);
});

$app->get('/factcomp/:idproveedor/:idtranban', function($idproveedor, $idtranban){
    $db = new dbcpm();
    $idmoneda = (int)$db->getOneField("SELECT b.idmoneda FROM tranban a INNER JOIN banco b ON b.id = a.idbanco WHERE a.id = $idtranban");
    $idempresa = (int)$db->getOneField("SELECT b.idempresa FROM tranban a INNER JOIN banco b ON b.id = a.idbanco WHERE a.id = $idtranban");
    $query = "SELECT a.id, a.idempresa, a.idproveedor, b.nombre AS proveedor, a.serie, a.documento, a.fechapago, a.conceptomayor, a.subtotal, a.totfact, ";
    $query.= "IFNULL(c.montopagado, 0.00) AS montopagado, (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) AS saldo, ";
    $query.= "CONCAT(a.serie, ' ', a.documento, ' - Total: ', (a.totfact - a.isr), ' - Pendiente: ', (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00)))) AS cadena, ";
    $query.= "a.fechafactura ";
    $query.= "FROM compra a LEFT JOIN proveedor b ON b.id = a.idproveedor LEFT JOIN (";
    $query.= "SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra ";
    $query.= "WHERE (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) > 0.00 AND a.idempresa = $idempresa AND a.idproveedor = ".$idproveedor." AND a.idmoneda = $idmoneda ";
    $query.= "ORDER BY a.serie, a.documento";
    //echo $query;
    print $db->doSelectASJson($query);
});

$app->get('/reem/:idbene', function($idbene){
    $db = new dbcpm();
    $query = "SELECT a.id, ";
    $query.= "CONCAT(b.desctiporeembolso,' - No. ',LPAD(a.id, 5, '0'), ' - ', DATE_FORMAT(a.finicio, '%d/%m/%Y'),  ' - ', c.nombre, ' - Q ', ";
    $query.= "IF(ISNULL(d.totreembolso), 0.00, d.totreembolso)) AS cadena, a.finicio AS fechafactura, 'REE' AS serie, a.id AS documento, ";
    $query.= "IF(ISNULL(d.totreembolso), 0.00, d.totreembolso) AS totfact ";
    $query.= "FROM reembolso a INNER JOIN tiporeembolso b ON b.id = a.idtiporeembolso INNER JOIN beneficiario c ON c.id = a.idbeneficiario LEFT JOIN (";
    $query.= "SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) d ON a.id = d.idreembolso ";
    $query.= "WHERE a.idtranban = 0 AND a.idbeneficiario = ".$idbene." ";
    $query.= "ORDER BY a.id";
    print $db->doSelectASJson($query);
});

//API Documentos de soporte
$app->get('/lstdocsop/:idtran', function($idtran){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idtranban, a.idtipodoc, b.desctipodoc, a.documento, a.fechadoc, a.monto, a.serie, a.iddocto ";
    $query.= "FROM doctotranban a INNER JOIN tipodocsoptranban b ON b.id = a.idtipodoc ";
    $query.= "WHERE a.idtranban = ".$idtran." ";
    $query.= "ORDER BY a.fechadoc DESC";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getdocsop/:iddoc', function($iddoc){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idtranban, a.idtipodoc, b.desctipodoc, a.documento, a.fechadoc, a.monto, a.serie, a.iddocto ";
    $query.= "FROM doctotranban a INNER JOIN tipodocsoptranban b ON b.id = a.idtipodoc ";
    $query.= "WHERE a.id = ".$iddoc;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getsumdocssop/:idtranban', function($idtranban){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT SUM(monto) AS totMonto FROM doctotranban WHERE idtranban = ".$idtranban;
    $data = $conn->query($query)->fetchColumn(0);
    print json_encode(['totmonto' => $data]);
});

function cierreReembolso($db, $d){
    $estatus = (int)$db->getOneField("SELECT estatus FROM reembolso WHERE id = ".$d->iddocto);
    if($estatus == 2){
        $query = "UPDATE reembolso SET idtranban = ".$d->idtranban." WHERE id = ".$d->iddocto;
        $db->doQuery($query);
    }else{
        $query = "UPDATE reembolso SET estatus = 2, idtranban = ".$d->idtranban.", ffin = NOW() WHERE id = ".$d->iddocto;
        $db->doQuery($query);
        //Generación del detalle contable del reembolso Origen = 5
        /*
        $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) ";
        $query.= "SELECT 5 AS origen, a.idreembolso AS idorigen, b.idcuenta, SUM(b.debe) AS debe, 0.00 AS haber, GROUP_CONCAT(b.conceptomayor SEPARATOR ', ') AS conceptomayor ";
        $query.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 INNER JOIN cuentac d ON d.id = b.idcuenta ";
        $query.= "WHERE a.idreembolso = ".$d->iddocto." ";
        $query.= "GROUP BY b.idcuenta ";
        $query.= "ORDER BY d.precedencia DESC, d.nombrecta";
        $db->doQuery($query);
        $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 5");
        if($ctaporliquidar > 0){
            $query = "SELECT SUM(b.debe) AS debe FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->iddocto;
            $haber = (float)$db->getOneField($query);
            $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= "5, ".$d->iddocto.", ".$ctaporliquidar.", 0.00, ".$haber.", 'Reembolso No. ".$d->iddocto."'";
            $query.= ")";
            $db->doQuery($query);
        }
        */
        $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) ";
        $query.= "SELECT 5 AS origen, a.idreembolso AS idorigen, b.idcuenta, b.debe, b.haber, b.conceptomayor ";
        $query.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 INNER JOIN cuentac d ON d.id = b.idcuenta WHERE a.idreembolso = ".$d->iddocto." ";
        $query.= "ORDER BY b.idorigen, d.precedencia DESC, d.nombrecta";
        $db->doQuery($query);
        $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 5");
        if($ctaporliquidar > 0){
            $query = "SELECT SUM(b.debe) AS debe FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->iddocto;
            $haber = (float)$db->getOneField($query);
            $query = "SELECT SUM(b.haber) AS haber FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->iddocto;
            $restar = (float)$db->getOneField($query);
            $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= "5, ".$d->iddocto.", ".$ctaporliquidar.", 0.00, ".round(($haber - $restar),2).", 'Reembolso No. ".$d->iddocto."'";
            $query.= ")";
            $db->doQuery($query);
        }

    }
}

function setFacturaPagada($db, $d){

    $query = "SELECT (a.totfact - IF(ISNULL(c.montopagado), 0.00, c.montopagado)) AS saldo FROM compra a ";
    $query.= "LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra ";
    $query.= "WHERE a.id = $d->iddocto LIMIT 1 ";

    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if(!$haypendiente){
        $query = "UPDATE tranban SET fechaliquida = '$d->fechaliquidastr' WHERE id = $d->idtranban";
        $db->doQuery($query);
    }
}

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO doctotranban(idtranban, idtipodoc, documento, fechadoc, monto, serie, iddocto) ";
    $query.= "VALUES(".$d->idtranban.", ".$d->idtipodoc.", ".$d->documento.", '".$d->fechadocstr."', ".$d->monto.", '".$d->serie."', ".$d->iddocto.")";
    $db->doQuery($query);
    $tipodoc = (int)$d->idtipodoc;

    switch($tipodoc){
        case 1:
            if($d->fechaliquidastr != ''){
                //Inserta abono a la factura
                $query = "INSERT INTO detpagocompra (idcompra, idtranban, monto) VALUES(".$d->iddocto.", ".$d->idtranban.", ".$d->monto.")";
                $db->doQuery($query);
                //Inserta la tercera partida contable...
                //Origen = 9 -> liquidaciones de cheques
                $query = "UPDATE tranban SET fechaliquida = '$d->fechaliquidastr' WHERE id = $d->idtranban";                
                $db->doQuery($query);
                //La tercera partida no va en SAYET
                /*
                $idempresa = (int)$db->getOneField("SELECT b.idempresa FROM tranban a INNER JOIN banco b ON b.id = a.idbanco WHERE a.id = ".$d->idtranban);
                $cxp = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = 6");
                $cxc = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = 7");
                $tc = (float)$db->getOneField("SELECT tipocambio FROM tranban WHERE id = $d->idtranban");
                $tcf = (float)$db->getOneField("SELECT tipocambio FROM compra WHERE id = $d->iddocto");
                $cdc = 0;

                $montoSegunCompra = round(($d->monto * $tcf), 2);
                $montoSegunTransaccion = round(($d->monto * $tc), 2);

                $diferencial = round(($montoSegunCompra - $montoSegunTransaccion), 2);

                if($diferencial < 0){ $cdc = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = 10"); }
                if($diferencial > 0){ $cdc = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = 11"); }

                if($cxp > 0){
                    $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
                    $query.= "9, ".$d->idtranban.", ".$cxp.", ".$montoSegunCompra.", 0.00, 'Pago de factura ".$d->serie." ".$d->documento."'";
                    $query.= ")";
                    $db->doQuery($query);
                }

                if($cdc > 0 && $diferencial != 0){
                    $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
                    $query.= "9, ".$d->idtranban.", ".$cdc.", ";
                    $query.= ($diferencial < 0 ? abs($diferencial) : "0.00").", ".($diferencial > 0 ? abs($diferencial) : "0.00").", ";
                    $query.= "'Pago de factura ".$d->serie." ".$d->documento."'";
                    $query.= ")";
                    $db->doQuery($query);
                }

                if($cxc > 0){
                    $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
                    $query.= "9, ".$d->idtranban.", ".$cxc.", 0.00, ".$montoSegunTransaccion.", 'Pago de factura ".$d->serie." ".$d->documento."'";
                    $query.= ")";
                    $db->doQuery($query);
                }
                */
            }
            break;
        case 2:
            if($d->fechaliquidastr != ''){
                $query = "UPDATE tranban SET fechaliquida = '$d->fechaliquidastr' WHERE id = $d->idtranban";                
                $db->doQuery($query);
            }
            cierreReembolso($db, $d);
            break;
    };
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE doctotranban SET monto = $d->monto WHERE id = ".$d->id);
    $db->doQuery("UPDATE detpagocompra SET monto = $d->monto WHERE idcompra = $d->iddocto");

    $query = "SELECT (a.totfact - IF(ISNULL(c.montopagado), 0.00, c.montopagado)) AS saldo FROM compra a ";
    $query.= "LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra ";
    $query.= "WHERE a.id = $d->iddocto LIMIT 1 ";

    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if(!$haypendiente){
        $db->doQuery($query);
    }else {
        //Poner como pagada la factura si su saldo es 0.00
        setFacturaPagada($db, $d);
    }
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM doctotranban WHERE id = ".$d->id);
    $db->doQuery("DELETE FROM detpagocompra WHERE idcompra = ".$d->iddocto);

    $query = "SELECT (a.totfact - IF(ISNULL(c.montopagado), 0.00, c.montopagado)) AS saldo FROM compra a ";
    $query.= "LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra ";
    $query.= "WHERE a.id = $d->iddocto LIMIT 1 ";

    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if(!$haypendiente){
        $db->doQuery($query);
    }
});

$app->get('/lstcompras/:idtranban', function($idtranban){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idproveedor, CONCAT(c.nombre, ' (', c.nit, ')') AS proveedor, CONCAT(e.siglas, '-', a.serie, '-', a.documento) AS factura, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechafactura, ";
    $query.= "d.simbolo AS moneda, a.totfact, a.noafecto, a.subtotal, a.iva, a.isr, a.idp ";
    $query.= "FROM compra a INNER JOIN doctotranban b ON a.id = b.iddocto INNER JOIN proveedor c ON c.id = a.idproveedor INNER JOIN moneda d ON d.id = a.idmoneda INNER JOIN tipofactura e ON e.id = a.idtipofactura ";
    $query.= "WHERE b.idtipodoc = 1 AND b.idtranban = $idtranban ";
    $query.= "ORDER BY c.nombre, a.fechafactura";
    print $db->doSelectASJson($query);
});

//API de reportería
$app->post('/rptcorrch', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();

    $fBco = "AND b.id = ".$d->idbanco." ";
    $fDel = "AND a.fecha >= '".$d->fdelstr."' ";
    $fAl = "AND a.fecha <= '".$d->falstr."' ";

    $query = "SELECT b.id AS idbanco, b.nombre AS banco, a.numero, a.fecha, a.beneficiario, a.concepto, a.monto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco ";
    $query.= "WHERE a.tipotrans = 'C' AND b.idempresa = ".$d->idempresa." ";
    $query.= (int)$d->idbanco > 0 ? $fBco : "";
    $query.= $d->fdelstr != '' ? $fDel : "";
    $query.= $d->falstr != '' ? $fAl : "";
    $query.= "ORDER BY b.nombre, a.numero, a.fecha";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/rptdocscircula', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $documentos = new stdclass();

    $query = "SELECT a.nombre, a.nocuenta, b.simbolo AS moneda, 0.00 AS totcirculacion, DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha, DATE_FORMAT(NOW(), '%H:%i') AS hora, ";
    $query.= "c.nomempresa AS empresa, c.abreviatura AS abreviaempre ";
    $query.= "FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.id = $d->idbanco";
    $documentos->generales = $db->getQuery($query)[0];
    
    $query = "SELECT b.id AS idbanco, b.nombre AS banco, c.abreviatura, c.descripcion, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.numero, a.beneficiario, ";
    $query.= "a.concepto, FORMAT(a.monto, 2) AS monto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN tipomovtranban c ON c.abreviatura = a.tipotrans ";
    $query.= "WHERE a.operado = 0 AND b.idempresa = $d->idempresa AND a.idbanco = $d->idbanco AND a.fecha <= '$d->falstr' ";
    $query.= "ORDER BY a.fecha, a.tipotrans, a.numero";
    $documentos->circulacion = $db->getQuery($query);

    $query = "SELECT FORMAT(SUM(a.monto), 2) ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN tipomovtranban c ON c.abreviatura = a.tipotrans ";
    $query.= "WHERE a.operado = 0 AND b.idempresa = $d->idempresa AND a.idbanco = $d->idbanco AND a.fecha <= '$d->falstr' ";
    $documentos->generales->totcirculacion = $db->getOneField($query);

    print json_encode($documentos);
});
/*
//Esta es la original
$app->get('/imprimir/:idtran', function($idtran){
    $db = new dbcpm();

    $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(1, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, DATE_FORMAT(b.fecha, '%d/%m/%Y') AS fecha, ";
    $query.= "CONCAT(d.descripcion, ' No. ', b.numero, ' del ', c.nombre) AS referencia, b.concepto, b.id, 1 AS origen, e.simbolo AS moneda, FORMAT(b.monto, 2) AS monto, FORMAT(b.tipocambio, 4) AS tipocambio, ";
    $query.= "CONCAT(f.nomempresa, ' (', f.abreviatura, ')') AS empresa, b.beneficiario ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda INNER JOIN empresa f ON f.id = c.idempresa ";
    $query.= "WHERE b.id = $idtran";
    //print $query;
    $tran = $db->getQuery($query);

    $query = "SELECT b.codigo, b.nombrecta, FORMAT(a.debe, 2) AS debe, FORMAT(a.haber, 2) AS haber, 0 AS estotal ";
    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
    $query.= "WHERE a.activada = 1 AND a.anulado = 0 AND a.origen = 1 AND a.idorigen = $idtran ";
    $query.= "ORDER BY a.debe DESC, b.nombrecta";
    //print $query;
    $tran[0]->detcont = $db->getQuery($query);

    if(count($tran[0]->detcont) > 0){
        $query = "SELECT 0 AS codigo, 'TOTALES:' AS nombrecta, FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber, 1 AS estotal ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.activada = 1 AND a.anulado = 0 AND a.origen = 1 AND a.idorigen = $idtran ";
        $query.= "GROUP BY a.origen, a.idorigen";
        //print $query;
        $sum = $db->getQuery($query);
        array_push($tran[0]->detcont, $sum[0]);
    }

    print json_encode($tran[0]);
});
*/

//Realizada por Rony Coyote
$app->get('/imprimir/:idtran', function($idtran){
    $db = new dbcpm();

    /* $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(1, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, DATE_FORMAT(b.fecha, '%d/%m/%Y') AS fecha, ";
    $query.= "CONCAT(d.descripcion, ' No. ', b.numero, ' del ', c.nombre) AS referencia, b.concepto, b.id, 1 AS origen, e.simbolo AS moneda, FORMAT(b.monto, 2) AS monto, FORMAT(b.tipocambio, 4) AS tipocambio, ";
    $query.= "CONCAT(f.nomempresa, ' (', f.abreviatura, ')') AS empresa, b.beneficiario, ab.fechadoc, ac.desctipodoc, ab.serie, ab.documento , ab.monto AS monttb  ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda INNER JOIN empresa f ON f.id = c.idempresa  ";
    //$query.= "INNER JOIN doctotranban ab ON ab.idtranban=b.id INNER JOIN  tipodocsoptranban ac ON ac.id=ab.idtipodoc ";
    $query.= "WHERE b.id = $idtran";*/

    $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(1, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, DATE_FORMAT(b.fecha, '%d/%m/%Y') AS fecha, ";
    $query.= "CONCAT(d.descripcion, ' No. ', b.numero, ' del ', c.nombre) AS referencia, b.concepto, b.id, 1 AS origen, e.simbolo AS moneda, FORMAT(b.monto, 2) AS monto, FORMAT(b.tipocambio, 4) AS tipocambio, ";
    $query.= "CONCAT(f.nomempresa, ' (', f.abreviatura, ')') AS empresa, b.beneficiario  ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda INNER JOIN empresa f ON f.id = c.idempresa  ";
    $query.= "WHERE b.id = $idtran";
    //print $query;
    $tran = $db->getQuery($query);

    $query = "SELECT b.codigo, b.nombrecta, FORMAT(a.debe, 2) AS debe, FORMAT(a.haber, 2) AS haber, 0 AS estotal ";
    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
    $query.= "WHERE a.activada = 1 AND a.anulado = 0 AND a.origen = 1 AND a.idorigen = $idtran ";
    $query.= "ORDER BY a.debe DESC, b.nombrecta";
    //print $query;
    $tran[0]->detcont = $db->getQuery($query);

    $query = " SELECT  a.id AS idrec, a.fecha, b.nombre AS cliente, c.numero, e.nombre, f.simbolo, FORMAT(c.monto, 2) AS monto, a.idempresa, d.razon, c.tipotrans, c.id ";
    $query.= " FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion  ";
    $query.= " LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda  ";
    $query.= " WHERE c.id=$idtran ";
    $tran[0]->reccont =$db->getQuery($query);

    $query = " SELECT a.idfactura, a.idrecibocli, d.siglas, b.serie, b.numero, b.fecha, c.simbolo, FORMAT(b.total, 2) AS total, FORMAT(a.monto, 2) AS monto, a.interes ";
    $query.= "FROM detcobroventa a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN tipofactura d ON d.id = b.idtipofactura ";
    $query.= "INNER JOIN recibocli n ON n.id = a.idrecibocli LEFT JOIN tranban m ON m.id = n.idtranban ";
    $query.= "WHERE m.id=$idtran ";
    $tran[0]->facrec =$db->getQuery($query);

    if(count($tran[0]->detcont) > 0){
        $query = "SELECT 0 AS codigo, 'TOTALES:' AS nombrecta, FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber, 1 AS estotal ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.activada = 1 AND a.anulado = 0 AND a.origen = 1 AND a.idorigen = $idtran ";
        $query.= "GROUP BY a.origen, a.idorigen";
        //print $query;
        $sum = $db->getQuery($query);
        array_push($tran[0]->detcont, $sum[0]);
    }
    print json_encode($tran[0]);

});
//Fin de realizada por Rony Coyote

$app->post('/existe', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "SELECT COUNT(*) FROM tranban WHERE idbanco = $d->idbanco AND tipotrans = '$d->tipotrans' AND numero = $d->numero AND anulado = 0";
    $existe = (int)$db->getOneField($query) > 0;
    print json_encode(['existe' => ($existe ? 1 : 0)]);
});

$app->run();