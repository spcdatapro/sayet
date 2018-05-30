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
    $query.= "CONCAT(";

    $query.= "'<small>',nit, '<br/>', IFNULL(direccion, ''),'</small>'";

    $query.= ") AS infocliente FROM (";

    $query.= "SELECT DISTINCT a.idcliente, a.facturara, a.nit, a.retisr, a.retiva, a.direccion ";
    $query.= "FROM detclientefact a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN contrato c ON b.id = c.idcliente ";
    $query.= "WHERE c.idempresa = $idempresa AND a.fal IS NULL AND a.facturara LIKE '%$qstr%' ";

    $query.= "UNION ALL ";

    $query.= "SELECT DISTINCT 0 AS idcliente, nombre AS facturara, nit, retenerisr AS retisr, reteneriva AS retiva, direccion ";
    $query.= "FROM factura ";
    $query.= "WHERE fecha >= '2017-09-01' AND idempresa = $idempresa AND nombre LIKE '%$qstr%' AND (idcontrato = 0 OR idcontrato IS NULL) ";
    //$query.= "AND TRIM(nit) NOT IN(SELECT TRIM(nit) FROM detclientefact WHERE fal IS NULL AND facturara LIKE '%$qstr%') ";
    $query.= "ORDER BY 2";

    $query.= ") a";
    //print $query;
    print json_encode(['results' => $db->getQuery($query)]);
});

$app->get('/lstfacturas/:idempresa/:cuales', function($idempresa, $cuales){
    $db = new dbcpm();
    $query = "SELECT DISTINCT a.id, a.fecha, a.serie, a.numero, a.idcontrato, a.idcliente, IF(a.nombre IS NULL OR TRIM(a.nombre) = '', b.facturara, a.nombre) AS cliente, ";
    $query.= "IF(a.nit IS NULL OR TRIM(a.nit) = '', b.nit, a.nit) AS nit, IF(a.idcontrato IS NULL, '', UnidadesPorContrato(a.idcontrato)) AS unidad, a.total, d.nomempresa AS empresa, ";
    $query.= "a.iva, a.total, a.noafecto, a.subtotal, a.retisr, a.retiva, a.totdescuento, a.tipocambio, a.reteneriva, a.retenerisr, a.mesafecta, a.anioafecta, a.direccion, d.abreviatura AS abreviaempre, ";
    $query.= "a.idproyecto, e.nomproyecto AS proyecto ";
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
    $query.= "a.iva, a.total, a.noafecto, a.subtotal, a.retisr, a.retiva, a.totdescuento, a.tipocambio, a.reteneriva, a.retenerisr, a.mesafecta, a.anioafecta, a.direccion, a.idproyecto ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.id = $idfactura";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "INSERT INTO factura(";
    $query.= "idempresa, idtipofactura, idcontrato, idcliente, nit, nombre, serie, numero, fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, idmoneda, tipocambio, esinsertada,";
    $query.= "reteneriva, retenerisr, mesafecta, anioafecta, direccion, idproyecto) VALUES(";
    $query.= "$d->idempresa, $d->idtipofactura, $d->idcontrato, $d->idcliente, ".($d->nit == '' ? "NULL" : "'".$d->nit."'").", ".($d->nombre == '' ? "NULL" : "'".$d->nombre."'").", ";
    $query.= ($d->serie == '' ? "NULL" : "'".$d->serie."'").", ".($d->numero == '' ? "NULL" : "'".$d->numero."'").", '$d->fechaingresostr', $d->mesiva, '$d->fechastr', $d->idtipoventa, ";
    $query.= "NULL, 1, $d->tipocambio, 1, $d->reteneriva, $d->retenerisr, $d->mesafecta, $d->anioafecta, '$d->direccion', $d->idproyecto";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE factura SET ";
    $query.= "idempresa = $d->idempresa, idtipofactura = $d->idtipofactura, idcontrato = $d->idcontrato, idcliente = $d->idcliente, nit = ".($d->nit == '' ? "NULL" : "'".$d->nit."'").", ";
    $query.= "nombre = ".($d->nombre == '' ? "NULL" : "'".$d->nombre."'").", serie = ".($d->serie == '' ? "NULL" : "'".$d->serie."'").", numero = ".($d->numero == '' ? "NULL" : "'".$d->numero."'").", ";
    $query.= "fechaingreso = '$d->fechaingresostr', mesiva = $d->mesiva, fecha = '$d->fechastr', idtipoventa = $d->idtipoventa, tipocambio = $d->tipocambio, ";
    $query.= "reteneriva = $d->reteneriva, retenerisr = $d->retenerisr, mesafecta = $d->mesafecta, anioafecta = $d->anioafecta, direccion = '$d->direccion', idproyecto = $d->idproyecto ";
    $query.= "WHERE id = $d->id";
    //print $query;
    $db->doQuery($query);

    $query = "UPDATE detfact SET mes = $d->mesafecta, anio = $d->anioafecta WHERE idfactura = $d->id";
    $db->doQuery($query);

    $d->idfactura = $d->id;
    updateDatosFactura($d);
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
    $r->retiva = (int)$d->reteneriva > 0 ? $db->calculaRetIVA((float)$d->montosiniva, ((int)$d->idtipocliente == 1 ? true : false), $d->montoconiva, ((int)$d->idtipocliente == 2 ? true : false), $d->iva) : 0.00;
    $r->totapagar = (float)$d->montoconiva - ($r->retisr + $r->retiva);

    return $r;
}

function updateDatosFactura($d){
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $data = new stdClass();
    $data->montoconiva = (float)$db->getOneField("SELECT SUM(preciotot) AS total FROM detfact WHERE idfactura = $d->idfactura");
    $data->totdescuento = (float)$db->getOneField("SELECT SUM(descuento) AS totdesc FROM detfact WHERE idfactura = $d->idfactura");
    $data->montosiniva = round((float)$data->montoconiva / 1.12, 7);
    $data->idempresa = (int)$db->getOneField("SELECT idempresa FROM factura WHERE id = $d->idfactura");
    $data->idtipocliente = (int)$db->getOneField("SELECT idtipocliente FROM contrato WHERE id = (SELECT idcontrato FROM factura WHERE id = $d->idfactura)");
    $data->reteneriva = (int)$db->getOneField("SELECT reteneriva FROM factura WHERE id = $d->idfactura");
    $data->retenerisr = (int)$db->getOneField("SELECT retenerisr FROM factura WHERE id = $d->idfactura");
    $data->iva = round($data->montoconiva - $data->montosiniva, 2);
    $data->montocargoiva = (float)$db->getOneField("SELECT SUM(montoconiva) FROM detfact WHERE idfactura = $d->idfactura");
    $tc = (float)$db->getOneField("SELECT tipocambio FROM factura WHERE id = $d->idfactura");
    $data->montocargoflat = (float)$db->getOneField("SELECT ROUND(SUM(montoconiva / 1.12) / $tc, 2) FROM detfact WHERE idfactura = $d->idfactura");

    $calculo = recalc($data);

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

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "INSERT INTO detfact(";
    $query.= "idfactura, cantidad, idtiposervicio, mes, anio, descripcion, preciounitario, preciotot, descuento, montoconiva, montoflatconiva";
    $query.= ") VALUES(";
    $query.= "$d->idfactura, $d->cantidad, $d->idtiposervicio, $d->mes, $d->anio, '$d->descripcion', ".round(((float)$d->preciotot - (float)$d->descuento)/(int)$d->cantidad, 2).", ".((float)$d->preciotot - (float)$d->descuento).", $d->descuento, $d->preciotot, $d->preciotot";
    $query.= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    updateDatosFactura($d);
    print json_encode(['lastid' => $lastid]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE detfact SET ";
    $query.= "cantidad = $d->cantidad, idtiposervicio = $d->idtiposervicio, mes = $d->mes, anio = $d->anio, descripcion = '$d->descripcion', ";
    $query.= "preciounitario = $d->preciounitario, preciotot = $d->preciotot, descuento = $d->descuento ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
    updateDatosFactura($d);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();    
    $query = "DELETE FROM detfact WHERE id = $d->id";
    $db->doQuery($query);
    updateDatosFactura($d);    
});

$app->response()->setStatus(200);
$app->run();