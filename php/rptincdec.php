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

    //$contratos = [];
    $empresas = [];

    //Extraigo las diferentes empresas para agrupar los incrementos/decrementos
    foreach($mtrx as $item){
        if(!existe((int)$item['idempresa'], $empresas, 'idempresa')){
            array_push($empresas,['idempresa' => (int)$item['idempresa'], 'empresa' => $item['empresa'], 'proyectos' => []]);
        }
    }
    //Ordeno por nombre de empresa
    usort($empresas, function($a, $b){ return $a['empresa'] == $b['empresa'] ? 0 : ($a['empresa'] < $b['empresa'] ? -1 : 1); });

    $cntEmp = count($empresas);
    for($i = 0; $i < $cntEmp; $i++){
        foreach($mtrx as $item){
            if((int)$item['idempresa'] == $empresas[$i]['idempresa']){
                if(!existe((int)$item['idproyecto'], $empresas[$i]['proyectos'], 'idproyecto')){
                    $empresas[$i]['proyectos'][] = ['idproyecto' => (int)$item['idproyecto'], 'proyecto' => $item['proyecto'], 'contratos' => []];
                }
            }
        }
        usort($empresas[$i]['proyectos'], function($a, $b){ return $a['proyecto'] == $b['proyecto'] ? 0 : ($a['proyecto'] < $b['proyecto'] ? -1 : 1); });
    }

    for($i = 0; $i < $cntEmp; $i++){
        $cntProy = count($empresas[$i]['proyectos']);
        for($j = 0; $j < $cntProy; $j++){
            foreach($mtrx as $item){
                if((int)$item['idempresa'] == $empresas[$i]['idempresa'] && (int)$item['idproyecto'] == $empresas[$i]['proyectos'][$j]['idproyecto']){
                    $empresas[$i]['proyectos'][$j]['contratos'][] = [
                        'cliente' => $item['cliente'],
                        'abreviatura' => $item['abreviatura'],
                        'servicio' => $item['servicio'],
                        'locales' => $item['locales'],
                        'fechainicia' => $item['fechainicia'],
                        'fechavence' => $item['fechavence'],
                        'monanterior' => $item['monanterior'],
                        'anterior' => $item['anterior'],
                        'monsiguiente' => $item['monsiguiente'],
                        'actual' => $item['actual'],
                        'incremento' => $item['incremento'],
                        'fechacobro' => $item['fechacobro']
                    ];
                }
            }
            usort($empresas[$i]['proyectos'][$j]['contratos'], function($a, $b){ return $a['cliente'] == $b['cliente'] ? 0 : ($a['cliente'] < $b['cliente'] ? -1 : 1); });
        }
    }

    return $empresas;
}

function incdec($d, $inc){
    $db = new dbcpm();
    $mtrx = [];

    $query = "SELECT DISTINCT idcontrato FROM cargo a WHERE a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' ORDER BY idcontrato";
    $contratos = $db->getQuery($query);

    foreach($contratos as $contrato){
        $query = "SELECT DISTINCT b.idtipoventa ";
        $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont ";
        $query.= "WHERE a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND a.idcontrato = $contrato->idcontrato";
        $tipos = $db->getQuery($query);
        foreach($tipos as $tipo){
            $query = "SELECT a.idcontrato, d.idempresa, f.nomempresa AS empresa, d.idproyecto, g.nomproyecto AS proyecto, e.nombre AS cliente, e.nombrecorto AS abreviatura, ";
            $query.= "b.idtipoventa, c.desctiposervventa AS servicio, UnidadesPorContrato(d.id) AS locales, d.fechainicia, d.fechavence, a.fechacobro, h.simbolo, a.monto, ";
            $query.= "i.descripcion AS incremento ";
            $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN tiposervicioventa c ON c.id = b.idtipoventa INNER JOIN contrato d ON d.id = a.idcontrato ";
            $query.= "INNER JOIN cliente e ON e.id = d.idcliente INNER JOIN empresa f ON f.id = d.idempresa INNER JOIN proyecto g ON g.id = d.idproyecto INNER JOIN moneda h ON h.id = b.idmoneda ";
            $query.= "LEFT JOIN tipoipc i ON i.id = d.idtipoipc ";
            $query.= "WHERE a.fechacobro >= DATE_SUB('$d->fdelstr', INTERVAL 1 MONTH) AND a.fechacobro <= '$d->falstr' AND a.idcontrato = $contrato->idcontrato AND b.idtipoventa = $tipo->idtipoventa AND ";
            $query.= "(d.inactivo = 0 OR (d.inactivo = 1 AND d.fechainactivo > '$d->falstr')) ";
            $query.= "ORDER BY a.fechacobro, f.nomempresa, g.nomproyecto, e.nombre, c.desctiposervventa";
            $cargos = $db->getQuery($query);
            $cnt = count($cargos);
            for($i = 0; $i < $cnt; $i++){
                $actual = $cargos[$i];
                $siguiente = array_key_exists(($i + 1), $cargos) ? $cargos[$i + 1] : $cargos[$i];
                if($inc){
                    if((float)$siguiente->monto > (float)$actual->monto){
                        array_push($mtrx, [
                            'idempresa' => $actual->idempresa,
                            'empresa' => $actual->empresa,
                            'idproyecto' => $actual->idproyecto,
                            'proyecto'=> $actual->proyecto,
                            'cliente' => $actual->cliente,
                            'abreviatura' => $actual->abreviatura,
                            'servicio' => $actual->servicio,
                            'locales' => $actual->locales,
                            'fechainicia' => $actual->fechainicia,
                            'fechavence' => $actual->fechavence,
                            'monanterior' => $actual->simbolo,
                            'anterior' => $actual->monto,
                            'monsiguiente' => $siguiente->simbolo,
                            'actual' => $siguiente->monto,
                            'incremento' => $siguiente->incremento,
                            'fechacobro' => $siguiente->fechacobro
                        ]);
                    }
                }else{
                    if((float)$siguiente->monto < (float)$actual->monto){
                        array_push($mtrx, [
                            'idempresa' => $actual->idempresa,
                            'empresa' => $actual->empresa,
                            'idproyecto' => $actual->idproyecto,
                            'proyecto'=> $actual->proyecto,
                            'cliente' => $actual->cliente,
                            'abreviatura' => $actual->abreviatura,
                            'servicio' => $actual->servicio,
                            'locales' => $actual->locales,
                            'fechainicia' => $actual->fechainicia,
                            'fechavence' => $actual->fechavence,
                            'monanterior' => $actual->simbolo,
                            'anterior' => $actual->monto,
                            'monsiguiente' => $siguiente->simbolo,
                            'actual' => $siguiente->monto,
                            'incremento' => $siguiente->incremento,
                            'fechacobro' => $siguiente->fechacobro
                        ]);
                    }
                }
            }//for
        }
    }

    //return prepMtrx($mtrx);
    return $mtrx;
}

$app->post('/incdec', function(){
    $d = json_decode(file_get_contents('php://input'));
    $tipo = (int)$d->tipo;
    switch($tipo){
        case 1:
            $inc = incdec($d, true);
            print json_encode(prepMtrx($inc));
            break;
        case 2:
            $dec = incdec($d, false);
            print json_encode(prepMtrx($dec));
            break;
        case 3:
            $inc = incdec($d, true);
            $dec = incdec($d, false);
            print json_encode(prepMtrx(array_merge($inc, $dec)));
            break;
    }
});

$app->run();