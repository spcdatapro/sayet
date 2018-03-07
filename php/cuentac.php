<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para cuentas contables
$app->get('/lstctas/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT id, idempresa, codigo, nombrecta, tipocuenta, CONCAT('(', codigo, ') ', nombrecta) AS codcta ";
    $query.= "FROM cuentac ";
    $query.= "WHERE idempresa = $idempresa ";
    $query.= "ORDER BY codigo";
    print $db->doSelectAsJSON($query);
});

$app->get('/getcta/:idcta', function($idcta){
    $db = new dbcpm();
    $query = "SELECT id, idempresa, codigo, nombrecta, tipocuenta, CONCAT('(', codigo, ') ', nombrecta) AS codcta ";
    $query.= "FROM cuentac ";
    $query.= "WHERE id = $idcta";
    print $db->doSelectAsJSON($query);
});

$app->get('/getbytipo/:idempresa/:tipo', function($idempresa, $tipo){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, idempresa, codigo, nombrecta, tipocuenta, CONCAT('(', codigo, ') ', nombrecta) AS codcta ";
    $query.= "FROM cuentac WHERE idempresa = ".$idempresa." AND tipocuenta = ".$tipo;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/ctasmov/:idempresa/:qstr', function($idempresa, $qstr){
    $db = new dbcpm();
    $query = "SELECT id, idempresa, codigo, nombrecta, tipocuenta, CONCAT('(', codigo, ') ', nombrecta) AS codcta ";
    $query.= "FROM cuentac WHERE idempresa = $idempresa AND tipocuenta = 0 AND (codigo LIKE '%$qstr%' OR nombrecta LIKE '%qstr%')";
    print json_encode(['results' => $db->getQuery($query)]);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO cuentac(idempresa, codigo, nombrecta, tipocuenta) ";
    $query.= "VALUES(".$d->idempresa.", '".$d->codigo."', '".$d->nombrecta."', ".$d->tipocuenta.")";
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE cuentac SET idempresa = ".$d->idempresa." , codigo = '".$d->codigo."', ";
    $query.= "nombrecta = '".$d->nombrecta."', tipocuenta = ".$d->tipocuenta." WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM cuentac WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();