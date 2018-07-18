<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/pagosiusi', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT a.id AS idempresa, a.nomempresa AS empresa, 0.00 AS totiusiempre, 0.00 totapagarempre ";
    $query.= "FROM empresa a INNER JOIN activo b ON a.id = b.idempresa WHERE b.debaja = 0 ";
    $query.= $d->idempresa != '' ? "AND a.id IN($d->idempresa) " : "";
    $query.= "ORDER BY a.nomempresa";
    $activos = $db->getQuery($query);

    $cntAct = count($activos);
    for($i = 0; $i < $cntAct; $i++){
        $activo = $activos[$i];
        $query = "SELECT DISTINCT b.id AS iddepto, CONCAT(b.nomdepto, ' - ', b.nombre) AS departamento ";
        $query.= "FROM activo a INNER JOIN municipio b ON b.id = a.departamento ";
        $query.= "WHERE a.debaja = 0 AND a.idempresa = $activo->idempresa ";
        $query.= $d->depto != '' ? "AND b.id IN($d->depto) " : "";
        $query.= "ORDER BY 2";
        $activo->deptos = $db->getQuery($query);
        $cntDep = count($activo->deptos);
        $sumEmp = ['iusi' => 0.00, 'apagar' => 0.00, 'trimestral' => 0.00];
        if($cntDep > 0){
            for($j = 0; $j < $cntDep; $j++){
                $depto = $activo->deptos[$j];
                $query = "SELECT CONCAT(a.finca, '-', a.folio, '-', a.libro) AS finca, ";
                $query.= "IF(a.horizontal = 0, '', 'Sí') AS eshorizontal, a.iusi, ROUND((a.iusi * (a.por_iusi / 1000)), 2) AS apagar, a.por_iusi, ROUND((a.iusi * (a.por_iusi / 1000)) / 4, 2) AS trimestral ";
                $query.= "FROM activo a LEFT JOIN municipio b ON b.id = a.departamento LEFT JOIN empresa c ON c.id = a.idempresa ";
                $query.= "WHERE a.debaja = 0 AND a.idempresa = $activo->idempresa AND a.departamento = $depto->iddepto ";
                //$query.= "ORDER BY digits(a.finca), digits(a.folio), digits(a.libro)";
                $query.= "ORDER BY digits(a.finca)";
                $depto->activos = $db->getQuery($query);
                $cntDet = count($depto->activos);
                if($cntDet > 0){
                    $sumAct = ['iusi' => 0.00, 'apagar' => 0.00, 'trimestral' => 0.00];
                    for($k = 0; $k < $cntDet; $k++){
                        $det = $depto->activos[$k];
                        $sumAct['iusi'] += (float)$det->iusi;
                        $sumAct['apagar'] += (float)$det->apagar;
                        $sumAct['trimestral'] += (float) $det->trimestral;
                    }
                    $depto->activos[] = [
                        'finca' => '', 'eshorizontal' => ('Total de '.$depto->departamento), 'iusi' => round($sumAct['iusi'], 2), 'apagar' => round($sumAct['apagar'], 2), 'trimestral' => round($sumAct['trimestral'], 2), 'por_iusi' => ''
                    ];
                    $sumEmp['iusi'] += $sumAct['iusi'];
                    $sumEmp['apagar'] += $sumAct['apagar'];
                    $sumEmp['trimestral'] += $sumAct['trimestral'];
                }
            }
            $activo->totiusiempre = $sumEmp['iusi'];
            $activo->totapagarempre = $sumEmp['apagar'];
            $activo->totapagarempretrimestral = $sumEmp['trimestral'];
        }
    }

    $sumGen = ['iusi' => 0.00, 'apagar' => 0.00, 'trimestral' => 0.00];
    for($l = 0; $l < $cntAct; $l++){
        $sumGen['iusi'] += $activos[$l]->totiusiempre;
        $sumGen['apagar'] += $activos[$l]->totapagarempre;
        $sumGen['trimestral'] += $activos[$l]->totapagarempretrimestral;
    }

    print json_encode(['activos' => $activos, 'totiusigen' => round($sumGen['iusi'], 2), 'totapagargen' => round($sumGen['apagar'], 2), 'totapagartrimestral' => round($sumGen['trimestral'], 2)]);
});


$app->run();