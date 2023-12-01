<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptempelados', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT a.id, a.nombre, IFNULL(CONCAT('(', a.numeropat, ')'), '') AS patronal 
    FROM plnempresa a INNER JOIN proyecto b ON b.idempresa = a.id WHERE b.id IN (SELECT idproyecto FROM plnempleado ";
    $query.= $d->inactivos == 0 ? "WHERE baja IS NULL) " : ")";
    $query.= $d->idempresa == 0 ? "" : "AND id = $d->idempresa ";
    $empresas = $db->getQuery($query);

    $cntEmpresas = count($empresas);

    for ($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $empresas[$i];

        $query = "SELECT id, nomproyecto AS proyecto FROM proyecto WHERE idempresa = $empresa->id AND 
        id IN (SELECT idproyecto FROM plnempleado ";
        $query.= $d->inactivos == 0 ? "WHERE baja IS NULL) " : ")";
        $empresa->proyectos = $db->getQuery($query);

        $cntProyectos = count($empresa->proyectos);

        if ($cntProyectos > 0) {
            $empresa->mostrar = true;
        } else {
            $empresa->mostrar = null;
        }

        for ($j = 0; $j < $cntProyectos; $j++) {
            $proyecto = $empresa->proyectos[$j];
    
            $query = "SELECT 
                        a.id,
                        CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                        DATE_FORMAT(ingreso, '%d/%m/%y') AS fingreso,
                        IFNULL(DATE_FORMAT(reingreso, '%d/%m/%y'), '') AS freingreso,
                        a.sueldo,
                        a.bonificacionley,
                        a.sueldo + a.bonificacionley AS sueldotot,
                        b.descripcion AS puesto
                    FROM
                        plnempleado a
                    INNER JOIN 
                        plnpuesto b ON a.idplnpuesto = b.id
                    WHERE
                        a.idproyecto = $proyecto->id ";
            $query.= $d->inactivos == 0 ? "AND a.baja IS NULL " : "";
            $query.= "ORDER BY a.nombre ASC ";
            $proyecto->empleados = $db->getQuery($query);
        }
    }

    print json_encode([ 'empresas' => $empresas ]);
});

$app->run();