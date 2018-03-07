<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para recibos de proveedores
$app->get('/lstrecibosprovs/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, a.fechacrea, a.idtranban, b.tipotrans, b.numero, c.nombre, d.simbolo, b.monto, a.idempresa ";
    $query.= "FROM reciboprov a LEFT JOIN tranban b ON b.id = a.idtranban LEFT JOIN banco c ON c.id = b.idbanco LEFT JOIN moneda d ON d.id = c.idmoneda ";
    $query.= "WHERE a.idempresa = ".$idempresa." ";
    $query.= "ORDER BY a.id";
    print $db->doSelectASJson($query);
});

$app->get('/getreciboprov/:idrecibo', function($idrecibo){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, a.fechacrea, a.idtranban, b.tipotrans, b.numero, c.nombre, d.simbolo, b.monto, a.idempresa ";
    $query.= "FROM reciboprov a LEFT JOIN tranban b ON b.id = a.idtranban LEFT JOIN banco c ON c.id = b.idbanco LEFT JOIN moneda d ON d.id = c.idmoneda ";
    $query.= "WHERE a.id = ".$idrecibo;
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO reciboprov(idempresa, fecha, fechacrea, idtranban) VALUES(".$d->idempresa.",'".$d->fechastr."', NOW(), ".$d->idtranban.")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE reciboprov SET fecha = '".$d->fechastr."', idtranban = ".$d->idtranban." WHERE id = ".$d->id);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM reciboprov WHERE id = ".$d->id);
});

$app->get('/lsttranban/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, b.nombre, a.tipotrans, a.numero, c.simbolo, a.monto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.tipotrans IN('B') AND b.idempresa = $idempresa ";
    $query.= "ORDER BY a.fecha, b.nombre, a.tipotrans, a.numero";
    print $db->doSelectASJson($query);
});

//API para detalle de recibos de proveedores
$app->get('/lstdetrecprov/:idrecprov', function($idrecprov){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idrecprov, a.origen, a.idorigen, a.arebajar, ";
    $query.= "CONCAT(c.nombre, ' - ', b.serie, ' ', b.documento, ' - Total: ', d.simbolo, ' ', b.totfact, ' - Pendiente: ', d.simbolo, ' ', (b.totfact - IFNULL(e.montopagado, 0.00))) AS cadena ";
    $query.= "FROM detrecprov a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN proveedor c ON c.id = b.idproveedor INNER JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) e ON a.id = e.idcompra ";
    $query.= "WHERE a.idrecprov = $idrecprov AND a.origen = 2 ";
    $query.= "UNION ALL ";
    $query.= "SELECT a.id, a.idrecprov, a.origen, a.idorigen, a.arebajar, ";
    $query.= "CONCAT(d.desctiporeembolso,' - No. ',LPAD(b.id, 5, '0'), ' - ', DATE_FORMAT(b.finicio, '%d/%m/%Y'),  ' - ', c.nombre, ' - Q ', IF(ISNULL(e.totreembolso), 0.00, e.totreembolso)) AS cadena ";
    $query.= "FROM detrecprov a INNER JOIN reembolso b ON b.id = a.idorigen INNER JOIN beneficiario c ON c.id = b.idbeneficiario INNER JOIN tiporeembolso d ON d.id = b.idtiporeembolso ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) e ON b.id = e.idreembolso ";
    $query.= "WHERE a.idrecprov = $idrecprov AND a.origen = 5 ";
    $query.= "ORDER BY 3";
    print $db->doSelectASJson($query);
});

$app->get('/getdetrecprov/:iddetrecprov', function($iddetrecprov){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idrecprov, a.origen, a.idorigen, a.arebajar, ";
    $query.= "CONCAT(c.nombre, ' - ', b.serie, ' ', b.documento, ' - Total: ', d.simbolo, ' ', b.totfact, ' - Pendiente: ', d.simbolo, ' ', (b.totfact - IFNULL(e.montopagado, 0.00))) AS cadena ";
    $query.= "FROM detrecprov a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN proveedor c ON c.id = b.idproveedor INNER JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) e ON a.id = e.idcompra ";
    $query.= "WHERE a.id = $iddetrecprov AND a.origen = 2 ";
    $query.= "UNION ALL ";
    $query.= "SELECT a.id, a.idrecprov, a.origen, a.idorigen, a.arebajar, ";
    $query.= "CONCAT(d.desctiporeembolso,' - No. ',LPAD(b.id, 5, '0'), ' - ', DATE_FORMAT(b.finicio, '%d/%m/%Y'),  ' - ', c.nombre, ' - Q ', IF(ISNULL(e.totreembolso), 0.00, e.totreembolso)) AS cadena ";
    $query.= "FROM detrecprov a INNER JOIN reembolso b ON b.id = a.idorigen INNER JOIN beneficiario c ON c.id = b.idbeneficiario INNER JOIN tiporeembolso d ON d.id = b.idtiporeembolso ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) e ON b.id = e.idreembolso ";
    $query.= "WHERE a.id = $iddetrecprov AND a.origen = 5 ";
    $query.= "ORDER BY 3";
    print $db->doSelectASJson($query);
});

$app->get('/docspend/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT 2 AS origen, a.id AS idorigen, CONCAT(b.nombre, ' - ', e.siglas, ' ', a.serie, ' ', a.documento, ' - Total: ', d.simbolo, ' ', a.totfact, ' - Pendiente: ', d.simbolo, ' ', (a.totfact - IFNULL(c.montopagado, 0.00))) AS cadena, ";
    $query.= "(a.totfact - IFNULL(c.montopagado, 0.00)) AS saldo ";
    $query.= "FROM compra a LEFT JOIN proveedor b ON b.id = a.idproveedor LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra LEFT JOIN moneda d ON d.id = a.idmoneda ";
    $query.= "LEFT JOIN tipofactura e ON e.id = a.idtipofactura ";
    $query.= "WHERE (a.totfact - IFNULL(c.montopagado, 0.00)) > 0.00 AND a.idempresa = $idempresa AND a.idreembolso = 0 ";
    $query.= "UNION ";
    $query.= "SELECT 5 AS origen, a.id AS idorigen, CONCAT(b.nombre, ' - ', c.desctiporeembolso, ' - No. ', LPAD(a.id, 5, '0'), ' - ', DATE_FORMAT(a.finicio, '%d/%m/%Y'), ' - Q ', IF(ISNULL(d.totreembolso), 0.00, d.totreembolso)) AS cadena, ";
    $query.= "IF(ISNULL(d.totreembolso), 0.00, d.totreembolso) AS saldo ";
    $query.= "FROM reembolso a INNER JOIN beneficiario b ON b.id = a.idbeneficiario INNER JOIN tiporeembolso c ON c.id = a.idtiporeembolso ";
    $query.= "LEFT JOIN (SELECT idreembolso, SUM(totfact) AS totreembolso FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) d ON a.id = d.idreembolso ";
    $query.= "WHERE a.idempresa = $idempresa AND a.idtranban = 0 ";
    $query.= "ORDER BY 3";
    print $db->doSelectASJson($query);
});

function cierreReembolso($db, $d){
    $estatus = (int)$db->getOneField("SELECT estatus FROM reembolso WHERE id = ".$d->idorigen);
    if($estatus == 2){
        $query = "UPDATE reembolso SET idtranban = ".$d->idrecprov.", esrecprov = 1 WHERE id = ".$d->idorigen;
        $db->doQuery($query);
    }else{
        $idempresa = (int)$db->getOneField("SELECT idempresa FROM reciboprov WHERE id = ".$d->idrecprov);
        $ffinreem = $db->getOneField("SELECT fecha FROM reciboprov WHERE id = ".$d->idrecprov);
        $query = "UPDATE reembolso SET estatus = 2, idtranban = ".$d->idrecprov.", ffin = '$ffinreem', esrecprov = 1 WHERE id = ".$d->idorigen;
        $db->doQuery($query);
        //Generación del detalle contable del reembolso Origen = 5
        $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) ";
        $query.= "SELECT 5 AS origen, a.idreembolso AS idorigen, b.idcuenta, b.debe, b.haber, b.conceptomayor ";
        $query.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 INNER JOIN cuentac d ON d.id = b.idcuenta WHERE a.idreembolso = $d->idorigen ";
        $query.= "ORDER BY b.idorigen, d.precedencia DESC, d.nombrecta";
        $db->doQuery($query);
        $ctaporliquidar = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = $idempresa AND idtipoconfig = 5");
        if($ctaporliquidar > 0){
            $query = "SELECT SUM(b.debe) AS debe FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->idorigen;
            $haber = (float)$db->getOneField($query);
            $query = "SELECT SUM(b.haber) AS haber FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen AND b.origen = 2 WHERE a.idreembolso = ".$d->idorigen;
            $restar = (float)$db->getOneField($query);
            $query = "INSERT INTO detallecontable (origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= "5, ".$d->idorigen.", ".$ctaporliquidar.", 0.00, ".round(($haber - $restar), 2).", 'Reembolso No. ".$d->idorigen."'";
            $query.= ")";
            $db->doQuery($query);
        }
    }
}

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detrecprov(idrecprov, origen, idorigen, arebajar) VALUES(".$d->idrecprov.",".$d->origen.", ".$d->idorigen.", ".$d->arebajar.")";
    $db->doQuery($query);
    $lastid = (int)$db->getLastId();
    $origen = (int)$d->origen;

    switch($origen){
        case 2:
            $query = "INSERT INTO detpagocompra(idcompra, idtranban, monto, esrecprov) VALUES($d->idorigen, $d->idrecprov, $d->arebajar, 1)";
            $db->doQuery($query);
            break;
        case 5:
            cierreReembolso($db, $d);
            break;
    }

    print json_encode(['lastid' => $lastid]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE detrecprov SET arebajar = ".$d->arebajar." WHERE id = ".$d->id);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detrecprov WHERE id = ".$d->id);
});



$app->run();