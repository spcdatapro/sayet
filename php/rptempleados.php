<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptempelados', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT 
               id, nombre, IFNULL(CONCAT('(', numeropat, ')'), '') AS patronal
            FROM
                plnempresa
            WHERE
                id IN (SELECT 
                        idempresadebito
                    FROM
                        plnempleado ";
    $query.= $d->inactivos == 0 ? "WHERE baja IS NULL) " : ")";
    $query.= $d->idempresa == 0 ? "" : "AND id = $d->idempresa ";
    $empresas = $db->getQuery($query);

    $cntEmpresas = count($empresas);

    for ($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $empresas[$i];

        $tordinario = array();
        $tbono = array();
        $total = array();

        $query = "SELECT 
                    a.id,
                    CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                    DATE_FORMAT(ingreso, '%d/%m/%Y') AS fingreso,
                    IFNULL(DATE_FORMAT(reingreso, '%d/%m/%Y'), '') AS freingreso,
                    a.sueldo,
                    a.bonificacionley,
                    a.sueldo + a.bonificacionley AS sueldotot
                FROM
                    plnempleado a
                WHERE
                    a.idempresadebito = $empresa->id ";
        $query.= $d->inactivos == 0 ? "AND a.baja IS NULL " : "";
        $empresa->empleados = $db->getQuery($query);
    }
    print json_encode([ 'empresa' => $empresas ]);
});

$app->run();