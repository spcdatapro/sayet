<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para encabezado de benefiaciario
$app->get('/lstbene', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, ";
    $query.= "CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov ";
    $query.= "FROM beneficiario a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "ORDER BY a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getbene/:idbene', function($idbene){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nit, a.nombre, a.direccion, a.telefono, a.correo, a.concepto, ";
    $query.= "CONCAT('(', a.nit, ') ', a.nombre, ' (', b.simbolo, ')') AS nitnombre, a.idmoneda, b.nommoneda AS moneda, a.tipocambioprov ";
    $query.= "FROM beneficiario a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.id = ".$idbene;
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO beneficiario(nit, nombre, direccion, telefono, correo, concepto, idmoneda, tipocambioprov) ";
    $query.= "VALUES('".$d->nit."', '".$d->nombre."', '".$d->direccion."', '".$d->telefono."', '".$d->correo."', '".$d->concepto."', ";
    $query.= $d->idmoneda.", ".$d->tipocambioprov.")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE beneficiario SET nit = '".$d->nit."', nombre = '".$d->nombre."', direccion = '".$d->direccion."', ";
    $query.= "telefono = '".$d->telefono."', correo = '".$d->correo."', concepto = '".$d->concepto."', ";
    $query.= "idmoneda = ".$d->idmoneda.", tipocambioprov = ".$d->tipocambioprov." ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM beneficiario WHERE id = ".$d->id;
    $db->doQuery($query);
    //$query = "DELETE FROM detcontprov WHERE idbeneficiario = ".$d->id;
    //$db->doQuery($query);
});

$app->run();