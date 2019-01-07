<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/catempleval', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, FORMAT($d->minimo, 2) AS minimo";
    $generales = $db->getQuery($query)[0];

    $qGen = "SELECT b.id AS idempresa, b.nombre AS empresa, a.id AS idempleado, TRIM(CONCAT(IFNULL(a.nombre, ''), ' ', IFNULL(a.apellidos, ''))) AS nombre, a.sueldo, ";
    $qGen.= "ROUND(($d->minimo - a.sueldo), 2) AS diferencia, a.bonificacionley, (a.sueldo + a.bonificacionley) AS anterior, ROUND(($d->minimo + a.bonificacionley), 2) AS actualizado, b.ordenreppres AS orden ";
    $qGen.= "FROM plnempleado a INNER JOIN plnempresa b ON b.id = a.idempresaactual ";
    $qGen.= "WHERE a.baja IS NULL AND a.sueldo < $d->minimo ";
    $qGen.= "ORDER BY b.ordenreppres, a.nombre, a.apellidos";

    $query = "SELECT DISTINCT idempresa, empresa FROM ($qGen) z ORDER BY z.orden";
    //print $query;
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $empresas[$i];
        $query = "SELECT LPAD(idempleado, 3, '0') AS idempleado, nombre, FORMAT(sueldo, 2) AS sueldo, FORMAT(diferencia, 2) AS diferencia, FORMAT(bonificacionley, 2) AS bonificacionley, ";
        $query.= "FORMAT(anterior, 2) AS anterior, FORMAT(actualizado, 2) AS actualizado ";
        $query.= "FROM ($qGen) z ";
        $query.= "WHERE idempresa = $empresa->idempresa ";
        $query.= "ORDER BY nombre";
        $empresa->empleados = $db->getQuery($query);
        if(count($empresa->empleados) > 0){
            $query = "SELECT FORMAT(SUM(sueldo), 2) AS sueldo, FORMAT(SUM(diferencia), 2) AS diferencia, FORMAT(SUM(bonificacionley), 2) AS bonificacionley, FORMAT(SUM(anterior), 2) AS anterior, ";
            $query.= "FORMAT(SUM(actualizado), 2) AS actualizado ";
            $query.= "FROM ($qGen) z ";
            $query.= "WHERE idempresa = $empresa->idempresa";
            $sumas = $db->getQuery($query)[0];
            $empresa->empleados[] = [
                'idempleado' => '', 'nombre' => 'Total empresa:', 'sueldo' => $sumas->sueldo, 'diferencia' => $sumas->diferencia,
                'bonificacionley' => $sumas->bonificacionley, 'anterior' => $sumas->anterior, 'actualizado' => $sumas->actualizado
            ];
        }
    }

    print json_encode(['generales' => $generales, 'empresas' => $empresas]);
});


$app->run();