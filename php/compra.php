<?php
require 'vendor/autoload.php';
require_once 'db.php';

$plantillas = $_SERVER["DOCUMENT_ROOT"] . "/sayet/pages";

$app = new \Slim\Slim(array('templates.path' => $plantillas));
$app->response->headers->set('Content-Type', 'application/json');

//API para compras
$app->get('/lstcomras/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, a.mesiva, ";
    $query.= "a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, a.ordentrabajo, ";
    $query.= "a.totfact, a.noafecto, a.subtotal, a.iva, IF(ISNULL(e.cantpagos), 0, e.cantpagos) AS cantpagos, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto, a.nombrerecibo ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN ( SELECT a.idcompra, COUNT(a.idtranban) AS cantpagos	";
    $query.= "FROM detpagocompra a INNER JOIN tranban b ON b.id = a.idtranban INNER JOIN banco c ON c.id = b.idbanco ";
    $query.= "INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda ";
    $query.= "GROUP BY a.idcompra) e ON a.id = e.idcompra LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "WHERE a.idempresa = ".$idempresa." AND a.idreembolso = 0 ";
    $query.= "ORDER BY a.fechapago, b.nombre";
    print $db->doSelectASJson($query);
});

$app->post('/lstcomprasfltr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idot)){ $d->idot = 0; }

    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, a.mesiva, ";
    $query.= "a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, a.ordentrabajo, ";
    $query.= "a.totfact, a.noafecto, a.subtotal, a.iva, IF(ISNULL(e.cantpagos), 0, e.cantpagos) AS cantpagos, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto, a.idunidad, a.nombrerecibo ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN ( SELECT a.idcompra, COUNT(a.idtranban) AS cantpagos	";
    $query.= "FROM detpagocompra a INNER JOIN tranban b ON b.id = a.idtranban INNER JOIN banco c ON c.id = b.idbanco ";
    $query.= "INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda ";
    $query.= "GROUP BY a.idcompra) e ON a.id = e.idcompra LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso = 0 ";
    $query.= $d->fdelstr != '' ? "AND a.fechafactura >= '$d->fdelstr' " : "" ;
    $query.= $d->falstr != '' ? "AND a.fechafactura <= '$d->falstr' " : "" ;
    $query.= (int)$d->idot == 0 ? '' : "AND a.ordentrabajo = $d->idot ";
    $query.= "ORDER BY a.fechapago, b.nombre";
    // print $query;
    print $db->doSelectASJson($query);
});

$app->get('/getcompra/:idcompra(/:idot)', function($idcompra, $idot = 0){
    $db = new dbcpm();
    $idcompra = (int)$idcompra;
    $idot = (int)$idot;
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, ";
    $query.= "a.mesiva, a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, ";
    $query.= "a.ordentrabajo, a.totfact, a.noafecto, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto, a.idunidad, a.nombrerecibo ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "WHERE ";

    if($idcompra > 0 && $idot == 0) {
        $query.= "a.id = $idcompra";
    } else if($idcompra == 0 && $idot > 0) {
        $query.= "a.ordentrabajo = $idot";
    } else if($idcompra > 0 && $idot > 0) {
        $query.= "a.id = $idcompra AND a.ordentrabajo = $idot";
    }
    
    print $db->doSelectASJson($query);
});

$app->post('/chkexiste', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "SELECT TRIM(b.nombre) AS proveedor, TRIM(b.nit) AS nit, TRIM(a.serie) AS serie, a.documento, TRIM(c.nomempresa) AS empresa, TRIM(c.abreviatura) AS abreviaempresa, 1 AS existe ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.idreembolso = 0 AND a.idproveedor = $d->idproveedor AND TRIM(a.serie) = '".trim($d->serie)."' AND a.documento = $d->documento ";
    $query.= "UNION ";
    $query.= "SELECT TRIM(a.proveedor) AS proveedor, TRIM(a.nit) AS nit, TRIM(a.serie) AS serie, a.documento, TRIM(c.nomempresa) AS empresa, TRIM(c.abreviatura) AS abreviaempresa, 1 AS existe ";
    $query.= "FROM compra a INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.idreembolso > 0 AND TRIM(a.nit) = '".trim($d->nit)."' AND TRIM(a.serie) = '".trim($d->serie)."' AND a.documento = $d->documento";
    $existentes = $db->getQuery($query);
    if(count($existentes) > 0){
        print json_encode($existentes[0]);
    }else{
        print json_encode(['existe' => '0']);
    }
});

$app->post('/buscar', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempresa, a.idreembolso, c.beneficiario, c.finicio AS finireem, c.ffin AS ffinreem,
    IF(a.idreembolso = 0, 'N/A', IF(c.estatus = 1, 'ABIERTO', 'CERRADO')) AS estatusreembolso, a.idtipofactura, d.desctipofact AS tipofactura, a.idproveedor, 
    IF(e.id IS NULL, a.proveedor, e.nombre) AS proveedor, IF(e.id IS NULL, a.nit, e.nit) AS nit, a.serie, a.documento, 
    DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechafactura, DATE_FORMAT(a.fechaingreso, '%d/%m/%Y') AS fechaingreso, DATE_FORMAT(a.fechapago, '%d/%m/%Y') AS fechapago,
    a.mesiva, a.idtipocompra, f.desctipocompra AS tipocompra, a.conceptomayor AS concepto, IF(a.creditofiscal = 1, 'SI', '') AS creditofiscal, 
    IF(a.extraordinario = 1, 'SI', '') AS extraordinario, a.totfact, a.noafecto, a.subtotal, a.iva, a.isr, a.idtipocombustible, g.descripcion AS tipocombustible, a.galones,
    a.idp, a.idmoneda, h.simbolo AS moneda, a.tipocambio, i.tranban, a.idproyecto, a.ordentrabajo, a.idunidad, a.nombrerecibo ";
    $query.= "FROM compra a LEFT JOIN empresa b ON b.id = a.idempresa LEFT JOIN reembolso c ON c.id = a.idreembolso LEFT JOIN tipofactura d ON d.id = a.idtipofactura 
    LEFT JOIN proveedor e ON e.id = a.idproveedor LEFT JOIN tipocompra f ON f.id = a.idtipocompra LEFT JOIN tipocombustible g ON g.id = a.idtipocombustible 
    LEFT JOIN moneda h ON h.id = a.idmoneda LEFT JOIN (
    SELECT detpagocompra.idcompra, TRIM(GROUP_CONCAT(DISTINCT CONCAT(TRIM(banco.siglas),  '-', TRIM(moneda.simbolo),  ' / ', TRIM(tranban.tipotrans), tranban.numero) SEPARATOR ', ')) AS tranban
    FROM detpagocompra
    INNER JOIN tranban ON tranban.id = detpagocompra.idtranban
    INNER JOIN banco ON banco.id = tranban.idbanco
    INNER JOIN moneda ON moneda.id = banco.idmoneda
    WHERE detpagocompra.esrecprov = 0
    GROUP BY detpagocompra.idcompra
    ) i ON a.id = i.idcompra ";
    $query.= "WHERE a.$d->qfecha >= '$d->fdelstr' AND a.$d->qfecha <= '$d->falstr' ";
    $query.= $d->proveedor != '' ? "AND (e.nombre LIKE '%$d->proveedor%' OR a.proveedor LIKE '%$d->proveedor%') " : "";
    $query.= $d->nit != '' ? "AND (e.nit LIKE '%$d->nit%' OR a.nit LIKE '%$d->nit%') " : "";
    $query.= $d->concepto != '' ? "AND a.conceptomayor LIKE '%$d->concepto%' " : "";
    $query.= $d->idempresa != '' ? "AND a.idempresa IN($d->idempresa) " : "";
    $query.= trim($d->serie) != '' ? "AND TRIM(a.serie) LIKE '%".trim($d->serie)."%' " : "";
    $query.= (int)$d->documento > 0 ? "AND a.documento = $d->documento ": "";
    $query.= "ORDER BY $d->orderby";

    print $db->doSelectASJson($query);
});

$app->post('/updproycomp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE compra SET idproyecto = $d->idproyecto WHERE id = $d->idcompra";
    $db->doQuery($query);
});

function insertaDetalleContable($d, $idorigen){
    $db = new dbcpm();
    $origen = 2;

    //Validación de la moneda de la factura
    $query = "SELECT eslocal FROM moneda WHERE id = $d->idmoneda";
    $esLocal = (int)$db->getOneField($query) === 1;
    $d->tipocambio = $esLocal ? 1.00 : $d->tipocambio;
    //Fin de la validación de la moneda de la factura

    //Inicia inserción automática de detalle contable de la factura
    $ctagastoprov = (int)$d->ctagastoprov;
    $ctaivaporpagar = (int)$db->getOneField("SELECT idcuentac FROM tipocompra WHERE id = ".$d->idtipocompra);
    if($ctaivaporpagar == 0){ $ctaivaporpagar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 2"); }

    $esAnticipo = false;
    if((int)$d->idcheque > 0){
        $esAnticipo = (int)$db->getOneField("SELECT anticipo FROM tranban WHERE id = $d->idcheque") === 1;
    }

    $ctaproveedores = (int)$db->getOneField("SELECT a.idcxp FROM detcontprov a INNER JOIN cuentac b ON b.id = a.idcxp WHERE a.idproveedor = $d->idproveedor AND b.idempresa = $d->idempresa LIMIT 1");    
    if(!($ctaproveedores > 0)) {
        $ctaproveedores = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = $d->idempresa AND idtipoconfig = 3");
    }

    if($esAnticipo) {
        $ctaproveedores = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = $d->idempresa AND idtipoconfig = 5");
    }    

    $ctaisrretenido = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 8");
    $ctaidp = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 9");
    $d->conceptoprov.= ", ".$d->serie." - ".$d->documento;
    $d->idp = (float)$d->idp;

    if($ctagastoprov > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$idorigen.", ".$ctagastoprov.", ".round((((float)$d->subtotal - $d->idp) * (float)$d->tipocambio), 2).", 0.00, '".$d->conceptomayor."')";
        $db->doQuery($query);
    };

    if($ctaivaporpagar > 0 && (float)$d->iva > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$idorigen.", ".$ctaivaporpagar.", ".round(((float)$d->iva * (float)$d->tipocambio), 2).", 0.00, '".$d->conceptomayor."')";
        $db->doQuery($query);
    };

    if($ctaisrretenido > 0 && $d->isr > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$idorigen.", ".$ctaisrretenido.", 0.00, ".round(((float)$d->isr * (float)$d->tipocambio), 2).", '".$d->conceptomayor."')";
        $db->doQuery($query);
    }

    if($ctaidp > 0 && $d->idp > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$idorigen.", ".$ctaidp.", ".round(((float)$d->idp * (float)$d->tipocambio), 2).", 0.00, '".$d->conceptomayor."')";
        $db->doQuery($query);
    }

    if($ctaproveedores > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$idorigen.", ".$ctaproveedores.", 0.00, ".round((((float)$d->totfact - $d->isr) * (float)$d->tipocambio), 2).", '".$d->conceptomayor."')";
        $db->doQuery($query);
    };

    //Agregado para la tasa municipal EEGSA. Solo va a funcionar con el nit 32644-5
    $nit = $db->getOneField("SELECT TRIM(nit) FROM proveedor WHERE id = $d->idproveedor");
    if(trim($nit) == '32644-5' && (float)$d->noafecto != 0){
        $ctaeegsa = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 12");
        if($ctaeegsa > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= $origen.", ".$idorigen.", ".$ctaeegsa.", ".round(((float)$d->noafecto * (float)$d->tipocambio), 2).", 0.00, '".$d->conceptomayor."')";
            $db->doQuery($query);
        }
    }

    $url = 'http://localhost/sayet/php/fixdescuadrecompra.php/fix';
    $dataa = ['idfactura' => $idorigen];
    $db->CallJSReportAPI('POST', $url, json_encode($dataa));
};

function generaDetalleProyecto($db, $lastid){
    $query = "SELECT idproyecto FROM compra WHERE id = $lastid";
    $idproyecto = (int)$db->getOneField($query);
    $query = "SELECT idunidad FROM compra WHERE id = $lastid";
    $idunidad = (int)$db->getOneField($query);
    $query = "SELECT a.idcuenta, a.debe FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta 
    WHERE a.origen = 2 AND a.idorigen = $lastid AND (b.codigo LIKE '5%' OR b.codigo LIKE '6%')";
    $gastos = $db->getQuery($query);
    $cntGastos = count($gastos);
    if($idproyecto > 0 && $cntGastos > 0){
        for($i = 0; $i < $cntGastos; $i++){
            $gasto = $gastos[$i];
            $query = "INSERT INTO compraproyecto(idcompra, idproyecto, idunidad, idcuentac, monto) VALUES(";
            $query.= "$lastid, $idproyecto, $idunidad, $gasto->idcuenta, $gasto->debe";
            $query.= ")";
            $db->doQuery($query);
        }
    }
};

function atarChequeAFactura($db, $d, $idfactura) {
    if((int)$d->idcheque > 0) {
        $query = "UPDATE tranban SET idfact = $idfactura WHERE id = $d->idcheque";
        $db->doQuery($query);        

        $query = "SELECT a.id, b.idmoneda, a.monto, c.eslocal ";
        $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
        $query.= "WHERE a.id = $d->idcheque";
        $datosCheque = $db->getQuery($query)[0];

        $montoAInsertar = (float)$datosCheque->monto;

        $esLocalMonedaFact = (int)$db->getOneField("SELECT eslocal FROM moneda WHERE id = $d->idmoneda") === 1;

        if((int)$datosCheque->idmoneda !== (int)$d->idmoneda) {
            if((int)$datosCheque->eslocal === 1 && !$esLocalMonedaFact) {
                $montoAInsertar = round((float)$datosCheque->monto / (float)$d->tipocambio, 2);
            } else {
                $montoAInsertar = round((float)$datosCheque->monto * (float)$d->tipocambio, 2);
            }
        }
        
        $query = "INSERT INTO detpagocompra(idcompra, idtranban, monto, esrecprov) VALUES($idfactura, $d->idcheque, $montoAInsertar, 0)";
        $db->doQuery($query);
    }
}

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idunidad)){ $d->idunidad = 0; }
    if(!isset($d->nombrerecibo)){ $d->nombrerecibo = 'NULL'; } else { $d->nombrerecibo = "'$d->nombrerecibo'"; }
    if(!isset($d->idcheque)){ $d->idcheque = 0; }

    if((int)$d->idtipofactura !== 5) {
        $calcisr = (int)$db->getOneField("SELECT retensionisr FROM proveedor WHERE id = ".$d->idproveedor) === 1;
    } else {
        $calcisr = false;
    }
    
    $esLocalMonedaFact = (int)$db->getOneField("SELECT eslocal FROM moneda WHERE id = $d->idmoneda") === 1;
    
    if ($esLocalMonedaFact) {
        $d->isr = !$calcisr ? 0.00 : $db->calculaISR((float)$d->subtotal);
    } else {
        $d->isr = !$calcisr ? 0.00 : round(($db->calculaISR((float)$d->subtotal * (float)$d->tipocambio)) / (float)$d->tipocambio, 2);
    }

    $query = "INSERT INTO compra(idempresa, idproveedor, serie, documento, fechaingreso, mesiva, fechafactura, idtipocompra, ";
    $query.= "conceptomayor, creditofiscal, extraordinario, fechapago, ordentrabajo, totfact, noafecto, subtotal, iva, idmoneda, tipocambio, ";
    $query.= "idtipofactura, isr, idtipocombustible, galones, idp, idproyecto, idunidad, nombrerecibo) ";
    $query.= "VALUES(".$d->idempresa.", ".$d->idproveedor.", '".$d->serie."', ".$d->documento.", '".$d->fechaingresostr."', ".$d->mesiva.", '".$d->fechafacturastr."', ";
    $query.= $d->idtipocompra.", '".$d->conceptomayor."', ".$d->creditofiscal.", ".$d->extraordinario.", '".$d->fechapagostr."', ".$d->ordentrabajo.", ";
    $query.= $d->totfact.", ".$d->noafecto.", ".$d->subtotal.", ".$d->iva.", ".$d->idmoneda.", ".$d->tipocambio.", ".$d->idtipofactura.", ".$d->isr.", ";
    $query.= $d->idtipocombustible.", ".$d->galones.", ".$d->idp.", $d->idproyecto, $d->idunidad, $d->nombrerecibo";
    $query.= ")";
    $db->doQuery($query);

    $lastid = $db->getLastId();
    if((int)$lastid > 0){
        //Inicia inserción automática de detalle contable de la factura
        insertaDetalleContable($d, $lastid);
        //Fin de inserción automática de detalle contable de la factura
        generaDetalleProyecto($db, $lastid);
        atarChequeAFactura($db, $d, $lastid);
    }

    print json_encode(['lastid' => $lastid]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idunidad)){ $d->idunidad = 0; }
    if(!isset($d->nombrerecibo)){ $d->nombrerecibo = 'NULL'; } else { $d->nombrerecibo = "'$d->nombrerecibo'"; }
    if(!isset($d->idcheque)){ $d->idcheque = 0; }

    if((int)$d->idtipofactura !== 5) {
        $calcisr = (int)$db->getOneField("SELECT retensionisr FROM proveedor WHERE id = ".$d->idproveedor) === 1;
    } else {
        $calcisr = false;        
    }
    
    $d->isr = !$calcisr ? 0.00 : $db->calculaISR((float)$d->subtotal, (float)$d->tipocambio);

    $query = "UPDATE compra SET ";
    $query.= "idproveedor = ".$d->idproveedor.", serie = '".$d->serie."', documento = ".$d->documento.", fechaingreso = '".$d->fechaingresostr."', ";
    $query.= "mesiva = ".$d->mesiva.", fechafactura = '".$d->fechafacturastr."', idtipocompra = ".$d->idtipocompra.", conceptomayor =  '".$d->conceptomayor."', ";
    $query.= "creditofiscal = ".$d->creditofiscal.", extraordinario = ".$d->extraordinario.", fechapago = '".$d->fechapagostr."', ordentrabajo = ".$d->ordentrabajo.", ";
    $query.= "totfact = ".$d->totfact.", noafecto = ".$d->noafecto.", subtotal = ".$d->subtotal.", iva = ".$d->iva.", ";
    $query.= "idmoneda = ".$d->idmoneda.", tipocambio = ".$d->tipocambio.", idtipofactura = ".$d->idtipofactura.", isr = ".$d->isr.", ";
    $query.= "idtipocombustible = ".$d->idtipocombustible.", galones = ".$d->galones.", idp = ".$d->idp.", idproyecto = $d->idproyecto, idunidad = $d->idunidad, ";
    $query.= "nombrerecibo = $d->nombrerecibo ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);

    $query = "UPDATE compraproyecto SET idproyecto = $d->idproyecto where idcompra = ".$d->id ;
    $db->doQuery($query);

    $origen = 2;
    $idorigen = (int)$d->id;
    $query = "DELETE FROM detallecontable WHERE origen = ".$origen." AND idorigen = ".$idorigen;
    $db->doQuery($query);

    //Inicia inserción automática de detalle contable de la factura
    insertaDetalleContable($d, $d->id);
    //Fin de inserción automática de detalle contable de la factura
    // atarChequeAFactura($db, $d, $d->id);
    print json_encode(['lastid' => $idorigen]);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detallecontable WHERE origen = 2 AND idorigen = ".$d->id);
    $db->doQuery("DELETE FROM compra WHERE id = ".$d->id);
});

$app->post('/uisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->noformisr = $d->noformisr == '' ? 'NULL' : "'".$d->noformisr."'";
    $d->noaccisr = $d->noaccisr == '' ? 'NULL' : "'".$d->noaccisr."'";
    $d->fecpagoformisrstr = $d->fecpagoformisrstr == '' ? 'NULL' : "'".$d->fecpagoformisrstr."'";
    $d->mesisr = (int)$d->mesisr == 0 ? 'NULL' : $d->mesisr;
    $d->anioisr = (int)$d->anioisr == 0 ? 'NULL' : $d->anioisr;
    $query = "UPDATE compra SET noformisr = ".$d->noformisr.", noaccisr = ".$d->noaccisr.", fecpagoformisr = ".$d->fecpagoformisrstr.", mesisr = ".$d->mesisr.", anioisr = ".$d->anioisr." WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->get('/tranpago/:idcompra', function($idcompra){
    $db = new dbcpm();
    $query = "SELECT a.idtranban, CONCAT('(', d.abreviatura, ') ', d.descripcion) AS tipodoc, b.numero, CONCAT(c.nombre, ' (', e.simbolo, ')') AS banco, b.monto ";
    $query.= "FROM detpagocompra a INNER JOIN tranban b ON b.id = a.idtranban INNER JOIN banco c ON c.id = b.idbanco ";
    $query.= "INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda ";
    $query.= "WHERE a.idcompra = ".$idcompra." AND a.esrecprov = 0 ";
    $query.= "UNION ALL ";
    $query.= "SELECT a.idtranban, 'Recibo' AS tipodoc, LPAD(b.id, 5, '0') AS numero, '' AS banco, c.arebajar AS monto ";
    $query.= "FROM detpagocompra a INNER JOIN reciboprov b ON b.id = a.idtranban INNER JOIN detrecprov c ON b.id = c.idrecprov ";
    $query.= "WHERE a.idcompra = $idcompra AND a.esrecprov = 1 AND c.origen = 2 AND c.idorigen = $idcompra ";
    $query.= "ORDER BY 2, 3";
    print $db->doSelectASJson($query);
});

$app->post('/lstchq', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id AS idtran, b.siglas, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.tipotrans, a.numero, c.simbolo AS moneda, FORMAT(a.monto, 2) AS monto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.tipotrans IN('C', 'B') AND a.anulado = 0 AND UPPER(a.beneficiario) NOT LIKE '%ANULAD%' AND a.concepto NOT LIKE '%ANULAD%' AND ";
    $query.= "a.idbeneficiario = $d->idproveedor AND b.idempresa = $d->idempresa AND b.idmoneda = $d->idmoneda AND ";
    $query.= "(SELECT COUNT(id) FROM periodocontable WHERE abierto = 1 AND a.fecha >= del AND a.fecha <= al) > 0 AND ";
    $query.= "a.id NOT IN(SELECT idtranban FROM doctotranban WHERE idtipodoc = 1 AND iddocto = $d->idcompra) ";
    $query.= "ORDER BY b.siglas, a.fecha, a.numero";
    print $db->doSelectASJson($query);
});

$app->post('/addtotranban', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $url = 'http://localhost/sayet/php/tranbanc.php/cd';
    $data = [
        'idtranban' => $d->idtranban, 'idtipodoc' => $d->idtipodoc, 'documento' => $d->documento, 'fechadocstr' => $d->fechadoc, 'monto' => $d->monto, 'serie' => $d->serie, 'iddocto' => $d->iddocto, 'fechaliquidastr' => $d->fechaliquidastr
    ];
    $db->CallJSReportAPI('POST', $url, json_encode($data));
});

$app->post('/lstcompisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $where = "";
    if($d->fdelstr != ''){ $where.= "AND a.fechafactura >= '".$d->fdelstr."' "; }
    if($d->falstr != ''){ $where.= "AND a.fechafactura <= '".$d->falstr."' "; }
    switch((int)$d->cuales){
        case 1:
            $where.= "AND LENGTH(a.noformisr) > 0 ";
            break;
        case 2:
            $where.= "AND (ISNULL(a.noformisr) OR LENGTH(a.noformisr) = 0) ";
            break;
    }

    $query = "SELECT a.id, b.nit, b.nombre AS nomproveedor, a.serie, a.documento, a.fechafactura, c.desctipocompra, a.tipocambio, f.simbolo AS moneda, g.desctipofact AS tipofactura, ";
    $query.= "a.totfact, a.isr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, ROUND((a.isr * a.tipocambio), 2) AS isrlocal, ";
    $query.= "ROUND(a.totfact * a.tipocambio, 2) AS totfactlocal, ROUND(a.subtotal * a.tipocambio, 2) AS montobase, ROUND(a.iva * a.tipocambio, 2) AS iva ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso = 0 AND a.isr > 0 ";
    $query.= $where;
    $query.= "UNION ";
    $query.= "SELECT a.id, a.nit, a.proveedor, a.serie, a.documento, a.fechafactura, c.desctipocompra, a.tipocambio, f.simbolo AS moneda, g.desctipofact AS tipofactura, ";
    $query.= "a.totfact, a.isr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, ROUND((a.isr * a.tipocambio), 2) AS isrlocal, ";
    $query.= "ROUND(a.totfact * a.tipocambio, 2) AS totfactlocal, ROUND(a.subtotal * a.tipocambio, 2) AS montobase, ROUND(a.iva * a.tipocambio, 2) AS iva ";
    $query.= "FROM compra a INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso > 0 AND a.isr > 0 ";
    $query.= $where;
    $query.= "ORDER BY 13, 3, 6";
    print $db->doSelectASJson($query);
});

$app->get('/getcompisr/:idcomp', function($idcomp){
    $db = new dbcpm();
    $query = "SELECT a.id, b.nit, b.nombre AS nomproveedor, a.serie, a.documento, a.fechafactura, c.desctipocompra, a.tipocambio, f.simbolo AS moneda, g.desctipofact AS tipofactura, ";
    $query.= "a.totfact, a.isr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, ROUND((a.isr * a.tipocambio), 2) AS isrlocal ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.id = ".$idcomp." AND a.idreembolso = 0 AND a.isr > 0 ";
    $query.= "UNION ";
    $query.= "SELECT a.id, a.nit, a.proveedor, a.serie, a.documento, a.fechafactura, c.desctipocompra, a.tipocambio, f.simbolo AS moneda, g.desctipofact AS tipofactura, ";
    $query.= "a.totfact, a.isr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, ROUND((a.isr * a.tipocambio), 2) AS isrlocal ";
    $query.= "FROM compra a INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.id = ".$idcomp." AND a.idreembolso > 0 AND a.isr > 0 ";
    $query.= "ORDER BY 13, 3, 6";
    print $db->doSelectASJson($query);
});

$app->post('/rptcompisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $info = new stdclass();

    //var_dump($d);

    $query = "SELECT TRIM(nomempresa) AS empresa, abreviatura AS abreviaempre, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS fdel, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, ";
    $query.= "DATE_FORMAT(NOW(), '%d/%m/%Y') AS hoy, 0.00 AS totisr, 0.00 AS totfact, 0.00 AS totiva, 0.00 AS totbase, FORMAT($d->isrempleados, 2) AS isrempleados, 0.00 AS isrpagar, ";
	$query.= "FORMAT($d->isrcapital, 2) AS isrcapital ";
    $query.= "FROM empresa WHERE id = $d->idempresa";
    //print $query;
    $info->general = $db->getQuery($query)[0];

    $where = "";
    if($d->fdelstr != ''){ $where.= "AND a.fechafactura >= '".$d->fdelstr."' "; }
    if($d->falstr != ''){ $where.= "AND a.fechafactura <= '".$d->falstr."' "; }
    switch((int)$d->cuales){
        case 1:
            $where.= "AND LENGTH(a.noformisr) > 0 ";
            break;
        case 2:
            $where.= "AND (ISNULL(a.noformisr) OR LENGTH(a.noformisr) = 0) ";
            break;
    }

    $query = "SELECT a.id, b.nit, b.nombre AS nomproveedor, a.serie, a.documento, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechafactura, c.desctipocompra, a.tipocambio, f.simbolo AS moneda, g.desctipofact AS tipofactura, ";
    $query.= "a.totfact, FORMAT(a.isr, 2) AS isr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, FORMAT(ROUND((a.isr * a.tipocambio), 2), 2) AS isrlocal, ";
    $query.= "FORMAT(ROUND(a.totfact * a.tipocambio, 2), 2) AS totfactlocal, FORMAT(ROUND(a.subtotal * a.tipocambio, 2), 2) AS montobase, ";
    $query.= "FORMAT(ROUND(a.iva * a.tipocambio, 2), 2) AS iva ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso = 0 AND a.isr > 0 ";
    $query.= $where;
    $query.= "UNION ";
    $query.= "SELECT a.id, a.nit, a.proveedor, a.serie, a.documento, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechafactura, c.desctipocompra, a.tipocambio, f.simbolo AS moneda, g.desctipofact AS tipofactura, ";
    $query.= "a.totfact, FORMAT(a.isr, 2) AS isr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, FORMAT(ROUND((a.isr * a.tipocambio), 2), 2) AS isrlocal, ";
    $query.= "FORMAT(ROUND(a.totfact * a.tipocambio, 2), 2) AS totfactlocal, FORMAT(ROUND(a.subtotal * a.tipocambio, 2), 2) AS montobase, ";
    $query.= "FORMAT(ROUND(a.iva * a.tipocambio, 2), 2) AS iva ";
    $query.= "FROM compra a INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso > 0 AND a.isr > 0 ";
    $query.= $where;
    $query.= "ORDER BY 13, 3, 6";
    $info->facturas = $db->getQuery($query);

    $query = "SELECT SUM(isrlocal) AS totisrlocal, FORMAT(SUM(totfactlocal), 2) AS totfactlocal, FORMAT(SUM(montobase), 2) AS montobase, ";
    $query.= "FORMAT(SUM(totiva), 2) AS totiva ";
    $query.= "FROM (SELECT ROUND(SUM(a.isr * a.tipocambio), 2) AS isrlocal, ";
    $query.= "ROUND(SUM(a.totfact * a.tipocambio), 2) AS totfactlocal, ROUND(SUM(a.subtotal * a.tipocambio), 2) AS montobase, ROUND(SUM(a.iva * a.tipocambio), 2) AS totiva ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso = 0 AND a.isr > 0 ";
    $query.= $where;
    $query.= "UNION ";
    $query.= "SELECT ROUND(SUM(a.isr * a.tipocambio), 2) AS isrlocal, ";
    $query.= "ROUND(SUM(a.totfact * a.tipocambio), 2) AS totfactlocal, ROUND(SUM(a.subtotal * a.tipocambio), 2) AS montobase, ROUND(SUM(a.iva * a.tipocambio), 2) AS totiva ";
    $query.= "FROM compra a INNER JOIN tipocompra c ON c.id = a.idtipocompra INNER JOIN empresa d ON d.id = a.idempresa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso > 0 AND a.isr > 0 ";
    $query.= $where;
    $query.= ") a";
    $totales = $db->getQuery($query)[0];

    $info->general->isrpagar = number_format((float)$totales->totisrlocal + (float)$d->isrempleados + (float)$d->isrcapital, 2);
    $info->general->totisr = number_format((float)$totales->totisrlocal, 2);
    $info->general->totfact = $totales->totfactlocal;
    $info->general->totbase = $totales->montobase;
    $info->general->totiva = $totales->totiva;

    print json_encode($info);
});

function getCompraDetalle($compra)
{
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, ";
    $query.= "a.mesiva, a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, ";
    $query.= "a.ordentrabajo, a.totfact, a.noafecto, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto, i.nomproyecto ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "LEFT JOIN proyecto i ON i.id = a.idproyecto ";
    $query.= "WHERE a.id = {$compra}";
    
    $infocompra = $db->getQuery($query)[0];
    
    $query = "SELECT a.id, a.origen, a.idorigen, a.idcuenta, CONCAT('(', b.codigo, ') ', b.nombrecta) AS desccuentacont, ";
    $query.= "a.debe, a.haber, a.conceptomayor ";
    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
    $query.= "WHERE a.origen = 2 AND a.idorigen = {$compra} ";
    $query.= "ORDER BY a.debe DESC, a.haber, b.codigo";
    $infocompra->detalle = $db->getQuery($query);

    return ["compra" => $infocompra];
}

$app->post('/rptcompra', function(){
	
	$d = json_decode(file_get_contents('php://input'));
	
    print json_encode(getCompraDetalle($d->idcompra));
});

//API para detalle de proyectos que son afectados en las compras
$app->get('/lstproycompra/:idcompra', function($idcompra){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcompra, a.idproyecto, b.nomproyecto, a.idcuentac, c.codigo, c.nombrecta, a.monto, a.idunidad, d.nombre AS unidad ";
    $query.= "FROM compraproyecto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "LEFT JOIN unidad d ON d.id = a.idunidad ";
    $query.= "WHERE a.idcompra = $idcompra ";
    $query.= "ORDER BY b.nomproyecto, d.nombre, c.nombrecta";
    print $db->doSelectASJson($query);
});

$app->get('/getproycompra/:idproycompra', function($idproycompra){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcompra, a.idproyecto, b.nomproyecto, a.idcuentac, c.codigo, c.nombrecta, a.monto, a.idunidad, d.nombre AS unidad ";
    $query.= "FROM compraproyecto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "LEFT JOIN unidad d ON d.id = a.idunidad ";
    $query.= "WHERE a.id = $idproycompra";
    print $db->doSelectASJson($query);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idunidad)){ $d->idunidad = 0; }

    $query = "INSERT INTO compraproyecto(idcompra, idproyecto, idcuentac, monto, idunidad) VALUES(";
    $query.= "$d->idcompra, $d->idproyecto, $d->idcuentac, $d->monto, $d->idunidad";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idunidad)){ $d->idunidad = 0; }

    $query = "UPDATE compraproyecto SET ";
    $query.= "idproyecto = $d->idproyecto, idcuentac = $d->idcuentac, monto = $d->monto, idunidad = $d->idunidad ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM compraproyecto WHERE id = $d->id";
    $db->doQuery($query);
});

$app->get('/fillproycomp', function(){
    $db = new dbcpm();

    $query = "SELECT a.id, a.idproyecto FROM compra a WHERE a.idproyecto > 0";
    $compras = $db->getQuery($query);
    $cntCompras = count($compras);
    for($i = 0; $i < $cntCompras; $i++){
        $compra = $compras[$i];
        $query = "SELECT a.idcuenta, b.nombrecta, b.codigo, a.debe, a.haber ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.origen = 2 AND a.idorigen = $compra->id AND b.codigo LIKE '5%'";
        $gastos = $db->getQuery($query);
        $cntGastos = count($gastos);
        for($j = 0; $j < $cntGastos; $j++){
            $gasto = $gastos[$j];
            $monto = $gasto->debe;
            if((float)$monto == 0){
                $monto = $gasto->haber;
            }

            $query = "INSERT INTO compraproyecto(idcompra, idproyecto, idcuentac, monto) VALUES(";
            $query.= "$compra->id, $compra->idproyecto, $gasto->idcuenta, $monto";
            $query.= ")";
            $db->doQuery($query);
        }
    }
    print json_encode(['mensaje' => 'Proceso terminado...']);
});

$app->get('/comprobante/:id', function($id) use ($app) {
    require_once 'ayuda.php';

    $app->response->headers->set('Content-Type', 'text/html');
    $app->render("compra/comprobante.php", getCompraDetalle($id));
});

$app->get('/selots/:idproveedor/:idempresa', function($idproveedor, $idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idproyecto, a.idmoneda, a.notas
            FROM detpresupuesto a 
            INNER JOIN presupuesto b ON b.id = a.idpresupuesto 
            WHERE a.idestatuspresupuesto = 3 AND a.idproveedor = $idproveedor AND b.idempresa = $idempresa ";
    print $db->doSelectASJson($query);
});

$app->get('/montoots/:idot', function($idot){
    $db = new dbcpm();
    $query = "SELECT ROUND(IFNULL(IF(a.id = e.iddetpresupuesto, 
    IF(c.eslocal = 1 AND d.eslocal = 1, (((e.monto + a.monto) * 0.10) + a.monto + e.monto) - SUM(b.totfact), 
    IF(c.eslocal = 1 AND d.eslocal = 2, (((e.monto + a.monto) * 0.10) + a.monto + e.monto) - SUM(b.totfact) * b.tipocambio, 
    IF(c.eslocal = 2 AND d.eslocal = 1, ((((e.monto + a.monto) * a.tipocambio) * 0.10) + a.monto * a.tipocambio) - SUM(b.totfact), 
    IF(c.eslocal = 2 AND d.eslocal = 2, ((((e.monto + a.monto) * a.tipocambio) * 0.10) + a.monto * a.tipocambio) - SUM(b.totfact) * b.tipocambio, 0)))), 
    IF(c.eslocal = 1 AND d.eslocal = 1, ((a.monto * 0.10) + a.monto) - SUM(b.totfact), 
    IF(c.eslocal = 1 AND d.eslocal = 2, ((a.monto * 0.10) + a.monto) - SUM(b.totfact) * b.tipocambio, 
    IF(c.eslocal = 2 AND d.eslocal = 1, (((a.monto * a.tipocambio) * 0.10) + a.monto * a.tipocambio) - SUM(b.totfact), 
    IF(c.eslocal = 2 AND d.eslocal = 2, (((a.monto * a.tipocambio) * 0.10) + a.monto * a.tipocambio) - SUM(b.totfact) * b.tipocambio, 0))))), (a.monto * 0.10) + a.monto), 2) AS monto
    FROM detpresupuesto a 
    INNER JOIN compra b ON a.id = b.ordentrabajo
    INNER JOIN moneda c ON c.id = a.idmoneda
    INNER JOIN moneda d ON d.id = a.idmoneda
    LEFT JOIN ampliapresupuesto e ON a.id = e.iddetpresupuesto
    WHERE a.id = $idot ";  

    $monto = $db->getOneField($query);
    
    print json_encode(['monto' => $monto ? $monto : 0.00 ]);
});

$app->get('/selcheques/:idot', function($idot){
    $db = new dbcpm();

    $query = "SELECT a.id, b.idmoneda, a.numero, FORMAT(IF(a.isr > 0.00, a.monto + a.isr, a.monto), 2) AS monto, ROUND(a.tipocambio, 5) AS tipocambio, a.concepto, a.tipotrans AS tipo, b.nombre AS banco, c.simbolo AS moneda, a.isr
            FROM tranban a
            INNER JOIN banco b ON b.id = a.idbanco 
            INNER JOIN moneda c ON  c.id = b.idmoneda
            WHERE a.iddetpresup = $idot AND a.anticipo = 1 AND a.idfact IS NULL AND a.idreembolso IS NULL ";
    print $db->doSelectASJson($query);
});

$app->run();