<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API tipo de gastos
$app->get('/lsttipogastos', function(){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctipogast FROM tipogasto ORDER BY desctipogast");
});

$app->get('/gettipogasto/:idtipogas', function($idtipogas){
    $db = new dbcpm();
    print $db->doSelectASJson("SELECT id, desctipogast FROM tipogasto WHERE id = $idtipogas");
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO tipogasto(desctipogast) ";
    $query.= "VALUES('".$d->desctipogast."')";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE tipogasto SET desctipogast = '".$d->desctipogast."' WHERE id = " .$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM tipogasto WHERE id = ".$d->id;
    $del = $conn->query($query);
});

//API de subtipo de gasto
$app->get('/lstallsubtipo', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtipogasto, b.desctipogast AS tipogasto, a.descripcion, a.idcuentac, c.codigo, c.nombrecta ";
    $query.= "FROM subtipogasto a INNER JOIN tipogasto b ON b.id = a.idtipogasto LEFT JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "ORDER BY b.desctipogast, a.descripcion";
    print $db->doSelectASJson($query);
});

$app->get('/lstsubtipobytipogasto/:idtipogasto', function($idtipogasto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtipogasto, b.desctipogast AS tipogasto, a.descripcion, a.idcuentac, c.codigo, c.nombrecta ";
    $query.= "FROM subtipogasto a INNER JOIN tipogasto b ON b.id = a.idtipogasto LEFT JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "WHERE a.idtipogasto = $idtipogasto ";
    $query.= "ORDER BY a.descripcion";
    print $db->doSelectASJson($query);
});

$app->get('/getsubtipogasto/:idsubtipogasto', function($idsubtipogasto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtipogasto, b.desctipogast AS tipogasto, a.descripcion, a.idcuentac, c.codigo, c.nombrecta ";
    $query.= "FROM subtipogasto a INNER JOIN tipogasto b ON b.id = a.idtipogasto LEFT JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "WHERE a.id = $idsubtipogasto";
    print $db->doSelectASJson($query);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO subtipogasto(idtipogasto, descripcion, idcuentac) ";
    $query.= "VALUES($d->idtipogasto, '$d->descripcion', $d->idcuentac)";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE subtipogasto SET descripcion = '$d->descripcion', idcuentac = $d->idcuentac WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM subtipogasto WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->get('/lstdetcontsubtipo/:idsubtipo', function($idsubtipo){
    $db = new dbcpm();

    $query = "SELECT a.id, a.idsubtipogasto, a.idempresa, b.nomempresa AS empresa, a.idcuentac, CONCAT('(', c.codigo,') ', c.nombrecta) AS cuentac, ";
    $query.= "e.id AS idtipogasto, e.desctipogast AS tipogasto, d.descripcion AS subtipogasto ";
    $query.= "FROM detcontsubtipogasto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN cuentac c ON c.id = a.idcuentac ";
    $query.= "INNER JOIN subtipogasto d ON d.id = a.idsubtipogasto INNER JOIN tipogasto e ON e.id = d.idtipogasto ";
    $query.= "WHERE a.idsubtipogasto = $idsubtipo";
    print $db->doSelectASJson($query);
});

$app->post('/acc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "INSERT INTO detcontsubtipogasto(idsubtipogasto, idempresa, idcuentac) VALUES(";
    $query.= "$d->idsubtipogasto, $d->idempresa, $d->idcuentac";
    $query.=")";
    $db->doQuery($query);
});

$app->post('/dcc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "DELETE FROM detcontsubtipogasto WHERE id = $d->id";
    $db->doQuery($query);
});

$app->run();