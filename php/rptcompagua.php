<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$db = new dbcpm();

$app->post('/comparativo', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, CONCAT(UPPER(a.nombre), ' ', $d->anio) AS al, CONCAT(UPPER(a.nombrecorto), SUBSTR('$d->anio', 3, 2)) AS actual FROM mes a WHERE a.id = $d->mes";
    $generales = $db->getQuery($query)[0];

    $qGen ="SELECT b.idempresa, i.nomempresa AS empresa, a.idproyecto, g.nomproyecto AS proyecto, a.idserviciobasico, b.numidentificacion, a.idunidad, h.nombre AS unidad, ";
    $qGen.= "0.00 AS mes01, 0.00 AS mes02, 0.00 AS mes03, 0.00 AS mes04, 0.00 AS mes05, 0.00 AS mes06, 0.00 AS mes07, 0.00 AS mes08, 0.00 AS mes09, 0.00 AS mes10, 0.00 AS mes11, 0.00 AS mes12, ";
    $qGen.= "0.00 AS promedio, (a.lectura - LecturaAnterior(a.idserviciobasico, $d->mes, $d->anio)) AS consumoactual, IFNULL(d.nombrecorto, 'VACANTE') AS cliente ";
    $qGen.= "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico LEFT JOIN contrato c ON c.id = (SELECT b.id FROM contrato b WHERE IF(b.inactivo = 1 AND MONTH(b.fechainactivo) = $d->mes AND YEAR(b.fechainactivo) = $d->anio, FIND_IN_SET(a.idunidad, b.idunidadbck), FIND_IN_SET(a.idunidad, b.idunidad)) LIMIT 1) ";
    $qGen.= "LEFT JOIN cliente d ON d.id = c.idcliente LEFT JOIN tiposervicioventa f ON f.id = b.idtiposervicio LEFT JOIN proyecto g ON g.id = a.idproyecto LEFT JOIN unidad h ON h.id = a.idunidad LEFT JOIN empresa i ON i.id = b.idempresa ";
    $qGen.= "WHERE a.estatus IN(2, 3) AND b.pagacliente = 0 AND (c.inactivo = 0 OR (c.inactivo = 1 AND MONTH(c.fechainactivo) = $d->mes AND YEAR(c.fechainactivo) = $d->anio) OR c.inactivo IS NULL) AND a.mes = $d->mes AND a.anio = $d->anio ";
    $qGen.= $d->empresas != '' ? "AND b.idempresa IN($d->empresas) " : '';
    $qGen.= $d->proyectos != '' ? "AND a.idproyecto IN($d->proyectos) " : '';
    //$qGen.= "ORDER BY i.nomempresa, g.nomproyecto, CAST(digits(h.nombre) AS UNSIGNED), h.nombre, b.numidentificacion";

    $query = "SELECT DISTINCT z.idempresa, z.empresa FROM ($qGen) z ORDER BY z.empresa";
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $empresas[$i];
        $query = "SELECT DISTINCT z.idproyecto, z.proyecto FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa ORDER BY z.proyecto";
        $empresa->proyectos = $db->getQuery($query);
        $cntProyectos = count($empresa->proyectos);
        for($j = 0; $j < $cntProyectos; $j++){
            $proyecto = $empresa->proyectos[$j];
            $query = "SELECT z.idserviciobasico, z.numidentificacion, z.idunidad, z.unidad, ";
            //$query.= "z.mes01, z.mes02, z.mes03, z.mes04, z.mes05, z.mes06, z.mes07, z.mes08, z.mes09, z.mes10, z.mes11, z.mes12, ";
            $query.= "z.promedio, z.consumoactual, z.cliente ";
            $query.= "FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa AND z.idproyecto = $proyecto->idproyecto ";
            $query.= "ORDER BY CAST(digits(z.unidad) AS UNSIGNED), z.unidad, z.numidentificacion";
            $proyecto->contadores = $db->getQuery($query);
            $cntContadores = count($proyecto->contadores);
            $sumatoriaProyecto = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '', 0.00];
            for($k = 0; $k < $cntContadores; $k++){
                $contador = $proyecto->contadores[$k];
                $cantMeses = 12;
                $sumProm = 0.00;
                $factorPromedio = 0;
                for($l = 1; $l <= 12; $l++){
                    $query = "SELECT MONTH(DATE_SUB('$d->anio-$d->mes-01', INTERVAL $cantMeses MONTH)) AS mes, YEAR(DATE_SUB('$d->anio-$d->mes-01', INTERVAL $cantMeses MONTH)) AS anio, ";
                    $query.= "CONCAT(UPPER(nombrecorto), SUBSTR('$d->anio', 3, 2)) AS messtr FROM mes WHERE id = MONTH(DATE_SUB('$d->anio-$d->mes-01', INTERVAL $cantMeses MONTH)) ";
                    $periodo = $db->getQuery($query)[0];
                    $mes = $periodo->mes;
                    $anio = $periodo->anio;
                    $query = "SELECT (IFNULL(lectura, 0.00) - IFNULL(LecturaAnterior($contador->idserviciobasico, $mes, $anio), 0.00)) AS consumo FROM lecturaservicio ";
                    $query.= "WHERE idserviciobasico = $contador->idserviciobasico AND mes = $mes AND anio = $anio";
                    $consumo = (float)$db->getOneField($query);
                    $consumoMes = $consumo >= 0 ? $consumo : 0;

                    $query = "SELECT IF(LecturaAnteriorAsNull($contador->idserviciobasico, $mes, $anio) IS NULL, 1, 0)";
                    $noesnulo = (int)$db->getOneField($query) === 0;
                    if($noesnulo){
                        $factorPromedio++;
                        $sumProm += $consumoMes;
                    }else{
                        $consumoMes = 0.00;
                    }

                    $query = "SELECT CONCAT(UPPER(nombrecorto), SUBSTR('$anio', 3, 2)) AS messtr FROM mes WHERE id = $mes";
                    $contador->consumos[] = ['mes' => $db->getOneField($query), 'consumo' => number_format($consumoMes, 2)];
                    $sumatoriaProyecto[$l - 1] += $consumoMes;
                    $cantMeses--;
                }
                $contador->promedio = number_format(round(($factorPromedio > 0 ? ($sumProm / $factorPromedio) : 0), 2), 2);
                $sumatoriaProyecto[13] += (float)$contador->consumoactual;
            }
            //array_map(function($num){return number_format($num,2);}, $array);
            $proyecto->sumatoria = array_map(function($num){ return $num !== '' ? number_format($num, 2) : ''; }, $sumatoriaProyecto);
        }
    }

    if($cntEmpresas > 0){
        $arrConsumos = $empresas[0]->proyectos[0]->contadores[0]->consumos;
        $cntArrConsumos = count($arrConsumos);
        for($i = 0; $i < $cntArrConsumos; $i++){
            $generales->columnas[] = ['columna' => $arrConsumos[$i]['mes']];
        }
    }

    print json_encode(['generales' => $generales, 'empresas' => $empresas]);

});

$app->run();