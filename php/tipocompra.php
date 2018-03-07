<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipos de compra
$app->get('/lsttiposcompra', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.desctipocompra, a.idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS codcta, a.idcuentacventa, ";
    $query.= "CONCAT('(', c.codigo, ') ', c.nombrecta) AS codctaventa, a.paraventa ";
    $query.= "FROM tipocompra a LEFT JOIN cuentac b ON b.id = a.idcuentac LEFT JOIN cuentac c ON c.id = a.idcuentacventa ";
    $query.= "ORDER BY a.desctipocompra";
    print $db->doSelectASJson($query);
});

$app->get('/gettipocompra/:idtipocomp', function($idtipocomp) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.desctipocompra, a.idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS codcta, a.idcuentacventa, ";
    $query.= "CONCAT('(', c.codigo, ') ', c.nombrecta) AS codctaventa, a.paraventa ";
    $query.= "FROM tipocompra a LEFT JOIN cuentac b ON b.id = a.idcuentac LEFT JOIN cuentac c ON c.id = a.idcuentacventa ";
    $query.= "WHERE a.id = ".$idtipocomp;
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO tipocompra(desctipocompra, idcuentac, idcuentacventa) VALUES('".$d->desctipocompra."', ".$d->idcuentac.", ".$d->idcuentacventa.")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE tipocompra SET desctipocompra = '".$d->desctipocompra."', idcuentac = ".$d->idcuentac.", idcuentacventa = ".$d->idcuentacventa." WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM tipocompra WHERE id = ".$d->id);
});

$app->run();