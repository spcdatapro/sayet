<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para ventas
$app->get('/lstventas/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, b.idempresa, d.nomempresa, b.idcliente, h.nombre AS cliente, a.serie, a.numero, a.fechaingreso, a.mesiva, a.fecha, a.idtipoventa, c.desctipocompra AS tipoventa, ";
    $query.= "a.conceptomayor, a.fechapago, a.anulada, a.idrazonanulafactura, a.total, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, a.idtipofactura, g.desctipofact AS tipofactura, ";
    $query.= "b.nocontrato, a.idcontrato, a.anulada, a.idrazonanulafactura, a.fechaanula, i.razon, CONCAT(a.serieadmin, '-', a.numeroadmin) AS correlativo ";
    $query.= "FROM factura a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN tipocompra c ON c.id = a.idtipoventa INNER JOIN empresa d ON d.id = b.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura INNER JOIN cliente h ON h.id = b.idcliente ";
    $query.= "LEFT JOIN razonanulacion i ON i.id = a.idrazonanulafactura ";
    $query.= "WHERE b.idempresa = ".$idempresa." ";
    $query.= "UNION ";
    $query.= "SELECT a.id, 0 AS idempresa, NULL AS nomempresa, a.idcliente, h.nombre AS cliente, a.serie, a.numero, a.fechaingreso, a.mesiva, a.fecha, a.idtipoventa, c.desctipocompra AS tipoventa, ";
    $query.= "a.conceptomayor, a.fechapago, a.anulada, a.idrazonanulafactura, a.total, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, a.idtipofactura, g.desctipofact AS tipofactura, ";
    $query.= "'Sin contrato' AS nocontrato, a.idcontrato, a.anulada, a.idrazonanulafactura, a.fechaanula, i.razon ";
    $query.= "FROM factura a INNER JOIN tipocompra c ON c.id = a.idtipoventa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN cliente h ON h.id = a.idcliente LEFT JOIN razonanulacion i ON i.id = a.idrazonanulafactura ";
    $query.= "WHERE a.idcontrato = 0 AND a.idempresa = ".$idempresa." ";
    $query.= "ORDER BY 10, 5, 25";
    print $db->doSelectASJson($query);
});

$app->post('/lstventas', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "SELECT a.serieadmin, a.numeroadmin, a.id, b.idempresa, d.nomempresa, b.idcliente, h.nombre AS cliente, a.serie, a.numero, a.fechaingreso, a.mesiva, a.fecha, a.idtipoventa, c.desctipocompra AS tipoventa, ";
    $query.= "a.conceptomayor, a.fechapago, a.anulada, a.idrazonanulafactura, a.total, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, a.idtipofactura, g.desctipofact AS tipofactura, ";
    $query.= "b.nocontrato, a.idcontrato, a.anulada, a.idrazonanulafactura, a.fechaanula, i.razon, CONCAT(a.serieadmin, '-', a.numeroadmin) AS correlativo, g.siglas AS abreviatipofactura ";
    $query.= "FROM factura a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN tipocompra c ON c.id = a.idtipoventa INNER JOIN empresa d ON d.id = b.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura INNER JOIN cliente h ON h.id = b.idcliente ";
    $query.= "LEFT JOIN razonanulacion i ON i.id = a.idrazonanulafactura ";
    $query.= "WHERE b.idempresa = $d->idempresa ";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : '';
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : '';
    $query.= "UNION ";
    $query.= "SELECT a.serieadmin, a.numeroadmin, a.id, 0 AS idempresa, NULL AS nomempresa, a.idcliente, h.nombre AS cliente, a.serie, a.numero, a.fechaingreso, a.mesiva, a.fecha, a.idtipoventa, c.desctipocompra AS tipoventa, ";
    $query.= "a.conceptomayor, a.fechapago, a.anulada, a.idrazonanulafactura, a.total, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, a.idtipofactura, g.desctipofact AS tipofactura, ";
    $query.= "'Sin contrato' AS nocontrato, a.idcontrato, a.anulada, a.idrazonanulafactura, a.fechaanula, i.razon, CONCAT(a.serieadmin, '-', a.numeroadmin) AS correlativo, g.siglas AS abreviatipofactura ";
    $query.= "FROM factura a INNER JOIN tipocompra c ON c.id = a.idtipoventa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN cliente h ON h.id = a.idcliente LEFT JOIN razonanulacion i ON i.id = a.idrazonanulafactura ";
    $query.= "WHERE a.idcontrato = 0 AND a.idempresa = $d->idempresa ";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : '';
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : '';
    $query.= "ORDER BY 1, 2, 12, 7, 27";
    print $db->doSelectASJson($query);
});

$app->get('/getventa/:idventa', function($idventa){
    $db = new dbcpm();
    $query = "SELECT a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, a.retisr,  a.noformiva, a.noacciva, a.fechapagoformiva, a.mespagoiva, a.aniopagoiva, a.retiva,   a.id, b.idempresa, d.nomempresa, b.idcliente, h.nombre AS cliente, a.serie, a.numero, a.fechaingreso, a.mesiva, a.fecha, a.idtipoventa, c.desctipocompra AS tipoventa, ";
    $query.= "a.conceptomayor, a.fechapago, a.anulada, a.idrazonanulafactura, a.total, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, a.idtipofactura, g.desctipofact AS tipofactura, ";
    $query.= "b.nocontrato, a.idcontrato, a.anulada, a.idrazonanulafactura, a.fechaanula, i.razon, CONCAT(a.serieadmin, '-', a.numeroadmin) AS correlativo, j.simbolo AS monedafact, ";
    $query.= "IF(a.idmonedafact = 1, a.importetotal, a.importetotalcnv) AS importetotal, g.siglas AS abreviatipofactura, ";
    $query.= "(SELECT COUNT(id) FROM factura WHERE idtipofactura IN(9) AND idfacturaafecta = a.id) AS connc ";
    $query.= "FROM factura a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN tipocompra c ON c.id = a.idtipoventa INNER JOIN empresa d ON d.id = b.idempresa INNER JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "INNER JOIN tipofactura g ON g.id = a.idtipofactura INNER JOIN cliente h ON h.id = b.idcliente ";
    $query.= "LEFT JOIN razonanulacion i ON i.id = a.idrazonanulafactura LEFT JOIN moneda j ON j.id = a.idmonedafact ";
    $query.= "WHERE a.id = ".$idventa." ";
    $query.= "UNION ";
    $query.= "SELECT a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, a.retisr,  a.noformiva, a.noacciva, a.fechapagoformiva, a.mespagoiva, a.aniopagoiva, a.retiva,  a.id, 0 AS idempresa, NULL AS nomempresa, a.idcliente, h.nombre AS cliente, a.serie, a.numero, a.fechaingreso, a.mesiva, a.fecha, a.idtipoventa, c.desctipocompra AS tipoventa, ";
    $query.= "a.conceptomayor, a.fechapago, a.anulada, a.idrazonanulafactura, a.total, a.subtotal, a.iva, a.idmoneda, a.tipocambio, f.simbolo AS moneda, a.idtipofactura, g.desctipofact AS tipofactura, ";
    $query.= "'Sin contrato' AS nocontrato, a.idcontrato, a.anulada, a.idrazonanulafactura, a.fechaanula, i.razon, CONCAT(a.serieadmin, '-', a.numeroadmin) AS correlativo, j.simbolo AS monedafact, ";
    $query.= "IF(a.idmonedafact = 1, a.importetotal, a.importetotalcnv) AS importetotal, g.siglas AS abreviatipofactura, ";
    $query.= "(SELECT COUNT(id) FROM factura WHERE idtipofactura IN(9) AND idfacturaafecta = a.id) AS connc ";
    $query.= "FROM factura a INNER JOIN tipocompra c ON c.id = a.idtipoventa INNER JOIN moneda f ON f.id = a.idmoneda INNER JOIN tipofactura g ON g.id = a.idtipofactura ";
    $query.= "LEFT JOIN cliente h ON h.id = a.idcliente LEFT JOIN razonanulacion i ON i.id = a.idrazonanulafactura LEFT JOIN moneda j ON j.id = a.idmonedafact ";
    $query.= "WHERE a.id = ".$idventa;
    print $db->doSelectASJson($query);
});


function insertaDetalleContable($d, $idorigen){
    $db = new dbcpm();
    $origen = 3;
    //Inicia inserción automática de detalle contable de la factura

    $idempresa = (int)$db->getOneField("SELECT idempresa FROM contrato WHERE id = ".$d->idcontrato);
    $ctaiva = (int)$db->getOneField("SELECT idcuentacventa FROM tipocompra WHERE id = ".$d->idtipoventa);
    if($ctaiva == 0){ $ctaiva = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = 2"); }

    if($ctaiva > 0 && (float)$d->iva > 0){
        $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
        $query.= $origen.", ".$idorigen.", ".$ctaiva.", 0.00, ".round(((float)$d->iva * (float)$d->tipocambio), 2).", '".$d->conceptomayor."')";
        $db->doQuery($query);
    };
};


$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    $codmongface = $db->getOneField("SELECT codgface FROM moneda WHERE id = ".$d->idmoneda);

    $query = "INSERT INTO factura(idtipofactura, idcontrato, idcliente, serie, numero, fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, ";
    $query.= "total, subtotal, iva, idmoneda, tipocambio, totalletras, idempresa) ";
    $query.= "VALUES(".$d->idtipofactura.", ".$d->idcontrato.", ".$d->idcliente.", '".$d->serie."', '".$d->numero."', '".$d->fechaingresostr."', ".$d->mesiva.", ";
    $query.= "'".$d->fechastr."', ".$d->idtipoventa.", '".$d->conceptomayor."', ".$d->total.", ".$d->subtotal.", ".$d->iva.", ".$d->idmoneda.", ".$d->tipocambio.", ";
    $query.= "'".$n2l->to_word(round((float)$d->total, 2), $codmongface)."', ".$d->idempresa;
    $query.= ")";
    $db->doQuery($query);

    $lastid = $db->getLastId();
    //Inicia inserción automática de detalle contable de la factura
    insertaDetalleContable($d, $lastid);
    //Fin de inserción automática de detalle contable de la factura
    print json_encode(['lastid' => $lastid]);
});
//inicio modal isr
$app->post('/uisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->noformisr = $d->noformisr == '' ? 'NULL' : "'".$d->noformisr."'";
    $d->noaccisr = $d->noaccisr == '' ? 'NULL' : "'".$d->noaccisr."'";
    $d->fecpagoformisrstr = $d->fecpagoformisrstr == '' ? 'NULL' : "'".$d->fecpagoformisrstr."'";
    $d->mesisr = (int)$d->mesisr == 0 ? 'NULL' : $d->mesisr;
    $d->anioisr = (int)$d->anioisr == 0 ? 'NULL' : $d->anioisr;
    $d->idventa = $d->id;
    $query = "UPDATE factura SET noformisr = ".$d->noformisr.", noaccisr = ".$d->noaccisr.", fecpagoformisr = ".$d->fecpagoformisrstr.", mesisr = ".$d->mesisr.", anioisr = ".$d->anioisr." WHERE id = ".$d->id;
    $db->doQuery($query);
});
//inicio modal iva
$app->post('/uiva', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->noformiva = $d->noformiva == '' ? 'NULL' : "'".$d->noformiva."'";
    $d->noacciva = $d->noacciva == '' ? 'NULL' : "'".$d->noacciva."'";
    $d->fecpagoformivastr = $d->fecpagoformivastr == '' ? 'NULL' : "'".$d->fecpagoformivastr."'";
    $d->mespagoiva = (int)$d->mespagoiva == 0 ? 'NULL' : $d->mespagoiva;
    $d->aniopagoiva = (int)$d->aniopagoiva == 0 ? 'NULL' : $d->aniopagoiva;
    $d->idventa = $d->id;
    $query = "UPDATE factura SET noformiva = ".$d->noformiva.", noacciva = ".$d->noacciva.", fechapagoformiva = ".$d->fecpagoformivastr.", mespagoiva = ".$d->mespagoiva.", aniopagoiva = ".$d->aniopagoiva." WHERE id = ".$d->id;
    $db->doQuery($query);
});
$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    $codmongface = $db->getOneField("SELECT codgface FROM moneda WHERE id = ".$d->idmoneda);

    $query = "UPDATE factura SET ";
    $query.= "idtipofactura = ".$d->idtipofactura.", idcontrato = ".$d->idcontrato.", idcliente = ".$d->idcliente.", serie = '".$d->serie."', numero = '".$d->numero."', ";
    $query.= "fechaingreso = '".$d->fechaingresostr."', mesiva = ".$d->mesiva.", fecha = '".$d->fechastr."', idtipoventa = ".$d->idtipoventa.", conceptomayor = '".$d->conceptomayor."', ";
    $query.= "total = ".$d->total.", subtotal = ".$d->subtotal.", iva = ".$d->iva.", idmoneda = ".$d->idmoneda.", tipocambio = ".$d->tipocambio.", ";
    $query.= "totalletras  = '".$n2l->to_word((float)$d->total, $codmongface)."' ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);

    $origen = 3;
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
    $db->doQuery("DELETE FROM detallecontable WHERE origen = 3 AND idorigen = ".$d->id);
    $db->doQuery("DELETE FROM detfact WHERE idfactura = ".$d->id);
    $db->doQuery("DELETE FROM factura WHERE id = ".$d->id);
});

$app->post('/anula', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE factura SET idrazonanulafactura = ".$d->idrazonanulacion.", anulada = 1, fechaanula = '".$d->fechaanulastr."' ";
    //$query.= "total = 0.00, noafecto = 0.00, subtotal = 0.00, iva = 0.00, totalletras = ''";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
    $db->doQuery("UPDATE detallecontable SET anulado = 1 WHERE origen = 3 AND idorigen = ".$d->id);

    //Reversion de cobros
    $query = "UPDATE cargo SET facturado = 0, fechaanula = '$d->fechaanulastr' WHERE idfactura = $d->id";
    $db->doQuery($query);
    $query = "UPDATE lecturaservicio SET facturado = 0, estatus = 2 WHERE idfactura = $d->id";
    $db->doQuery($query);
});

$app->get('/clientes', function(){
    $db = new dbcpm();

    $query = "SELECT DISTINCT TRIM(nombre) AS cliente FROM factura WHERE fecha >= '2017-09-01' ORDER BY TRIM(nombre)";
    print $db->doSelectASJson($query);
});

$app->post('/modmontos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT CONCAT(a.serie, '-', a.numero) AS factura, b.simbolo AS moneda, FORMAT(a.iva, 2) AS iva, FORMAT(a.total, 2) AS total, FORMAT(a.retisr, 2) AS retisr, FORMAT(a.retiva, 2) AS retiva, ";
    $query.= "IF(a.idcontrato > 0, 1, 0) AS concontrato ";
    $query.= "FROM factura a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.id = $d->id";
    $old = $db->getQuery($query)[0];

    $query = "UPDATE factura SET total = $d->total, retisr = $d->retisr, retiva = $d->retiva WHERE (id = $d->id OR idfacturaafecta = $d->id)";
    $db->doQuery($query);

    $cambio = "Modificación a la venta $old->factura de los montos: TOTAL A COBRAR = $old->moneda $old->total, ";
    $cambio.= "RETENCIÓN ISR = $old->moneda $old->retisr, RETENCIÓN DE IVA = $old->moneda $old->retiva a los montos: ";
    $cambio.= "TOTAL A COBRAR = $old->moneda ".number_format((float)$d->total, 2).", ";
    $cambio.= "RETENCIÓN ISR = $old->moneda ".number_format((float)$d->retisr, 2).", RETENCIÓN DE IVA = $old->moneda ".number_format((float)$d->retiva, 2);
    $cambio.= " y la regeneración del detalle contable.";

    $query = "INSERT INTO auditoria (idusuario, tabla, cambio, fecha, tipo) VALUES(";
    $query.= "$d->idusuario, 'factura', '$cambio', NOW(), 'U'";
    $query.= ")";
    $db->doQuery($query);

    $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
    $data = ['ids' => $d->id, 'idcontrato' => $old->concontrato];
    $db->CallJSReportAPI('POST', $url, json_encode($data));
});

//API para detalle de ventas
$app->get('/lstdetfact/:idfactura', function($idfactura){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idfactura, a.cantidad, a.descripcion, a.preciounitario, a.preciotot ";
    $query.= "FROM detfact a ";
    $query.= "WHERE a.idfactura = ".$idfactura." ";
    $query.= "ORDER BY a.cantidad, a.descripcion";
    print $db->doSelectASJson($query);
});

$app->get('/getdetfact/:iddetfact', function($iddetfact){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idfactura, a.cantidad, a.descripcion, a.preciounitario, a.preciotot ";
    $query.= "FROM detfact a ";
    $query.= "WHERE a.id = ".$iddetfact;
    print $db->doSelectASJson($query);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "INSERT INTO detfact(idfactura, cantidad, descripcion, preciounitario, preciotot) ";
    $query.= "VALUES(".$d->idfactura.", ".$d->cantidad.", '".$d->descripcion."', ".$d->preciounitario.", ".$d->preciotot.")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE detfact SET ";
    $query.= "cantidad = ".$d->cantidad.", descripcion = '".$d->descripcion."', preciounitario = ".$d->preciounitario.", preciotot = ".$d->preciotot." ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
    print json_encode(['lastid' => $d->id]);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detfact WHERE id = ".$d->id);
});

//API para pantalla para agregar/modificar los formularios de retenciones de ISR/IVA
$app->post('/lstfactret', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id, a.idempresa, b.abreviatura AS empresa, a.nombre, a.nit, a.serie, a.numero, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, FORMAT(a.total, 2) AS total, FORMAT(a.iva, 2) AS iva, ";
    $query.= "FORMAT(a.retisr, 2) AS retisr, a.noformisr, a.noaccisr, a.fecpagoformisr, a.mesisr, a.anioisr, ";
    $query.= "FORMAT(a.retiva, 2) AS retiva, a.noformiva, a.noacciva, a.fechapagoformiva, a.mespagoiva, a.aniopagoiva ";
    $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query.= "WHERE a.anulada = 0 AND (a.retisr <> 0 OR a.retiva <> 0) ";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : '';
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : '';
    $query.= (int)$d->idempresa > 0 ? "AND a.idempresa = $d->idempresa " : '';
    $query.= $d->numero != '' ? "AND a.numero LIKE '%$d->numero%' " : '';
    $query.= "ORDER BY b.ordensumario, a.serie, a.numero";
    print $db->doSelectASJson($query);
});

function calculaMontos($cantidad, $valor, $tc) {
    $importe = new stdClass();

    $importe->bruto = round((float)$cantidad * (float)$valor, 2);
    $importe->brutocnv = round($importe->bruto / $tc, 2);

    $importe->neto = round($importe->bruto / 1.12, 2);
    $importe->netocnv = round($importe->neto / $tc, 2);

    $importe->iva = round($importe->bruto - $importe->neto, 2);
    $importe->ivacnv = round($importe->iva / $tc, 2);

    $importe->total = round($importe->neto + $importe->iva, 2);
    $importe->totalcnv = round($importe->total / $tc, 2);

    return $importe;
}

$app->post('/generandc', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if ((int)$d->tipo === 2) {
        $query = "SELECT GROUP_CONCAT(COLUMN_NAME SEPARATOR ', ') FROM information_schema.columns WHERE TABLE_SCHEMA = 'sayet' AND TABLE_NAME = 'factura' AND COLUMN_NAME NOT IN('id', 'serie', 'numero', 'idtipofactura')";
        $columnas = $db->getOneField($query);

        $query = "INSERT INTO factura($columnas) SELECT $columnas FROM factura WHERE id = $d->idfactura";
        $db->doQuery($query);
        $lastid = $db->getLastId();

        if((int)$lastid > 0) {
            $query = "UPDATE factura SET ";
            $query.= "idtipofactura = 9, fecha = DATE(NOW()), fechaingreso = DATE(NOW()), mesiva = MONTH(NOW()), ";
            $query.= "firmaelectronica = NULL, respuestagface = NULL, idfacturaafecta = $d->idfactura ";
            $query.= "WHERE id = $lastid";
            $db->doQuery($query);

            $query = "SELECT GROUP_CONCAT(COLUMN_NAME SEPARATOR ', ') AS columnas FROM information_schema.columns WHERE TABLE_SCHEMA = 'sayet' AND TABLE_NAME = 'detfact' AND COLUMN_NAME NOT IN('id', 'idfactura')";
            $coldet = $db->getOneField($query);

            $query = "INSERT INTO detfact(idfactura, $coldet) SELECT $lastid, $coldet FROM detfact WHERE idfactura = $d->idfactura";
            $db->doQuery($query);

            //Inserción del detalle contable de la NC
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado)
            SELECT 3, $lastid, idcuenta, IF(debe = 0, haber, 0.00) AS debe, IF(haber = 0, debe, 0.00) AS haber, conceptomayor, activada, anulado
            FROM detallecontable WHERE origen = 3 and idorigen = $d->idfactura";
            $db->doQuery($query);

            //Liberación de cargos atados a la factura que se le generó NdC
            $query = "UPDATE cargo SET facturado = 0, idfactura = 0 WHERE facturado = 1 AND idfactura = $d->idfactura";
            $db->doQuery($query);
            $query = "UPDATE lecturaservicio SET estatus = 2, facturado = 0, idfactura = 0 WHERE estatus = 3 AND facturado = 1 AND idfactura = $d->idfactura";
            $db->doQuery($query);
            $query = "UPDATE factura SET pagada = 1 WHERE id = $d->idfactura";
            $db->doQuery($query);
        }
    } else {
        $query = "SELECT * FROM factura WHERE id = $d->idfactura";
        $factura = $db->getQuery($query)[0];
        $n2l = new NumberToLetterConverter();

        $p = calculaMontos(1, $d->monto, (float)$factura->tipocambio);
        $montoletras = (int)$factura->idmonedafact == 1 ? $n2l->to_word($p->total, 'GTQ') : $n2l->to_word($p->totalcnv, 'USD');
        
        $query = "INSERT INTO factura(";
        $query.= "idempresa, idtipofactura, idcontrato, idcliente, ";
        $query.= "fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, iva, ";
        $query.= "total, subtotal, totalletras, idmoneda, tipocambio, ";
        $query.= "retisr, retiva, totdescuento, nit, nombre, direccion, idmonedafact,";
        $query.= "subtotalcnv, totalcnv, retivacnv, retisrcnv, totdescuentocnv,";
        $query.= "importebruto, importeneto, importeiva, importetotal, descuentosiniva, descuentoiva, ";
        $query.= "importebrutocnv, importenetocnv, importeivacnv, importetotalcnv, descuentosinivacnv, descuentoivacnv, ";
        $query.= "serieadmin, numeroadmin, porretiva, idfacturaafecta, idproyecto";
        $query.= ") VALUES (";
        $query.= "$factura->idempresa, 9, $factura->idcontrato, $factura->idcliente, ";
        $query.= "NOW(), MONTH(NOW()), DATE(NOW()), $factura->idtipoventa, '$d->descripcion', $p->iva, ";
        $query.= "$p->total, $p->total, '$montoletras', 1, $factura->tipocambio, ";
        $query.= "0.00, 0.00, 0.00, '$factura->nit', '$factura->nombre', '$factura->direccion', $factura->idmonedafact, ";
        $query.= "$p->totalcnv, $p->totalcnv, 0.00, 0.00, 0.00, ";
        $query.= "$p->bruto, $p->neto, $p->iva, $p->total, 0.00, 0.00, ";
        $query.= "$p->brutocnv, $p->netocnv, $p->ivacnv, $p->totalcnv, 0.00, 0.00, ";
        $query.= "'$factura->serieadmin', $factura->numeroadmin, $factura->porretiva, $d->idfactura, $factura->idproyecto";
        $query.= ")";

        $db->doQuery($query);
        $lastid = $db->getLastId();
        
        if ((int)$lastid > 0) {
            $query = "INSERT INTO detfact(";
            $query.= "idfactura, cantidad, descripcion, preciounitario, preciounitariocnv, preciotot, idtiposervicio, mes, anio, descuento, ";
            $query.= "conceptoadicional, descuentocnv, importebruto, importeneto, importeiva, importetotal, descuentosiniva, descuentoiva, ";
            $query.= "importebrutocnv, importenetocnv, importeivacnv, importetotalcnv, descuentosinivacnv, descuentoivacnv, porcentajedescuento, ";
            $query.= "precio, preciocnv, descripcionlarga";                
            $query.= ") VALUES(";                
            $query.= "$lastid, 1, '$d->descripcion', $p->bruto, $p->brutocnv, $p->total, $d->idtiposervicio, MONTH(NOW()), YEAR(NOW()), 0.00, ";
            $query.= "'$d->descripcion', 0.00, $p->bruto, $p->neto, $p->iva, $p->total, 0.00, 0.00, ";
            $query.= "$p->brutocnv, $p->netocnv, $p->ivacnv, $p->totalcnv, 0.00, 0.00, 0.00,";
            $query.= "$p->bruto, $p->brutocnv, '$d->descripcion'";
            $query.= ")";

            $db->doQuery($query);
        }
    }

    print json_encode(['exito' => true, 'mensaje' => 'Nota de crédito generada con éxito.']);
});

$app->run();