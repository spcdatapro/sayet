<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para empresas
$app->get('/lstempresas', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, IF(a.propia = 1, a.nomempresa, CONCAT(a.nomempresa, ' (Ajena)')) AS nomempresa, a.idmoneda, b.nommoneda, b.simbolo, a.propia, a.dectc, ";
    $query.= "a.retisr, a.abreviatura, TRIM(a.nit) AS nit, TRIM(a.formatofactura) AS formatofactura, a.congface, TRIM(a.seriefact) AS seriefact, a.correlafact, a.sifactura, a.fechavencefact, a.ultimocorrelativofact, a.direccion, ";
    $query.= "IF(a.congface = 0 AND a.sifactura = 1, (a.ultimocorrelativofact - a.correlafact), NULL) AS formspend, IF(a.congface = 0 AND a.sifactura = 1,TIMESTAMPDIFF(MONTH, DATE(NOW()), a.fechavencefact), NULL) AS mesesfaltan, ";
    $query.= "a.ndplanilla ";
    $query.= "FROM empresa a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "ORDER BY a.propia DESC, a.nomempresa, b.nommoneda";
    print $db->doSelectASJson($query);
});

$app->get('/getemp/:idemp', function($idemp){
    $db = new dbcpm();
    $query = "SELECT a.id, IF(a.propia = 1, a.nomempresa, CONCAT(a.nomempresa, ' (Ajena)')) AS nomempresa, a.idmoneda, b.nommoneda, b.simbolo, a.propia, a.dectc, ";
    $query.= "a.retisr, a.abreviatura, TRIM(a.nit) AS nit, TRIM(a.formatofactura) AS formatofactura, a.congface, TRIM(a.seriefact) AS seriefact, a.correlafact, a.sifactura, a.fechavencefact, a.ultimocorrelativofact, a.direccion, ";
    $query.= "IF(a.congface = 0 AND a.sifactura = 1, (a.ultimocorrelativofact - a.correlafact), NULL) AS formspend, IF(a.congface = 0 AND a.sifactura = 1,TIMESTAMPDIFF(MONTH, DATE(NOW()), a.fechavencefact), NULL) AS mesesfaltan, ";
    $query.= "a.ndplanilla ";
    $query.= "FROM empresa a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.id = ".$idemp;
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->seriefact = $d->seriefact != '' ? "'$d->seriefact'" : 'NULL';
    $d->fechavencefactstr = $d->fechavencefactstr != '' ? "'$d->fechavencefactstr'" : 'NULL';
    $query = "INSERT INTO empresa(nomempresa, idmoneda, propia, abreviatura, nit, seriefact, correlafact, fechavencefact, ultimocorrelativofact, direccion, ndplanilla) VALUES(";
    $query.= "'$d->nomempresa', $d->idmoneda, $d->propia, '$d->abreviatura', '$d->nit', $d->seriefact, $d->correlafact, $d->fechavencefactstr, $d->ultimocorrelativofact, '$d->direccion', $d->ndplanilla";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->seriefact = $d->seriefact != '' ? "'$d->seriefact'" : 'NULL';
    $d->fechavencefactstr = $d->fechavencefactstr != '' ? "'$d->fechavencefactstr'" : 'NULL';
    $query = "UPDATE empresa SET ";
    $query.= "nomempresa = '$d->nomempresa' , idmoneda = $d->idmoneda, propia = $d->propia, abreviatura = '$d->abreviatura', nit = '$d->nit', seriefact = $d->seriefact, correlafact = $d->correlafact, ";
    $query.= "fechavencefact = $d->fechavencefactstr, ultimocorrelativofact = $d->ultimocorrelativofact, direccion = '$d->direccion', ndplanilla = $d->ndplanilla ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM empresa WHERE id = $d->id";
    $db->doQuery($query);
});

//API para la configuración contable de las empresas
$app->get('/lstconf/:idempresa', function($idempresa){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idempresa, a.idtipoconfig, b.desctipoconfconta, a.idcuentac, CONCAT('(',c.codigo,') ', c.nombrecta) AS cuentac ";
    $query.= "FROM detcontempresa a INNER JOIN tipoconfigconta b ON b.id = a.idtipoconfig INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "WHERE a.idempresa = ".$idempresa." ";
    $query.= "ORDER BY b.desctipoconfconta";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getconf/:idconf', function($idconf){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idempresa, a.idtipoconfig, b.desctipoconfconta, a.idcuentac, CONCAT('(',c.codigo,') ', c.nombrecta) AS cuentac ";
    $query.= "FROM detcontempresa a INNER JOIN tipoconfigconta b ON b.id = a.idtipoconfig INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "WHERE a.id = ".$idconf;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/cc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO detcontempresa(idempresa, idtipoconfig, idcuentac) VALUES(".$d->idempresa.", ".$d->idtipoconfig.", ".$d->idcuentac.")";
    $ins = $conn->query($query);
});

$app->post('/uc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE detcontempresa SET idtipoconfig = ".$d->idtipoconfig.", idcuentac = ".$d->idcuentac." WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/dc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM detcontempresa WHERE id = ".$d->id;
    $del = $conn->query($query);
});


$app->run();