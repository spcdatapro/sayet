<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para encabezado de benefiaciario
$app->get('/lstbene(/:todos)', function($todos = 0){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.idbancopais, a.tipcuenta, a.identificacion, ";
    $query.= "CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov, a.debaja, a.cuentabanco ";
    $query.= "FROM beneficiario a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= (int)$todos === 0 ? 'WHERE a.debaja = 0 ' : '';
    $query.= "ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getbene/:idbene', function($idbene){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, a.idbancopais, a.tipcuenta, a.identificacion, ";
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

    $query = "INSERT INTO beneficiario(nit, nombre, direccion, telefono, correo, concepto, idmoneda, tipocambioprov, debaja, cuentabanco, idbancopais, tipcuenta, identificacion ) ";
    $query.= "VALUES('$d->nit', '$d->nombre', '$d->direccion', '$d->telefono', '$d->correo', '$d->concepto', $d->idbancopais, $d->tipcuenta, $d->identificacion, ";
    $query.= "$d->idmoneda, $d->tipocambioprov, $d->debaja, $d->cuentabanco)";
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

    $query = "UPDATE beneficiario SET nit = '$d->nit', nombre = '$d->nombre', direccion = '$d->direccion', idbancopais = $d->idbancopais, tipcuenta = $d->tipcuenta, ";
    $query.= "telefono = '$d->telefono', correo = '$d->correo', concepto = '$d->concepto', ";
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

$app->run();