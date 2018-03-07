<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para No. Orden de cédulas
$app->get('/lstnoorden', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.noorden, a.idmunicipio, CONCAT(b.nomdepto,' - ' ,b.nombre) AS departamento ";
    $query.= "FROM ordencedula a INNER JOIN municipio b ON b.id = a.idmunicipio ";
    $query.= "UNION SELECT 0, 'N/A', 0, '' ORDER BY 4, 2";
    print $db->doSelectASJson($query);
});

$app->get('/getnoorden/:idnoorden', function($idnoorden){
    $db = new dbcpm();
    $query = "SELECT a.id, a.noorden, a.idmunicipio, CONCAT(b.nomdepto,' - ' ,b.nombre) AS departamento ";
    $query.= "FROM ordencedula a INNER JOIN municipio b ON b.id = a.idmunicipio ";
    $query.= "WHERE a.id = ".$idnoorden;
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO ordencedula(noorden, idmunicipio) VALUES('".$d->noorden."', ".$d->idmunicipio.")";
    $db->doQuery($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE ordencedula SET noorden = '".$d->noorden."' , idmunicipio = ".$d->idmunicipio." WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM ordencedula WHERE id = ".$d->id);
});

$app->run();