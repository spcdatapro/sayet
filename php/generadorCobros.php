<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function procDataGen($d){
    $d->id = (int)$d->id;
    $d->idcontrato = (int)$d->idcontrato;
    $d->fdel = new DateTime($d->fdel, new DateTimeZone('America/Guatemala'));
    $d->fal = new DateTime($d->fal, new DateTimeZone('America/Guatemala'));
    $d->monto = (float)$d->monto;
    $d->dias = (int)$d->dias;
    return $d;
};

$app->get('/generar', function(){
    $db = new dbcpm();

    $query = "SELECT a.id, a.idcontrato, CONCAT(YEAR(a.fdel), '-', LPAD(MONTH(a.fdel), 2, '0'), '-01') AS fdel, a.fal, a.monto, c.dias ";
    $query.= "FROM detfactcontrato a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN periodicidad c ON c.id = b.idperiodicidad ";
    $detcont = $db->getQuery($query);
    foreach ($detcont as $det) {
        $infoGen = procDataGen($det);
        $fecha = $infoGen->fdel;
        while($fecha <= $infoGen->fal){
            $query = "INSERT INTO cargo(idcontrato, iddetcont, fgeneracion, fechacobro, monto) VALUES(";
            $query.= $infoGen->idcontrato.", ".$infoGen->id.", NOW(), '".$fecha->format('Y-m-01')."', ".$infoGen->monto;
            $query.= ")";
            $db->doQuery($query);
            $fecha->add(new DateInterval('P1M'));
        };
    }

    print json_encode(['Proceso terminado...']);
});

$app->run();