<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API
$app->get('/lstsolicitudes', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idtipogasto, a.idgastocontable, a.idempresa, a.idproveedor, ";
    $query.= "a.numorden, a.fechasolicitud, a.valormaterial, a.valorobramano, ";
    $query.= "a.montosolicita, a.descripcion, a.trabajomayor, a.ivaincluido, a.dolares, ";
    $query.= "b.desctipogast, c.descgastoconta, d.nomempresa, e.nombre ";
    $query.= "from solicitudot a left join tipogasto b on a.idtipogasto=b.id left join gastoconta c ON a.idgastocontable = c.id,";
    $query.= "left join empresa d ON a.idempresa= d.id left join proveedor e ON a.idproveedor=e.id ORDER BY a.numorden";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getsolicitud/:idsolicitud', function($idsolicitud){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('solicitudot',['id', 'idtipogasto', 'idgastocontable', 'idempresa', 'idproveedor', 'numorden', 'fechasolicitud', 'valormaterial', 'valorobramano', 'montosolicita', 'descripcion', 'trabajomayor', 'ivaincluido', 'dolares'],['id' => $idsolicitud, 'ORDER' => 'numorden']);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO solicitudot(idtipogasto, idgastocontable, idempresa, idproveedor, idproyecto, numorden, fechasolicitud, ";
    $query .="valormaterial, valorobramano, montosolicita, descripcion, trabajomayor, ivaincluido, dolares) ";
    $query.= "VALUES(".$d->idtipogasto.", ".$d->idgastocontable.", ".$d->idempresa.", ".$d->idproveedor.", ".$d->idproyecto.", ".$d->numorden.", '".$d->fechasolicitud."', ".$d->valormaterial.", ".$d->valorobramano.", ".$d->montosolicita.", '".$d->descripcion."', ".$d->trabajomayor.", ".$d->ivaincluido.", ".$d->dolares.")";
    
    $ins = $conn->query($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE solicitudot SET idtipogasto, idgastocontable, idempresa, idproveedor, numorden, fechasolicitud, valormaterial, valorobramano, montosolicita, descripcion, trabajomayor, ivaincluido, dolares = '".$d->idtipogasto."', '".$d->idgastocontable."', '".$d->idempresa."', '".$d->iproveedor."', '".$d->numorden."', '".$d->numorden."', '".$d->fechasolicita."', '".$d->montosolicita."', '".$d->valormateria."', '".$d->valorobramano."', '".$d->trabajomayor."', '".$d->ivaincluido."', '".$d->dolares."' WHERE id = " .$d->id;
    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM solicitudot WHERE id = ".$d->id;
    $del = $conn->query($query);
});


$app->run();