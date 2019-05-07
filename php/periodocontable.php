<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para períodos contables
$app->get('/lstpcont(/:vercerrados(/:anio))', function($vercerrados = 0, $anio = null){
    $db = new dbcpm();
    if(!$anio){ $anio = date("Y"); }
    $query = "SELECT id, del, al, abierto, DATE_FORMAT(del, '%d/%m/%Y') AS delstr, DATE_FORMAT(al, '%d/%m/%Y') AS alstr ";
    $query.= "FROM periodocontable WHERE 1 = 1 ";
    $query.= (int)$anio > 0 ? "AND YEAR(del) = $anio AND YEAR(al) = $anio " : '';
    $query.= (int)$vercerrados === 0 ? "AND abierto = 1 " : '';
    $query.= "ORDER BY abierto DESC, del DESC, al";
    print $db->doSelectASJson($query);
});

$app->get('/getpcont/:idpcont', function($idpcont){
    $db = new dbcpm();
    $query = "SELECT id, del, al, abierto, DATE_FORMAT(del, '%d/%m/%Y') AS delstr, DATE_FORMAT(al, '%d/%m/%Y') AS alstr FROM periodocontable WHERE id = $idpcont";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO periodocontable(del, al, abierto) VALUES('$d->delstr', '$d->alstr', $d->abierto)";
    $db->doQuery($query);

});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE periodocontable SET del = '$d->delstr' , al = '$d->alstr', abierto = $d->abierto WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM periodocontable WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/validar', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "SELECT COUNT(id) AS abiertos FROM periodocontable WHERE abierto = 1 AND '$d->fecha' >= del AND '$d->fecha' <= al";
    print json_encode(['valida' => (int)$db->getOneField($query)]);
});

$app->run();