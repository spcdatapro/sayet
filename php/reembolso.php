<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para reembolsos
$app->get('/lstreembolsos/:idemp', function($idemp){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, a.idtiporeembolso, b.desctiporeembolso AS tipo, a.finicio, a.ffin, a.beneficiario, ";
    $query.= "a.estatus, a.idbeneficiario, a.tblbeneficiario, IF(ISNULL(c.totreembolso), 0.00, c.totreembolso) AS totreembolso, a.fondoasignado, a.idsubtipogasto, a.idcuentaliq, a.ordentrabajo, ";
    $query.= "a.idproyecto ";
    $query.= "FROM reembolso a INNER JOIN tiporeembolso b ON b.id = a.idtiporeembolso ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) c ON a.id = c.idreembolso ";
    $query.= "WHERE a.idempresa = ".$idemp." ";
    $query.= "ORDER BY a.estatus, a.finicio, b.desctiporeembolso";
    print $db->doSelectASJson($query);
});

$app->post('/lstreembolsos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idemp)){ $d->idemp = 4; }
    if(!isset($d->estatus)){ $d->estatus = 1; }
    if(!isset($d->tipo)){ $d->tipo = 0; }
    if(!isset($d->idot)){ $d->idot = 0; }

    $query = "SELECT a.id, a.idempresa, a.idtiporeembolso, b.desctiporeembolso AS tipo, a.finicio, a.ffin, a.beneficiario, ";
    $query.= "a.estatus, a.idbeneficiario, a.tblbeneficiario, IF(ISNULL(c.totreembolso), 0.00, c.totreembolso) AS totreembolso, a.fondoasignado, a.idsubtipogasto, a.idcuentaliq, a.ordentrabajo, ";
    $query.= "a.idproyecto ";
    $query.= "FROM reembolso a INNER JOIN tiporeembolso b ON b.id = a.idtiporeembolso ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) c ON a.id = c.idreembolso ";
    $query.= "WHERE a.idempresa = $d->idemp AND a.finicio >= '$d->fdel' AND a.finicio <= '$d->fal' ";
    $query.= (int)$d->estatus > 0 ? "AND a.estatus = $d->estatus " : '';
    $query.= (int)$d->tipo > 0 ? "AND a.idtiporeembolso = $d->tipo " : '';
    $query.= (int)$d->idot > 0 ? "AND a.ordentrabajo = $d->idot " : '';
    $query.= "ORDER BY a.estatus, a.finicio, b.desctiporeembolso";
    print $db->doSelectASJson($query);
});

$app->get('/getreembolso/:idreembolso(/:idot)', function($idreembolso, $idot = 0){
    $db = new dbcpm();
    $idreembolso = (int)$idreembolso;
    $idot = (int)$idot;
    $query = "SELECT a.id, a.idempresa, a.idtiporeembolso, b.desctiporeembolso AS tipo, a.finicio, a.ffin, a.beneficiario, ";
    $query.= "a.estatus, a.idbeneficiario, a.tblbeneficiario, IF(ISNULL(c.totreembolso), 0.00, c.totreembolso) AS totreembolso, a.fondoasignado, a.idsubtipogasto, a.idcuentaliq, a.ordentrabajo, ";
    $query.= "a.idproyecto, a.pagado ";
    $query.= "FROM reembolso a INNER JOIN tiporeembolso b ON b.id = a.idtiporeembolso ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) c ON a.id = c.idreembolso ";
    $query.= "WHERE ";

    if($idreembolso > 0 && $idot == 0) {
        $query.= "a.id = $idreembolso";
    } else if($idreembolso == 0 && $idot > 0) {
        $query.= "a.ordentrabajo = $idot";
    } else if($idreembolso > 0 && $idot > 0) {
        $query.= "a.id = $idreembolso AND a.ordentrabajo = $idot";
    }

    print $db->doSelectASJson($query);
});

$app->get('/lstbenef', function(){
    $db = new dbcpm();
    $query = "SELECT id, nombre, 'proveedor' FROM proveedor ";
    $query.= "UNION ";
    $query.= "SELECT id, nombre, 'provequipo' FROM provequipo ";
    $query.= "ORDER BY 2";
    print $db->doSelectASJson($query);
});

$app->get('/srchnit/:qstr', function($qstr){
    $db = new dbcpm();
    $query = "SELECT DISTINCT nit, nombre AS proveedor FROM proveedor WHERE nit LIKE '$qstr%' ORDER BY nit";
    print json_encode(['results' => $db->getQuery($query)]);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->ordentrabajo)) { $d->ordentrabajo = 0; }
    if(!isset($d->idproyecto)) { $d->idproyecto = 0; }
    $db = new dbcpm();
    $fftmp = $d->ffinstr == '' ? 'NULL' : "'".$d->ffinstr."'";
    $query = "INSERT INTO reembolso(idempresa, finicio, ffin, beneficiario, idbeneficiario, tblbeneficiario, estatus, idtiporeembolso, fondoasignado, idsubtipogasto, idcuentaliq, ordentrabajo, idproyecto) VALUES(";
    $query.= "$d->idempresa, '$d->finiciostr', ".$fftmp.", '$d->beneficiario', $d->idbeneficiario, ";
    $query.= "'$d->tblbeneficiario', 1, $d->idtiporeembolso, $d->fondoasignado, $d->idsubtipogasto, $d->idcuentaliq, $d->ordentrabajo, $d->idproyecto";
    $query.=")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->ordentrabajo)) { $d->ordentrabajo = 0; }
    if(!isset($d->idproyecto)) { $d->idproyecto = 0; }
    $db = new dbcpm();
    $fftmp = $d->ffinstr == '' ? 'NULL' : "'".$d->ffinstr."'";
    $query = "UPDATE reembolso SET finicio = '$d->finiciostr', ffin = ".$fftmp.", beneficiario = '$d->beneficiario', ";
    $query.= "idbeneficiario = $d->idbeneficiario, tblbeneficiario = '$d->tblbeneficiario', idtiporeembolso = $d->idtiporeembolso, ";
    $query.= "fondoasignado = $d->fondoasignado, idsubtipogasto = $d->idsubtipogasto, idcuentaliq = $d->idcuentaliq, ordentrabajo = $d->ordentrabajo, idproyecto = $d->idproyecto ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE detallecontable ";
    $query.= "FROM detallecontable INNER JOIN compra ON compra.id = detallecontable.idorigen AND detallecontable.origen = ".$d->origen." ";
    $query.= "INNER JOIN reembolso ON reembolso.id = compra.idreembolso ";
    $query.= "WHERE reembolso.id = ".$d->id;
    $db->doQuery($query);
    $db->doQuery("DELETE FROM compra WHERE idreembolso = ".$d->id);
    $db->doQuery("DELETE FROM reembolso WHERE id = ".$d->id);
});

$app->post('/reapertura', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "INSERT INTO auditoria(idusuario, tabla, cambio, fecha, tipo) VALUES(";
    $query.= "$d->userid, 'reembolso', 'Reapertura de CC/REE No. $d->id', NOW(), 'U'";
    $query.=")";
    $db->doQuery($query);

    $query = "DELETE FROM detallecontable WHERE origen = 5 AND idorigen = $d->id";
    $db->doQuery($query);
    $query = "UPDATE reembolso SET ffin = NULL, estatus = 1 WHERE id = $d->id";
    $db->doQuery($query);
});

$app->get('/toprint/:idreem', function($idreem){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, a.idreembolso, a.idtipofactura, d.desctipofact AS tipofactura, a.proveedor, a.nit, a.serie, a.documento, ";
    $query.= "a.fechaingreso, a.mesiva, a.fechafactura, a.idtipocompra, b.desctipocompra AS tipocompra, a.totfact, a.iva, a.idmoneda, c.simbolo, a.tipocambio, ";
    $query.= "a.idproveedor, a.subtotal, a.noafecto, a.conceptomayor, a.retenerisr, a.isr, d.siglas ";
    $query.= "FROM compra a INNER JOIN tipocompra b ON b.id = a.idtipocompra INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
    $query.= "WHERE a.idreembolso = ".$idreem." ";
    $query.= "ORDER BY a.proveedor, a.fechafactura";
    $compras = $db->getQuery($query);
    $cantComp = count($compras);
    for($i = 0; $i < $cantComp; $i++){
        $query = "SELECT a.id, a.origen, a.idorigen, a.idcuenta, CONCAT('(', b.codigo, ') ', b.nombrecta) AS desccuentacont, a.debe, a.haber, a.conceptomayor, a.activada ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.origen = 2 AND a.idorigen = ".$compras[$i]->id." ";
        $query.= "ORDER BY a.debe DESC, b.precedencia DESC";
        $res1 = $db->getQuery($query);

        $query = "SELECT 0 AS id, origen, idorigen, IF(SUM(debe) = SUM(haber), 0, -1) AS idcuenta, 'Total --->' AS desccuentacont, ";
        $query.= "SUM(debe) AS debe, SUM(haber) AS haber, IF(SUM(debe) = SUM(haber), '', '') AS conceptomayor, 1 AS activada ";
        $query.= "FROM detallecontable WHERE origen = 2 AND idorigen = ".$compras[$i]->id." ";
        $query.= "GROUP BY origen, idorigen";
        $res2 = $db->getQuery($query);
        if(count($res1) > 0){ array_push($res1, $res2[0]); }

        $compras[$i]->detcont = $res1;
    }
    print json_encode($compras);
});

//API para detalles de reembolsos
$app->get('/getdet/:idreem', function($idreem){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, a.idreembolso, a.idtipofactura, d.desctipofact AS tipofactura, a.proveedor, a.nit, a.serie, a.documento, ";
    $query.= "a.fechaingreso, a.mesiva, a.fechafactura, a.idtipocompra, b.desctipocompra AS tipocompra, a.totfact, a.iva, a.idmoneda, c.simbolo, a.tipocambio, ";
    $query.= "a.idproveedor, a.subtotal, a.noafecto, a.conceptomayor, a.retenerisr, a.isr, a.idp, a.galones, a.idtipocombustible, a.revisada, a.idproyecto, a.idsubtipogasto, a.idunidad ";
    $query.= "FROM compra a INNER JOIN tipocompra b ON b.id = a.idtipocompra INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
    $query.= "WHERE a.idreembolso = ".$idreem." ";
    $query.= "ORDER BY a.revisada, a.id";

    $compras = $db->getQuery($query);
    for($i = 0; $i < count($compras); $i++){ $compras[$i]->correlativo = $i + 1; }

    //print $db->doSelectASJson($query);
    print json_encode($compras);
});

$app->get('/getcomp/:idcomp', function($idcomp){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, a.idreembolso, a.idtipofactura, d.desctipofact AS tipofactura, a.proveedor, a.nit, a.serie, a.documento, ";
    $query.= "a.fechaingreso, a.mesiva, a.fechafactura, a.idtipocompra, b.desctipocompra AS tipocompra, a.totfact, a.iva, a.idmoneda, c.simbolo, a.tipocambio, ";
    $query.= "a.idproveedor, a.subtotal, a.noafecto, a.conceptomayor, a.retenerisr, a.isr, a.idp, a.galones, a.idtipocombustible, a.revisada, a.idproyecto, a.idsubtipogasto, a.idunidad, a.retiva ";
    $query.= "FROM compra a INNER JOIN tipocompra b ON b.id = a.idtipocompra INNER JOIN moneda c ON c.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
    $query.= "WHERE a.id = ".$idcomp;
    print $db->doSelectASJson($query);
});

$app->post('/existeprov', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->nit = trim($d->nit);
    $d->nombre = trim($d->nombre);

    $query = "SELECT COUNT(id) FROM proveedor WHERE TRIM(nit) = '$d->nit'";
    $cont = (int)$db->getOneField($query);
    if($cont <= 0){
        $query = "INSERT INTO proveedor(nit, nombre) VALUES('$d->nit', '$d->nombre')";
        $db->doQuery($query);
    }
});

function insertaDetalleContable($d, $db, $lastid){
    if((int)$d->ctagastoprov > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada) VALUES(";
        $query.= "2, $lastid, $d->ctagastoprov, ".round((float)$d->subtotal, 2).", 0.00, '$d->conceptomayor', 0)";
        $db->doQuery($query);
    }

    if((float)$d->iva > 0){
        $ctaivaporpagar = (int)$db->getOneField("SELECT idcuentac FROM tipocompra WHERE id = ".$d->idtipocompra);
        if($ctaivaporpagar == 0){$ctaivaporpagar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 2");}
        if($ctaivaporpagar > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada) VALUES(";
            $query.= "2, ".$lastid.", ".$ctaivaporpagar.", ".round((float)$d->iva, 2).", 0.00, '".$d->conceptomayor."', 0)";
            $db->doQuery($query);
        }
    }

    if((float)$d->idp > 0){
        $ctaidp = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 9");
        if($ctaidp > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada) VALUES(";
            $query.= "2, ".$lastid.", ".$ctaidp.", ".round((float)$d->idp, 2).", 0.00, '".$d->conceptomayor."', 0)";
            $db->doQuery($query);
        }
    }

    if($d->isr > 0){
        $ctaisrretenido = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 8");
        if($ctaisrretenido > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada) VALUES(";
            $query.= "2, ".$lastid.", ".$ctaisrretenido.", 0.00, ".round(($d->isr * (float)$d->tipocambio), 2).", '".$d->conceptomayor."', 0)";
            $db->doQuery($query);
        }
    }

    $ctaivaretener = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = $d->idempresa AND idtipoconfig = 28");
    if($ctaivaretener > 0 && $d->retIva > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= "2, ".$lastid.", ".$ctaivaretener.", 0.00, ".round(((float)$d->retIva * (float)$d->tipocambio), 2).", '".$d->conceptomayor."')";
        $db->doQuery($query);
    }

    $ctaliq = (int)$db->getOneField("SELECT idcuentaliq FROM reembolso WHERE id = $d->idreembolso");
    if($ctaliq == 0){
        $ctaliq = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 5");
    }    
    if($ctaliq > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada) VALUES(";
        $query.= "2, ".$lastid.", ".$ctaliq.", 0.00, ".round((($d->totfact - $d->isr - $d->retIva) * (float)$d->tipocambio), 2).", '".$d->conceptomayor."', 0)";
        $db->doQuery($query);
    }

    //Agregado para la tasa municipal EEGSA. Solo va a funcionar con el nit 32644-5
    if(trim($d->nit) == '32644-5' && (float)$d->noafecto != 0){
        $ctaeegsa = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 12");
        if($ctaeegsa > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada) VALUES(";
            $query.= "2, ".$lastid.", ".$ctaeegsa.", ".round(((float)$d->noafecto * (float)$d->tipocambio), 2).", 0.00, '".$d->conceptomayor."', 0)";
            $db->doQuery($query);
        }
    }

    $url = 'http://localhost/sayet/php/fixdescuadrecompra.php/fix';
    $dataa = ['idfactura' => $lastid];
    $db->CallJSReportAPI('POST', $url, json_encode($dataa));
}

$app->post('/calcisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    print json_encode(['isr' => $db->calculaISR((float)$d->subtotal, 1.00)]);
});

function updateIdProveedor($db, $idcompra) {
    $query = "UPDATE compra a INNER JOIN proveedor b ON b.nit = a.nit SET a.idproveedor = b.id WHERE a.id = $idcompra AND TRIM(a.nit) <> 'CF'";
    $db->doQuery($query);
}

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->retIva = 0.00;

    //$calcisr = (int)$d->retenerisr === 1;
    //$d->isr = !$calcisr ? 0.00 : $db->calculaISR((float)$d->subtotal, 1.00);

    // ver si empresa es retenedora
    $empresaRet = (int)$db->getOneField("SELECT retenedora FROM empresa WHERE id = $d->idempresa") === 1;

    // ver si proveedor es peque cont.
    $esPeque = (int)$db->getOneField("SELECT pequeniocont FROM proveedor WHERE id = $d->idproveedor") === 1;

    // ver si el proveedor esta marcado como retenedor
    $esRet = (int)$db->getOneField("SELECT retensioniva FROM proveedor WHERE id = $d->idproveedor ") === 1;

    $esLocalMonedaFact = (int)$db->getOneField("SELECT eslocal FROM moneda WHERE id = $d->idmoneda") === 1;

    // calculo de retencion de iva
    if((int)$d->idtipofactura !== 5) {
        // si la empresa es retenedora y el proveedor no es retenedor retener iva
        if (($empresaRet && !$esRet) && ($d->totfact - $d->noafecto) >= 2500) {
            // si es pequeno enviar 5%
            $d->retIva = $esPeque ? $db->retIVA((float)$d->totfact, 0.05, $d->tipocambio, $esLocalMonedaFact) :
            // si no 15%
            $db->retIVA((float)$d->iva, 0.15, $d->tipocambio, $esLocalMonedaFact);
        }
    }

    if(!isset($d->idunidad)){ $d->idunidad = 0; }

    $query = "INSERT INTO compra(";
    $query.= "idempresa, idreembolso, idtipofactura, idproveedor, proveedor, ";
    $query.= "nit, serie, documento, fechaingreso, mesiva, ";
    $query.= "fechafactura, idtipocompra, totfact, noafecto, subtotal, iva, ";
    $query.= "idmoneda, tipocambio, conceptomayor, retenerisr, isr, idp, galones, idtipocombustible, idproyecto, idsubtipogasto, idunidad, ordentrabajo, retiva";
    $query.= ") VALUES(";
    $query.= $d->idempresa.", ".$d->idreembolso.", ".$d->idtipofactura.", ".$d->idproveedor.", '".$d->proveedor."', ";
    $query.= "'".$d->nit."', '".$d->serie."', ".$d->documento.", '".$d->fechaingresostr."', ".$d->mesiva.", ";
    $query.= "'".$d->fechafacturastr."', ".$d->idtipocompra.", ".$d->totfact.", ".$d->noafecto.", ".$d->subtotal.", ".$d->iva.", ";
    $query.= $d->idmoneda.", ".$d->tipocambio.", '".$d->conceptomayor."', ".$d->retenerisr.", ".$d->isr.", $d->idp, $d->galones, $d->idtipocombustible, $d->idproyecto, $d->idsubtipogasto, $d->idunidad, $d->ordentrabajo, $d->retIva";
    $query.= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();

    insertaDetalleContable($d, $db, $lastid);
    updateIdProveedor($db, $lastid);

    print json_encode(['lastid' => $lastid]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if(!isset($d->idunidad)){ $d->idunidad = 0; }

    $d->retIva = 0.00;

    // ver si empresa es retenedora
    $empresaRet = (int)$db->getOneField("SELECT retenedora FROM empresa WHERE id = $d->idempresa") === 1;
    // ver si proveedor es peque cont.
    $esPeque = (int)$db->getOneField("SELECT pequeniocont FROM proveedor WHERE id = $d->idproveedor") === 1;
    // ver si el proveedor esta marcado como retenedor
    $esRet = (int)$db->getOneField("SELECT retensioniva FROM proveedor WHERE id = $d->idproveedor ") === 1;
    $esLocalMonedaFact = (int)$db->getOneField("SELECT eslocal FROM moneda WHERE id = $d->idmoneda") === 1;

    if((int)$d->idtipofactura !== 5) {
        $calcisr = (int)$db->getOneField("SELECT retensionisr FROM proveedor WHERE id = ".$d->idproveedor) === 1;

        // si la empresa es retenedora y el proveedor no es retenedor retener iva
        if (($empresaRet && !$esRet) && $d->totfact >= 2500) {
            // si es pequeno enviar 5%
            $d->retIva = $esPeque ? $db->retIVA((float)$d->totfact, 0.05, $d->tipocambio, $esLocalMonedaFact) :
            // si no 15%
            $db->retIVA((float)$d->iva, 0.15, $d->tipocambio, $esLocalMonedaFact);
        }
    }

    $query = "UPDATE compra SET ";
    $query.= "idtipofactura = ".$d->idtipofactura.", idproveedor = ".$d->idproveedor.", proveedor = '".$d->proveedor."', ";
    $query.= "nit = '".$d->nit."', serie = '".$d->serie."', documento = ".$d->documento.", fechaingreso = '".$d->fechaingresostr."', mesiva = ".$d->mesiva.", ";
    $query.= "fechafactura = '".$d->fechafacturastr."', idtipocompra = ".$d->idtipocompra.", ";
    $query.= "totfact = ".$d->totfact.", subtotal = ".$d->subtotal.", noafecto = ".$d->noafecto.", iva = ".$d->iva.", ";
    $query.= "idmoneda = ".$d->idmoneda.", tipocambio = ".$d->tipocambio.", conceptomayor = '".$d->conceptomayor."', idp = $d->idp, galones = $d->galones, ";
    $query.= "idtipocombustible = $d->idtipocombustible, idproyecto = $d->idproyecto, retenerisr = $d->retenerisr, isr = $d->isr, idsubtipogasto = $d->idsubtipogasto, idunidad = $d->idunidad, ";
    $query.= "ordentrabajo = $d->ordentrabajo, retiva = $d->retIva ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);

    //$query = "DELETE FROM detallecontable WHERE origen = 2 AND idorigen = $d->id AND activada = 0";
    $query = "DELETE FROM detallecontable WHERE origen = 2 AND idorigen = $d->id";
    $db->doQuery($query);
    insertaDetalleContable($d, $db, $d->id);
    updateIdProveedor($db, $d->id);

    print json_encode(['lastid' => $d->id]);
});

$app->get('/setrevisada/:idcompra', function($idcompra){
    $db = new dbcpm();
    $db->doQuery("UPDATE compra SET revisada = 1 WHERE id = $idcompra");
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detallecontable WHERE origen = ".$d->origen." AND idorigen = ".$d->id);
    $db->doQuery("DELETE FROM compra WHERE id = ".$d->id);
});

$app->post('/cierre', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    //Generación del detalle contable del reembolso Origen = 5
    $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) ";
    $query.= "SELECT 5 AS origen, a.idreembolso AS idorigen, b.idcuenta, b.debe, b.haber, b.conceptomayor ";
    $query.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 INNER JOIN cuentac d ON d.id = b.idcuenta WHERE a.idreembolso = ".$d->id." ";
    $query.= "ORDER BY b.idorigen, d.precedencia DESC, d.nombrecta";
    $db->doQuery($query);
    $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 5");
    //$haber = 0.00;
    if($ctaporliquidar > 0){
        $query = "SELECT SUM(b.debe) AS debe FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->id;
        $haber = (float)$db->getOneField($query);
        $query = "SELECT SUM(b.haber) AS haber FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->id;
        $restar = (float)$db->getOneField($query);
        $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= "5, ".$d->id.", ".$ctaporliquidar.", 0.00, ".round(($haber - $restar),2).", 'Reembolso No. ".$d->id."'";
        $query.= ")";
        $db->doQuery($query);
    }

    $query = "UPDATE reembolso SET ffin = '".$d->ffinstr."', estatus = 2, idtranban = 0 WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/gentranban', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT SUM(totfact) FROM compra WHERE idreembolso = $d->id";
    $total = (float)$db->getOneField($query);

    $query = "SELECT SUM(isr) FROM compra WHERE idreembolso = $d->id";
    $isr = (float)$db->getOneField($query);

    $query = "SELECT SUM(retiva) FROM compra WHERE idreembolso = $d->id";
    $retiva = (float)$db->getOneField($query);

    $retiva = $retiva > 0 ? $retiva : 0.00;

    $monto = $total - $isr - $retiva;

    if ($d->tipoMonto == 1) {
        $haber = $monto - getTotPagado($d->id, $db);
    } else {
        $haber = $monto;
    }
    //Generación del cheque/nota de débito para pagar el reembolso
    $getCorrela = $d->numero;
    $query = "INSERT INTO tranban(idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, origenbene, idbeneficiario, idreembolso, iddetpresup) ";
    $query.= "VALUES(".$d->objBanco->id.", '".$d->tipotrans."', '".$d->fechatrans."', ".$monto.", '".$d->beneficiario."', ";
    $query.= "'Pago de reembolso No. ".$d->id."', ".$getCorrela.", 2, $d->idbeneficiario, ".$d->id.", $d->ordentrabajo)";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    if($d->tipotrans == 'C') { $db->doQuery("UPDATE banco SET correlativo = correlativo + 1 WHERE id = " . $d->objBanco->id); }

    //Inserto el reembolso como documento de soporte :-)
    $query = "INSERT INTO doctotranban(idtranban, idtipodoc, fechadoc, serie, documento, monto, iddocto) ";
    $query.= "SELECT ".$lastid.", 2, ffin, 'REE', id, ".$haber.", id FROM reembolso WHERE id = ".$d->id;
    $db->doQuery($query);

    $origen = 1;
    //Inserto el detalle contable de la transacción bancaria
    $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$d->idempresa." AND idtipoconfig = 5");
    $ctabanco = (int)$db->getOneField("SELECT idcuentac FROM banco WHERE id = ".$d->objBanco->id);

    if($ctaporliquidar > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$lastid.", ".$ctaporliquidar.", ".($haber * (float)$d->objBanco->tipocambio).", 0.00, 'Pago de reembolso No. ".$d->id."')";
        $db->doQuery($query);
    }

    if($ctabanco > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$lastid.", ".$ctabanco.", 0.00, ".($haber * (float)$d->objBanco->tipocambio).", 'Pago de reembolso No. ".$d->id."')";
        $db->doQuery($query);
    }

    $query = "INSERT INTO dettranreem(idtranban, idreembolso, monto) VALUES($lastid, $d->id, $haber)";
    $db->doQuery($query);

    if ($monto - getTotPagado($d->id, $db) <= 0.00) {
        $query = "UPDATE reembolso SET pagado = 1 WHERE id = $d->id";
        $db->doQuery($query);
    }

});

function getTotPagado ($idreembolso, $db) {
    $query = "SELECT IFNULL(SUM(monto), 0.00) AS monto FROM dettranreem WHERE idreembolso = $idreembolso";
    $pagado = (float)$db->getOneField($query);
    return $pagado;
}

$app->get('/tranban/:idreembolso', function($idreembolso){
    $db = new dbcpm();
    $query = "SELECT 
                b.id,
                CONCAT('(', b.tipotrans, ') ', d.descripcion) AS tipodoc,
                b.numero,
                CONCAT(c.nombre, ' (', e.simbolo, ')') AS banco,
                a.monto,
                1 AS origen
            FROM
                dettranreem a
                    INNER JOIN
                tranban b ON a.idtranban = b.id
                    INNER JOIN
                banco c ON b.idbanco = c.id
                    INNER JOIN
                tipomovtranban d ON d.abreviatura = b.tipotrans
                    INNER JOIN
                moneda e ON c.idmoneda = e.id
            WHERE
                a.idreembolso = $idreembolso";
    print $db->doSelectASJson($query);
});

//API reportes
$app->post('/rptpendliquida', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $pendientes = new stdClass();
    $pendientes->generales = $db->getQuery("SELECT nomempresa AS empresa, abreviatura AS abreviaempresa, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS fdel, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal FROM empresa WHERE id = $d->idempresa")[0];

    $query = "SELECT c.nomempresa, c.abreviatura, LPAD(a.id, 5, '0') AS noreembolso, DATE_FORMAT(a.finicio, '%d/%m/%Y') AS finicio, b.desctiporeembolso AS tipo, a.beneficiario, ";
    $query.= "FORMAT(IF(ISNULL(d.totreembolso), 0.00, d.totreembolso), 2) AS totreembolso ";
    $query.= "FROM reembolso a INNER JOIN tiporeembolso b ON b.id = a.idtiporeembolso INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) d ON a.id = d.idreembolso ";
    $query.= "WHERE a.idtranban = 0 AND a.idempresa = $d->idempresa AND d.totreembolso > 0 ";
    $query.= $d->fdelstr != '' ? "AND a.finicio >= '$d->fdelstr' " : "";
    $query.= $d->falstr != '' ? "AND a.finicio <= '$d->falstr' " : "";
    $query.= "ORDER BY c.nomempresa, a.finicio, a.beneficiario";
    $pendientes->pendientes = $db->getQuery($query);

    $query = "SELECT COUNT(a.id) AS cantidad, IF(ISNULL(FORMAT(SUM(IF(ISNULL(d.totreembolso), 0.00, d.totreembolso)), 2)), 0.00, FORMAT(SUM(IF(ISNULL(d.totreembolso), 0.00, d.totreembolso)), 2)) AS sumtotreem ";
    $query.= "FROM reembolso a INNER JOIN tiporeembolso b ON b.id = a.idtiporeembolso INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) d ON a.id = d.idreembolso ";
    $query.= "WHERE a.idtranban = 0 AND a.idempresa = $d->idempresa AND d.totreembolso > 0 ";
    $query.= $d->fdelstr != '' ? "AND a.finicio >= '$d->fdelstr' " : "";
    $query.= $d->falstr != '' ? "AND a.finicio <= '$d->falstr' " : "";
    $pendientes->resumen = $db->getQuery($query)[0];

    print json_encode($pendientes);
});

$app->run();