<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para encabezado de benefiaciario
$app->get('/lstbene(/:todos)', function($todos = 0){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.idbancopais, a.tipcuenta, a.identificacion, a.fondo, ";
    $query.= "CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov, a.debaja, a.cuentabanco ";
    $query.= "FROM beneficiario a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= (int)$todos === 0 ? 'WHERE a.debaja = 0 ' : '';
    $query.= "ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getbene/:idbene', function($idbene){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.idbancopais, a.tipcuenta, a.identificacion, a.fondo, ";
    $query.= "CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov, a.debaja, a.cuentabanco ";
    $query.= "FROM beneficiario a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.id = ".$idbene;
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

    if (!isset($d->tipcuenta)) { $d->tipcuenta = 0; }
    if (!isset($d->identificacion)) { $d->identificacion = 0; }
    if (!isset($d->fondo)) { $d->fondo = 0; }

    $query = "INSERT INTO beneficiario(nit, nombre, direccion, telefono, correo, concepto, idbancopais, tipcuenta, identificacion, idmoneda, tipocambioprov, debaja, cuentabanco, fondo ) ";
    $query.= "VALUES('$d->nit', '$d->nombre', '$d->direccion', '$d->telefono', '$d->correo', '$d->concepto', $d->idbancopais, $d->tipcuenta, $d->identificacion, ";
    $query.= "$d->idmoneda, $d->tipocambioprov, $d->debaja, $d->cuentabanco, $d->fondo)";
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

    if (!isset($d->tipcuenta)) { $d->tipcuenta = 0; }
    if (!isset($d->identificacion)) { $d->identificacion = 0; }
    if (!isset($d->fondo)) { $d->fondo = 0; }

    $query = "UPDATE beneficiario SET nit = '$d->nit', nombre = '$d->nombre', direccion = '$d->direccion', idbancopais = $d->idbancopais, tipcuenta = $d->tipcuenta, ";
    $query.= "telefono = '$d->telefono', correo = '$d->correo', concepto = '$d->concepto',  fondo = $d->fondo, ";
    $query.= "idmoneda = $d->idmoneda, tipocambioprov = $d->tipocambioprov, debaja = $d->debaja, cuentabanco = $d->cuentabanco, identificacion = $d->identificacion ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM beneficiario WHERE id = $d->id";
    $db->doQuery($query);
});

// permisos usuario
$app->get('/usrbene/:idbene', function ($idbene) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.idusuario, b.nombre FROM usuario_beneficiario a INNER JOIN usuario b ON a.idusuario = b.id WHERE a.idbeneficiario = $idbene";
    print $db->doSelectASJson($query);
});

$app->get('/ap/:idusuario/:idbene', function ($idusuario, $idbene) {
    $db = new dbcpm();
    $db->doQuery("INSERT INTO usuario_beneficiario(idusuario, idbeneficiario) VALUES($idusuario, $idbene)");
    $lastid = $db->getLastId();

    if ($lastid > 0) {
        $tipo = 'success';
        $mensaje = 'Se agrego el permiso del usuario correctamente.';
    } else {
        $tipo = 'error';
        $mensaje = 'Error al agregar permiso, favor volver a intentar.';
    }

    print json_encode([ 'tipo' => $tipo, 'mensaje' => $mensaje ]);
});

$app->get('/qp/:id', function ($id) {
    $db = new dbcpm();
    $db->doQuery("DELETE FROM usuario_beneficiario WHERE id = $id");

    $existe = $db->getOneField("SELECT id FROM usuario_beneficiario WHERE id = $id") > 0;

    if (!$existe) {
        $tipo = 'success';
        $mensaje = 'Se quito el permiso del usuario correctamente.';
    } else {
        $tipo = 'error';
        $mensaje = 'Error al quitar permiso, favor volver a intentar.';
    }

    print json_encode([ 'tipo' => $tipo, 'mensaje' => $mensaje ]);
});

$app->get('/beneusr/:idusuario', function ($idusuario) {
    $db = new dbcpm();
    $query = "SELECT id FROM beneficiario WHERE id IN(SELECT idbeneficiario FROM usuario_beneficiario WHERE idusuario = $idusuario)";
    print $db->doSelectASJson($query);
});

$app->run();