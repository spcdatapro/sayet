<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para compras
$app->get('/lstcomras/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, a.mesiva, ";
    $query.= "a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, a.ordentrabajo, ";
    $query.= "a.totfact, a.noafecto, a.subtotal, a.iva, IF(ISNULL(e.cantpagos), 0, e.cantpagos) AS cantpagos, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto ";
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
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, a.mesiva, ";
    $query.= "a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, a.ordentrabajo, ";
    $query.= "a.totfact, a.noafecto, a.subtotal, a.iva, IF(ISNULL(e.cantpagos), 0, e.cantpagos) AS cantpagos, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN ( SELECT a.idcompra, COUNT(a.idtranban) AS cantpagos	";
    $query.= "FROM detpagocompra a INNER JOIN tranban b ON b.id = a.idtranban INNER JOIN banco c ON c.id = b.idbanco ";
    $query.= "INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans INNER JOIN moneda e ON e.id = c.idmoneda ";
    $query.= "GROUP BY a.idcompra) e ON a.id = e.idcompra LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." AND a.idreembolso = 0 ";
    $query.= $d->fdelstr != '' ? "AND a.fechafactura >= '$d->fdelstr' " : "" ;
    $query.= $d->falstr != '' ? "AND a.fechafactura <= '$d->falstr' " : "" ;
    $query.= "ORDER BY a.fechapago, b.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getcompra/:idcompra', function($idcompra){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, ";
    $query.= "a.mesiva, a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, ";
    $query.= "a.ordentrabajo, a.totfact, a.noafecto, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "WHERE a.id = ".$idcompra;
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
    a.idp, a.idmoneda, h.simbolo AS moneda, a.tipocambio, i.tranban, a.idproyecto ";
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
    //Inicia inserción automática de detalle contable de la factura
    $ctagastoprov = (int)$d->ctagastoprov;
    $ctaivaporpagar = (int)$db->getOneField("SELECT idcuentac FROM tipocompra WHERE id = ".$d->idtipocompra);
    if($ctaivaporpagar == 0){ $ctaivaporpagar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 2"); }

    $ctaproveedores = (int)$db->getOneField("SELECT a.idcxp FROM detcontprov a INNER JOIN cuentac b ON b.id = a.idcxp WHERE a.idproveedor = $d->idproveedor AND b.idempresa = $d->idempresa LIMIT 1");
    if(!($ctaproveedores > 0)) {
        $ctaproveedores = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = " . $d->idempresa . " AND idtipoconfig = 3");
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
};

function generaDetalleProyecto($db, $lastid){
    $query = "SELECT idproyecto FROM compra WHERE id = $lastid";
    $idproyecto = (int)$db->getOneField($query);
    $query = "SELECT a.idcuenta, a.debe FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 2 AND a.idorigen = $lastid AND (b.codigo LIKE '5%' OR b.codigo LIKE '6%')";
    $gastos = $db->getQuery($query);
    $cntGastos = count($gastos);
    if($idproyecto > 0 && $cntGastos > 0){
        for($i = 0; $i < $cntGastos; $i++){
            $gasto = $gastos[$i];
            $query = "INSERT INTO compraproyecto(idcompra, idproyecto, idcuentac, monto) VALUES(";
            $query.= "$lastid, $idproyecto, $gasto->idcuenta, $gasto->debe";
            $query.= ")";
            $db->doQuery($query);
        }
    }
};

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $calcisr = (int)$db->getOneField("SELECT retensionisr FROM proveedor WHERE id = ".$d->idproveedor) === 1;
    $d->isr = !$calcisr ? 0.00 : $db->calculaISR((float)$d->subtotal, (float)$d->tipocambio);

    $query = "INSERT INTO compra(idempresa, idproveedor, serie, documento, fechaingreso, mesiva, fechafactura, idtipocompra, ";
    $query.= "conceptomayor, creditofiscal, extraordinario, fechapago, ordentrabajo, totfact, noafecto, subtotal, iva, idmoneda, tipocambio, ";
    $query.= "idtipofactura, isr, idtipocombustible, galones, idp, idproyecto) ";
    $query.= "VALUES(".$d->idempresa.", ".$d->idproveedor.", '".$d->serie."', ".$d->documento.", '".$d->fechaingresostr."', ".$d->mesiva.", '".$d->fechafacturastr."', ";
    $query.= $d->idtipocompra.", '".$d->conceptomayor."', ".$d->creditofiscal.", ".$d->extraordinario.", '".$d->fechapagostr."', ".$d->ordentrabajo.", ";
    $query.= $d->totfact.", ".$d->noafecto.", ".$d->subtotal.", ".$d->iva.", ".$d->idmoneda.", ".$d->tipocambio.", ".$d->idtipofactura.", ".$d->isr.", ";
    $query.= $d->idtipocombustible.", ".$d->galones.", ".$d->idp.", $d->idproyecto";
    $query.= ")";
    $db->doQuery($query);

    $lastid = $db->getLastId();
    if((int)$lastid > 0){
        //Inicia inserción automática de detalle contable de la factura
        insertaDetalleContable($d, $lastid);
        //Fin de inserción automática de detalle contable de la factura
        generaDetalleProyecto($db, $lastid);
    }

    print json_encode(['lastid' => $lastid]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $calcisr = (int)$db->getOneField("SELECT retensionisr FROM proveedor WHERE id = ".$d->idproveedor) === 1;
    $d->isr = !$calcisr ? 0.00 : $db->calculaISR((float)$d->subtotal, (float)$d->tipocambio);

    $query = "UPDATE compra SET ";
    $query.= "idproveedor = ".$d->idproveedor.", serie = '".$d->serie."', documento = ".$d->documento.", fechaingreso = '".$d->fechaingresostr."', ";
    $query.= "mesiva = ".$d->mesiva.", fechafactura = '".$d->fechafacturastr."', idtipocompra = ".$d->idtipocompra.", conceptomayor =  '".$d->conceptomayor."', ";
    $query.= "creditofiscal = ".$d->creditofiscal.", extraordinario = ".$d->extraordinario.", fechapago = '".$d->fechapagostr."', ordentrabajo = ".$d->ordentrabajo.", ";
    $query.= "totfact = ".$d->totfact.", noafecto = ".$d->noafecto.", subtotal = ".$d->subtotal.", iva = ".$d->iva.", ";
    $query.= "idmoneda = ".$d->idmoneda.", tipocambio = ".$d->tipocambio.", idtipofactura = ".$d->idtipofactura.", isr = ".$d->isr.", ";
    $query.= "idtipocombustible = ".$d->idtipocombustible.", galones = ".$d->galones.", idp = ".$d->idp.", idproyecto = $d->idproyecto ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);

    $origen = 2;
    $idorigen = (int)$d->id;
    $query = "DELETE FROM detallecontable WHERE origen = ".$origen." AND idorigen = ".$idorigen;
    $db->doQuery($query);

    //Inicia inserción automática de detalle contable de la factura
    insertaDetalleContable($d, $idorigen);
    //Fin de inserción automática de detalle contable de la factura
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

$app->post('/rptcompra', function(){
	
	$d = json_decode(file_get_contents('php://input'));
	
	$acompra = array();
	
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, d.nomempresa, a.idproveedor, b.nombre AS nomproveedor, a.serie, a.documento, a.fechaingreso, ";
    $query.= "a.mesiva, a.fechafactura, a.idtipocompra, c.desctipocompra, a.conceptomayor, a.creditofiscal, a.extraordinario, a.fechapago, ";
    $query.= "a.ordentrabajo, a.totfact, a.noafecto, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, ";
    $query.= "a.idtipofactura, g.desctipofact AS tipofactura, a.isr, a.idtipocombustible, h.descripcion AS tipocombustible, a.galones, a.idp, ";
    $query.= "a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, g.siglas, a.idproyecto ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipocompra c ON c.id = a.idtipocompra ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN tipocombustible h ON h.id = a.idtipocombustible ";
    $query.= "WHERE a.id = ".$d->idcompra;
	
	$infocompra = $db->getQuery($query);
	
	$query = "SELECT a.id, a.origen, a.idorigen, a.idcuenta, CONCAT('(', b.codigo, ') ', b.nombrecta) AS desccuentacont, ";
    $query.= "a.debe, a.haber, a.conceptomayor ";
    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
    $query.= "WHERE a.origen = 2 AND a.idorigen = ".$d->idcompra." ";
    $query.= "ORDER BY a.debe DESC, a.haber, b.codigo";
    $res1 = $db->getQuery($query);

    /*$query = "SELECT 0 AS id, origen, idorigen, IF(SUM(debe) = SUM(haber), 0, -1) AS idcuenta, 'Total de partida' AS desccuentacont, ";
    $query.= "SUM(debe) AS debe, SUM(haber) AS haber, IF(SUM(debe) = SUM(haber), 'Partida cuadrada', 'Partida descuadrada') AS conceptomayor ";
    $query.= "FROM detallecontable ";
    $query.= "WHERE origen = 2 AND idorigen = ".$d->idcompra." ";
    $query.= "GROUP BY origen, idorigen";
    $res2 = $db->getQuery($query);*/

    //if(count($res1) > 0){ array_push($res1, $res2[0]); }
	
	$acompra = [ "compra" => $infocompra, "detalle" => $res1];
	
    print json_encode($acompra);
});

//API para detalle de proyectos que son afectados en las compras
$app->get('/lstproycompra/:idcompra', function($idcompra){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcompra, a.idproyecto, b.nomproyecto, a.idcuentac, c.codigo, c.nombrecta, a.monto ";
    $query.= "FROM compraproyecto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "WHERE a.idcompra = $idcompra ";
    $query.= "ORDER BY b.nomproyecto, c.nombrecta";
    print $db->doSelectASJson($query);
});

$app->get('/getproycompra/:idproycompra', function($idproycompra){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcompra, a.idproyecto, b.nomproyecto, a.idcuentac, c.codigo, c.nombrecta, a.monto ";
    $query.= "FROM compraproyecto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "WHERE a.id = $idproycompra";
    print $db->doSelectASJson($query);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO compraproyecto(idcompra, idproyecto, idcuentac, monto) VALUES(";
    $query.= "$d->idcompra, $d->idproyecto, $d->idcuentac, $d->monto";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE compraproyecto SET ";
    $query.= "idproyecto = $d->idproyecto, idcuentac = $d->idcuentac, monto = $d->monto ";
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

$app->run();