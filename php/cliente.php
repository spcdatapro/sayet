<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para clientes
$app->get('/lstcliente', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nombre, a.nombrecorto, a.direntrega, a.dirplanta, a.telpbx, a.teldirecto, a.telfax, a.telcel, a.correo, a.idordencedula, a.regcedula, a.dpi, a.cargolegal, a.nomlegal, a.apellidolegal, ";
    $query.= "a.nomadmon, a.mailadmon, a.nompago, a.mailcont, a.idcuentac, a.creadopor, a.fhcreacion, a.actualizadopor, a.fhactualizacion, c.contratos ";
    $query.= "FROM cliente a LEFT JOIN (";
    $query.= "SELECT idcliente, GROUP_CONCAT(contratos SEPARATOR ';') AS contratos FROM (";
    $query.= "SELECT c.idcliente, CONCAT(c.idcontrato, '_', GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ')) AS contratos FROM (";
    $query.= "SELECT b.idcliente, b.id AS idcontrato, a.nombre FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad)) c GROUP BY c.idcliente, c.idcontrato) a ";
    $query.= "GROUP BY idcliente) c ON a.id = c.idcliente ";
    $query.= "ORDER BY a.nombre";
    //echo $query.'<br/>';
    print $db->doSelectASJson($query);
});

$app->get('/getcliente/:idcliente', function($idcliente){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nombre, a.nombrecorto, a.direntrega, a.dirplanta, a.telpbx, a.teldirecto, a.telfax, a.telcel, a.correo, a.idordencedula, b.noorden, a.regcedula, ";
    $query.= "a.dpi, a.cargolegal, a.nomlegal, a.apellidolegal, a.nomadmon, a.mailadmon, a.nompago, a.mailcont, a.idcuentac, ";
    $query.= "a.creadopor, a.fhcreacion, a.actualizadopor, a.fhactualizacion, c.contratos ";
    $query.= "FROM cliente a LEFT JOIN ordencedula b ON b.id = a.idordencedula ";
    $query.= "LEFT JOIN (SELECT idcliente, GROUP_CONCAT(CONCAT(id, '-', nocontrato) SEPARATOR ',') AS contratos FROM contrato GROUP BY idcliente) c ON a.id = c.idcliente ";
    $query.= "WHERE a.id = ".$idcliente;
    print $db->doSelectASJson($query);
});

$app->get('/rptdetcont', function(){
    $db = new dbcpm();
    $query = "SELECT a.id AS idcliente, a.nombre, a.nombrecorto, b.id, b.nocontrato, UnidadesPorContrato(b.id) AS unidades ";
    $query.= "FROM cliente a INNER JOIN contrato b ON a.id = b.idcliente ";
    $query.= "ORDER BY a.nombre, 6";
    //echo $query.'<br/>';
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO cliente(";
    $query.= "nombre, nombrecorto, direntrega, telpbx, teldirecto, telcel, correo, ";
    $query.= "dpi, cargolegal, nomlegal, apellidolegal, nomadmon, mailadmon, nompago, mailcont, idcuentac, ";
    $query.= "creadopor, fhcreacion";
    $query.= ") VALUES(";
    $query.= "'".$d->nombre."', '".$d->nombrecorto."', '".$d->direntrega."', ";
    $query.= "'".$d->telpbx."', '".$d->teldirecto."', '".$d->telcel."', '".$d->correo."', '".$d->dpi."', ";
    $query.= "'".$d->cargolegal."', '".$d->nomlegal."', '".$d->apellidolegal."', '".$d->nomadmon."', '".$d->mailadmon."', ";
    $query.= "'".$d->nompago."', '".$d->mailcont."', '".$d->idcuentac."', '".$d->creadopor."', NOW()";
    $query.= ")";
    //echo $query."<br/><br/>";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});


$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE cliente SET ";
    $query.= "nombre = '".$d->nombre."', nombrecorto = '".$d->nombrecorto."', direntrega = '".$d->direntrega."', telpbx = '".$d->telpbx."', ";
    $query.= "teldirecto = '".$d->teldirecto."', telcel = '".$d->telcel."', correo = '".$d->correo."', ";
    $query.= "dpi = '".$d->dpi."', cargolegal = '".$d->cargolegal."', nomlegal = '".$d->nomlegal."', apellidolegal = '".$d->apellidolegal."', ";
    $query.= "nomadmon = '".$d->nomadmon."', mailadmon = '".$d->mailadmon."', nompago = '".$d->nompago."', mailcont = '".$d->mailcont."', idcuentac = '".$d->idcuentac."', ";
    $query.= "actualizadopor = '".$d->actualizadopor."', fhactualizacion = NOW() ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM cliente WHERE id = ".$d->id);
});

$app->get('/clientetoprint/:idcliente', function($idcliente){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nombre, a.nombrecorto, a.direntrega, a.dirplanta, a.telpbx, a.teldirecto, a.telfax, a.telcel, a.correo, a.idordencedula, b.noorden, a.regcedula, ";
    $query.= "a.dpi, a.cargolegal, a.nomlegal, a.apellidolegal, a.nomadmon, a.mailadmon, a.nompago, a.mailcont, a.idcuentac, ";
    $query.= "a.creadopor, a.fhcreacion, a.actualizadopor, a.fhactualizacion, c.contratos ";
    $query.= "FROM cliente a LEFT JOIN ordencedula b ON b.id = a.idordencedula ";
    $query.= "LEFT JOIN (SELECT idcliente, GROUP_CONCAT(CONCAT(id, '-', nocontrato) SEPARATOR ',') AS contratos FROM contrato GROUP BY idcliente) c ON a.id = c.idcliente ";
    $query.= "WHERE a.id = ".$idcliente;
    $cliente = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.abogado, a.inactivo, a.fechainicia, a.fechavence, a.nuevarenta, a.nuevomantenimiento, a.idmoneda, b.simbolo AS moneda, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.deposito, a.idproyecto, d.nomproyecto AS proyecto, a.idunidad, a.retiva, a.prorrogable, a.retisr, ";
    $query.= "a.documento, a.adelantado, a.subarrendado, a.idtipocliente, f.desctipocliente AS tipocliente, a.idcuentac, a.observaciones, a.idmonedadep, ";
    $query.= "h.simbolo AS monedadep, i.unidades, j.mcrentados ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep LEFT JOIN (";
    $query.= "SELECT c.idcontrato, GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS unidades FROM (SELECT b.id AS idcontrato, a.nombre ";
    $query.= "FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad)) c GROUP BY c.idcontrato";
    $query.= ") i ON a.id = i.idcontrato ";
    $query.= "LEFT JOIN (SELECT b.id AS idcontrato, SUM(a.mcuad) AS mcrentados FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad) GROUP BY b.idcliente, b.id) j ON a.id = j.idcontrato ";
    $query.= "WHERE a.idcliente = ".$idcliente;
    $cliente->contratos = $db->getQuery($query);

    $query = "SELECT facturara, emailfactura, direccion, nit FROM detclientefact WHERE idcliente = ".$idcliente." AND ISNULL(fal) ORDER BY fdel DESC LIMIT 1";
    $df = $db->getQuery($query);
    $cliente->datafact = count($df) > 0 ? $df[0] : [];

    $query = "SELECT nombre, empresa, direccion, telefono, identificacion FROM detclientefiadores WHERE idcliente = ".$idcliente;
    $fia = $db->getQuery($query);
    $cliente->fiadores = count($fia) > 0 ? $fia : [];

    print json_encode($cliente);
});

//Detalle de datos de facturacion
$app->get('/lstdatosfact/:idcliente', function($idcliente){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.facturara, a.direccion, a.nit, a.fdel, a.fal, a.emailfactura, ";
    $query.= "(SELECT IF(GROUP_CONCAT(DISTINCT c.desctiposervventa ORDER BY c.desctiposervventa SEPARATOR ', ') IS NULL, 'Todos', GROUP_CONCAT(DISTINCT c.desctiposervventa ORDER BY c.desctiposervventa SEPARATOR ', ')) ";
    $query.= "FROM detclienteserv b INNER JOIN tiposervicioventa c ON c.id = b.idservicioventa ";
    $query.= "WHERE b.iddetclientefact = a.id) AS serviciosafact, a.retisr, a.retiva ";
    $query.= "FROM detclientefact a ";
    $query.= "WHERE a.idcliente = $idcliente ";
    $query.= "ORDER BY a.facturara";
    print $db->doSelectASJson($query);
});

$app->get('/getfacturara/:iddetfact', function($iddetfact){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.facturara, a.direccion, a.nit, a.fdel, a.fal, a.emailfactura, ";
    $query.= "(SELECT IF(GROUP_CONCAT(DISTINCT c.desctiposervventa ORDER BY c.desctiposervventa SEPARATOR ', ') IS NULL, 'Todos', GROUP_CONCAT(DISTINCT c.desctiposervventa ORDER BY c.desctiposervventa SEPARATOR ', ')) ";
    $query.= "FROM detclienteserv b INNER JOIN tiposervicioventa c ON c.id = b.idservicioventa ";
    $query.= "WHERE b.iddetclientefact = a.id) AS serviciosafact, a.retisr, a.retiva ";
    $query.= "FROM detclientefact a ";
    $query.= "WHERE a.id = $iddetfact";
    print $db->doSelectASJson($query);
});

$app->post('/cdf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fdelstr = $d->fdelstr != '' ? "'".$d->fdelstr."'" : 'NULL';
    $d->falstr = $d->falstr != '' ? "'".$d->falstr."'" : 'NULL';
    $query = "INSERT INTO detclientefact(";
    $query.= "idcliente, facturara, direccion, nit, fdel, fal, emailfactura, retisr, retiva";
    $query.= ") VALUES(";
    $query.= "$d->idcliente, '$d->facturara', '$d->direccion', '$d->nit', $d->fdelstr, $d->falstr, '$d->emailfactura', $d->retisr, $d->retiva";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});


$app->post('/udf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fdelstr = $d->fdelstr != '' ? "'".$d->fdelstr."'" : 'NULL';
    $d->falstr = $d->falstr != '' ? "'".$d->falstr."'" : 'NULL';
    $query = "UPDATE detclientefact SET ";
    $query.= "facturara = '$d->facturara', direccion = '$d->direccion', nit = '$d->nit', fdel = $d->fdelstr, fal = $d->falstr, emailfactura = '$d->emailfactura', retisr = $d->retisr, retiva = $d->retiva ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});


$app->post('/ddf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detclientefact WHERE id = ".$d->id);
});

//Detalle de servicios de datos de facturacion
$app->get('/lstservfact/:iddetclientefact', function($iddetclientefact){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetclientefact, a.idservicioventa, b.desctiposervventa AS servicio ";
    $query.= "FROM detclienteserv a INNER JOIN tiposervicioventa b ON b.id = a.idservicioventa ";
    $query.= "WHERE a.iddetclientefact = $iddetclientefact ";
    $query.= "ORDER BY b.desctiposervventa";
    print $db->doSelectASJson($query);
});

$app->get('/getservfact/:idservfact', function($idservfact){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetclientefact, a.idservicioventa, b.desctiposervventa AS servicio ";
    $query.= "FROM detclienteserv a INNER JOIN tiposervicioventa b ON b.id = a.idservicioventa ";
    $query.= "WHERE a.id = $idservfact ";
    print $db->doSelectASJson($query);
});

$app->post('/cddf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "INSERT INTO detclienteserv(";
    $query.= "iddetclientefact, idservicioventa";
    $query.= ") VALUES(";
    $query.= "$d->iddetclientefact, $d->idservicioventa";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/uddf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detclienteserv SET ";
    $query.= "idservicioventa = $d->idservicioventa ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/dddf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detclienteserv WHERE id = ".$d->id);
});

//Detalle de datos de fiadores
$app->get('/lstdatosfia/:idcontrato', function($idcontrato){
    $db = new dbcpm();
    $query = "SELECT id, idcontrato, idcliente, nombre, direccion, telefono, identificacion, empresa FROM detclientefiadores WHERE idcontrato = $idcontrato ORDER BY nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getfiador/:idfia', function($idfia){
    $db = new dbcpm();
    $query = "SELECT id, idcontrato, idcliente, nombre, direccion, telefono, identificacion, empresa FROM detclientefiadores WHERE id = ".$idfia;
    print $db->doSelectASJson($query);
});

$app->post('/cfia', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detclientefiadores(";
    $query.= "idcontrato, idcliente, nombre, direccion, telefono, identificacion, empresa";
    $query.= ") VALUES(";
    $query.= $d->idcontrato.", ".$d->idcliente.", '".$d->nombre."', '".$d->direccion."', '".$d->telefono."', '".$d->identificacion."', '".$d->empresa."'";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});


$app->post('/ufia', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detclientefiadores SET ";
    $query.= "nombre = '".$d->nombre."', direccion = '".$d->direccion."', telefono = '".$d->telefono."', identificacion  = '".$d->identificacion."', empresa = '".$d->empresa."' ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});


$app->post('/dfia', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detclientefiadores WHERE id = ".$d->id);
});

//Contratos del cliente
$app->get('/lstcontratos/:idcliente', function($idcliente){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.abogado, a.inactivo, a.fechainactivo, a.fechainicia, a.fechavence, a.nuevarenta, a.nuevomantenimiento, a.idmoneda, b.simbolo AS moneda, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.deposito, a.idproyecto, d.nomproyecto AS proyecto, a.idunidad, UnidadesPorContrato(a.id) AS unidad, a.retiva, a.prorrogable, a.retisr, ";
    $query.= "a.documento, a.adelantado, a.subarrendado, a.idtipocliente, f.desctipocliente AS tipocliente, a.idcuentac, a.observaciones, a.idmonedadep, ";
    $query.= "h.simbolo AS monedadep, a.reciboprov, a.idperiodicidad, a.idtipoipc, a.cobro, a.plazofdel, a.plazofal, a.prescision ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep ";
    $query.= "WHERE a.idcliente = ".$idcliente;
    print $db->doSelectASJson($query);
});

$app->get('/lstcontemp/:idcliente/:idempresa', function($idcliente, $idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.abogado, a.inactivo, a.fechainactivo, a.fechainicia, a.fechavence, a.nuevarenta, a.nuevomantenimiento, a.idmoneda, b.simbolo AS moneda, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.deposito, a.idproyecto, d.nomproyecto AS proyecto, a.idunidad, UnidadesPorContrato(a.id) AS unidad, a.retiva, a.prorrogable, a.retisr, ";
    $query.= "a.documento, a.adelantado, a.subarrendado, a.idtipocliente, f.desctipocliente AS tipocliente, a.idcuentac, a.observaciones, a.idmonedadep, ";
    $query.= "h.simbolo AS monedadep, a.reciboprov, a.idperiodicidad, a.idtipoipc, a.cobro, a.plazofdel, a.plazofal, a.prescision ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep ";
    $query.= "WHERE a.idcliente = $idcliente AND a.idempresa = $idempresa ";
    print $db->doSelectASJson($query);
});

$app->get('/getcontrato/:idcontrato', function($idcontrato){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.abogado, a.inactivo, a.fechainactivo, a.fechainicia, a.fechavence, a.nuevarenta, a.nuevomantenimiento, a.idmoneda, b.simbolo AS moneda, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.deposito, a.idproyecto, d.nomproyecto AS proyecto, a.idunidad, e.nombre AS unidad, a.retiva, a.prorrogable, a.retisr, ";
    $query.= "a.documento, a.adelantado, a.subarrendado, a.idtipocliente, f.desctipocliente AS tipocliente, a.idcuentac, a.observaciones, a.idmonedadep, ";
    $query.= "h.simbolo AS monedadep, a.reciboprov, a.idperiodicidad, a.idtipoipc, a.cobro, a.plazofdel, a.plazofal, a.prescision ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto LEFT JOIN unidad e ON e.id = a.idunidad ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep ";
    $query.= "WHERE a.id = ".$idcontrato;
    print $db->doSelectASJson($query);
});

$app->get('/contratotoprint/:idcontrato', function($idcontrato){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.fechainicia, a.fechavence, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.idproyecto, d.nomproyecto AS proyecto, ";
    $query.= "i.unidades, a.reciboprov, j.descperiodicidad AS periodicidad ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep LEFT JOIN (";
    $query.= "SELECT c.idcontrato, GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS unidades FROM (SELECT b.id AS idcontrato, a.nombre ";
    $query.= "FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad)) c GROUP BY c.idcontrato";
    $query.= ") i ON a.id = i.idcontrato LEFT JOIN periodicidad j ON j.id = a.idperiodicidad ";
    $query.= "WHERE a.id = ".$idcontrato;
    $contrato = $db->getQuery($query)[0];
    $query = "SELECT a.nombre, a.nombrecorto, b.facturara, b.nit ";
    $query.= "FROM cliente a LEFT JOIN (SELECT idcliente, facturara, nit FROM detclientefact WHERE idcliente = ".$contrato->idcliente." AND ISNULL(fal) ORDER BY fdel DESC LIMIT 1) b ON a.id = b.idcliente ";
    $query.= "WHERE a.id = ".$contrato->idcliente;
    $contrato->datacliente = $db->getQuery($query)[0];
    $contrato->servicios = [];
    $serviciosVenta = $db->getQuery("SELECT id, desctiposervventa AS servicio FROM tiposervicioventa ORDER BY desctiposervventa");
    foreach($serviciosVenta as $sv){
        $query = "SELECT a.id, a.idcontrato, a.fdel, a.fal, a.monto, a.idtipoventa, b.desctiposervventa AS tipoventa, a.idmoneda, c.simbolo AS moneda, a.cobro, a.idperiodicidad, ";
        $query.= "d.descperiodicidad AS periodicidad, a.noperiodo FROM detfactcontrato a INNER JOIN tiposervicioventa b ON b.id = a.idtipoventa INNER JOIN moneda c ON c.id = a.idmoneda ";
        $query.= "INNER JOIN periodicidad d ON d.id = a.idperiodicidad WHERE a.idcontrato = ".$idcontrato." AND b.id = ".$sv->id." ORDER BY b.desctiposervventa, a.noperiodo";
        $tmp = $db->getQuery($query);
        if(count($tmp) > 0){
            $contrato->servicios[$sv->servicio] = $db->getQuery($query);
        }
    }

    print json_encode($contrato);
});

$app->get('/lstabogados/:qstr', function($qstr){
    $db = new dbcpm();
    $query = "SELECT DISTINCT abogado FROM contrato WHERE abogado LIKE '%$qstr%'";
    print json_encode(['results' => $db->getQuery($query)]);
});

$app->post('/cc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->plazofdelstr = $d->plazofdelstr == '' ? "NULL" : "'$d->plazofdelstr'";
    $d->plazofalstr = $d->plazofalstr == '' ? "NULL" : "'$d->plazofalstr'";
    $d->fechainactivostr = ($d->fechainactivostr == '' || (int)$d->inactivo == 0) ? "NULL" : "'$d->fechainactivostr'";

    $query = "INSERT INTO contrato(";
    $query.= "idcliente, nocontrato, abogado, inactivo, fechainicia, fechavence, nuevarenta, nuevomantenimiento, ";
    $query.= "idmoneda, idempresa, deposito, idproyecto, idunidad, retiva, prorrogable, retisr, ";
    $query.= "documento, adelantado, subarrendado, idtipocliente, idcuentac, observaciones, idmonedadep, ";
    $query.= "reciboprov, idperiodicidad, lastuser, idtipoipc, cobro, plazofdel, plazofal, prescision, ";
    $query.= "fechainactivo";
    $query.= ") VALUES(";
    $query.= $d->idcliente.", '".$d->nocontrato."', '".$d->abogado."', ".$d->inactivo.", '".$d->fechainiciastr."', '".$d->fechavencestr."', ".$d->nuevarenta.", ".$d->nuevomantenimiento.", ";
    $query.= $d->idmoneda.", ".$d->idempresa.", ".$d->deposito.", ".$d->idproyecto.", '".$d->idunidad."', ".$d->retiva.", ".$d->prorrogable.", ".$d->retisr.", ";
    $query.= $d->documento.", ".$d->adelantado.", ".$d->subarrendado.", ".$d->idtipocliente.", '".$d->idcuentac."', '".$d->observaciones."', ".$d->idmonedadep.", ";
    $query.= "'".$d->reciboprov."', ".$d->idperiodicidad.", $d->lastuser, $d->idtipoipc, $d->cobro, $d->plazofdelstr, $d->plazofalstr, $d->prescision, $d->fechainactivostr";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/uc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->plazofdelstr = $d->plazofdelstr == '' ? "NULL" : "'$d->plazofdelstr'";
    $d->plazofalstr = $d->plazofalstr == '' ? "NULL" : "'$d->plazofalstr'";
    $d->fechainactivostr = ($d->fechainactivostr == '' || (int)$d->inactivo == 0) ? "NULL" : "'$d->fechainactivostr'";

    $query = "UPDATE contrato SET ";
    $query.= "nocontrato = '".$d->nocontrato."', abogado = '".$d->abogado."', inactivo = ".$d->inactivo.", fechainicia = '".$d->fechainiciastr."', fechavence = '".$d->fechavencestr."', ";
    $query.= "nuevarenta = ".$d->nuevarenta.", nuevomantenimiento = ".$d->nuevomantenimiento.", ";
    $query.= "idmoneda = ".$d->idmoneda.", idempresa = ".$d->idempresa.", deposito = ".$d->deposito.", idproyecto = ".$d->idproyecto.", idunidad = '".$d->idunidad."', retiva = ".$d->retiva.", ";
    $query.= "prorrogable = ".$d->prorrogable.", retisr = ".$d->retisr.", ";
    $query.= "documento = ".$d->documento.", adelantado = ".$d->adelantado.", subarrendado = ".$d->subarrendado.", idtipocliente = ".$d->idtipocliente.", ";
    $query.= "idcuentac = '".$d->idcuentac."', observaciones = '".$d->observaciones."', idmonedadep = ".$d->idmonedadep.", reciboprov = '".$d->reciboprov."', ";
    $query.= "idperiodicidad = ".$d->idperiodicidad.", lastuser = $d->lastuser, idtipoipc = $d->idtipoipc, cobro = $d->cobro, ";
    $query.= "plazofdel = $d->plazofdelstr, plazofal = $d->plazofalstr, prescision = $d->prescision, fechainactivo = $d->fechainactivostr ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/dc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM contrato WHERE id = ".$d->id);
});

//Detalle de los contratos del cliente
$app->get('/lstdetcontrato/:idcontrato', function($idcontrato){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcontrato, a.fdel, a.fal, a.monto, a.idtipoventa, b.desctiposervventa AS tipoventa, a.idmoneda, c.simbolo AS moneda, a.cobro, a.idperiodicidad, ";
    $query.= "d.descperiodicidad AS periodicidad, a.noperiodo, e.fechavence, IF(a.fal > e.fechavence, 1, 0) AS fuerarango ";
    $query.= "FROM detfactcontrato a INNER JOIN tiposervicioventa b ON b.id = a.idtipoventa INNER JOIN moneda c ON c.id = a.idmoneda INNER JOIN periodicidad d ON d.id = a.idperiodicidad ";
    $query.= "INNER JOIN contrato e ON e.id = a.idcontrato ";
    $query.= "WHERE a.idcontrato = ".$idcontrato." ";
    $query.= "ORDER BY b.desctiposervventa, a.noperiodo";
    print $db->doSelectASJson($query);
});

$app->get('/getdetcontrato/:iddetcontrato', function($iddetcontrato){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcontrato, a.fdel, a.fal, a.monto, a.idtipoventa, b.desctiposervventa AS tipoventa, a.idmoneda, c.simbolo AS moneda, a.cobro, a.idperiodicidad, ";
    $query.= "d.descperiodicidad AS periodicidad, a.noperiodo, e.fechavence, IF(a.fal > e.fechavence, 1, 0) AS fuerarango ";
    $query.= "FROM detfactcontrato a INNER JOIN tiposervicioventa b ON b.id = a.idtipoventa INNER JOIN moneda c ON c.id = a.idmoneda INNER JOIN periodicidad d ON d.id = a.idperiodicidad ";
    $query.= "INNER JOIN contrato e ON e.id = a.idcontrato ";
    $query.= "WHERE a.id = ".$iddetcontrato;
    print $db->doSelectASJson($query);
});

$app->post('/cdc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detfactcontrato(";
    $query.= "idcontrato, fdel, fal, monto, idtipoventa, idmoneda, idperiodicidad, noperiodo";
    $query.= ") VALUES(";
    $query.= $d->idcontrato.", '".$d->fdelstr."', '".$d->falstr."', ".$d->monto.", ".$d->idtipoventa.", ".$d->idmoneda.", ".$d->idperiodicidad.", ".$d->noperiodo;
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/udc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detfactcontrato SET ";
    $query.= "fdel = '".$d->fdelstr."', fal = '".$d->falstr."', monto = ".$d->monto.", idtipoventa = ".$d->idtipoventa.", idmoneda = ".$d->idmoneda.", ";
    $query.= "idperiodicidad = ".$d->idperiodicidad.", noperiodo = ".$d->noperiodo." ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
    $query = "DELETE FROM cargo WHERE iddetcont = $d->id AND facturado = 0";
    $db->doQuery($query);
});

$app->post('/ddc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM cargo WHERE iddetcont = $d->id AND facturado = 0");
    $db->doQuery("DELETE FROM detfactcontrato WHERE id = $d->id");
});

$app->get('/chkdetcontfacturado/:iddetcontrato', function($iddetcontrato){
    $db = new dbcpm();
    print json_encode(['facturado' => (int)$db->getOneField("SELECT COUNT(id) FROM cargo WHERE iddetcont = $iddetcontrato AND facturado = 1")]);
});

$app->post('/apf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE cargo SET anulado = 1, fechaanula = NOW(), usranula = $d->idusuario, idrazonanulacargo = $d->idrazonanula WHERE iddetcont = $d->iddetcontrato AND facturado = 0";
    $db->doQuery($query);
});

$app->get('/chkcargoanulado/:iddetcontrato', function($iddetcontrato){
    $db = new dbcpm();
    $query = "SELECT CONCAT(b.nombre, ' (', b.usuario, ')') AS usuario, a.fechaanula, c.razon ";
    $query.= "FROM cargo a INNER JOIN usuario b ON b.id = a.usranula INNER JOIN razonanulacion c ON c.id = a.idrazonanulacargo ";
    $query.= "WHERE a.iddetcont = $iddetcontrato AND a.anulado = 1 ";
    $query.= "LIMIT 1";
    print $db->doSelectASJson($query);
});

function procDataGen($d){
    $d->id = (int)$d->id;
    $d->idcontrato = (int)$d->idcontrato;
    $d->fdel = new DateTime($d->fdel, new DateTimeZone('America/Guatemala'));
    $d->fal = new DateTime($d->fal, new DateTimeZone('America/Guatemala'));
    $d->monto = (float)$d->monto;
    $d->dias = (int)$d->dias;
    $d->idperiodicidad = (int)$d->idperiodicidad;
    return $d;
};

$app->post('/gencobros', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    //$meses = [1 => '1', 2 => '3', 3 => '6', 4 => '2'];
    $query = "SELECT a.id, a.idcontrato, ";
    $query.= "CONCAT(YEAR(a.fdel), '-', LPAD(MONTH(a.fdel), 2, '0'), '-01') AS fdel, a.fal, a.monto, c.dias, c.id AS idperiodicidad, b.cobro, c.meses ";
    $query.= "FROM detfactcontrato a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN periodicidad c ON c.id = b.idperiodicidad ";
    $query.= "WHERE a.id = ".$d->id." LIMIT 1";
    $infoGen = procDataGen($db->getQuery($query)[0]);
    $fecha = $infoGen->fdel;
    while($fecha <= $infoGen->fal){
        $query = "INSERT INTO cargo(idcontrato, iddetcont, fgeneracion, fechacobro, monto) VALUES(";
        //$query.= $infoGen->idcontrato.", ".$infoGen->id.", NOW(), '".$fecha->format('Y-m-01')."', ".round((float)$infoGen->monto * (int)$meses[$infoGen->idperiodicidad], 2);
        $query.= $infoGen->idcontrato.", ".$infoGen->id.", NOW(), '".$fecha->format('Y-m-01')."', ".round((float)$infoGen->monto * (int)$infoGen->meses, 2);
        $query.= ")";
        $db->doQuery($query);
        //$fecha->add(new DateInterval('P'.$meses[$infoGen->idperiodicidad].'M'));
        $fecha->add(new DateInterval('P'.$infoGen->meses.'M'));
    };
});

$app->get('/getcargos/:iddetcont', function($iddetcont){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetcont, a.fechacobro, a.monto, a.descuento, a.facturado FROM cargo a WHERE a.iddetcont = ".$iddetcont." ORDER BY a.fechacobro";
    print $db->doSelectASJson($query);
});

$app->post('/udesccargo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE cargo SET descuento = ".$d->descuento." WHERE id = ".$d->id;
    $db->doQuery($query);
});

//Adjuntos de los contratos del cliente
$app->get('/lstadj/:idcontrato', function($idcontrato){
    $db = new dbcpm();
    $query = "SELECT id, idcontrato, descripcion, ubicacion FROM contratoadjunto WHERE idcontrato = $idcontrato ORDER BY descripcion";
    print $db->doSelectASJson($query);
});

$app->get('/getadj/:idadj', function($idadj){
    $db = new dbcpm();
    $query = "SELECT id, idcontrato, descripcion, ubicacion FROM contratoadjunto WHERE id = $idadj";
    print $db->doSelectASJson($query);
});

$app->post('/cac', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO contratoadjunto(";
    $query.= "idcontrato, descripcion, ubicacion";
    $query.= ") VALUES(";
    $query.= $d->idcontrato.", '".$d->descripcion."', '".$d->ubicacion."'";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/uac', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE contratoadjunto SET ";
    $query.= "descripcion = '$d->descripcion', ubicacion = '$d->ubicacion' ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/dac', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $ubicacion = $db->getOneField("SELECT ubicacion FROM contratoadjunto WHERE id = ".$d->id);
    if(file_exists('../'.$ubicacion)){ unlink('../'.$ubicacion); }
    $db->doQuery("DELETE FROM contratoadjunto WHERE id = ".$d->id);
});

$app->get('/chkadjuntos', function(){
    $db = new dbcpm();

    $unfound = [];
    $query = "SELECT b.id AS idcontrato, TRIM(c.nombre) AS nombre, b.nocontrato, a.descripcion, a.ubicacion
            FROM contratoadjunto a
            INNER JOIN contrato b ON b.id = a.idcontrato
            INNER JOIN cliente c ON c.id = b.idcliente
            ORDER BY c.nombre, b.nocontrato, a.descripcion";
    $adjuntos = $db->getQuery($query);
    $cntAdjuntos = count($adjuntos);
    for($i = 0; $i < $cntAdjuntos; $i ++){
        $adjunto = $adjuntos[$i];
        if(!file_exists("../$adjunto->ubicacion")){
            $unfound[] = [
                'idcontrato' => $adjunto->idcontrato,
                'cliente' => $adjunto->nombre,
                'contrato' => $adjunto->nocontrato,
                'adjunto' => $adjunto->descripcion,
                'ubicacion' => $adjunto->ubicacion
            ];
        }
    }

    print json_encode($unfound);

});

$app->run();