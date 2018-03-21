<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/lista', function(){
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT id, nomempresa AS empresa FROM empresa WHERE propia = 1 ".($d->idempresa != '' ? " AND id IN($d->idempresa) " : "")."ORDER BY nomempresa";
    $activos = $db->getQuery($query);

    foreach($activos as $act){

        $query = "SELECT id, CONCAT(nombre, ' - ', nomdepto) AS departamento FROM municipio ";
        $query.= "WHERE id IN(SELECT DISTINCT departamento FROM activo WHERE idempresa = $act->id) ";
        $query.= $d->iddepto != '' ? "AND id IN($d->iddepto) " : "";
        $query.= "ORDER BY nomdepto, nombre";
        $act->depto = $db->getQuery($query);

        foreach($act->depto as $muni){
            $query = "SELECT a.id, c.descripcion AS tipoactivo, CONCAT(a.finca, '-', a.folio, '-', a.libro) AS ffl, ";
            $query.= "CONCAT(a.nombre_largo, ', zona ', a.zona) AS direccion, a.iusi, IF(a.multilotes = 1, 'Sí', '') AS multiproyectos, a.valor_muni, a.metros_muni, IF(a.horizontal = 1, 'Sí', '') AS horizontal, ";
            $query.= "IF(a.metros_muni > 0, ROUND(a.valor_muni / a.metros_muni, 2), 0.00) AS valprop, e.proyectos ";
            $query.= "FROM activo a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_activo c ON c.id = a.tipo_activo INNER JOIN municipio d ON d.id = a.departamento LEFT JOIN (";
            $query.= "SELECT z.idactivo, GROUP_CONCAT(DISTINCT y.nomproyecto ORDER BY y.nomproyecto SEPARATOR ', ') AS proyectos FROM detalle_activo_proyecto z INNER JOIN proyecto y ON y.id = z.idproyecto GROUP BY z.idactivo";
            $query.= ") e ON a.id = e.idactivo ";
            $query.= "WHERE a.idempresa = $act->id AND d.id = $muni->id ";
            $query.= $d->idtipo != '' ? "AND c.id IN($d->idtipo) " : "";
            //$query.= "ORDER BY c.descripcion, a.finca, a.folio, a.libro";
            $query.= "ORDER BY CAST(digits(a.finca) AS UNSIGNED), CAST(digits(a.folio) AS UNSIGNED), CAST(digits(a.libro) AS UNSIGNED)";
            $muni->lista = $db->getQuery($query);
        }
    }

    print json_encode($activos);
});

$app->run();