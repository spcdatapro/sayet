<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/premios', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al";
    $generales = $db->getQuery($query)[0];

    $qGen = "SELECT a.idempresaactual AS idempresa, c.nombre AS empresa, '' AS depto, a.id AS codigo, TRIM(CONCAT(a.nombre, ' ', IFNULL(a.apellidos, ''))) AS nombres, ";
    $qGen.= "DATE_FORMAT(a.ingreso, '%d/%m/%Y') AS ingreso, DATEDIFF('$d->falstr', a.ingreso) AS dias, (DATEDIFF('$d->falstr', a.ingreso) DIV 365) AS anioslaborados, ";
    $qGen.= "TRUNCATE(ABS(((DATEDIFF('$d->falstr', a.ingreso) / 365) - (DATEDIFF('$d->falstr', a.ingreso) DIV 365))) * 12, 0) AS meses, b.descripcion AS puesto, a.sueldo, ";
    $qGen.= "a.bonificacionley AS bonif, (a.sueldo + a.bonificacionley) AS sueldocompleto, d.nombre AS premio, d.id AS idpremio, d.anios, c.ordenreppres, d.esefectivo ";
    $qGen.= "FROM plnempleado a INNER JOIN plnpremioanti d ON d.anios = (DATEDIFF('$d->falstr', a.ingreso) DIV 365) LEFT JOIN plnpuesto b ON b.id = a.idplnpuesto ";
    $qGen.= "LEFT JOIN plnempresa c ON c.id = a.idempresaactual ";
    $qGen.= "WHERE a.baja IS NULL AND (DATEDIFF('$d->falstr', a.ingreso) DIV 365) >= 5 ";
    //$qGen.= "ORDER BY c.ordenreppres, c.nomempresa, b.descripcion, a.id";

    $query = "SELECT DISTINCT z.idpremio, z.anios, z.premio FROM ($qGen) z ORDER BY z.anios";
    //print $query;
    $premios = $db->getQuery($query);
    $cntPremios = count($premios);
    for($i = 0; $i < $cntPremios; $i++){
        $premio = $premios[$i];
        $query = "SELECT DISTINCT z.idempresa, z.empresa FROM ($qGen) z WHERE z.idpremio = $premio->idpremio ORDER BY z.ordenreppres";
        $premio->empresas = $db->getQuery($query);
        $cntEmpresas = count($premio->empresas);
        for($j = 0; $j < $cntEmpresas; $j++){
            $empresa = $premio->empresas[$j];
            $query = "SELECT LPAD(z.codigo, 3, '0') AS codigo, z.nombres, z.puesto, z.ingreso, z.anioslaborados, z.meses, ";
            $query.= "FORMAT(z.sueldo, 2) AS sueldo, FORMAT(z.bonif, 2) AS bonif, FORMAT(z.sueldocompleto, 2) AS sueldocompleto, FORMAT(z.sueldocompleto * z.idpremio, 2) AS sueldopremio, IF(z.esefectivo = 0, NULL, 1) AS esefectivo ";
            $query.= "FROM ($qGen) z ";
            $query.= "WHERE z.idpremio = $premio->idpremio AND z.idempresa = $empresa->idempresa ";
            $query.= "ORDER BY z.codigo, z.anioslaborados, z.meses DESC";
            $empresa->premiados = $db->getQuery($query);
        }
    }

    print json_encode(['generales' => $generales, 'premios' => $premios]);
});


$app->run();