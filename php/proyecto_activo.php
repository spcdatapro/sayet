<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para activos de proyectos
$app->get('/lstproyectoactivo', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('detalle_activo_proyecto',['idproyectoactivo', 'idproyecto', 'idactivo'],['ORDER' => 'idactivo']);
    print json_encode($data);

});

$app->get('/getproyectoactivo/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.idproyectoactivo, a.idproyecto, a.idactivo, b.nombre_corto, b.finca, b.folio, b.libro, b.metros_muni ";
    $query.= "FROM detalle_activo_proyecto a ";
    $query.= "LEFT JOIN activo b on a.idactivo=b.id ";
    $query.= "WHERE a.idproyecto = ".$idproyecto." ";
    $query.= "ORDER BY b.nombre_corto";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO detalle_activo_proyecto(idproyecto,idactivo) ";
    $query.= "VALUES(".$d->idproyecto.",".$d->idactivo.")";
    $ins = $conn->query($query);
});


$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE detalle_activo_proyecto SET idactivo = ".$d->idactivo." WHERE id = ".$d->id;
    $upd = $conn->query($query);
});


$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM detalle_activo_proyecto WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();