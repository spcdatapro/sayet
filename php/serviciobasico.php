<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para servicios básicos
$app->get('/lstservicios/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tiposervicio, a.idproveedor, c.nombre AS proveedor, ";
    $query.= "a.numidentificacion, a.numreferencia, a.idempresa, d.nomempresa AS empresa, a.pagacliente, a.preciomcubsug, a.mcubsug, a.espropio, a.ubicadoen, a.debaja, a.fechabaja, ";
    $query.= "a.idpadre, a.nivel, a.cobrar, e.numidentificacion AS contadorpadre, a.notas, a.asignado ";
    $query.= "FROM serviciobasico a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio LEFT JOIN proveedor c ON c.id = a.idproveedor ";
    $query.= "LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN serviciobasico e ON e.id = a.idpadre ";
    $query.= (int)$idempresa > 0 ? "WHERE d.id = $idempresa " : "";
    $query.= "ORDER BY a.nivel, e.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/getservicio/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tiposervicio, a.idproveedor, c.nombre AS proveedor, ";
    $query.= "a.numidentificacion, a.numreferencia, a.idempresa, d.nomempresa AS empresa, a.pagacliente, a.preciomcubsug, a.mcubsug, a.espropio, a.ubicadoen, a.debaja, a.fechabaja, ";
    $query.= "a.idpadre, a.nivel, a.cobrar, e.numidentificacion AS contadorpadre, a.notas, a.asignado ";
    $query.= "FROM serviciobasico a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio LEFT JOIN proveedor c ON c.id = a.idproveedor ";
    $query.= "LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN serviciobasico e ON e.id = a.idpadre ";
    $query.= "WHERE a.id = $idservicio";
    print $db->doSelectASJson($query);
});

$app->get('/lstservdispon/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, ";
    $query.= "CONCAT(b.desctiposervventa, ' - ', c.nombre, ' - ', a.numidentificacion, ' - ', a.numreferencia, ' - ', RTRIM(d.nomempresa), IF(a.pagacliente = 1, ' - Pagado por cliente','')) AS serviciobasico, ";
    $query.= "a.espropio, a.idpadre, a.nivel, a.cobrar, e.numidentificacion AS contadorpadre ";
    $query.= "FROM serviciobasico a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio LEFT JOIN proveedor c ON c.id = a.idproveedor ";
    $query.= "LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN serviciobasico e ON e.id = a.idpadre ";
    $query.= "WHERE a.asignado = 0 AND a.debaja = 0 ";
    $query.= (int)$idempresa > 0 ? "AND d.id = $idempresa " : "";
    $query.= "ORDER BY a.nivel, e.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/lstsrvpadres', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, b.nomempresa AS empresa, a.numidentificacion, a.numreferencia, c.numidentificacion AS contadorpadre ";
    $query.= "FROM serviciobasico a INNER JOIN empresa b ON b.id = a.idempresa LEFT JOIN serviciobasico c ON a.id = c.idpadre ";
    $query.= "WHERE a.asignado = 0 AND a.debaja = 0 ";
    //$query.= "AND a.id IN (SELECT z.id FROM serviciobasico z INNER JOIN serviciobasico y ON z.id = y.idpadre GROUP BY z.id HAVING COUNT(y.idpadre) > 0) ";
    $query.= "ORDER BY b.nomempresa, a.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/histo/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT d.nomproyecto AS proyecto, c.descripcion AS tipolocal, b.nombre, b.descripcion, a.fini, IF(a.ffin IS NULL, 'A la fecha', a.ffin) AS ffin ";
    $query.= "FROM unidadservicio a LEFT JOIN unidad b ON b.id = a.idunidad LEFT JOIN tipolocal c ON c.id = b.idtipolocal LEFT JOIN proyecto d ON d.id = b.idproyecto ";
    $query.= "WHERE a.idserviciobasico = $idservicio ";
    $query.= "ORDER BY a.ffin DESC";
    print $db->doSelectASJson($query);
});

$app->get('/histocb/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idunidadservicio, a.idproyecto, b.nomproyecto AS proyecto, a.idunidad, c.nombre AS unidad, a.usrcambio, CONCAT(d.nombre, ' (', d.usuario, ')') AS usuario, a.fechacambio, a.cantbase ";
    $query.= "FROM detunidadservicio a LEFT JOIN proyecto b ON b.id = a.idproyecto LEFT JOIN unidad c ON c.id = a.idunidad LEFT JOIN usuario d ON d.id = a.usrcambio ";
    $query.= "WHERE a.idserviciobasico = $idservicio ";
    $query.= "ORDER BY a.fechacambio DESC";
    print $db->doSelectASJson($query);
});

function getNivel($db, $idpadre){ return (int)$db->getOneField("SELECT nivel + 1 FROM serviciobasico WHERE id = $idpadre"); }

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $nivel = (int)$d->idpadre > 0 ? getNivel($db, (int)$d->idpadre) : 0;
	$d->idpadre = (int)$d->idpadre > 0 ? $d->idpadre : 0;
    $notas = $d->notas = '' ? 'NULL' : "'$d->notas'";
    $query = "INSERT INTO serviciobasico(idtiposervicio, idproveedor, numidentificacion, numreferencia, idempresa, ";
    $query.= "pagacliente, preciomcubsug, mcubsug, espropio, ubicadoen, idpadre, nivel, cobrar, notas) VALUES(";
    $query.= "$d->idtiposervicio, $d->idproveedor, '$d->numidentificacion', '$d->numreferencia', $d->idempresa, ";
    $query.= "$d->pagacliente, $d->preciomcubsug, $d->mcubsug, $d->espropio, '$d->ubicadoen', $d->idpadre, $nivel, $d->cobrar, $notas";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fechabajastr = $d->fechabajastr == '' ? "NULL" : "'$d->fechabajastr'";
    $nivel = (int)$d->idpadre > 0 ? getNivel($db, (int)$d->idpadre) : 0;
    $notas = $d->notas = '' ? 'NULL' : "'$d->notas'";
    $query = "UPDATE serviciobasico SET ";
    $query.= "idtiposervicio = $d->idtiposervicio, idproveedor = $d->idproveedor, numidentificacion = '$d->numidentificacion', ";
    $query.= "numreferencia = '$d->numreferencia', idempresa = $d->idempresa, pagacliente = $d->pagacliente, ";
    $query.= "preciomcubsug = $d->preciomcubsug, mcubsug = $d->mcubsug, espropio = $d->espropio, ubicadoen = '$d->ubicadoen', ";
    $query.= "debaja = $d->debaja, fechabaja = $d->fechabajastr, idpadre = $d->idpadre, nivel = $nivel, cobrar = $d->cobrar, notas = $notas ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);

    if((int)$d->debaja == 1){
        $db->doQuery("UPDATE unidadservicio SET ffin = NOW() WHERE idserviciobasico = $d->id");
        $db->doQuery("UPDATE serviciobasico SET asignado = 0 WHERE id = $d->id");
    }
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $noAsignado = (int)$db->getOneField("SELECT asignado FROM serviciobasico WHERE id = $d->id") == 0;
    if($noAsignado){
        $db->doQuery("DELETE FROM unidadservicio WHERE idserviciobasico = $d->id");
        $db->doQuery("DELETE FROM serviciobasico WHERE id = $d->id");
    }    
});

$app->run();