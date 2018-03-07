<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/audit', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $where = $d->usuario != '' || $d->tabla != '' || $d->tipo != '' || $d->fdelstr != '' || $d->falstr != '' || $d->descripcion != '' ? "WHERE " : "";

    $f[1] = $d->usuario != '' ? "(b.nombre LIKE '%$d->usuario%' OR b.usuario LIKE '%$d->usuario%')" : "";
    $f[2] = $d->tabla != '' ? "(a.tabla LIKE '%$d->tabla%' OR c.nombre LIKE '%$d->tabla%')" : "";
    $f[3] = $d->tipo != '' ? "d.abrevia = '$d->tipo'" : "";
    $f[4] = $d->fdelstr != '' ? "DATE(a.fecha) >= '$d->fdelstr'" : "";
    $f[5] = $d->falstr != '' ? "DATE(a.fecha) <= '$d->falstr'" : "";
    $f[6] = $d->descripcion != '' ? "a.cambio LIKE '%".str_replace(' ', '%', $d->descripcion)."%'" : "";


    $complemento = "";
    for($x = 1; $x <= 6; $x++){
        if($complemento != "" && $f[$x] != ""){ $complemento .= " AND "; }
        $complemento.= $f[$x];
    }

    $query = "SELECT a.id, a.idusuario, b.nombre AS nombrecompleto, b.usuario, a.tabla, c.nombre, a.cambio, a.fecha, a.tipo, d.descripcion, DATE_FORMAT(a.fecha, '%d/%m/%Y %H:%i:%s') AS fechastr ";
    $query.= "FROM auditoria a INNER JOIN usuario b ON b.id = a.idusuario INNER JOIN tabla c ON c.nombredb = a.tabla INNER JOIN operacion d ON d.abrevia = a.tipo ";
    $query.= $where.$complemento." ";
    $query.= "ORDER BY a.fecha DESC";

    print $db->doSelectASJson($query);

});

$app->run();