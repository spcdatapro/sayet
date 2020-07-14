<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptivs', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, a.id, LPAD(a.id, 3, '0') AS codigo, a.nombre, a.igss AS afiliacionigss, ";
    $query.= "DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al ";
    $query.= "FROM plnempleado a ";
    $query.= "WHERE a.id = $d->idempleado";
    $generales = $db->getQuery($query)[0];    

    $qGen = "
        SELECT a.id, a.idplnempleado, a.idempresa, b.nombre AS empresa, b.numeropat, a.fecha, IFNULL(a.sueldoordinario, 0.00) AS sueldoordinario, IFNULL(a.sueldoextra, 0.00) AS sueldoextra, 
        (IFNULL(a.sueldoordinario, 0.00) + IFNULL(a.sueldoextra, 0.00)) AS totalsueldo,  IFNULL(a.vacaciones, 0.00) AS vacaciones, 
        (IFNULL(a.sueldoordinario, 0.00) + IFNULL(a.sueldoextra, 0.00) + IFNULL(a.vacaciones, 0.00)) AS grantotal, YEAR(a.fecha) AS anio, MONTH(a.fecha) AS nomes, (SELECT nombre FROM mes WHERE id = MONTH(a.fecha)) AS mes
        FROM plnnomina a
        INNER JOIN plnempresa b ON b.id = a.idempresa
        WHERE a.idplnempleado = $d->idempleado AND DAY(a.fecha) <> 15 AND a.esbonocatorce = 0 AND a.aguinaldo = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr'
        ORDER BY a.fecha";
    
    $query = "SELECT DISTINCT z.idempresa, z.empresa, z.numeropat FROM ($qGen) z ORDER BY z.fecha";
    $data = $db->getQuery($query);
    $cntEmpresas = count($data);
    for($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $data[$i];
        $query = "SELECT DISTINCT z.anio FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa ORDER BY z.fecha";
        $empresa->anios = $db->getQuery($query);
        $cntAnios = count($empresa->anios);
        for($j = 0; $j < $cntAnios; $j++) {
            $anio = $empresa->anios[$j];
            $query = "SELECT z.mes, FORMAT(z.sueldoordinario, 2) AS sueldoordinario, FORMAT(z.sueldoextra, 2) AS sueldoextra, FORMAT(z.totalsueldo, 2) AS totalsueldo, FORMAT(z.vacaciones, 2) AS vacaciones, ";
            $query.= "FORMAT(z.grantotal, 2) AS grantotal ";
            $query.= "FROM ($qGen) z ";
            $query.= "WHERE z.idempresa = $empresa->idempresa AND z.anio = $anio->anio ORDER BY z.fecha";
            $anio->pagos = $db->getQuery($query);            
        }
        $query = "SELECT FORMAT(SUM(z.sueldoordinario), 2) AS sueldoordinario, FORMAT(SUM(z.sueldoextra), 2) AS sueldoextra, FORMAT(SUM(z.totalsueldo), 2) AS totalsueldo, ";
        $query.= "FORMAT(SUM(z.vacaciones), 2) AS vacaciones, FORMAT(SUM(z.grantotal), 2) AS grantotal ";
        $query.= "FROM ($qGen) z ";
        $query.= "WHERE z.idempresa = $empresa->idempresa";
        $empresa->totalesempresa = $db->getQuery($query)[0];
    }

    $query = "SELECT FORMAT(SUM(z.sueldoordinario), 2) AS sueldoordinario, FORMAT(SUM(z.sueldoextra), 2) AS sueldoextra, FORMAT(SUM(z.totalsueldo), 2) AS totalsueldo, ";
    $query.= "FORMAT(SUM(z.vacaciones), 2) AS vacaciones, FORMAT(SUM(z.grantotal), 2) AS grantotal ";
    $query.= "FROM ($qGen) z ";
    $generales->totales = $db->getQuery($query)[0];

    print json_encode(['generales' => $generales, 'data' => $data]);
});

$app->run();