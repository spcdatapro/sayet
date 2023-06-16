<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para encabezado de proveedores
$app->get('/lstprovs(/:todos)', function($todos = 0){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.chequesa, a.retensionisr, a.diascred, a.limitecred, a.idbancopais, ";
    $query.= "a.pequeniocont, CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, a.tipcuenta, b.nommoneda AS moneda, a.tipocambioprov, a.debaja, a.cuentabanco, a.recurrente, a.identificacion ";
    $query.= "FROM proveedor a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= (int)$todos === 0 ? 'WHERE a.debaja = 0 ' : '';
    $query.= "ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getprov/:idprov', function($idprov){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.chequesa, a.retensionisr, a.diascred, a.limitecred, a.idbancopais, ";
    $query.= "a.pequeniocont, CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.tipcuenta, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov, a.debaja, a.cuentabanco, a.recurrente, a.identificacion, a.retensioniva ";
    $query.= "FROM proveedor a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.id = ".$idprov;
    print $db->doSelectASJson($query);
});

$app->get('/getprovbynit/:nit', function($nit){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.chequesa, a.retensionisr, a.diascred, a.limitecred, a.idbancopais, ";
    $query.= "a.pequeniocont, CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.tipcuenta, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov, a.debaja, a.cuentabanco, a.recurrente, a.identificacion, a.retensioniva ";
    $query.= "FROM proveedor a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE TRIM(a.nit) = '".trim($nit)."' LIMIT 1";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if (!isset($d->debaja)) { $d->debaja = 0; }    
    if (!isset($d->cuentabanco)) { 
        $d->cuentabanco = 'NULL'; 
    } else {
        $d->cuentabanco = "'$d->cuentabanco'";
    }
    if (!isset($d->identificacion)) { 
        $d->identificacion = 'NULL'; 
    } else {
        $d->identificacion = "'$d->identificacion'";
    }
    if (!isset($d->recurrente)) { $d->recurrente = 0; }
    if (!isset($d->tipcuenta)) { $d->tipcuenta = 'NULL'; }

    $query = "INSERT INTO proveedor(nit, nombre, direccion, telefono, correo, concepto, chequesa, ";
    $query.= "retensionisr, diascred, limitecred, pequeniocont, idmoneda, tipocambioprov, debaja, cuentabanco, recurrente, idbancopais, tipcuenta, identificacion, retensioniva) ";
    $query.= "VALUES('$d->nit', '$d->nombre', '$d->direccion', '$d->telefono', '$d->correo', '$d->concepto', '$d->chequesa', ";
    $query.= "$d->retensionisr, $d->diascred, $d->limitecred, $d->pequeniocont, $d->idmoneda, $d->tipocambioprov, $d->debaja, $d->cuentabanco, $d->recurrente, $d->idbancopais, $d->tipcuenta, $d->identificacion, ";
    $query.= "$d->retensioniva)";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if (!isset($d->debaja)) { $d->debaja = 0; }
    if (!isset($d->cuentabanco)) { 
        $d->cuentabanco = 'NULL'; 
    } else {
        $d->cuentabanco = "'$d->cuentabanco'";
    }
    if (!isset($d->identificacion)) { 
        $d->identificacion = 'NULL'; 
    } else {
        $d->identificacion = "'$d->identificacion'";
    }
    if (!isset($d->recurrente)) { $d->recurrente = 0; }
    if (!isset($d->tipcuenta)) { $d->tipcuenta = 'NULL'; }

    $query = "UPDATE proveedor SET nit = '$d->nit', nombre = '$d->nombre', direccion = '$d->direccion', telefono = '$d->telefono', correo = '$d->correo', concepto = '$d->concepto', ";
    $query.= "chequesa = '$d->chequesa', retensionisr = $d->retensionisr, diascred = $d->diascred, limitecred = $d->limitecred, pequeniocont = $d->pequeniocont, ";
    $query.= "idmoneda = $d->idmoneda, tipocambioprov = $d->tipocambioprov, debaja = $d->debaja, cuentabanco = $d->cuentabanco, "; 
    $query.= "idbancopais = $d->idbancopais, recurrente = $d->recurrente, tipcuenta = $d->tipcuenta, identificacion = $d->identificacion, retensioniva = $d->retensioniva ";
    $query.= "WHERE id = $d->id";
    // print $query;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $tieneCompras = (int)$db->getOneField("SELECT COUNT(id) FROM compra WHERE idproveedor = $d->id");
    if($tieneCompras > 0){
        print json_encode(['tienecompras' => 1]);
    }else{
        $query = "DELETE FROM proveedor WHERE id = ".$d->id;
        $db->doQuery($query);
        $query = "DELETE FROM detcontprov WHERE idproveedor = ".$d->id;
        $db->doQuery($query);
        print json_encode(['tienecompras' => 0]);
    }    
});

//API para detalle contable de proveedores
$app->get('/detcontprov/:idprov', function($idprov){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idproveedor, b.nombre, c.idempresa, d.nomempresa, a.idcuentac, c.codigo, c.nombrecta, a.idcxp, e.nombrecta AS cuentacxp, e.codigo AS codigocxp ";
    $query.= "FROM detcontprov a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "INNER JOIN empresa d ON d.id = c.idempresa ";
    $query.= "LEFT JOIN cuentac e ON e.id = a.idcxp ";
    $query.= "WHERE a.idproveedor = ".$idprov." ";
    $query.= "ORDER BY d.nomempresa, c.codigo, c.nombrecta";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getdetcontprov/:iddetcont', function($iddetcont){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idproveedor, b.nombre, c.idempresa, d.nomempresa, a.idcuentac, c.codigo, c.nombrecta, a.idcxp, e.nombrecta AS cuentacxp, e.codigo AS codigocxp ";
    $query.= "FROM detcontprov a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "INNER JOIN empresa d ON d.id = c.idempresa ";
    $query.= "LEFT JOIN cuentac e ON e.id = a.idcxp ";
    $query.= "WHERE a.id = ".$iddetcont;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/lstdetcontprov/:idprov/:idempresa', function($idprov, $idempresa){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.idcuentac, CONCAT('(', b.codigo,') ', b.nombrecta) as cuentac ";
    $query.= "FROM detcontprov a INNER JOIN cuentac b ON b.id = a.idcuentac ";
    $query.= "WHERE a.idproveedor = ".$idprov." AND b.idempresa = $idempresa ";
    $query.= "ORDER BY b.codigo";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detcontprov(idproveedor, idcuentac, idcxp) ";
    $query.= "VALUES($d->idproveedor, $d->idcuentac, $d->idcxp)";
    $db->doQuery($query);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detcontprov SET idcuentac = $d->idcuentac, idcxp = $d->idcxp WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM detcontprov WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/ubp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE proveedor SET bancopais = $d->idbancopais WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->get('/lstdetcontprovifnull/:idprov/:idempresa', function($idprov, $idempresa){
    $db = new dbcpm();
    $nomcuenta = $db->getOneField(
        "SELECT b.nombrecta AS nombre
        FROM 
            detcontprov a 
                INNER JOIN 
            cuentac b ON b.id = a.idcuentac
        WHERE 
            a.idproveedor = $idprov
        ORDER BY b.codigo
        LIMIT 1 ");

    if($nomcuenta != NULL){
        $idcuentac = $db->getOneField(
            "SELECT 
                id 
            FROM 
                cuentac 
            WHERE 
                nombrecta LIKE '%$nomcuenta%' 
                    AND idempresa = $idempresa");

        $insertcc = "INSERT INTO detcontprov(idproveedor, idcuentac, idcxp) VALUES ($idprov, $idcuentac, 0)";
        $db->doQuery($insertcc);

        $conn = $db->getConn();
        $query = 
        "SELECT 
            a.idcuentac,
            CONCAT('(', b.codigo, ') ', b.nombrecta) AS cuentac
        FROM
            detcontprov a
                INNER JOIN
            cuentac b ON b.id = a.idcuentac
        WHERE
            a.idproveedor = $idprov AND idempresa = $idempresa
        ORDER BY b.codigo
        LIMIT 1";
        $data = $conn->query($query)->fetchAll(5);
        print json_encode($data);
    }
});

$app->run();