<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para encabezado de unidades
$app->get('/lstunidades', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idempresa, b.nomempresa AS empresa, a.nombre, a.mcuad, a.descripcion, a.nolineastel, a.numeros, a.conteegsa, a.observaciones, ";
    $query.= "a.idtipolocal, c.descripcion AS tipolocal ";
    $query.= "FROM unidad a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipolocal c ON c.id = a.idtipolocal ";
    $query.= "ORDER BY b.nomempresa, c.descripcion, a.nombre";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getunidad/:idunidad', function($idunidad){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idempresa, b.nomempresa AS empresa, a.nombre, a.mcuad, a.descripcion, a.nolineastel, a.numeros, a.conteegsa, a.observaciones, ";
    $query.= "a.idtipolocal, c.descripcion AS tipolocal ";
    $query.= "FROM unidad a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipolocal c ON c.id = a.idtipolocal ";
    $query.= "WHERE a.id = ".$idunidad;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO unidad(idempresa, nombre, mcuad, descripcion, nolineastel, numeros, conteegsa, observaciones, idtipolocal) ";
    $query.= "VALUES(".$d->idempresa.", '".$d->nombre."', ".$d->mcuad.", '".$d->descripcion."', ".$d->nolineastel.", ";
    $query.= "'".$d->numeros."', '".$d->conteegsa."', '".$d->observaciones."', ".$d->idtipolocal.")";
    $conn->query($query);
    $lastid = $conn->query("SELECT LAST_INSERT_ID()")->fetchColumn(0);
    print json_encode(['lastid' => $lastid]);
});

$app->post('/dupproy', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO unidad(idempresa, idproyecto, idtipolocal, nombre, mcuad, descripcion, observaciones) ";
    $query.= "SELECT idempresa, id, ".$d->idtipolocal.", referencia, ".$d->mcuad.", nomproyecto, notas FROM proyecto WHERE id = ".$d->idproyecto;
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE unidad SET idempresa = ".$d->idempresa.", nombre = '".$d->nombre."', mcuad = ".$d->mcuad.", descripcion = '".$d->descripcion."', ";
    $query.= "nolineastel = ".$d->nolineastel.", idtipolocal = ".$d->idtipolocal.", ";
    $query.= "numeros = '".$d->numeros."', conteegsa = '".$d->conteegsa."', observaciones = '".$d->observaciones."' WHERE id = ".$d->id;
    $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM unidad WHERE id = ".$d->id;
    $conn->query($query);
    //$query = "DELETE FROM detcontprov WHERE idproveedor = ".$d->id;
    //$conn->query($query);
});

//API para servicios por unidad
$app->get('/servicios/:idunidad', function($idunidad){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idunidad, a.idtiposervicio, b.descripcion AS tiposervicio, a.descripcion ";
    $query.= "FROM unidadservicio a INNER JOIN tiposervicio b ON b.id = a.idtiposervicio ";
    $query.= "WHERE a.idunidad = ".$idunidad." ";
    $query.= "ORDER BY b.descripcion, a.descripcion";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getservicio/:idservicio', function($idservicio){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idunidad, a.idtiposervicio, b.descripcion AS tiposervicio, a.descripcion ";
    $query.= "FROM unidadservicio a INNER JOIN tiposervicio b ON b.id = a.idtiposervicio ";
    $query.= "WHERE a.id = ".$idservicio;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/cs', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO unidadservicio(idunidad, idtiposervicio, descripcion) ";
    $query.= "VALUES(".$d->idunidad.", ".$d->idtiposervicio.", '".$d->descripcion."')";
    $conn->query($query);
});

$app->post('/us', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE unidadservicio SET idtiposervicio = ".$d->idtiposervicio.", descripcion = '".$d->descripcion."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/ds', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM unidadservicio WHERE id = ".$d->id;
    $del = $conn->query($query);
});


//API para detalle de contadores por unidad
$app->get('/contadores/:idunidad', function($idunidad){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idunidad, a.contador, a.alta, a.baja, a.mcubbase, a.parafactura ";
    $query.= "FROM detunidacont a ";
    $query.= "WHERE a.idunidad = ".$idunidad." ";
    $query.= "ORDER BY a.contador";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getcontador/:idcont', function($idcont){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idunidad, a.contador, a.alta, a.baja, a.mcubbase, a.parafactura FROM detunidacont a WHERE a.id = ".$idcont;
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO detunidacont(idunidad, contador, alta, baja, mcubbase, parafactura) ";
    $query.= "VALUES(".$d->idunidad.", '".$d->contador."', '".$d->alta."', '".$d->baja."', ".$d->mcubbase.", '".$d->parafactura."')";
    $conn->query($query);
});

$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE detunidacont SET contador = '".$d->contador."', alta = '".$d->alta."', baja = '".$d->baja."', ";
    $query.= "mcubbase = ".$d->mcubbase.", parafactura = '".$d->parafactura."' WHERE id = ".$d->id;
    $upd = $conn->query($query);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM detunidacont WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();