<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function generaArbol($nivel, $idpadre, $mes, $anio){
    $db = new dbcpm();

    //Selecciono las diferentes empresas que hay del nivel
    $query = "SELECT DISTINCT a.idempresa, TRIM(b.nomempresa) AS empresa, TRIM(b.abreviatura) AS abreviaempresa, $nivel AS nivel ";
    $query.= "FROM serviciobasico a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query.= "WHERE a.nivel = $nivel ";
    $query.= $nivel > 0 ? "" : "AND a.id IN (SELECT z.id FROM serviciobasico z INNER JOIN serviciobasico y ON z.id = y.idpadre GROUP BY z.id HAVING COUNT(y.idpadre) > 0) ";
    $query.= $idpadre > 0 ? "AND a.idpadre = $idpadre " : "";
    $query.= "ORDER BY b.nomempresa";
    $arbol = $db->getQuery($query);
    $cntRamas = count($arbol);

    //Por empresa voy seleccionando los contadores
    for($i = 0; $i < $cntRamas; $i++){
        $rama = $arbol[$i];
        $query = "SELECT a.id, a.idtiposervicio, TRIM(c.desctiposervventa) AS tiposervicio, TRIM(a.numidentificacion) AS numidentificacion, TRIM(a.numreferencia) AS numreferencia, a.idempresa, ";
        $query.= "TRIM(b.nomempresa) AS empresa, TRIM(b.abreviatura) AS abreviaempresa, ";
        $query.= "TRIM(a.ubicadoen) AS ubicadoen, DATE_FORMAT(d.fechainicial, '%d/%m/%Y') AS fechainicial, FORMAT(d.lecturainicial, 2) AS lecturainicial, ";
        $query.= "DATE_FORMAT(d.fechafinal, '%d/%m/%Y') AS fechafinal, FORMAT(d.lecturafinal, 2) AS lecturafinal, ";
        $query.= "FORMAT((d.lecturafinal - d.lecturainicial), 2) AS consumo, a.idpadre, a.nivel, FORMAT(a.mcubsug, 2) AS base, FORMAT(a.preciomcubsug, 2) preciobase, ";
        $query.= "IF(((d.lecturafinal - d.lecturainicial) - a.mcubsug) < 0, 0.00, FORMAT(((d.lecturafinal - d.lecturainicial) - a.mcubsug) * a.preciomcubsug, 2)) AS afacturar ";

        $query.= "FROM serviciobasico a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tiposervicioventa c ON c.id = a.idtiposervicio LEFT JOIN (";
        $query.= "SELECT x.idserviciobasico, x.mes, x.anio, x.fechacorte AS fechafinal, x.lectura AS lecturafinal, FechaLecturaAnterior(x.idserviciobasico, $mes, $anio) AS fechainicial, ";
        $query.= "LecturaAnterior(x.idserviciobasico, $mes, $anio) AS lecturainicial ";
        $query.= "FROM lecturaservicio x WHERE x.mes = $mes AND x.anio = $anio) d ON a.id = d.idserviciobasico ";

        $query.= "WHERE a.nivel = $nivel ";
        $query.= $nivel > 0 ? "": "AND a.id IN (SELECT z.id FROM serviciobasico z INNER JOIN serviciobasico y ON z.id = y.idpadre GROUP BY z.id HAVING COUNT(y.idpadre) > 0) ";
        $query.= "AND a.idempresa = $rama->idempresa ";

        $query.= $idpadre > 0 ? "AND a.idpadre = $idpadre " : "";

        $query.= "ORDER BY a.numidentificacion, a.ubicadoen";
        $rama->ramas = $db->getQuery($query);
        $cntSubRamas = count($rama->ramas);

        for($j = 0; $j < $cntSubRamas; $j++){
            $subrama = $rama->ramas[$j];
            $subrama->ramas = generaArbol($nivel + 1, (int)$subrama->id, $mes, $anio);
        }
    }
    return $arbol;
}


$app->get('/rptaguatree/:mes/:anio', function($mes, $anio){
    $db = new dbcpm();
    $info = new stdclass();
    $query = "SELECT CONCAT(nombre, ' de $anio') AS periodo, DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha FROM mes WHERE id = $mes";
    $info->generales = $db->getQuery($query)[0];

    $info->contadores = generaArbol(0, 0, $mes, $anio);
    print json_encode($info);
});


$app->run();