<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/proyecto/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nomproyecto AS proyecto, a.tipo_proyecto, a.referencia, b.nomempresa AS empresa, c.descripcion AS tipoproyecto, a.direccion, a.metros_rentable AS mcrentables, a.metros AS mcuad, a.notas, a.fechaapertura ";
    $query.= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
    $query.= "WHERE a.id = $idproyecto";
    $proyecto = $db->getQuery($query)[0];

    //Calculo de área rentable
    $query = "SELECT DISTINCT b.id, b.descripcion AS tipo FROM unidad a ";
    $query.= "INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
    $query.= "LEFT JOIN tipo_proyecto_orden c on c.idtipolocal = a.idtipolocal and c.idtipo_proyecto = {$proyecto->tipo_proyecto} ";
    $query.= "WHERE a.idproyecto = $idproyecto AND b.esrentable = 1 ";
    $query.= "ORDER BY IFNULL(c.orden, b.descripcion)";
    $proyecto->tunidad = $db->getQuery($query);

    $proyecto->arearentable = 0.00;
    foreach($proyecto->tunidad as $tu){
        $query = "SELECT a.nombre, a.mcuad ";
        $query.= "FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
        $query.= "WHERE a.idproyecto = $idproyecto AND b.id = $tu->id ";
        $query.= "ORDER BY CAST(digits(a.nombre) AS UNSIGNED), a.nombre";
        $tu->unidades = $db->getQuery($query);
        $subtot = 0.00;
        foreach($tu->unidades as $u){
            $subtot += round((float)$u->mcuad, 2);
            $proyecto->arearentable += round((float)$u->mcuad, 2);
        }
        array_push($tu->unidades, ['nombre' => 'Total', 'mcuad' => $subtot]);
    }

    //Calculo de area no rentable
    $query = "SELECT DISTINCT b.id, b.descripcion AS tipo FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
    $query.= "WHERE a.idproyecto = $idproyecto AND b.esrentable = 0 ";
    $query.= "ORDER BY b.descripcion";
    $proyecto->tunidadnorent = $db->getQuery($query);

    $proyecto->areanorentable = 0.00;
    foreach($proyecto->tunidadnorent as $tu){
        $query = "SELECT a.nombre, a.mcuad ";
        $query.= "FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
        $query.= "WHERE a.idproyecto = $idproyecto AND b.id = $tu->id ";
        $query.= "ORDER BY CAST(digits(a.nombre) AS UNSIGNED), a.nombre";
        $tu->unidades = $db->getQuery($query);
        $subtot = 0.00;
        foreach($tu->unidades as $u){
            $subtot += round((float)$u->mcuad, 2);
            $proyecto->areanorentable += round((float)$u->mcuad, 2);
        }
        array_push($tu->unidades, ['nombre' => 'Total', 'mcuad' => $subtot]);
    }

    $proyecto->mtot = $proyecto->arearentable + $proyecto->areanorentable;

    //Activos
    $query = "SELECT DISTINCT b.idempresa, c.nomempresa AS empresa ";
    $query.= "FROM detalle_activo_proyecto a INNER JOIN activo b ON b.id = a.idactivo INNER JOIN empresa c ON c.id = b.idempresa WHERE a.idproyecto = $idproyecto ";
    $query.= "ORDER BY c.nomempresa";
    $proyecto->tactivo = $db->getQuery($query);

    $proyecto->atot = 0.00;
    foreach($proyecto->tactivo as $ta){
        $query = "SELECT CONCAT(b.finca, '-', b.folio, '-', b.libro) AS ffl, b.metros_muni, b.nombre_largo AS descripcion ";
        $query.= "FROM detalle_activo_proyecto a INNER JOIN activo b ON b.id = a.idactivo INNER JOIN empresa c ON c.id = b.idempresa ";
        $query.= "WHERE a.idproyecto = $idproyecto AND b.idempresa = $ta->idempresa ";
        $query.= "ORDER BY CAST(digits(b.finca) AS UNSIGNED), CAST(digits(b.folio) AS UNSIGNED), CAST(digits(b.libro) AS UNSIGNED)";
        $ta->activos = $db->getQuery($query);
        $subtot = 0.00;
        foreach($ta->activos as $a){
            $subtot += round((float)$a->metros_muni, 2);
            $proyecto->atot += round((float)$a->metros_muni, 2);
        }
        array_push($ta->activos, ['ffl' => '', 'metros_muni' => $subtot, 'descripcion' => 'Total']);
    }

    print json_encode($proyecto);
});

$app->post('/catproy', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT b.id AS idempresa, b.nomempresa AS empresa ";
    $query.= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
    $query.= $d->idempresa != '' ? "WHERE b.id IN($d->idempresa) " : "";
    $query.= "ORDER BY b.nomempresa";
    $proyectos = $db->getQuery($query);


    foreach($proyectos as $p){
        $query = "SELECT DISTINCT c.id AS idtipo, c.descripcion AS tipoproyecto ";
        $query.= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
        $query.= "WHERE b.id = $p->idempresa ";
        $query.= $d->idtipo != '' ? "AND c.id IN($d->idtipo) " : "";
        $query.= "ORDER BY c.descripcion";
        $p->tipo = $db->getQuery($query);

        foreach($p->tipo as $t){
            $query = "SELECT a.id AS idproyecto, a.nomproyecto AS proyecto, a.referencia, a.direccion, a.metros_rentable AS mcrentables, a.metros AS mcuad, a.notas, a.fechaapertura ";
            $query.= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
            $query.= "WHERE b.id = $p->idempresa AND c.id = $t->idtipo ";
            $query.= $d->idproyecto != '' ? "AND a.id IN($d->idproyecto) " : "";
            $query.= "ORDER BY a.nomproyecto";
            $t->proyectos = $db->getQuery($query);

            if((int)$d->detallado == 1){
                foreach($t->proyectos as $proy){
                    //$query = "SELECT DISTINCT b.idempresa, c.nomempresa AS empresa ";
                    $query = "SELECT DISTINCT b.idempresa, IF(b.idempresa = $p->idempresa, '', c.nomempresa) AS empresa ";
                    $query.= "FROM detalle_activo_proyecto a INNER JOIN activo b ON b.id = a.idactivo INNER JOIN empresa c ON c.id = b.idempresa WHERE a.idproyecto = $proy->idproyecto ";
                    $query.= "ORDER BY c.nomempresa";
                    $proy->tactivo = $db->getQuery($query);

                    $proy->atot = 0.00;
                    foreach($proy->tactivo as $ta){
                        $query = "SELECT CONCAT(b.finca, '-', b.folio, '-', b.libro) AS ffl, b.metros_muni ";
                        $query.= "FROM detalle_activo_proyecto a INNER JOIN activo b ON b.id = a.idactivo INNER JOIN empresa c ON c.id = b.idempresa ";
                        $query.= "WHERE a.idproyecto = $proy->idproyecto AND b.idempresa = $ta->idempresa ";
                        $query.= "ORDER BY CAST(digits(b.finca) AS UNSIGNED), CAST(digits(b.folio) AS UNSIGNED), CAST(digits(b.libro) AS UNSIGNED)";
                        $ta->activos = $db->getQuery($query);
                        $subtot = 0.00;
                        foreach($ta->activos as $a){
                            $subtot += round((float)$a->metros_muni, 2);
                            $proy->atot += round((float)$a->metros_muni, 2);
                        }
                        array_push($ta->activos, ['ffl' => 'Total', 'metros_muni' => $subtot]);
                    }

                    $query = "SELECT DISTINCT b.id AS idtipou, b.descripcion AS tipo FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal WHERE a.idproyecto = $proy->idproyecto ORDER BY b.descripcion";
                    $proy->tunidad = $db->getQuery($query);

                    $proy->utot = 0.00;
                    foreach($proy->tunidad as $tu){
                        $query = "SELECT a.nombre, a.mcuad ";
                        $query.= "FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
                        $query.= "WHERE a.idproyecto = $proy->idproyecto AND b.id = $tu->idtipou ";
                        $query.= "ORDER BY CAST(digits(a.nombre) AS UNSIGNED), a.nombre";
                        $tu->unidades = $db->getQuery($query);
                        $subtot = 0.00;
                        foreach($tu->unidades as $u){
                            $subtot += round((float)$u->mcuad, 2);
                            $proy->utot += round((float)$u->mcuad, 2);
                        }
                        array_push($tu->unidades, ['nombre' => 'Total', 'mcuad' => $subtot]);
                    }
                }
            }
        }
    }

    print json_encode($proyectos);
});

$app->run();