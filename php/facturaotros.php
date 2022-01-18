<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$view = $app->view();
$view->setTemplatesDirectory('../php');

$app->notFound(function () use ($app) {
    $app->response()->setStatus(200);
    $app->render('error.php', array(), 200);
});

$app->get('/srchcli/:idempresa/:qstra+', function($idempresa, $qstra){
    $db = new dbcpm();
    $qstr = $qstra[0];
    $query = "SELECT DISTINCT idcliente, facturara, nit, retisr, retiva, direccion, ";
    $query.= "CONCAT('<small>',nit, '<br/>', IFNULL(direccion, ''),'</small>') AS infocliente, porretiva, exentoiva, origen FROM (";
    $query.= "SELECT DISTINCT a.idcliente, a.facturara, a.nit, a.retisr, a.retiva, a.direccion, a.porretiva, a.exentoiva, 1 AS origen  ";
    $query.= "FROM detclientefact a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN contrato c ON b.id = c.idcliente ";
    $query.= "WHERE c.idempresa = $idempresa AND a.fal IS NULL AND (a.facturara LIKE '%$qstr%' OR b.nombrecorto LIKE '%$qstr%') ";
    $query.= "UNION ";
    $query.= "SELECT DISTINCT 0 AS idcliente, nombre AS facturara, nit, retenerisr AS retisr, reteneriva AS retiva, direccion, porretiva, IF(importeexento = 0, 0, 1) AS exentoiva, 2 AS origen  ";
    $query.= "FROM factura ";
    $query.= "WHERE fecha >= '2017-09-01' AND idempresa = $idempresa AND nombre LIKE '%$qstr%' AND (idcontrato = 0 OR idcontrato IS NULL)";
    $query.= ") a ";
    $query.= "ORDER BY origen, facturara";
    //print $query;
    print json_encode(['results' => $db->getQuery($query)]);
});

$app->get('/lstfacturas/:idempresa/:cuales', function($idempresa, $cuales){
    $db = new dbcpm();
    $query = "SELECT DISTINCT a.id, a.fecha, a.serie, a.numero, a.idcontrato, a.idcliente, IF(a.nombre IS NULL OR TRIM(a.nombre) = '', b.facturara, a.nombre) AS cliente, ";
    $query.= "IF(a.nit IS NULL OR TRIM(a.nit) = '', b.nit, a.nit) AS nit, IF(a.idcontrato IS NULL, '', UnidadesPorContrato(a.idcontrato)) AS unidad, a.total, d.nomempresa AS empresa, ";
    $query.= "a.iva, a.total, a.noafecto, a.subtotal, a.retisr, a.retiva, a.totdescuento, a.tipocambio, a.reteneriva, a.retenerisr, a.mesafecta, a.anioafecta, a.direccion, d.abreviatura AS abreviaempre, ";
    $query.= "a.idproyecto, e.nomproyecto AS proyecto, a.porretiva, a.exentoiva ";
    $query.= "FROM factura a LEFT JOIN detclientefact b ON b.idcliente = a.idcliente LEFT JOIN contrato c ON c.id = a.idcontrato LEFT JOIN empresa d ON d.id = a.idempresa ";
    $query.= "LEFT JOIN proyecto e ON e.id = a.idproyecto ";
    $query.= "WHERE a.esinsertada = 1 AND b.fal IS NULL AND a.anulada = 0 ";
    $query.= (int)$idempresa > 0 ? "AND a.idempresa = $idempresa " : "";
	$query.= (int)$cuales == 1 ? "AND a.pendiente = 0 " : "";
    $query.= "ORDER BY 2 DESC, 7";
    print $db->doSelectASJson($query);
});

$app->get('/getfactura/:idfactura', function($idfactura){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.nit, a.nombre, a.idcontrato, a.serie, a.numero, a.fechaingreso, a.fecha, a.idtipoventa, a.conceptomayor, a.idempresa, a.idtipofactura, ";
    $query.= "a.iva, a.total, a.noafecto, a.subtotal, a.retisr, a.retiva, a.totdescuento, a.tipocambio, a.reteneriva, a.retenerisr, a.mesafecta, a.anioafecta, a.direccion, a.idproyecto, a.porretiva, a.exentoiva ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.id = $idfactura";
    print $db->doSelectASJson($query);
});

function actualizaContratoClienteCF($db, $idfactura)
{
    $query = "SELECT * FROM factura WHERE id = $idfactura AND idcontrato = 0 AND (idcliente = 0 OR idcliente = 207) ";
    $factura = $db->getQuery($query);
    if (count($factura) > 0)
    {
        $fac = $factura[0];
        $query = "SELECT id FROM contrato WHERE inactivo = 0 AND idempresa = $fac->idempresa AND idcliente = 207 AND idproyecto = $fac->idproyecto LIMIT 1";
        $idcontrato = (int)$db->getOneField($query);
        if ($idcontrato === NULL)
        {
            $query = "INSERT INTO contrato(idcliente, idempresa, idproyecto, inactivo, nocontrato, idunidad, idcuentac, idusuariocopia) VALUES(";
            $query.= "207, $fac->idempresa, $fac->idproyecto, 0, 'CF".$fac->idempresa.$fac->idproyecto."', '', '', 0";
            $query.= ")";
            $db->doQuery($query);
            $idcontrato = (int)$db->getLastId();
        }
        $query = "UPDATE factura SET idcontrato = $idcontrato, idcliente = 207 WHERE id = $idfactura";
        $db->doQuery($query);
    }
}

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->porretiva)){ $d->porretiva = 0.00; }
    if(!isset($d->exentoiva)){ $d->exentoiva = 0; }
    $d->nit = trim($d->nit) == '' ? 'NULL' : ("'".(strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", $d->nit)))."'");
    $d->nombre = trim($d->nombre) == '' ? 'NULL' : ("'".trim($d->nombre)."'");
    $d->direccion = trim($d->direccion) == '' ? 'Ciudad' : ("'".trim($d->direccion)."'");

    $query = "SELECT IFNULL(seriefel, 'A') AS seriefel FROM empresa WHERE id = $d->idempresa";
    $datosFel = $db->getQuery($query)[0];
    // $datosFel->correlativofel = (int)$datosFel->correlativofel;
    // $datosFel->correlativofel++;

    $query = "INSERT INTO factura(";
    $query.= "idempresa, idtipofactura, idcontrato, idcliente, nit, nombre, fechaingreso, mesiva, fecha, idtipoventa, idmoneda, tipocambio, esinsertada,";
    $query.= "reteneriva, retenerisr, mesafecta, anioafecta, direccion, idproyecto, porretiva, serieadmin, numeroadmin, exentoiva) VALUES(";
    $query.= "$d->idempresa, $d->idtipofactura, $d->idcontrato, $d->idcliente, $d->nit, $d->nombre, '$d->fechaingresostr', $d->mesiva, '$d->fechastr', $d->idtipoventa, 1, $d->tipocambio, 1,";
    $query.= "$d->reteneriva, $d->retenerisr, $d->mesafecta, $d->anioafecta, $d->direccion, $d->idproyecto, $d->porretiva, '$datosFel->seriefel', NULL, $d->exentoiva";
    $query.= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    // if((int)$lastid > 0) {
    //     $query = "UPDATE empresa SET correlativofel = $datosFel->correlativofel WHERE id = $d->idempresa";
    //     $db->doQuery($query);
    // }

    actualizaContratoClienteCF($db, $lastid);

    print json_encode(['lastid' => $lastid]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->porretiva)){ $d->porretiva = 0.00; }
    if(!isset($d->exentoiva)){ $d->exentoiva = 0; }
    $d->nit = trim($d->nit) == '' ? 'NULL' : ("'".(strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", $d->nit)))."'");
    $d->nombre = trim($d->nombre) == '' ? 'NULL' : ("'".trim($d->nombre)."'");
    $d->direccion = trim($d->direccion) == '' ? 'Ciudad' : ("'".trim($d->direccion)."'");

    $query = "UPDATE factura SET ";
    $query.= "idempresa = $d->idempresa, idtipofactura = $d->idtipofactura, idcontrato = $d->idcontrato, idcliente = $d->idcliente, nit = $d->nit, ";
    $query.= "nombre = $d->nombre, fechaingreso = '$d->fechaingresostr', mesiva = $d->mesiva, fecha = '$d->fechastr', idtipoventa = $d->idtipoventa, tipocambio = $d->tipocambio, ";
    $query.= "reteneriva = $d->reteneriva, retenerisr = $d->retenerisr, mesafecta = $d->mesafecta, anioafecta = $d->anioafecta, direccion = $d->direccion, ";
    $query.= "idproyecto = $d->idproyecto, porretiva = $d->porretiva, exentoiva = $d->exentoiva ";
    $query.= "WHERE id = $d->id";
    //print $query;
    $db->doQuery($query);

    $query = "UPDATE detfact SET mes = $d->mesafecta, anio = $d->anioafecta WHERE idfactura = $d->id";
    $db->doQuery($query);

    $d->idfactura = $d->id;
    updateDatosFacturaFEL($d);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "DELETE FROM detallecontable WHERE origen = 3 AND idorigen = $d->id";
    $db->doQuery($query);
    $query = "DELETE FROM detfact WHERE idfactura = $d->id";
    $db->doQuery($query);
    $query = "DELETE FROM factura WHERE id = $d->id";
    $db->doQuery($query);
});

//Detalle de factura
$app->get('/lstdetfact/:idfactura', function($idfactura){
    $db = new dbcpm();

    $query = "SELECT a.id, a.idfactura, a.cantidad, a.idtiposervicio, a.mes, a.anio, a.descripcion, a.preciounitario, a.preciotot, a.descuento, b.desctiposervventa AS tiposervicio ";
    $query.= "FROM detfact a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio ";
    $query.= "WHERE a.idfactura = $idfactura ";
    $query.= "ORDER BY 11";
    print $db->doSelectASJson($query);
});

$app->get('/getdetfact/:iddetfact', function($iddetfact){
    $db = new dbcpm();

    $query = "SELECT a.id, a.idfactura, a.cantidad, a.idtiposervicio, a.mes, a.anio, a.descripcion, a.preciounitario, a.preciotot, a.descuento ";
    $query.= "FROM detfact a ";
    $query.= "WHERE a.id = $iddetfact";
    print $db->doSelectASJson($query);
});

function recalc($d){
    $db = new dbcpm();

    $r = new stdClass();
    //$d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");
    $r->retisr = (int)$d->retenerisr > 0 ? $db->calculaISR((float)$d->montosiniva) : 0.00;
    $r->retiva = (int)$d->reteneriva > 0 ? $db->calculaRetIVA((float)$d->montosiniva, ((int)$d->idtipocliente == 1 ? true : false), $d->montoconiva, ((int)$d->idtipocliente == 2 ? true : false), $d->iva, $d->porretiva) : 0.00;
    $r->totapagar = (float)$d->montoconiva - ($r->retisr + $r->retiva);

    return $r;
}

function calculaImpuestosYTotal($db, $d, $factura) {
    $noEsExentoIVA = (int)$factura->exentoiva === 0;
    $factura->isrporretener = (int)$factura->retenerisr > 0 ? $db->calculaISR((float)$factura->montosiniva) : 0.00;
    $factura->isrporretenercnv = round($factura->isrporretener / (float)$d->tc, 2);
    $factura->ivaporretener = $noEsExentoIVA ? ((int)$factura->reteneriva > 0 ? $db->calculaRetIVA((float)$factura->montosiniva, ((int)$factura->idtipocliente == 1 ? true : false), (float)$factura->montoconiva, ((int)$factura->idtipocliente == 2 ? true : false), (float)$factura->iva, (float)$factura->porcentajeretiva) : 0.00) : 0.00;
    $factura->ivaporretenercnv = round($factura->ivaporretener / (float)$d->tc, 2);
    $factura->totapagar = round((float)$factura->montoconiva - ($factura->isrporretener + $factura->ivaporretener), 2);
    $factura->totapagarcnv = round($factura->totapagar / (float)$d->tc, 2);
    return $factura;
}

function updateDatosFactura($d){
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $data = new stdClass();
    $data->montoconiva = round((float)$db->getOneField("SELECT SUM(preciotot) FROM detfact WHERE idfactura = $d->idfactura"), 2);
    $data->totdescuento = round((float)$db->getOneField("SELECT SUM(descuento) FROM detfact WHERE idfactura = $d->idfactura"), 2);
    $data->montosiniva = round((float)$data->montoconiva / 1.12, 2);
    $data->idempresa = (int)$db->getOneField("SELECT idempresa FROM factura WHERE id = $d->idfactura");
    $data->idtipocliente = (int)$db->getOneField("SELECT idtipocliente FROM contrato WHERE id = (SELECT idcontrato FROM factura WHERE id = $d->idfactura)");    
    $data->reteneriva = (int)$db->getOneField("SELECT reteneriva FROM factura WHERE id = $d->idfactura");
    $data->retenerisr = (int)$db->getOneField("SELECT retenerisr FROM factura WHERE id = $d->idfactura");
    $data->porretiva = (float)$db->getOneField("SELECT porretiva FROM factura WHERE id = $d->idfactura");
    $data->iva = round($data->montoconiva - $data->montosiniva, 2);
    $data->montocargoiva = $data->montoconiva;
    $tc = (float)$db->getOneField("SELECT tipocambio FROM factura WHERE id = $d->idfactura");
    $data->montocargoflat = round($data->montoconiva / $tc, 2);

    $calculo = recalc($d);

    $query = "SELECT GROUP_CONCAT(DISTINCT TRIM(descripcion) SEPARATOR ', ') FROM detfact WHERE idfactura = $d->idfactura";
    $conceptomayor = $db->getOneField($query);
    $conceptomayor = trim($conceptomayor) != '' ? ("'".trim($conceptomayor)."'") : 'NULL';

    $query = "UPDATE factura SET iva = $data->iva, total = $calculo->totapagar, noafecto = 0.00, subtotal = $data->montoconiva, ";
    $query.= "retisr = $calculo->retisr, retiva = $calculo->retiva, totdescuento = $data->totdescuento, totalletras = '".$n2l->to_word($calculo->totapagar, 'GTQ')."', conceptomayor = $conceptomayor, ";
    $query.= "montocargoiva = $data->montocargoiva, montocargoflat = $data->montocargoflat ";
    $query.= "WHERE id = $d->idfactura";
    $db->doQuery($query);


    $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
    $dataa = ['ids' => $d->idfactura, 'idcontrato' => (int)$db->getOneField("SELECT idcontrato FROM factura WHERE id = $d->idfactura")];
    $db->CallJSReportAPI('POST', $url, json_encode($dataa));
}

function updateDatosFacturaFEL($d){
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $data = new stdClass();
    $data->montoconiva = round((float)$db->getOneField("SELECT SUM(importetotal) FROM detfact WHERE idfactura = $d->idfactura"), 2);
    $data->totdescuento = round((float)$db->getOneField("SELECT SUM(descuento) FROM detfact WHERE idfactura = $d->idfactura"), 2);
    $data->montosiniva = round((float)$db->getOneField("SELECT SUM(importeneto) FROM detfact WHERE idfactura = $d->idfactura"), 2);    
    $data->idempresa = (int)$db->getOneField("SELECT idempresa FROM factura WHERE id = $d->idfactura");
    $data->idtipocliente = (int)$db->getOneField("SELECT idtipocliente FROM contrato WHERE id = (SELECT idcontrato FROM factura WHERE id = $d->idfactura)");    
    $data->reteneriva = (int)$db->getOneField("SELECT reteneriva FROM factura WHERE id = $d->idfactura");
    $data->retenerisr = (int)$db->getOneField("SELECT retenerisr FROM factura WHERE id = $d->idfactura");
    $data->porcentajeretiva = (float)$db->getOneField("SELECT porretiva FROM factura WHERE id = $d->idfactura");
    $data->iva = round($data->montoconiva - $data->montosiniva, 2);
    $data->exentoiva = (int)$db->getOneField("SELECT exentoiva FROM factura WHERE id = $d->idfactura");
    // $data->montocargoiva = $data->montoconiva;
    $d->tc = (float)$db->getOneField("SELECT tipocambio FROM factura WHERE id = $d->idfactura");    

    $calculo = calculaImpuestosYTotal($db, $d, $data);

    $query = "SELECT GROUP_CONCAT(DISTINCT TRIM(descripcion) SEPARATOR ', ') FROM detfact WHERE idfactura = $d->idfactura";
    $conceptomayor = $db->getOneField($query);
    $conceptomayor = trim($conceptomayor) != '' ? ("'".trim($conceptomayor)."'") : 'NULL';

    $query = "SELECT IFNULL(SUM(importebruto), 0.00) AS importebruto, IFNULL(SUM(importeneto), 0.00) AS importeneto, IFNULL(SUM(importeiva), 0.00) AS importeiva, IFNULL(SUM(descuentosiniva), 0.00) AS descuentosiniva, 
    IFNULL(SUM(descuentoiva), 0.00) AS descuentoiva, IFNULL(SUM(importebrutocnv), 0.00) AS importebrutocnv, IFNULL(SUM(importenetocnv), 0.00) AS importenetocnv, IFNULL(SUM(importeivacnv), 0.00) AS importeivacnv, 
    IFNULL(SUM(descuentosinivacnv), 0.00) AS descuentosinivacnv, IFNULL(SUM(descuentoivacnv), 0.00) AS descuentoivacnv, IFNULL(SUM(importetotal), 0.00) AS importetotal, IFNULL(SUM(importetotalcnv), 0.00) AS importetotalcnv,
    IFNULL(SUM(importeexento), 0.00) AS importeexento, IFNULL(SUM(importeexentocnv), 0.00) AS importeexentocnv
    FROM detfact WHERE idfactura = $d->idfactura";
    $importe = $db->getQuery($query)[0];

    $query = "UPDATE factura SET iva = $data->iva, total = $calculo->totapagar, subtotal = $data->montoconiva, ";
    $query.= "retisr = $calculo->isrporretener, retiva = $calculo->ivaporretener, totdescuento = $data->totdescuento, totalletras = '".$n2l->to_word($calculo->totapagar, 'GTQ')."', conceptomayor = $conceptomayor, ";
    $query.= "importebruto = $importe->importebruto, importeneto = $importe->importeneto, importeiva = $importe->importeiva, importetotal = $importe->importetotal, descuentosiniva = $importe->descuentosiniva, ";
    $query.= "descuentoiva = $importe->descuentoiva, importebrutocnv = $importe->importebrutocnv, importenetocnv = $importe->importenetocnv, importeivacnv = $importe->importeivacnv, importetotalcnv = $importe->importetotalcnv, ";
    $query.= "descuentosinivacnv = $importe->descuentosinivacnv, descuentoivacnv = $importe->descuentoivacnv, totalcnv = $calculo->totapagarcnv, ";
    $query.= "importeexento = $importe->importeexento, importeexentocnv = $importe->importeexentocnv ";
    $query.= "WHERE id = $d->idfactura";
    $db->doQuery($query);


    $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
    $dataa = ['ids' => $d->idfactura, 'idcontrato' => (int)$db->getOneField("SELECT idcontrato FROM factura WHERE id = $d->idfactura")];
    $db->CallJSReportAPI('POST', $url, json_encode($dataa));
}

function calcularImportes($d) {
    $db = new dbcpm();
    $factor = ((int)$db->getOneField("SELECT exentoiva FROM factura WHERE id = $d->idfactura") === 0) ? 1.12 : 1;

    $importe = new stdClass();

    $importe->preciounitario = round((float)$d->preciounitario, 2);
    $importe->descuento = round((float)$d->descuento, 2);
    $importe->descuentosiniva = round($importe->descuento / $factor, 2);
    $importe->descuentoiva = round($importe->descuento - $importe->descuentosiniva, 2);
    $importe->preciototal = round((int)$d->cantidad * $importe->preciounitario, 2);

    $importe->bruto = round($d->cantidad * $importe->preciounitario, 2);
    $importe->porcentajedescuento = round(($importe->descuento * 100) / $importe->bruto, 4);
    $importe->neto = round(($importe->bruto - $importe->descuento) / $factor, 2);
    $importe->iva = round($importe->bruto - $importe->descuento - $importe->neto, 2);
    $importe->total = round($importe->neto + $importe->iva, 2);

    $importe->preciounitariocnv = round($importe->preciounitario / $d->tipocambio, 2);
    $importe->descuentocnv = round($importe->descuento / $d->tipocambio, 2);

    $importe->brutocnv = round($importe->bruto / $d->tipocambio, 2);
    $importe->netocnv = round($importe->neto / $d->tipocambio, 2);
    $importe->ivacnv = round($importe->iva / $d->tipocambio, 2);
    $importe->totalcnv = round($importe->total / $d->tipocambio, 2);
    $importe->descuentosinivacnv = round($importe->descuentosiniva / $d->tipocambio, 2);
    $importe->descuentoivacnv = round($importe->descuentoiva / $d->tipocambio, 2);

    $importe->exento = $factor === 1 ? $importe->total : 0.00;
    $importe->exentocnv = $factor === 1 ? $importe->totalcnv : 0.00;

    return $importe;
}

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $importe = calcularImportes($d);

    $query = "INSERT INTO detfact(";
    $query.= "idfactura, cantidad, descripcion, preciounitario, preciounitariocnv, preciotot, idtiposervicio, mes, anio, descuento, descuentocnv, ";
    $query.= "importebruto, importeneto, importeiva, importetotal, descuentosiniva, descuentoiva, ";
    $query.= "importebrutocnv, importenetocnv, importeivacnv, importetotalcnv, descuentosinivacnv, descuentoivacnv, porcentajedescuento, ";
    $query.= "precio, preciocnv, descripcionlarga, importeexento, importeexentocnv";
    $query.= ") VALUES(";
    $query.= "$d->idfactura, $d->cantidad, '$d->descripcion', $importe->preciounitario, $importe->preciounitariocnv, $importe->preciototal, $d->idtiposervicio, $d->mes, $d->anio, $importe->descuento, $importe->descuentocnv, ";
    $query.= "$importe->bruto, $importe->neto, $importe->iva, $importe->total, $importe->descuentosiniva, $importe->descuentoiva, ";
    $query.= "$importe->brutocnv, $importe->netocnv, $importe->ivacnv, $importe->totalcnv, $importe->descuentosinivacnv, $importe->descuentoivacnv, $importe->porcentajedescuento, ";
    $query.= "$importe->preciounitario, $importe->preciounitariocnv, '$d->descripcion', $importe->exento, $importe->exentocnv";
    $query.= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    updateDatosFacturaFEL($d);
    print json_encode(['lastid' => $lastid]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $importe = calcularImportes($d);

    $query = "UPDATE detfact SET ";
    $query.= "cantidad = $d->cantidad, idtiposervicio = $d->idtiposervicio, mes = $d->mes, anio = $d->anio, descripcion = '$d->descripcion', ";
    $query.= "preciounitario = $importe->preciounitario, preciotot = $importe->preciototal, descuento = $importe->descuento, ";
    $query.= "importebruto = $importe->bruto, importeneto = $importe->neto, importeiva = $importe->iva, importetotal = $importe->total, descuentosiniva = $importe->descuentosiniva, descuentoiva = $importe->descuentoiva, ";
    $query.= "importebrutocnv = $importe->brutocnv, importenetocnv = $importe->netocnv, importeivacnv = $importe->ivacnv, importetotalcnv = $importe->totalcnv, descuentosinivacnv = $importe->descuentosinivacnv, ";
    $query.= "descuentoivacnv = $importe->descuentoivacnv, precio = $importe->preciounitario, preciocnv = $importe->preciounitariocnv, descripcionlarga = '$d->descripcion', ";
    $query.= "importeexento = $importe->exento, importeexentocnv = $importe->exentocnv ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
    updateDatosFacturaFEL($d);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();    
    $query = "DELETE FROM detfact WHERE id = $d->id";
    $db->doQuery($query);
    updateDatosFacturaFEL($d);    
});

$app->response()->setStatus(200);
$app->run();