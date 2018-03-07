<?php
require 'vendor/autoload.php';
require_once 'db.php';

//header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para recibos de clientes
//Inicio modificacion
$app->post('/lstreciboscli', function(){ 
    $d = json_decode(file_get_contents('php://input'));

    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, b.nombre AS cliente, c.tipotrans, c.numero AS notranban, e.nombre, ";
    $query.= "f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea ";
    $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion ";
    $query.= "LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.idempresa = " . $d->idempresa . " ";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : "" ;
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : "" ;
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "" ;
    $query.= (int)$d->recibostr != 0 ? "AND a.numero = $d->recibostr " : "" ;
    $query.= $d->clientestr != '' ? "AND b.nombre LIKE '%$d->clientestr%' " : "" ;
    $query.= $d->ban_numerostr != '' ? "AND c.numero = '$d->ban_numerostr' " : "" ;
    $query.= $d->ban_cuentastr != '' ? "AND e.nombre LIKE '%$d->ban_cuentastr%' " : "" ;
    $query.= " UNION ALL ";
    $query.= "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, 'Facturas contado (Clientes varios)' AS cliente, c.tipotrans, c.numero AS notranban, e.nombre, ";
    $query.= "f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea ";
    $query.= "FROM recibocli a LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion ";
    $query.= "LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.idempresa = " . $d->idempresa . " ";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : "" ;
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : "" ;
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "" ;
    $query.= (int)$d->recibostr != 0 ? "AND a.numero = $d->recibostr " : "" ;
    $query.= $d->ban_numerostr != '' ? "AND c.numero = '$d->ban_numerostr' " : "" ;
    $query.= $d->ban_cuentastr != '' ? "AND e.nombre LIKE '%$d->ban_cuentastr%' " : "" ;
    $query.= "ORDER BY 18, 19";
    print $db->doSelectASJson($query);
});
//Fin modificacion
$app->get('/getrecibocli/:idrecibo', function($idrecibo){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, b.nombre AS cliente, c.tipotrans, c.numero AS notranban, e.nombre, ";
    $query.= "f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea ";
    $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion ";
    $query.= "LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.id = $idrecibo";
    $query.= " UNION ALL ";
    $query.= "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, 'Facturas contado (Clientes varios)' AS cliente, c.tipotrans, c.numero AS notranban, e.nombre, ";
    $query.= "f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea ";
    $query.= "FROM recibocli a LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion ";
    $query.= "LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.id = $idrecibo";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO recibocli(idempresa, fecha, fechacrea, idcliente, espropio, idtranban, serie, numero, usuariocrea) VALUES(";
    $query.= "$d->idempresa,'$d->fechastr', NOW(), $d->idcliente, $d->espropio, $d->idtranban, '$d->serie', $d->numero, '$d->usuariocrea'";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE recibocli SET ";
    $query.= "fecha = '$d->fechastr', idcliente = $d->idcliente, espropio = $d->espropio, idtranban = $d->idtranban, serie = '$d->serie', numero = $d->numero, usuariocrea = '$d->usuariocrea' ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    //Rony 2017-11-21 Mantiene los registro de facturas aplicadas al recibo en un array
    $datos = [];
    $query ="SELECT * FROM detcobroventa WHERE idrecibocli = $d->id";
    $datos = $db->getQuery($query);

    //Rony 2017-11-21 Poner como NO pagada las facturas aplicdas en recibo eliminado
    $registros = count($datos);
    for($i = 0; $i < $registros; $i++){
        $registro = $datos[$i];

        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $registro->idfactura";
        $db->doQuery($query);
    }

    // Elimina registros del recibo, detalle contable y facturas aplicadas
    $db->doQuery("DELETE FROM detallecontable WHERE origen = 8 AND idorigen = $d->id");
    $db->doQuery("DELETE FROM detcobroventa WHERE idrecibocli = $d->id");
    $db->doQuery("DELETE FROM recibocli WHERE id = ".$d->id);

});

$app->post('/anula', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE recibocli SET anulado = 1, idrazonanulacion = $d->idrazonanulacion, fechaanula = '$d->fechaanulastr' WHERE id = $d->id";
    $db->doQuery($query);
    $query = "UPDATE detallecontable SET activada = 0, anulado = 1 WHERE origen = 8 AND idorigen = $d->id";
    $db->doQuery($query);
});

$app->get('/lsttranban/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, b.nombre, a.tipotrans, a.numero, c.simbolo, a.monto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.tipotrans IN('D', 'R') AND b.idempresa = $idempresa ";
    $query.= "ORDER BY a.fecha, b.nombre, a.tipotrans, a.numero";
    //echo $query;
    print $db->doSelectASJson($query);
});

$app->get('/docspend/:idempresa/:idcliente', function($idempresa, $idcliente){
    $db = new dbcpm();
    $query = "SELECT a.id, c.siglas, a.serie, a.numero, a.fecha, b.simbolo, a.total, IF(ISNULL(d.cobrado), 0.00, d.cobrado) AS cobrado, ";
    $query.= "(a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo, ";

    $query.= "CONCAT(c.siglas, ' - ', a.serie, ' ', a.numero, ' - ', DATE_FORMAT(a.fecha, '%d/%m/%Y'), ' - Total: ', b.simbolo, ' ', TRUNCATE(a.total, 2),  ' - Abonado: ', ";
    $query.= "IF(ISNULL(d.cobrado), 0.00, TRUNCATE(d.cobrado, 2)),  ' - Saldo: ',TRUNCATE((a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)), 2)) AS cadena ";

    $query.= "FROM factura a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN tipofactura c ON c.id = a.idtipofactura ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.anulada = 0 AND a.idempresa = $idempresa AND a.pagada = 0 AND (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) > 0 AND a.idcliente = $idcliente ";
    $query.= "ORDER BY a.fecha";
    print $db->doSelectASJson($query);
});

//API para detalle de recibos de clientes
$app->get('/lstdetreccli/:idrecibo', function($idrecibo){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idfactura, a.idrecibocli, d.siglas, b.serie, b.numero, b.fecha, c.simbolo, b.total, a.monto, a.interes ";
    $query.= "FROM detcobroventa a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN tipofactura d ON d.id = b.idtipofactura ";
    $query.= "WHERE a.idrecibocli = $idrecibo ";
    $query.= "ORDER BY b.fecha";
    print $db->doSelectASJson($query);
});

$app->get('/getdetreccli/:iddetrec', function($iddetrec){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idfactura, a.idrecibocli, d.siglas, b.serie, b.numero, b.fecha, c.simbolo, b.total, a.monto, a.interes ";
    $query.= "FROM detcobroventa a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN tipofactura d ON d.id = b.idtipofactura ";
    $query.= "WHERE a.id = $iddetrec";
    print $db->doSelectASJson($query);
});

function setFacturaPagada($db, $d){
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $d->idfactura LIMIT 1";
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if(!$haypendiente){
        $query = "UPDATE factura SET pagada = 1, fechapago = (SELECT fecha FROM recibocli WHERE id = $d->idrecibocli) WHERE id = $d->idfactura";
        $db->doQuery($query);
    }    
}

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detcobroventa(idfactura, idrecibocli, monto, interes, esrecprov) VALUES($d->idfactura, $d->idrecibocli, $d->monto, $d->interes, 1)";
    $db->doQuery($query);

    //Poner como pagada la factura si su saldo es 0.00
    setFacturaPagada($db, $d);
    print json_encode(['lastid' => $db->getLastId()]);
});
//esto es el modelo para actualizar el campo de abono
$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE detcobroventa SET monto = $d->monto, interes = $d->interes WHERE id = $d->id");

    //Rony 2017-11-16 Editar monto abono
    //Obtiene saldo de factura
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $d->idfactura LIMIT 1";
    
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if($haypendiente){
        //Poner como NO pagada la factura
        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $d->idfactura";
        $db->doQuery($query);
    } else {
        //Poner como pagada la factura si su saldo es 0.00
        setFacturaPagada($db, $d);
    }
    //Rony 2017-11-16 Editar monto abono

});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detcobroventa WHERE id = $d->id");

    //Poner como NO pagada la factura
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $d->idfactura LIMIT 1";
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if($haypendiente){
        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $d->idfactura";
        $db->doQuery($query);
    }
});

$app->run();