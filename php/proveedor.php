<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para encabezado de proveedores
$app->get('/lstprovs', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.chequesa, a.retensionisr, a.diascred, a.limitecred, ";
    $query.= "a.pequeniocont, CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov ";
    $query.= "FROM proveedor a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getprov/:idprov', function($idprov){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.chequesa, a.retensionisr, a.diascred, a.limitecred, ";
    $query.= "a.pequeniocont, CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov ";
    $query.= "FROM proveedor a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.id = ".$idprov;
    print $db->doSelectASJson($query);
});

$app->get('/getprovbynit/:nit', function($nit){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.chequesa, a.retensionisr, a.diascred, a.limitecred, ";
    $query.= "a.pequeniocont, CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov ";
    $query.= "FROM proveedor a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE TRIM(a.nit) = '".trim($nit)."' LIMIT 1";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO proveedor(nit, nombre, direccion, telefono, correo, concepto, chequesa, ";
    $query.= "retensionisr, diascred, limitecred, pequeniocont, idmoneda, tipocambioprov) ";
    $query.= "VALUES('".$d->nit."', '".$d->nombre."', '".$d->direccion."', '".$d->telefono."', '".$d->correo."', '".$d->concepto."', '".$d->chequesa."', ";
    $query.= $d->retensionisr.", ".$d->diascred.", ".$d->limitecred.", ".$d->pequeniocont.", ".$d->idmoneda.", ".$d->tipocambioprov.")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE proveedor SET nit = '".$d->nit."', nombre = '".$d->nombre."', direccion = '".$d->direccion."', ";
    $query.= "telefono = '".$d->telefono."', correo = '".$d->correo."', concepto = '".$d->concepto."', ";
    $query.= "chequesa = '".$d->chequesa."', retensionisr = ".$d->retensionisr.", diascred = ".$d->diascred.", ";
    $query.= "limitecred = ".$d->limitecred.", pequeniocont = ".$d->pequeniocont.", idmoneda = ".$d->idmoneda.", tipocambioprov = ".$d->tipocambioprov." ";
    $query.= "WHERE id = ".$d->id;
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

$app->run();