<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/lstempleados', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, CONCAT(b.primernombre, ' ', IFNULL(b.segundonombre, ''), ' ', IFNULL(b.tercernombre, ''), IFNULL(b.primerapellido, ''), ' ', IFNULL(b.segundoapellido, ''), ' ', IFNULL(b.apellidocasada, '')) AS nombre,
        IFNULL(c.reingreso, c.ingreso) AS ingreso FROM plnempleado a INNER JOIN plnpersonal b ON a.idpersonal = b.id INNER JOIN plnlaboral c ON a.idlaboral = c.id 
        WHERE a.nombre IS NOT NULL AND a.nombre != '' AND a.activo = 1 ORDER BY 2";
    print $db->doSelectASJson($query);
});

$app->post('/historial', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al";
    $generales = $db->getQuery($query)[0];

    $qGen = "SELECT a.id, a.idplnempleado, TRIM(CONCAT(IFNULL(TRIM(b.nombre), ''), ' ',IFNULL(TRIM(b.apellidos), ''))) AS nombre, c.nomempresa AS empresadebito, d.nombre AS empresaactual, d.numeropat, ";
    $qGen.= "DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.sueldoordinario, a.bonificacion, a.fecha AS fechaOrd ";
    $qGen.= "FROM plnnomina a LEFT JOIN plnempleado b ON b.id = a.idplnempleado LEFT JOIN plnlaboral e ON b.idlaboral = e.id LEFT JOIN empresa c ON c.id = e.idempresadebito LEFT JOIN plnempresa d ON d.id = e.idempresaactual ";
    $qGen.= "WHERE a.idplnempleado = $d->idempleado AND DAY(a.fecha) > 15 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";

    $query = "SELECT DISTINCT z.idplnempleado, z.nombre, z.empresaactual, z.numeropat FROM ($qGen) z ORDER BY z.nombre";
    //print $query;
    $empleados = $db->getQuery($query);
    $cntEmpleados = count($empleados);
    for($i = 0; $i < $cntEmpleados; $i++){
        $empleado = $empleados[$i];
        $query = "SELECT DISTINCT z.fecha, FORMAT(z.sueldoordinario, 2) AS sueldoordinario, FORMAT(z.bonificacion, 2) AS bonificacion FROM ($qGen) z ORDER BY z.fechaOrd";
        $empleado->historial = $db->getQuery($query);
        if(count($empleado->historial) > 0){
            $query = "SELECT DISTINCT FORMAT(SUM(z.sueldoordinario), 2) AS sueldoordinario, FORMAT(SUM(z.bonificacion), 2) AS bonificacion FROM ($qGen) z ORDER BY z.fechaOrd";
            $sumas = $db->getQuery($query)[0];
            $empleado->historial[] = ['fecha' => 'Total:', 'sueldoordinario' => $sumas->sueldoordinario, 'bonificacion' => $sumas->bonificacion];
        }
    }

    print json_encode(['generales' => $generales, 'empleados' => $empleados]);
});


$app->run();