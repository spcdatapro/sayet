<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para transacciones bancarias
$app->get('/lsttranbanc/:idbanco(/:tipotrans)', function($idbanco, $tipotrans = ''){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto, ";
    $query.= "a.beneficiario, a.concepto, a.operado, a.anticipo, a.idbeneficiario, a.origenbene, a.anulado, a.fechaanula, a.tipocambio, a.impreso, a.fechaliquida, a.esnegociable, ";
    $query.= "CONCAT('OT: ', c.idpresupuesto, '-', c.correlativo, ' (', e.nombre,')') AS ot, a.iddetpresup, a.iddetpagopresup, a.idproyecto, a.iddocliquida ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco ";
    $query.= "LEFT JOIN detpresupuesto c ON c.id = a.iddetpresup LEFT JOIN presupuesto d ON d.id = c.idpresupuesto LEFT JOIN proveedor e ON e.id = c.idproveedor ";
    $query.= "WHERE a.idbanco = ".$idbanco." ";
    $query.= $tipotrans === '' ? '' : " AND a.tipotrans = '$tipotrans' ";
    $query.= "ORDER BY a.fecha DESC, a.operado, b.nombre, a.tipotrans, a.numero";
    print $db->doSelectASJson($query);
});

$app->post('/lsttran', function(){
    $d = json_decode(file_get_contents('php://input'));    
    if(!isset($d->tipotrans)){ $d->tipotrans = ''; };
    if(!isset($d->idot)){ $d->idot = 0; }
    $db = new dbcpm();
    $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto,  a.retisr, a.montooriginal, a.isr, a.montocalcisr, ";
    $query.= "a.beneficiario, a.concepto, a.operado, a.anticipo, a.idbeneficiario, a.origenbene, a.anulado, a.fechaanula, a.tipocambio, a.impreso, a.fechaliquida, a.esnegociable, ";
    $query.= "CONCAT('OT: ', c.idpresupuesto, '-', c.correlativo, ' (', e.nombre,')') AS ot, a.iddetpresup, a.iddetpagopresup, a.idproyecto, a.iddocliquida ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco ";
    $query.= "LEFT JOIN detpresupuesto c ON c.id = a.iddetpresup LEFT JOIN presupuesto d ON d.id = c.idpresupuesto LEFT JOIN proveedor e ON e.id = c.idproveedor ";
    $query.= "WHERE a.idbanco = $d->idbanco ";
    $query.= $d->fdelstr != "" ? "AND a.fecha >= '$d->fdelstr' " : "";
    $query.= $d->falstr != "" ? "AND a.fecha <= '$d->falstr' " : "";
    $query.= $d->tipotrans != '' ? "AND a.tipotrans = '$d->tipotrans' " : "";
    $query.= (int)$d->idot > 0 ? "AND a.iddetpresup = $d->idot " : '';
    $query.= "ORDER BY a.fecha DESC, a.operado, b.nombre, a.tipotrans, a.numero";
    print $db->doSelectASJson($query);
});

$app->get('/gettran/:idtran', function($idtran){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idbanco, CONCAT(b.nombre, ' (', b.nocuenta, ')') AS nombanco, a.tipotrans, a.numero, a.fecha, a.monto,  a.retisr, a.montooriginal, a.isr, a.montocalcisr, ";
    $query.= "a.beneficiario, a.concepto, a.operado, a.anticipo, a.idbeneficiario, a.origenbene, a.anulado, c.razon, a.fechaanula, a.tipocambio, d.simbolo AS moneda, a.impreso, a.fechaliquida, a.esnegociable, ";
    $query.= "CONCAT('OT: ', e.idpresupuesto, '-', e.correlativo, ' (', g.nombre,')') AS ot, a.iddetpresup, a.iddetpagopresup, a.idproyecto, a.iddocliquida, group_concat(h.id) AS idrecibocli ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco LEFT JOIN razonanulacion c ON c.id = a.idrazonanulacion LEFT JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "LEFT JOIN detpresupuesto e ON e.id = a.iddetpresup LEFT JOIN presupuesto f ON f.id = e.idpresupuesto LEFT JOIN proveedor g ON g.id = e.idproveedor LEFT JOIN recibocli h ON a.id = h.idtranban ";
    $query.= "WHERE a.id = ".$idtran;
    // print $query;
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
    $tc = (float)$db->getOneField("SELECT a.tipocambio FROM moneda a INNER JOIN banco b ON a.id = b.idmoneda WHERE b.id = ".$d->idbanco);

    $cuenta = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = ".((int)$d->anticipo === 1 ? 5 : 3));


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

$app->post('/doinsdetcont', function(){
    $d = json_decode(file_get_contents('php://input'));
    insertaDetalleContable($d->obj, $d->lastid);
});

function updateGastosOT($iddetpresup){
    $db = new dbcpm();

    //$idot = $db->getOneField("SELECT idpresupuesto FROM detpresupuesto WHERE id = $iddetpresup");
    $idot = 0;
    if((int)$idot > 0){
        $query = "UPDATE presupuesto SET gastado = (IFNULL(montoGastadoPresupuesto(id), 0) + IFNULL(getMontoISROT(id, 1), 0)) WHERE id >= $idot";
        //$db->doQuery($query);
    }
}

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $ttsalida = ['C', 'B'];
    $tentrada = ['D', 'R'];
    $query = "INSERT INTO tranban(idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, anticipo, idbeneficiario, origenbene, tipocambio, esnegociable, iddetpresup, ";
    $query.= "iddetpagopresup, idproyecto, iddocliquida, retisr, montooriginal, isr, montocalcisr) ";
    $query.= "VALUES(".$d->idbanco.", '".$d->tipotrans."', '".$d->fechastr."', ".$d->monto.", '".$d->beneficiario."', '".$d->concepto."', ";
    $query.= $d->numero.", ".$d->anticipo.", ".$d->idbeneficiario.", ".$d->origenbene.", ".$d->tipocambio.", $d->esnegociable, $d->iddetpresup, $d->iddetpagopresup, $d->idproyecto, $d->iddocliquida, $d->retisr, $d->montooriginal, $d->isr, $d->montocalcisr)";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    if(in_array($d->tipotrans, $tentrada)){
        if($d->iddocliquida > 0){
            $db->doQuery("UPDATE tranban SET liquidado = 1 where id = $d->iddocliquida"); 
            $db->doQuery("DELETE FROM detpagocompra WHERE idtranban = $d->iddocliquida");
            $db->doQuery("UPDATE tranban SET idfact = NULL where id = $d->iddocliquida");
            // $db->doQuery("UPDATE tranban SET anticipo = WHERE id = $d->iddocliquida");
            $idreembolso = (int)$db->getOneField("SELECT a.id FROM reembolso a INNER JOIN dettranreem b ON b.idreembolso = a.id WHERE b.idtranban = $d->iddocliquida");
            $db->doQuery("DELETE FROM dettranreem WHERE idtranban = $d->iddocliquida");
            $db->doQuery("UPDATE reembolso SET pagado = 0 WHERE id = $idreembolso");
        };

        if ($d->idrecibocli > 0) {
            $recibos = count($d->idrecibocli);
            if ($recibos > 0) {
                $i = 0;
                while ($recibos > $i) {
                    $recibo = $d->idrecibocli[$i];
                    $db->doQuery("UPDATE recibocli SET idtranban = $lastid WHERE id = $recibo");
                    $i++;
                }
            };
        };
    }
    if(in_array($d->tipotrans, $ttsalida)){
        if($d->tipotrans === 'C'){ $db->doQuery("UPDATE banco SET correlativo = correlativo + 1 WHERE id = ".$d->idbanco); }
        //Inserta detalle contable
        insertaDetalleContable($d, $lastid);
        //Actualización de pago de OT, en caso de que tenga OT
        if((int)$d->iddetpresup > 0 && (int)$d->iddetpagopresup > 0){
            $query = "UPDATE detpagopresup SET pagado = 1, origen = 1, idorigen = $lastid WHERE id = $d->iddetpagopresup";
            $db->doQuery($query);
            updateGastosOT($d->iddetpresup);
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
    $query.= "fecha = '$d->fechastr', monto = $d->monto, beneficiario = '$d->beneficiario', concepto = '$d->concepto', ";
    $query.= "operado = $d->operado, numero = $d->numero, anticipo = $d->anticipo, idbeneficiario = $d->idbeneficiario, ";
    $query.= "origenbene = $d->origenbene, tipocambio = $d->tipocambio, esnegociable = $d->esnegociable, iddetpresup = $d->iddetpresup, ";
    $query.= "iddetpagopresup = $d->iddetpagopresup, idproyecto = $d->idproyecto, iddocliquida = $d->iddocliquida ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);

    //Fix para que libere compras o pagos de OTs si ponenen la palabra ANULADO en beneficiario o concepto. 04/11/2020
    $enBene = strpos(strtoupper($d->beneficiario), 'ANULA');
    $enConc = strpos(strtoupper($d->concepto), 'ANULA');
    if ($enBene !== false || $enConc !== false) {
        $db->doQuery("DELETE FROM detpagocompra WHERE idtranban = $d->id");
        $db->doQuery("UPDATE reembolso SET idtranban = 0 WHERE idtranban = $d->id");
    
        $tran = $db->getQuery("SELECT iddetpresup, iddetpagopresup FROM tranban WHERE id = $d->id")[0];
        if((int)$tran->iddetpresup > 0 && (int)$tran->iddetpagopresup > 0){
            $query = "UPDATE detpagopresup SET pagado = 0, origen = 1, idorigen = 0 WHERE id = $tran->iddetpagopresup AND origen = 1 AND idorigen = $d->id";
            $db->doQuery($query);
            updateGastosOT($tran->iddetpresup);
        }
    }

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
            updateGastosOT($d->iddetpresup);
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
    if((int)$d->iddocliquida > 0){
        $query = "UPDATE tranban SET iddetpresup = $d->iddetpresup, iddetpagopresup = $d->iddetpagopresup WHERE id = $d->iddocliquida";
        $db->doQuery($query);
    }

    if(strrpos(strtoupper($d->beneficiario), 'ANULA') !== FALSE || strrpos(strtoupper($d->concepto), 'ANULA') !== FALSE){
        if((int)$d->iddetpresup > 0 && (int)$d->iddetpagopresup > 0){
            $query = "UPDATE detpagopresup SET pagado = 0, origen = 1, idorigen = 0 WHERE id = $d->iddetpagopresup AND origen = 1 AND idorigen = $d->id";
            $db->doQuery($query);
        }
    }
});

$app->post('/uda', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    
    $query = "UPDATE tranban SET monto = 0.00, fechaanula = '$d->fechaanulastr' WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $tran = $db->getQuery("SELECT tipotrans, numero, idbanco, iddetpresup, iddetpagopresup FROM tranban WHERE id = $d->id")[0];
    if(trim($tran->tipotrans) == 'C'){ $db->doQuery("UPDATE banco SET correlativo = $tran->numero WHERE id = $tran->idbanco"); }

	$db->doQuery("UPDATE reembolso SET idtranban = 0 WHERE idtranban = $d->id AND esrecprov = 0");
    $db->doQuery("UPDATE recibocli SET idtranban = 0 WHERE idtranban = $d->id");
    $db->doQuery("DELETE FROM doctotranban WHERE idtranban = $d->id");
    $db->doQuery("DELETE FROM detpagocompra WHERE idtranban = $d->id");
    $db->doQuery("DELETE FROM detallecontable WHERE origen = 1 AND idorigen = $d->id");
    $idreembolso = (int)$db->getOneField("SELECT a.id FROM reembolso a INNER JOIN dettranreem b ON b.idreembolso = a.id WHERE b.idtranban = $d->id");
    $db->doQuery("DELETE FROM dettranreem WHERE idtranban = $d->id");
    $db->doQuery("UPDATE reembolso SET pagado = 0 WHERE id = $idreembolso");
    $db->doQuery("DELETE FROM tranban WHERE id = $d->id");

    if((int)$tran->iddetpresup > 0 && (int)$tran->iddetpagopresup > 0){
        $query = "UPDATE detpagopresup SET pagado = 0, origen = 1, idorigen = 0 WHERE id = $tran->iddetpagopresup AND origen = 1 AND idorigen = $d->id";
        $db->doQuery($query);
        updateGastosOT($tran->iddetpresup);
    }

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

function getConceptoExtra($db, $iddetpagopresup){
    $conceptoext = '';
    $laot = '';
    if((int)$iddetpagopresup > 0){
        $laot = $db->getOneField("SELECT CONCAT(b.idpresupuesto, '-', b.correlativo) FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup WHERE a.id = $iddetpagopresup");
    }

    if($conceptoext != ''){ $conceptoext.= ' - '; }
    $conceptoext.= $laot != '' ? ('OT: '.$laot) : '';

    return $conceptoext;
}

/*
function getFieldInfo($db, $formato, $field){
    $query = "SELECT formato, campo, superior, izquierda, ancho, alto, tamletra AS tamanioletra, tipoletra, ajustelinea AS ajustedelinea, NULL AS valor ";
    $query.= "FROM etiqueta ";
    $query.= "WHERE formato = '$formato' AND campo = '$field'";
    $info = $db->getQuery($query);
    if(count($info) > 0){
        $info = $info[0];
    } else {
        $info = null;
    }
    return $info;
}
*/

function getInfoCheque($db, $idtran, $idusr) {
    $n2l = new NumberToLetterConverter();

    $query = "SELECT CONCAT(a.numero, '/', b.siglas) AS numero, CONCAT('Guatemala, ', DAY(a.fecha), ' de ', (SELECT LOWER(nombre) FROM mes WHERE id = MONTH(a.fecha)), ' de ', YEAR(a.fecha)) AS fecha, ";
    $query.= "FORMAT(a.monto, 2) AS monto, a.monto AS numMonto, a.beneficiario, '' AS montoEnLetras, b.siglas AS banco, d.abreviatura AS empresa, e.formato, e.impresora, a.concepto, a.montooriginal, a.isr, a.montocalcisr, a.retisr, ";
    $query.= "a.iddetpagopresup, a.iddetpresup, a.esnegociable, e.pagewidth, e.pageheight, e.papel, (SELECT UPPER(TRIM(iniciales)) FROM usuario WHERE id = $idusr) AS hechopor ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN empresa d ON d.id = b.idempresa ";
    $query.= "LEFT JOIN tipoimpresioncheque e ON e.id = b.idtipoimpresion ";
    $query.= "WHERE a.id = $idtran";
    $cheque = $db->getQuery($query)[0];
    $cheque->montoEnLetras = $n2l->to_word_int($cheque->numMonto);
    $cheque->concepto.= '. '.getConceptoExtra($db, $cheque->iddetpagopresup);
    $cheque->concepto = trim($cheque->concepto);

    $campos = [];
    foreach($cheque as $key => $value){ $campos[] = $key; }

    $query = "SELECT b.codigo, b.nombrecta AS cuenta, ";
    $query.= "IF(a.debe <> 0, FORMAT(a.debe, 2), '') AS debe, ";
    $query.= "IF(a.haber <> 0, FORMAT(a.haber, 2), '') AS haber ";
    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 1 AND a.idorigen = ".$idtran." ORDER BY a.debe DESC";
    $detcont = $db->getQuery($query);

    $camposdetcont = [];
    if(count($detcont) > 0){
        foreach($detcont[0] as $key => $value){ $camposdetcont[] = $key; }
    }

    $query = "SELECT '' AS codigo, 'TOTALES' AS cuenta, FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber ";
    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 1 AND a.idorigen = ".$idtran;
    $totdet = $db->getQuery($query)[0];
    array_push($detcont, $totdet);

    $cheque->detallecontable = $detcont;    

    $cntCampos = count($campos);
    for($i = 0; $i < $cntCampos; $i++){
        $campo = $campos[$i];
        $info = $db->getFieldInfo($cheque->formato, $campo);
        if($info){
            $info->valor = $cheque->{$campo};
            $cheque->{$campo} = $info;
        }        
    }

    $cntCamposDetCont = count($camposdetcont);
    $cntDetCont = count($cheque->detallecontable);
    for($i = 0; $i < $cntDetCont; $i++){
        $ld = $cheque->detallecontable[$i];
        for($j = 0; $j < $cntCamposDetCont; $j++){
            $campo = $camposdetcont[$j];
            $info = $db->getFieldInfo($cheque->formato, $campo);
            if($info){
                $info->valor = $ld->{$campo};
                $ld->{$campo} = $info;
            }
        }
    }


    return $cheque;
}

$app->get('/prntinfochq/:idtran/:idusr', function($idtran, $idusr){
    $db = new dbcpm();
    $cheques[] = getInfoCheque($db, $idtran, $idusr);
    print json_encode($cheques);
});

$app->get('/prntchqcont/:idbanco/:del/:al/:idusr', function($idbanco, $del, $al, $idusr){
    $db = new dbcpm();
    $cheques = [];
    $query = "SELECT id FROM tranban WHERE tipotrans = 'C' AND idbanco = $idbanco and numero >= $del and numero <= $al ORDER BY numero";
    $idstran = $db->getQuery($query);
    $cntIdsTran = count($idstran);
    for($i = 0; $i < $cntIdsTran; $i++){
        $cheques[] = getInfoCheque($db, $idstran[$i]->id, $idusr);
    }
    print json_encode($cheques);
});

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

    $tran = $db->getQuery("SELECT iddetpresup, iddetpagopresup FROM tranban WHERE id = $d->id")[0];
    if((int)$tran->iddetpresup > 0 && (int)$tran->iddetpagopresup > 0){
        $query = "UPDATE detpagopresup SET pagado = 0, origen = 1, idorigen = 0 WHERE id = $tran->iddetpagopresup AND origen = 1 AND idorigen = $d->id";
        $db->doQuery($query);
        updateGastosOT($tran->iddetpresup);
    }
});

$app->get('/lstbeneficiarios', function(){
    $db = new dbcpm();
    $query = "SELECT id, CONCAT(nit, ' (', nombre, ')') AS beneficiario, chequesa, 1 AS dedonde, concepto, CONVERT(retensionisr, UNSIGNED) AS retieneisr, nit, 'Proveedor(es)' AS grupo FROM proveedor UNION ALL ";
    $query.= "SELECT id, CONCAT(nit, ' (', nombre, ')') AS beneficiario, nombre AS chequesa, 2 AS dedonde, concepto, 0 AS retieneisr, nit, 'Beneficiario(s)' AS grupo FROM beneficiario ";
    $query.= "ORDER BY 4, 2";
    print $db->doSelectASJson($query);
});

$app->get('/lstproveedores', function(){
    $db = new dbcpm();
    $query = "SELECT id, CONCAT(nit, ' (', nombre, ')') AS beneficiario, chequesa, 1 AS dedonde, concepto, CONVERT(retensionisr, UNSIGNED) AS retieneisr, nit, 'Proveedor(es)' AS grupo FROM proveedor ";
    $query.= "ORDER BY 2";
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
    $query.= "WHERE (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) > 0.00 AND a.idempresa = $idempresa AND a.idproveedor = ".$idproveedor." AND a.idmoneda = $idmoneda AND a.alcontado = 0 ";
    $query.= "ORDER BY a.serie, a.documento";
    //echo $query;
    print $db->doSelectASJson($query);
});

$app->get('/reem/:idbene', function($idbene){
    $db = new dbcpm();
    $query = "SELECT 
                a.id,
                CONCAT(b.desctiporeembolso,
                        ' - No. ',
                        LPAD(a.id, 5, '0'),
                        ' - ',
                        DATE_FORMAT(a.finicio, '%d/%m/%Y'),
                        ' - ',
                        c.nombre,
                        ' - Q ',
                        IF(ISNULL(d.totreembolso),
                            0.00,
                            d.totreembolso), ' SALDO - Q ',
                IFNULL(d.totreembolso, 0.00) - IFNULL(e.totpagado, 0.00)) AS cadena,
                a.finicio AS fechafactura,
                'REE' AS serie,
                a.id AS documento,
                IF(ISNULL(d.totreembolso),
                    0.00,
                    d.totreembolso) AS totfact,
                IFNULL(d.totreembolso, 0.00) - IFNULL(e.totpagado, 0.00) AS saldo
            FROM
                reembolso a
                    INNER JOIN
                tiporeembolso b ON b.id = a.idtiporeembolso
                    INNER JOIN
                beneficiario c ON c.id = a.idbeneficiario
                    LEFT JOIN
                (SELECT 
                    idreembolso, SUM(totfact) AS totreembolso
                FROM
                    compra
                WHERE
                    idreembolso > 0
                GROUP BY idreembolso) d ON a.id = d.idreembolso
                    LEFT JOIN
                (SELECT 
                    idreembolso, SUM(monto) AS totpagado
                FROM
                    dettranreem
                GROUP BY idreembolso) e ON a.id = e.idreembolso
            WHERE
                (a.idtranban = 0 AND pagado = 0)
                    AND a.idbeneficiario = $idbene
            ORDER BY a.id";
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
    $total = (int) $db->getOneField("SELECT SUM(totfact) FROM compra WHERE idreembolso = $d->iddocto");
    if($estatus == 2){
        $query = "UPDATE reembolso SET idtranban = ".$d->idtranban." WHERE id = ".$d->iddocto;
        $db->doQuery($query);
    }else{
        $query = "UPDATE reembolso SET estatus = 2, idtranban = ".$d->idtranban.", ffin = NOW() WHERE id = ".$d->iddocto;
        $db->doQuery($query);
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

    if($total - getTotPagado($d->iddocto, $db) <= 0.00) {
        $query = "UPDATE reembolso SET pagado = 1 WHERE id = $d->iddocto";
        $db->doQuery($query);
    }

}

function getTotPagado ($idreembolso, $db) {
    $query = "SELECT IFNULL(SUM(monto), 0.00) AS monto FROM dettranreem WHERE idreembolso = $idreembolso";
    $pagado = (float)$db->getOneField($query);
    return $pagado;
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

    switch(true){
        case in_array($tipodoc, array(1, 3)):
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
        case in_array($tipodoc, array(2, 4)):
            if($d->fechaliquidastr != ''){
                $query = "UPDATE tranban SET fechaliquida = '$d->fechaliquidastr' WHERE id = $d->idtranban";                
                $db->doQuery($query);
            }

            $query = "INSERT INTO dettranreem (idtranban, idreembolso, monto) VALUES ($d->idtranban, $d->iddocto, $d->monto)";
            $db->doQuery($query);

            cierreReembolso($db, $d);
            break;
    };
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE dettranreem SET monto = $d->monto WHERE idtranban = $d->idtranban");
    $db->doQuery("UPDATE doctotranban SET monto = $d->monto WHERE id = ".$d->id);
    $db->doQuery("UPDATE dettranreem SET monto = $d->monto WHERE id = ".$d->id);

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
    $db->doQuery("DELETE FROM dettranreem WHERE idtranban = $d->idtranban");
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

    $query = "
        SELECT 0 AS oby, serie, documento, DATE_FORMAT(fechadoc, '%d/%m/%Y') AS fechadoc, FORMAT(monto, 2) AS monto FROM doctotranban 
        WHERE idtranban = $idtran
        UNION
        SELECT 1 AS oby, '' AS serie, '' AS documento, 'Total:' AS fechadoc, FORMAT(SUM(monto), 2) AS monto
        FROM doctotranban 
        WHERE idtranban = $idtran
        ORDER BY 1, fechadoc";
    $tran[0]->docsop = $db->getQuery($query);

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

$app->post('/sellofactura', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT b.siglas AS banco, a.tipotrans AS tipo, a.numero, 
              DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, c.serie, c.numero AS numerorecibo, 
              DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy
              FROM tranban a
              INNER JOIN banco b ON b.id = a.idbanco
              INNER JOIN recibocli c ON a.id = c.idtranban
              INNER JOIN detcobroventa d ON c.id = d.idrecibocli
              WHERE c.idtranban = $d->idtranban";
    $sellos = $db->getQuery($query);
    $sello = new stdClass();
    if (count($sellos) > 0) {
        $sello = $sellos[0];
    }
    print json_encode(['sello' => $sello]);
});

$app->post('/sellonc', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT e.nombre AS cliente, e.serie, e.numero, e.serieadmin, e.numeroadmin, 
            DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, a.beneficiario, a.concepto
            FROM tranban a
            INNER JOIN banco b ON b.id = a.idbanco
            INNER JOIN recibocli c ON a.id = c.idtranban
            INNER JOIN detcobroventa d ON c.id = d.idrecibocli
            INNER JOIN factura e ON e.id = d.idfactura
            WHERE c.idtranban = $d->idtranban
            ORDER BY e.numeroadmin";
    print $db->doSelectASJson($query);
});

$app->get('/montoots/:idot', function($idot){
    $db = new dbcpm();
    $query = "SELECT ROUND((ROUND(IF(c.eslocal = 1, IF(a.id = d.iddetpresupuesto, a.monto + SUM(d.monto), a.monto), 
    IF(a.id = d.iddetpresupuesto, (a.monto * a.tipocambio) + (d.monto * d.tipocambio), a.monto * a.tipocambio)) - (IFNULL((SELECT SUM(b.totfact) FROM detpresupuesto a INNER JOIN compra b ON a.id = b.ordentrabajo WHERE a.id = $idot AND a.idmoneda = b.idmoneda), 0.00) + 
    IFNULL(IF(c.eslocal = 1, (SELECT SUM(b.totfact * b.tipocambio) FROM detpresupuesto a INNER JOIN compra b ON a.id = b.ordentrabajo WHERE a.id = $idot AND a.idmoneda != b.idmoneda),
    (SELECT SUM(b.totfact) / b.tipocambio FROM detpresupuesto a INNER JOIN compra b ON a.id = b.ordentrabajo WHERE a.id = $idot AND a.idmoneda != b.idmoneda)), 0.00)), 2) +
    ROUND(IF(c.eslocal = 1, IF(a.id = d.iddetpresupuesto, a.monto + SUM(d.monto), a.monto), 
    IF(a.id = d.iddetpresupuesto, (a.monto * a.tipocambio) + (d.monto * d.tipocambio), a.monto * a.tipocambio)) - (IFNULL((SELECT SUM(b.totfact) FROM detpresupuesto a INNER JOIN compra b ON a.id = b.ordentrabajo WHERE a.id = $idot AND a.idmoneda = b.idmoneda), 0.00) + 
    IFNULL(IF(c.eslocal = 1, (SELECT SUM(b.totfact * b.tipocambio) FROM detpresupuesto a INNER JOIN compra b ON a.id = b.ordentrabajo WHERE a.id = $idot AND a.idmoneda != b.idmoneda),
    (SELECT SUM(b.totfact) / b.tipocambio FROM detpresupuesto a INNER JOIN compra b ON a.id = b.ordentrabajo WHERE a.id = $idot AND a.idmoneda != b.idmoneda)), 0.00)), 2) * 0.10), 2)
    AS monto
    FROM detpresupuesto a 
    INNER JOIN compra b ON a.id = b.ordentrabajo
    INNER JOIN moneda c ON c.id = a.idmoneda
    LEFT JOIN ampliapresupuesto d ON a.id = d.iddetpresupuesto
    WHERE a.id = $idot AND d.idestatuspresupuesto = 3";

    $monto = $db->getOneField($query);
    
    print json_encode(['monto' => $monto ? $monto : 0.00 ]);
});

$app->post('/calcisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    
    $montoOriginal = (float)$d->montooriginal;
    $montoCalcISR = (float)$d->montocalcisr;
    $idbanco = $d->objBanco->id;

    $eslocal = (int)$db->getOneField("SELECT a.eslocal FROM moneda a INNER JOIN banco b ON a.id = b.idmoneda WHERE b.id = $idbanco") === 1;
    if (!$eslocal) {
        $montoCalcISR = round((float)$d->montocalcisr * (float)$d->tipocambio, 2);
    }

    $d->isr = $db->calculaISR($montoCalcISR);
    
    if (!$eslocal) {
        $d->isr = round($d->isr / (float)$d->tipocambio, 2);
    }

    $d->monto = round($montoOriginal - $d->isr, 2);

    print json_encode([
        'isr' => $d->isr,
        'monto' => $d->monto
    ]);
});

$app->run();