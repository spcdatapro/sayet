<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para transacciones bancarias
$app->get('/lsttipodoc/:idtdmov', function($idtdmov){
    $db = new dbcpm();
    $conn = $db->getConn();
    //$query = "SELECT id, desctipodoc, abreviatipodoc, CONCAT(abreviatipodoc, ' - ', desctipodoc) AS abreviadesc FROM tipodocsoptranban ORDER BY abreviatipodoc, desctipodoc";
    $query = "SELECT a.id, a.idtipomovtranban, CONCAT('(', b.abreviatura, ') ', b.descripcion) AS tipomov, a.abreviatipodoc, a.desctipodoc, ";
    $query.= "CONCAT('(', a.abreviatipodoc, ') ', a.desctipodoc) AS abreviadesc ";
    $query.= "FROM tipodocsoptranban a INNER JOIN tipomovtranban b ON b.id = a.idtipomovtranban ";
    $query.= "WHERE a.idtipomovtranban = ".$idtdmov." ";
    $query.= "ORDER BY a.desctipodoc";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->run();