<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function existe($needle, $haystack, $key){
    //if($key == 'idproyecto'){ var_dump($haystack); }
    foreach($haystack as $item){
        if($item[$key] == $needle){
            return true;
        }
    }
    return false;
}

function prepMtrx($mtrx){

    $empresas = [];

    //Extraigo las diferentes empresas para agrupar los incrementos/decrementos
    foreach($mtrx as $item){
        if(!existe((int)$item->idempresa, $empresas, 'idempresa')){
            array_push($empresas,['idempresa' => (int)$item->idempresa, 'empresa' => $item->empresa, 'proyectos' => []]);
        }
    }
    //Ordeno por nombre de empresa
    usort($empresas, function($a, $b){ return $a['empresa'] == $b['empresa'] ? 0 : ($a['empresa'] < $b['empresa'] ? -1 : 1); });

    $cntEmp = count($empresas);
    for($i = 0; $i < $cntEmp; $i++){
        foreach($mtrx as $item){
            if((int)$item->idempresa == $empresas[$i]['idempresa']){
                if(!existe((int)$item->idproyecto, $empresas[$i]['proyectos'], 'idproyecto')){
                    $empresas[$i]['proyectos'][] = ['idproyecto' => (int)$item->idproyecto, 'proyecto' => $item->proyecto, 'contratos' => []];
                }
            }
        }
        usort($empresas[$i]['proyectos'], function($a, $b){ return $a['proyecto'] == $b['proyecto'] ? 0 : ($a['proyecto'] < $b['proyecto'] ? -1 : 1); });
    }

    for($i = 0; $i < $cntEmp; $i++){
        $cntProy = count($empresas[$i]['proyectos']);
        for($j = 0; $j < $cntProy; $j++){
            foreach($mtrx as $item){
                if((int)$item->idempresa == $empresas[$i]['idempresa'] && (int)$item->idproyecto == $empresas[$i]['proyectos'][$j]['idproyecto']){
                    $empresas[$i]['proyectos'][$j]['contratos'][] = [
                        'cliente' => $item->cliente,
                        'abreviatura' => $item->abreviatura,
                        'locales' => $item->locales,
                        'fechainicia' => $item->fechainicia,
                        'fechavence' => $item->fechavence,
                        'cargos' => $item->cargos
                    ];
                }
            }
            usort($empresas[$i]['proyectos'][$j]['contratos'], function($a, $b){ return $a['cliente'] == $b['cliente'] ? 0 : ($a['cliente'] < $b['cliente'] ? -1 : 1); });
        }
    }

    return $empresas;
}

function calcPorAumento($rActual, $rAnterior){ return (float)$rAnterior > 0 ? round(((float)$rActual - (float)$rAnterior) * 100 / (float)$rAnterior, 2) : 0.00; }

function vencimientos($d, $inc){
    $db = new dbcpm();

    $query = "SELECT d.id AS idcontrato, d.idempresa, f.nomempresa AS empresa, d.idproyecto, g.nomproyecto AS proyecto, d.idcliente, e.nombre AS cliente, e.nombrecorto AS abreviatura, ";
    $query.= "UnidadesPorContrato(d.id) AS locales, d.fechainicia, d.fechavence, i.descripcion AS incremento ";
    $query.= "FROM contrato d INNER JOIN cliente e ON e.id = d.idcliente INNER JOIN empresa f ON f.id = d.idempresa INNER JOIN proyecto g ON g.id = d.idproyecto LEFT JOIN tipoipc i ON i.id = d.idtipoipc ";
    $query.= "WHERE d.fechavence >= '$d->fdelstr' AND d.fechavence <= '$d->falstr' AND ";
    $query.= "(d.inactivo = 0 OR (d.inactivo = 1 AND d.fechainactivo > '$d->falstr')) ";
    $query.= "ORDER BY f.nomempresa, g.nomproyecto, e.nombre";
    $contratos = $db->getQuery($query);
    $cntCont = count($contratos);

    for($i = 0; $i < $cntCont; $i++ ){
        $contrato = $contratos[$i];
        $contrato->cargos = [];
        $query = "SELECT DISTINCT b.idtipoventa, c.desctiposervventa AS servicio ";
        $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN tiposervicioventa c ON c.id = b.idtipoventa ";
        $query.= "WHERE a.idcontrato = $contrato->idcontrato AND a.fechacobro <= '$d->falstr' ";
        $query.= "ORDER BY c.desctiposervventa";
        $tipos = $db->getQuery($query);
        foreach($tipos as $tipo){
            $query = "SELECT a.fechacobro, c.simbolo, a.monto ";
            $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN moneda c ON c.id = b.idmoneda ";
            $query.= "WHERE a.idcontrato = $contrato->idcontrato AND a.fechacobro <= '$d->falstr' AND b.idtipoventa = $tipo->idtipoventa ";
            $query.= "ORDER BY a.fechacobro DESC";
            $cargos = $db->getQuery($query);
            $cnt = count($cargos);
            for($j = 0; $j < $cnt; $j++){
                $actual = $cargos[$j];
                $siguiente = array_key_exists(($j + 1), $cargos) ? $cargos[$j + 1] : $cargos[$j];
                if((float)$siguiente->monto != (float)$actual->monto){
                    array_push($contrato->cargos, [
                        'idtipo' => $tipo->idtipoventa,
                        'servicio' => $tipo->servicio,
                        'fechaaumento' => $actual->fechacobro,
                        'aumento'=> calcPorAumento($actual->monto, $siguiente->monto),
                        'rentaactual' => $actual->monto,
                        'moneda' => $actual->simbolo
                    ]);
                    break;
                }else{
                    if($j == ($cnt - 1)){
                        array_push($contrato->cargos, [
                            'idtipo' => $tipo->idtipoventa,
                            'servicio' => $tipo->servicio,
                            'fechaaumento' => $actual->fechacobro,
                            'aumento'=> 0.00,
                            'rentaactual' => $actual->monto,
                            'moneda' => $actual->simbolo
                        ]);
                    }
                }
            }//for
        }
    }

    return prepMtrx($contratos);
    //return $contratos;
}

$app->post('/vencimientos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $vencimientos = vencimientos($d, true);
    print json_encode($vencimientos);
    //print json_encode(prepMtrx($vencimientos));
});

$app->run();