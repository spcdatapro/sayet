<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/rptservicios', function() use($db, $app){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT DISTINCT a.idempresa, c.nomempresa AS empresa, c.abreviatura AS abreviaempre ";
    $query.= "FROM serviciobasico a INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio INNER JOIN empresa c ON c.id = a.idempresa LEFT JOIN proveedor d ON d.id = a.idproveedor ";
    $query.= (int)$d->idempresa > 0 ? "WHERE a.idempresa = $d->idempresa " : "";
    $query.= "ORDER BY c.nomempresa";
    $datos = $db->getQuery($query);
    $cntDatos = count($datos);

    for($i = 0; $i < $cntDatos; $i++){
        $dato = $datos[$i];
        $query = "SELECT DISTINCT a.idtiposervicio, b.desctiposervventa AS tipo ";
        $query.= "FROM serviciobasico a INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio INNER JOIN empresa c ON c.id = a.idempresa LEFT JOIN proveedor d ON d.id = a.idproveedor ";
        $query.= "WHERE a.idempresa = $dato->idempresa ";
        $query.= (int)$d->idtipo > 0 ? "AND a.idtiposervicio = $d->idtipo " : "";
        $query.= "ORDER BY b.desctiposervventa";
        $dato->tipos = $db->getQuery($query);
        $cntTipos = count($dato->tipos);
        for($j = 0; $j < $cntTipos; $j++){
            $tipo = $dato->tipos[$j];
            $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tipo, a.idproveedor, d.nombre AS proveedor, a.numidentificacion, a.numreferencia, a.idempresa, c.nomempresa AS empresa, c.abreviatura AS abreviaempre, a.ubicadoen, ";
            $query.= "IF(a.debaja = 0, NULL, 'Sí') AS debaja, DATE_FORMAT(a.fechabaja, '%d/%m/%Y') AS fechabaja, IF(a.cobrar = 1, 'Sí', 'NO') AS cobrar, a.notas ";
            $query.= "FROM serviciobasico a INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio INNER JOIN empresa c ON c.id = a.idempresa LEFT JOIN proveedor d ON d.id = a.idproveedor ";
            $query.= "WHERE a.idempresa = $dato->idempresa AND a.idtiposervicio = $tipo->idtiposervicio ";
            $query.= (int)$d->verbaja > 0 ? "" : "AND a.debaja = 0 ";
            $query.= "ORDER BY c.nomempresa, b.desctiposervventa, espropio DESC, a.numidentificacion, a.numreferencia";
            $tipo->servicios = $db->getQuery($query);
        }
    }

    print json_encode($datos);
});


$app->run();