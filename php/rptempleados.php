<?php
require 'vendor/autoload.php';
require_once 'db.php';
require 'Reportes.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptempelados', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = ['sueldo', 'bonificacionley', 'sueldotot'];

    // clase para fechas
    $letra = new stdClass();
    $fecha = new DateTime($d->fecha);

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = $d->inactivos ? 'Activos al '.$fecha->format('d/m/Y') : 'Activos';

    // array de facturas
    $empleados = array();

    $query = "SELECT 
                a.id,
                c.id AS idempresa,
                IFNULL(c.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                c.numeropat AS numero,
                e.idproyecto,
                IFNULL(d.nomproyecto, 'NO ESPECIFICADO') AS proyecto,
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                DATE_FORMAT(e.ingreso, '%d/%m/%y') AS fingreso,
                IFNULL(DATE_FORMAT(e.reingreso, '%d/%m/%y'), '') AS freingreso,
                e.sueldo,
                e.bonificacionley,
                e.sueldo + e.bonificacionley AS sueldotot,
                b.descripcion AS puesto,
                c.abreviatura
            FROM
                plnempleado a
                    INNER JOIN 
                plnlaboral e ON a.idlaboral = e.id
                    INNER JOIN
                plnpuesto b ON a.idplnpuesto = b.id
                    LEFT JOIN
                plnempresa c ON e.idempresaactual = c.id
                    LEFT JOIN
                proyecto d ON e.idproyecto = d.id ";
    $query.= !$d->inactivos ? "WHERE a.baja IS NULL " : "WHERE (a.baja <= $d->fechastr OR a.baja IS NULL) ";
    $query.= isset($d->idempresa) ? "AND a.idempresadebito = $d->idempresa " : "";
    $query.= "ORDER BY  3 , ";
    $query.= $d->agrupar == 2 ? " 6 , 7" : " 7";
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        minusculas($dat);
    }

    $porproyecto = $d->agrupar == 2 ? true : false;

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', $totales, $porproyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    foreach($totales as $t) {
        $letra->$t = array_sum($montos_generales->$t);
    }

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/altasbajas', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // separadores
    $separador_tipo = new StdClass;
    $separador_empresa = new StdClass;
    $separador_proyecto = new StdClass;

    // clase para fechas
    $letra = new stdClass();

    $letra->al = new DateTime($d->falstr);
    $letra->al = $letra->al->format('d/m/Y');
    $letra->del = new DateTime($d->fdelstr);
    $letra->del = $letra->del->format('d/m/Y');

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    if ($d->tipo == 1) {
        $letra->tipo = 'altas';
    } else if ($d->tipo == 2) {
        $letra->tipo = 'bajas';
    } else {
        $letra->tipo = '';
    }

    if ($d->agrupar == 2) {
        $letra->agrupar = 'proyecto';
    } else {
        $letra->agrupar = 'empresa';
    }

    // array de facturas
    $empleados = array();

    $query = "SELECT 
                a.id AS idempleado,
                IFNULL(b.id, '9999') AS idempresa,
                f.idproyecto,
                IF(a.activo = 0 AND ($d->tipo = 3 OR $d->tipo = 2), '1', '0') AS tipo,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                c.nomproyecto AS proyecto,
                CONCAT(e.primernombre, ' ', 
                IFNULL(e.segundonombre, '')
                , ' ', 
                IFNULL(e.tercernombre, '')
                , ' ', 
                IFNULL(e.primerapellido, ''), 
                ' ', 
                IFNULL(e.segundoapellido, ''), 
                ' ', 
                IFNULL(e.apellidocasada, '')) AS nombre,
                IFNULL(d.descripcion, 'NO ESPECIFICADO') AS puesto,
                IF(f.baja AND ($d->tipo = 3 OR $d->tipo = 2),
                    DATE_FORMAT(f.baja, '%d/%m/%Y'),
                    DATE_FORMAT(f.ingreso, '%d/%m/%Y')) AS fecha,
                f.sueldo,
                f.bonificacionley AS bono,
                (f.bonificacionley + f.sueldo) AS total,
                f.frecuencia AS pago,
                b.numeropat AS numero
            FROM
                plnempleado a
                    INNER JOIN 
                plnpersonal e ON a.idpersonal = e.id
                    INNER JOIN 
                plnlaboral f ON a.idlaboral = f.id
                    LEFT JOIN
                plnempresa b ON f.idempresaactual = b.id
                    INNER JOIN
                proyecto c ON f.idproyecto = c.id
                    LEFT JOIN
                plnpuesto d ON a.idplnpuesto = d.id
            WHERE  1 = 1 ";
    $query.= $d->tipo == 1 ? "AND f.ingreso >= '$d->fdelstr' AND f.ingreso <= '$d->falstr' " :
    ($d->tipo == 2 ? "AND f.baja >= '$d->fdelstr' AND f.baja <= '$d->falstr' " : 
    "AND (f.ingreso >= '$d->fdelstr' AND f.ingreso <= '$d->falstr' OR f.baja >= '$d->fdelstr' AND f.baja <= '$d->falstr') ");
    $query.= isset($d->idempresa) ? "AND f.idempresadebito = $d->idempresa " : "";
    $query.= isset($d->idproyecto) ? "AND f.idproyecto = $d->idproyecto " : "";
    $query.=   "ORDER BY 4 , 5 ,"; 
    $query.= $d->agrupar == 2 ? " 6 , 7" : " 7";
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        minusculas($dat);
    }

    $cntsFacturas = count($data);

    if ($cntsFacturas > 1) {
    for ($i = 1; $i < $cntsFacturas; $i++)  {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            // tipo 
            $separador_tipo->nombre = $anterior->tipo == 1 ? 'BAJAS' : 'ALTAS';
            $separador_tipo->mostrar = $d->tipo == 3 ? true : null;
            $separador_tipo->empresas = array();
            // empresa
            $separador_empresa->nombre = $anterior->empresa;
            $separador_empresa->numero = $anterior->numero;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            if ($d->agrupar == 2) {
                $separador_empresa->proyectos = array();
                // proyecto
                $separador_proyecto->nombre = $anterior->proyecto;
                $separador_proyecto->empleados = array();
            } else {
                $separador_empresa->empleados = array();
            }
            $primero = false;
        }

        if ($d->agrupar == 2) {
            array_push($separador_proyecto->empleados, $anterior);
        } else {
            array_push($separador_empresa->empleados, $anterior);
        }

        if ($anterior->tipo !== $actual->tipo) {
            // empujar a array padre
            if ($d->agrupar == 2) {
                array_push($separador_empresa->proyectos, $separador_proyecto);

                // separador
                $separador_proyecto = new StdClass;
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
            }

            array_push($separador_tipo->empresas, $separador_empresa);

            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->numero = $actual->numero;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            if ($d->agrupar == 2) {
                $separador_empresa->proyectos = array();
            } else {
                $separador_empresa->empleados = array();
            }

            // empujar a array padre
            array_push($empleados, $separador_tipo);

            // separador
            $separador_tipo = new StdClass;
            $separador_tipo->nombre = $actual->tipo == 1 ? 'BAJAS' : 'ALTAS';
            $separador_tipo->mostrar = $d->tipo == 3 ? true : null;
            $separador_tipo->empresas = array();
        }

        if ($d->agrupar == 2) {
            if ($anterior->idproyecto !== $actual->idproyecto && $anterior->tipo == $actual->tipo) {
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

                // separador
                $separador_proyecto = new StdClass;
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
            }
        }

        if ($anterior->idempresa !== $actual->idempresa && $anterior->tipo == $actual->tipo) {
            // empujar a array padre
            array_push($separador_tipo->empresas, $separador_empresa);

            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->numero = $actual->numero;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            if ($d->agrupar == 2) {
                $separador_empresa->proyectos = array();
            } else {
                $separador_empresa->empleados = array();
            }
        }
        
        // para empujar el ultimo dato
        if ($i+1 == $cntsFacturas) {
            // empujar ultimo
            if ($d->agrupar == 2) {
                array_push($separador_proyecto->empleados, $actual);
            } else {
                array_push($separador_empresa->empleados, $actual);
            }

            if ($d->agrupar == 2) {
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);
            }

            array_push($separador_tipo->empresas, $separador_empresa);
            
            // empujar a array padre
            array_push($empleados, $separador_tipo);
        }
    }
    } else {
        for ($i = 0; $i < $cntsFacturas; $i++)  {
            // traer valor actual y anterior
            $actual = $data[$i];

            // si es el primero insertar nombre del separador y crear array de recibos
            if ($primero) {
                // tipo 
                $separador_tipo->nombre = $actual->tipo == 1 ? 'BAJAS' : 'ALTAS';
                $separador_tipo->mostrar = $d->tipo == 3 ? true : null;
                $separador_tipo->empresas = array();
                // empresa
                $separador_empresa->nombre = $actual->empresa;
                $separador_empresa->numero = $actual->numero;
                $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
                if ($d->agrupar == 2) {
                    $separador_empresa->proyectos = array();
                    // proyecto
                    $separador_proyecto->nombre = $anterior->proyecto;
                    $separador_proyecto->empleados = array();
                    $primero = false;
                } else {
                    $separador_empresa->empleados = array();
                }
            }

            if ($d->agrupar == 2) {
                array_push($separador_proyecto->empleados, $actual);
            } else {
                array_push($separador_empresa->empleados, $actual);
            }
            array_push($separador_empresa->proyectos, $separador_proyecto);
            array_push($separador_tipo->empresas, $separador_empresa);
            array_push($empleados, $separador_tipo);
        }
    } 

    print json_encode([ 'encabezado' => $letra, 'tipo' => $empleados ]);
});

$app->post('/bono14', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = ['bonocatorce'];

    // clase para fechas
    $letra = new stdClass();

    $anio_anterior = $d->anio - 1;
    $fdel = $anio_anterior.'-07-01';
    $fal = $d->anio.'-06-30';

    $letra->al = new DateTime($fal);
    $letra->al = $letra->al->format('d/m/Y');
    $letra->del = new DateTime($fdel);
    $letra->del = $letra->del->format('d/m/Y');

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
                a.id AS idempleado,
                IFNULL(b.id, '9999') AS idempresa,
                IFNULL(g.idproyecto, '9999') AS idproyecto,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                IFNULL(c.nomproyecto, 'SIN PROYECTO') AS proyecto,
                CONCAT(IFNULL(f.primernombre, ''), ' ', IFNULL(f.segundonombre, '')
                , ' ', 
                IFNULL(f.tercernombre, ''), ' ', 
                IFNULL(f.primerapellido, ''), ' ',
                IFNULL(f.primerapellido, ''), ' ', 
                IFNULL(f.segundoapellido, ''), ' ', 
                IFNULL(f.apellidocasada, '')) AS nombre,
                b.numeropat AS numero,
                IFNULL(d.descripcion, 'NO ESPECIFICADO') AS puesto,
                DATE_FORMAT(IFNULL(g.reingreso, g.ingreso), '%d/%m/%Y') AS fecha,
                g.sueldo,
                e.bonocatorcedias,
                e.bonocatorce,
                b.abreviatura
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal f ON a.idpersonal = f.id
                    INNER JOIN
                plnlaboral g ON a.idlaboral = g.id
                    INNER JOIN
                plnnomina e ON e.idplnempleado = a.id
                    LEFT JOIN
                plnempresa b ON g.idempresaactual = b.id
                    LEFT JOIN
                proyecto c ON g.idproyecto = c.id
                    LEFT JOIN
                plnpuesto d ON a.idplnpuesto = d.id
            WHERE
                e.bonocatorce > 0 AND YEAR(fecha) = $d->anio ";
    $query.= isset($d->idempresa) ? "AND f.idempresadebito = $d->idempresa " : "";
    $query.=   "ORDER BY 4 ,";
    $query.= $d->agrupar == 2 ? " 5 , 6" : " 6";
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        minusculas($dat);
    }

    $porproyecto = $d->agrupar == 2 ? true : false;

    $letra->agrupar = $d->agrupar == 2 ? 'proyecto' : 'empresa';

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', $totales, $porproyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    foreach($totales as $t) {
        $letra->$t = array_sum($montos_generales->$t);
    }

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/aguinaldo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = ['aguinaldo'];

    // clase para fechas
    $letra = new stdClass();

    $anio_anterior = $d->anio - 1;
    $fdel = $anio_anterior.'-12-01';
    $fal = $d->anio.'-11-30';

    $letra->al = new DateTime($fal);
    $letra->al = $letra->al->format('d/m/Y');
    $letra->del = new DateTime($fdel);
    $letra->del = $letra->del->format('d/m/Y');

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
                a.id AS idempleado,
                IFNULL(b.id, '9999') AS idempresa,
                IFNULL(g.idproyecto, '9999') AS idproyecto,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                IFNULL(c.nomproyecto, 'SIN PROYECTO') AS proyecto,
                CONCAT(f.primernombre, ' ', IFNULL(f.segundonombre, ''), ' ', IFNULL(f.tercernombre, ''), ' ', IFNULL(f.primerapellido, ''), 
                ' ', IFNULL(f.segundoapellido, ''), ' ', IFNULL(f.apellidocasada, '')) AS nombre,
                b.numeropat AS numero,
                IFNULL(d.descripcion, 'NO ESPECIFICADO') AS puesto,
                DATE_FORMAT(IFNULL(g.reingreso, g.ingreso), '%d/%m/%Y') AS fecha,
                g.sueldo,
                e.aguinaldodias,
                e.aguinaldo,
                b.abreviatura
            FROM
                plnempleado a
                    INNER JOIN
                plnnomina e ON e.idplnempleado = a.id
                    LEFT JOIN
                plnpuesto d ON a.idplnpuesto = d.id
                    LEFT JOIN 
                plnpersonal f ON a.idpersonal = f.id
                    LEFT JOIN
                plnlaboral g ON a.idlaboral = g.id
                    LEFT JOIN
                proyecto c ON g.idproyecto = c.id
                    LEFT JOIN
                plnempresa b ON g.idempresaactual = b.id
            WHERE
                e.aguinaldo > 0 AND YEAR(fecha) = $d->anio ";
    $query.= isset($d->idempresa) ? "AND g.idempresadebito = $d->idempresa " : "";
    $query.=   "ORDER BY 4 ,";
    $query.= $d->agrupar == 2 ? " 5 , 6" : " 6";
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        minusculas($dat);
    }

    $porproyecto = $d->agrupar == 2 ? true : false;

    $letra->agrupar = $d->agrupar == 2 ? 'proyecto' : 'empresa';

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', $totales, $porproyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    foreach($totales as $t) {
        $letra->$t = array_sum($montos_generales->$t);
    }

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/vacaciones', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = [];

    // para periodo
    $fal = $d->anio.'-01-01';
    $fdel = $d->anio.'-12-31';
    $al= new DateTime($fal);
    $del = new DateTime($fdel);

    // clase para fechas
    $letra = new stdClass();
    $letra->estampa = new DateTime();

    // encabezado
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = 'Período del '.$al->format('d/m/Y').' al '. $del->format('d/m/Y');
    $letra->anio = $d->anio;

    $letra->agrupar = $d->agrupar == 2 ? 'proyecto' : 'empresa';

    // array de facturas
    $empleados = array();

    $query = "SELECT 
                d.id AS idempresa,
                d.nombre AS empresa,
                d.numeropat AS numero,
                d.abreviatura,
                c.idproyecto,
                e.nomproyecto AS proyecto,
                a.id AS codigo,
                CONCAT(IFNULL(b.primernombre, ''),
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                f.descripcion AS puesto,
                DATE_FORMAT(IFNULL(c.reingreso, c.ingreso), '%d/%m/%Y') AS ingreso,
                IFNULL(c.reingreso, c.ingreso) AS fecha,
                g.anio,
                NULL AS dias_totales,
                SUM(g.dias) AS dias,
                NULL AS dias_gozar
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnempresa d ON c.idempresadebito = d.id
                    INNER JOIN
                proyecto e ON c.idproyecto = e.id
                    INNER JOIN
                plnpuesto f ON a.idplnpuesto = f.id
                    INNER JOIN
                plnvacaciones g ON g.idplnempleado = a.id AND g.anulado = 0
            WHERE
                g.anio = $d->anio ";
    $query.= isset($d->idempresa) ? "AND c.idempresadebito = $d->idempresa " : "";
    $query.= isset($d->idproyecto) ? "AND c.idproyecto = $d->idproyecto " : "";
    $query.="GROUP BY a.id
                ORDER BY  2 , ";
    $query.= $d->agrupar == 2 ? " 6 , 8" : " 8";
    $data = $db->getQuery($query);

    foreach($data as $v) {
        // calcular dias desde fecha_ingreso hasta fecha_baja (si existe) o hasta el ultimo dia del año
        $inicio = $v->fecha;
        $inicio = new DateTime($inicio);
        $fin = new DateTime($letra->anio . '-12-31');

        if ($inicio->format('Y') == $letra->anio){
            if ($fin < $inicio) {
                $totalDias = 0;
            } else {
                $interval = $inicio->diff($fin);
                // incluir ambos extremos
                $totalDias = (float)$interval->days + 1;
            }

            $v->dias_totales = round(($totalDias * 15) / 365, 2);
        } else {
            $v->dias_totales = 15;
        }

        $v->dias_gozar = $v->dias_totales - $v->dias;
    }

    $porproyecto = $d->agrupar == 2 ? true : false;

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', $totales, $porproyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/prestamos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = ['monto', 'cuota', 'saldoant', 'nuevo', 'descnomina', 'descuento', 'totdesc', 'saldo'];

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // para periodo
    $mes = $d->mes;

    // clase para fechas
    $letra = new stdClass();
    $letra->estampa = new DateTime();

    // encabezado
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = $meses[$mes].' '.$d->anio;

    // parametros 
    $d->mes = $d->mes + 1;

    $query = "SELECT 
                h.id AS idempresa,
                h.nombre AS empresa,
                h.numeropat AS numero,
                h.abreviatura,
                i.id AS idproyecto,
                i.nomproyecto AS proyecto,
                a.id AS idempleado,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                d.descripcion AS puesto,
                g.id AS idprestamo,
                DATE_FORMAT(g.fecha, '%d/%m/%Y') AS fecha,
                g.monto,
                g.cuotamensual AS cuota,
                (
                    g.monto
                    - COALESCE((SELECT SUM(pn.monto)
                                FROM plnpresnom pn
                                INNER JOIN plnnomina n ON pn.idplnnomina = n.id
                                WHERE pn.idplnprestamo = g.id
                                AND MONTH(n.fecha) <= $d->mes-1 AND YEAR(n.fecha) <= $d->anio-1), 0)
                    - COALESCE((SELECT SUM(pa.monto)
                                FROM plnpresabono pa
                                WHERE pa.idplnprestamo = g.id
                                AND MONTH(pa.fecha) <= $d->mes-1 AND YEAR(pa.fecha) <= $d->anio-1), 0)
                ) AS saldoant,
                IF(MONTH(g.fecha) = $d->mes
                        AND YEAR(g.fecha) = $d->anio,
                    g.monto,
                    0.00) AS nuevo,
                f.monto AS descnomina,
                j.monto AS descuento,
                f.monto + IFNULL(j.monto, 0.00) AS totdesc,
                (
                    g.monto
                    - COALESCE((SELECT SUM(pn.monto)
                                FROM plnpresnom pn
                                INNER JOIN plnnomina n ON pn.idplnnomina = n.id
                                WHERE pn.idplnprestamo = g.id
                                AND MONTH(n.fecha) <= $d->mes AND YEAR(n.fecha) <= $d->anio), 0)
                    - COALESCE((SELECT SUM(pa.monto)
                                FROM plnpresabono pa
                                WHERE pa.idplnprestamo = g.id
                                AND MONTH(pa.fecha) <= $d->mes AND YEAR(pa.fecha) <= $d->anio), 0)
                ) AS saldo
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnpuesto d ON a.idplnpuesto = d.id
                    INNER JOIN
                plnnomina e ON e.idplnempleado = a.id
                    INNER JOIN
                plnpresnom f ON f.idplnnomina = e.id
                    INNER JOIN
                plnprestamo g ON f.idplnprestamo = g.id
                    INNER JOIN
                plnempresa h ON e.idempresa = h.id
                    INNER JOIN
                proyecto i ON c.idproyecto = i.id
                    LEFT JOIN
                (SELECT 
                    idplnprestamo, SUM(monto) AS monto
                FROM
                    plnpresabono
                WHERE
                    MONTH(fecha) = $d->mes AND YEAR(fecha) = $d->anio
                GROUP BY idplnprestamo) j ON j.idplnprestamo = g.id
            WHERE
                g.anulado = 0 AND g.esembargo = 0
                    AND MONTH(g.fecha) <= $d->mes 
                    AND YEAR(g.fecha) <= $d->anio
                    AND YEAR(g.liquidacion) => $d->anio
                    AND MONTH(g.liquidacion) => $d->mes ";
    $query.= isset($d->idempresa) ? "AND h.id = $d->idempresa " : "";
    $query.= "GROUP BY g.id ORDER BY  2 , ";
    $query.= $d->agrupar == 2 ? " 6 , 8, 11" : " 8, 11";
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        minusculas($dat);
    }

    $porproyecto = $d->agrupar == 2 ? true : false;

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', $totales, $porproyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    foreach($totales as $t) {
        $letra->$t = array_sum($montos_generales->$t);
    }

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/antiguedad', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = ['monto', 'cuota', 'saldoant', 'nuevo', 'descnomina', 'descuento', 'saldo'];

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // clase para fechas
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->al = new DateTime($d->falstr);

    // encabezado
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = 'Al '.$letra->al->format('d/m/Y');

    $query = "SELECT 
                d.id AS idempresa,
                IFNULL(d.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                d.numeropat AS numero,
                d.abreviatura,
                h.idproyecto,
                IFNULL(e.nomproyecto, 'NO ESPECIFICADO') AS proyecto,
                c.id AS idempleado,
                CONCAT(IFNULL(g.primernombre, ''), ' ', IFNULL(g.segundonombre, ''), ' ', IFNULL(g.tercernombre, ''), ' ', IFNULL(g.primerapellido, ''), ' ',
                IFNULL(g.segundoapellido, ''), ' ', IFNULL(g.apellidocasada, '')) AS nombre,
                IFNULL(f.descripcion, 'NO ESPECIFICADO') As puesto,
                DATE_FORMAT(h.ingreso, '%d/%m/%Y') AS ingreso,
                DATEDIFF('$d->falstr', h.ingreso) AS dias,
                TIMESTAMPDIFF(YEAR,
                    h.ingreso,
                    '$d->falstr') AS anios,
                TIMESTAMPDIFF(MONTH,
                    h.ingreso,
                    '$d->falstr') AS meses
            FROM
                plnempleado c
                    INNER JOIN 
                plnpersonal g ON c.idpersonal = g.id
                    INNER JOIN
                plnlaboral h ON c.idlaboral = h.id
                    LEFT JOIN
                plnempresa d ON h.idempresaactual = d.id
                    LEFT JOIN
                proyecto e ON h.idproyecto = e.id
                    LEFT JOIN
                plnpuesto f ON c.idplnpuesto = f.id ";
    $query.= isset($d->idempresa) ? "WHERE h.idempresadebito = $d->idempresa " : "";
    $query.= isset($d->idempleado) ? "AND b.idplnempleado = $d->idempleado " : "";
    $query.= "ORDER BY  2 , ";
    $query.= $d->agrupar == 2 ? " 6 , 8" : " 8";
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        minusculas($dat);
    }

    $porproyecto = $d->agrupar == 2 ? true : false;

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', [], $porproyecto);
    $empleados = $reporte->getReporte();

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

function minusculas ($dat) {
    $dat->nombre = ucwords(strtolower($dat->nombre), ' ');
    if (isset($dat->puesto)) {
        $dat->puesto = ucfirst(strtolower($dat->puesto));
    }
}

$app->post('/ficha', function () {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    date_default_timezone_set("America/Guatemala");

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // clase para fechas
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    

    // encabezado
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    

    // SELECT DE FICHA DE EMPLEADO PLNEMPLEADO CONDICION EL ID DEL EMPLEADO 
    $query = "SELECT 
                a.id AS idempleado,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                e.nomproyecto AS proyecto,
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                IFNULL(c.descripcion, 'NO ESPECIFICADO') AS puesto,
                DATE_FORMAT(a.ingreso, '%d/%m/%Y') AS ingreso,
                a.dpi AS dpi,
                a.igss AS igss,
                a.Nit AS nit,
                a.cuentabanco AS cuentabancaria,
                a.direccion AS domicilio,
                a.telefono AS telefono,
                a.fechanacimiento AS fechadenacimiento,
                IF(a.estadocivil = '2',
                    'Casado',
                    'Soltero') AS estadocivil
            FROM
                plnempleado a
                    LEFT JOIN
                plnempresa b ON a.idempresadebito = b.id
                    LEFT JOIN
                plnpuesto c ON a.idplnpuesto = c.id
                    LEFT JOIN
                proyecto e ON a.idproyecto = e.id
            WHERE
                a.id = $d->idempleado";
    $empleado = $db->getQuery($query)[0];

    print json_encode([ 'encabezado' => $letra, 'empleado' => $empleado ]);
});

$app->get('/datos_empleador/:anio', function ($anio) {
    $db = new dbcpm();

    $query = "SELECT 
                a.id,
                b.primernombre,
                IFNULL(b.segundonombre, '') AS segundonombre,
                IFNULL(b.tercernombre, '') AS tercernombre,
                b.primerapellido,
                IFNULL(b.segundoapellido, '') AS segundoapellido,
                IFNULL(b.apellidocasada, '') AS apellidocasada,
                d.codigo AS nacionalidad,
                b.iddiscapacidad AS discapacidad,
                FIELD(b.estadocivil,
                        'soltero',
                        'casado',
                        'unido') AS estadocivil,
                FIELD(b.tipodoc,
                        'dpi',
                        'certificado de nacimiento',
                        'pasaporte') AS documento,
                b.documento AS numdocumento,
                d.codigo AS origen,
                '' AS permiso,
                IFNULL(b.idmunicipio, '') AS municipio,
                b.nit,
                c.igss,
                FIELD(b.sexo, 'hombre', 'mujer') AS sexo,
                DATE_FORMAT(b.nacimiento, '%d/%m/%Y') AS nacimiento,
                IFNULL(b.ideducacion, '') AS educacion,
                IFNULL(b.profesion, '') AS profesion,
                b.idcasta AS pueblo,
                b.idlengua AS lengua,
                IFNULL(b.hijos, 0) AS hijos,
                FIELD(c.temporalidad, 'indefinido', 'definido') AS temporalidad,
                FIELD(c.tipocontrato, 'verbal', 'escrito') AS tipo,
                DATE_FORMAT(c.ingreso, '%d/%m/%Y') AS inicio,
                IFNULL(DATE_FORMAT(c.reingreso, '%d/%m/%Y'), '') AS reinicio,
                IFNULL(DATE_FORMAT(c.baja, '%d/%m/%Y'), '') AS fin,
                IFNULL(c.idpuesto, '') AS puesto,
                FIELD(c.jornada,
                        'diurna',
                        'mixta',
                        'nocturna',
                        'no esta sujeto a jornada') AS jornada,
                IF(e.dias > 250, 250, e.dias) AS dias,
                c.sueldo,
                c.sueldo * 12 AS sueldo_anual,
                c.bonificacionley,
                '' AS horas_extra,
                '' AS valor_extra,
                e.aguinaldo,
                e.bonocatorce,
                '' AS comision,
                e.viaticos AS viaticos,
                e.otrosingresos,
                e.vacaciones,
                e.indemnizacion,
                IF(c.idproyecto = 16, 2, 1) AS sucursal
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                nacionalidad d ON b.idnacionalidad = d.id
                    INNER JOIN
                (SELECT 
                    SUM(diastrabajados) AS dias,
                        idplnempleado,
                        SUM(aguinaldo) AS aguinaldo,
                        SUM(bonocatorce) AS bonocatorce,
                        SUM(otrosingresos) AS otrosingresos,
                        SUM(viaticos) AS viaticos,
                        SUM(vacaciones) AS vacaciones,
                        SUM(indemnizacion) AS indemnizacion
                FROM
                    plnnomina
                WHERE
                    YEAR(fecha) = $anio
                GROUP BY idplnempleado) e ON e.idplnempleado = a.id
            WHERE
                (c.baja IS NULL OR YEAR(c.baja) = $anio)
                    AND YEAR(c.ingreso) <= $anio";
    print json_encode(["empleados" => $db->getQuery($query)]);
});

$app->get('/ficha/:idempleado', function ($idempelado) {
    $db = new dbcpm();

    $query = "SELECT 
                a.id,
                d.nombre AS empresa,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        ' ',
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                e.descripcion,
                DATE_FORMAT(c.ingreso, '%d/%m/%Y') AS ingreso,
                b.documento,
                c.igss,
                b.nit,
                c.cuentabanco,
                b.direccion,
                b.telefono,
                DATE_FORMAT(b.nacimiento, '%d/%m/%Y') AS nacimiento,
                b.estadocivil,
                f.nombre AS nom_emergencia,
                f.telefono AS tel_emergencia,
                f.direccion AS dir_emergencia,
                a.observaciones
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnempresa d ON c.idempresadebito = d.id
                    INNER JOIN
                plnpuesto e ON a.idplnpuesto = e.id
                    LEFT JOIN
                plnemergencia f ON a.idemergencia = f.id
            WHERE
                a.id = $idempelado";
    print json_encode([ 'empleado' => $db->getQuery($query)[0] ]);
});

$app->post('/contrato', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $meses = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");

    $query = "SELECT nombre, TIMESTAMPDIFF(YEAR, nacimiento, now()) AS edad, estadocivil, genero, profesion, nacionalidad, identificacion, domicilio FROM representante WHERE id = $d->idrepresentante";
    $representante = $db->getQuery($query)[0];

    $query = "SELECT 
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        ' ',
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                TIMESTAMPDIFF(YEAR, nacimiento, NOW()) AS edad,
                b.estadocivil,
                b.sexo,
                b.profesion,
                d.nacionalidad,
                b.documento,
                b.direccion,
                c.ingreso,
                c.temporalidad,
                e.descripcion AS puesto,
                c.jornada,
                c.sueldo,
                c.bonificacionley,
                f.nombre AS empresa
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                nacionalidad d ON b.idnacionalidad = d.id
                    INNER JOIN
                puesto e ON c.idpuesto = e.id
                    INNER JOIN
                plnempresa f ON c.idempresadebito = f.id
            WHERE
                a.id = $d->idempleado";
    $empleado = $db->getQuery($query)[0];

    $fecha = new DateTime($empleado->ingreso);
    // fecha
    $empleado->dia = $fecha->format('d');
    $empleado->mes = $meses[$fecha->format('n') - 1];
    $empleado->anio = $fecha->format('Y');

    // horas
    if ($empleado->jornada === 'diurna') {
        $empleado->diario = 8;
        $empleado->semana = 40;
        $empleado->incia_manana = '07:00';
        $empleado->fin = '12:00';
        $empleado->incia_tarde = '13:00';
        $empleado->fin_tarde = '16:00';
    }

    // numeros a letras
    $empleado->dia_letras = $n2l->to_word($empleado->dia);
    $empleado->anio_letras = $n2l->to_word($empleado->anio);
    $empleado->sueldo_letras = $n2l->to_word_int($empleado->sueldo, 'GTQ');
    $empleado->bonificacion_letras = $n2l->to_word_int($empleado->bonificacionley, 'GTQ');
    $empleado->diario_letras = $n2l->to_word($empleado->diario);
    $empleado->semana_letras = $n2l->to_word($empleado->semana);

    print json_encode([ 'representante' => $representante, 'empleado' => $empleado ]);
});

$app->post('/isr', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    date_default_timezone_set("America/Guatemala");
    $totales = ['devengado', 'isr'];
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    $letra = new StdClass();
    $letra->por_proyecto = $d->agrupar == 2 ? true : false;

    $query = "SELECT 
                a.id,
                e.id AS idempresa,
                IFNULL(c.idproyecto, 9999) AS idproyecto,
                e.nombre AS empresa,
                e.numeropat AS numero, 
                e.abreviatura,
                f.nomproyecto AS proyecto,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                b.nit,
                DATE_FORMAT(c.ingreso, '%d/%m/%Y') AS ingreso,
                SUM(d.devengado) AS devengado,
                SUM(d.descisr) AS isr
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnnomina d ON d.idplnempleado = a.id
                    INNER JOIN
                plnempresa e ON d.idempresa = e.id
                    LEFT JOIN
                proyecto f ON c.idproyecto = f.id
            WHERE
                MONTH(d.fecha) = $d->mes
                    AND YEAR(d.fecha) = $d->anio ";
    $query.= isset($d->idempresa) ? "AND d.idempresa = $d->idempresa " : "";
    $query.=       "AND d.diastrabajados > 0 
            GROUP BY a.id ";
    $query.= $d->agrupar == 1 ? "ORDER BY 2, 8" : "ORDER BY 2, 3, 8";
    $datos = $db->getQuery($query);

    $letra->t_empleados = count($datos);
    $letra->empleados_isr = 0;
    $letra->empleados_sin_isr = 0;

    foreach($datos as $emp) {
        if ($emp->isr > 0) {
            $letra->empleados_isr++;
        } else {
            $letra->empleados_sin_isr++;
        }
    }

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($datos, 'empleados', $totales, $letra->por_proyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = 'Del mes de ' . $meses[$d->mes-1] . ' de ' . $d->anio;

    foreach($totales as $t) {
        $letra->$t = array_sum($montos_generales->$t);
    }

    return print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/proyeccion', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");
    $d->meses = 12;

    $hoy = new DateTime();
    $hoy = $hoy->format('Y');

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // clase para fechas
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    // $letra->al = new DateTime($d->falstr);

    // encabezado
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
                a.id,
                c.idempresadebito AS idempresa,
                d.nombre AS empresa,
                d.numeropat AS numero,
                d.abreviatura,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        b.tercernombre,
                        b.primerapellido,
                        ' ',
                        b.segundoapellido,
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                NULL AS meses,
                b.nit,
                c.sueldo,
                c.bonificacionley,
                c.sueldo,
                c.sueldo,
                c.sueldo * $d->meses AS sueldo_global,
                0.00 AS sueldo_e,
                c.bonificacionley * $d->meses AS bonificacion_global,
                NULL AS premio,
                NULL AS renta,
                48000 AS personales,
                ROUND(c.sueldo * (c.porcentajeigss / 100) * $d->meses,
                        2) AS igss,
                NULL AS deducciones,
                NULL AS imponible,
                NULL AS impuesto,
                NULL AS mensual, 
                c.ingreso
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnempresa d ON c.idempresaactual = d.id
            WHERE
                a.activo = 1 AND a.nombre IS NOT NULL
                    AND a.apellidos IS NOT NULL  ";
    $query.= isset($d->idempresa) ? "AND c.idempresadebito = $d->idempresa " : "";
    $query.= "ORDER BY b.primernombre , b.primerapellido";
    $data = $db->getQuery($query);

    foreach($data AS $emp) {
        $ingreso = new DateTime($emp->ingreso);
        $ingreso = $ingreso->format('Y');
        $anios = $hoy - $ingreso;
        if ($anios == 40) {
            $emp->premio = 70000;
        } else if ($anios == 35) {
            $emp->premio = 17500;
        } else if ($anios == 30) {
            $emp->premio = 15000;
        } else if ($anios == 25) {
            $emp->premio = 12500;
        } else if ($anios == 20) {
            $emp->premio = 10000;
        } else if ($anios == 15) {
            $emp->premio = $emp->sueldo * 3;
        } else if ($anios == 10) {
            $emp->premio = $emp->sueldo * 2;
        } else if ($anios == 5) {
            $emp->premio = $emp->sueldo;
        } else {
            $emp->premio = 0;
        }

        // $emp->sueldo_e = isset($emp->sueldo_e) ? $emp->sueldo_e : 0;
        $emp->renta = $emp->sueldo_global + $emp->bonificacion_global + $emp->premio + $emp->sueldo_e;
        $emp->deducciones = $emp->igss + $emp->personales;
        $emp->imponible = round($emp->renta - $emp->deducciones, 2);
        $emp->impuesto = round($emp->imponible * 0.05, 2); 
        $emp->mensual = round($emp->impuesto / $d->meses, 2);
    }

    foreach($data as $dat) {
        minusculas($dat);
    }

    $letra->empresa = $data[0]->empresa;
    $letra->anio = $d->anio;

    // $porproyecto = $d->agrupar == 2 ? true : false;

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', [], false);
    $empleados = $reporte->getReporte();

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/proyectado', function() {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));
    date_default_timezone_set("America/Guatemala");

    $hoy = new DateTime();
    $hoy = $hoy->format('Y');

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    // para totales
    $sueldo = [];
    $bonificacion = [];
    $igss = [];
    $isr = [];
    $bonos = [];
    $extra = [];

    $query = "SELECT 
                a.id,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        b.tercernombre,
                        b.primerapellido,
                        ' ',
                        b.segundoapellido,
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                b.nit,
                c.sueldo + c.bonificacionley AS sueldofijo,
                MONTH(fecha) AS mes,
                SUM(d.sueldoordinario) AS sueldo,
                SUM(d.bonificacion) AS bonificacion,
                SUM(d.descigss) AS igss,
                SUM(d.descisr) AS isr,
                d.aguinaldo + d.bonocatorce AS aguinaldo,
                d.sueldoextra,
                c.ingreso
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idpersonal = c.id
                    INNER JOIN
                plnnomina d ON d.idplnempleado = a.id
            WHERE
                a.id = $d->idempleado
                    AND YEAR(d.fecha) = $d->anio
            GROUP BY MONTH(fecha)";
    $data = $db->getQuery($query);

    // para encabezado
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = 'Ingresos del: 1 de enero al 31 de diciembre de '. $d->anio;
    $letra->nombre = $data[0]->nombre;
    $letra->nit = $data[0]->nit;
    $letra->sueldo = $data[0]->sueldofijo;

    // para calculo mes a mes
    $proyeccion = [];
    $ultimoMes = null;

    for ($i = 0; $i < 12; $i++) {
        if (isset($data[$i]) && $data[$i]->sueldo > 0) {
            $proyeccion[$i] = $data[$i];
            $proyeccion[$i]->mes_letra = $meses[$proyeccion[$i]->mes - 1];
            $ultimoMes = $proyeccion[$i];
        } else {
            $proyeccion[$i] = clone $ultimoMes ?? new stdClass();
            $proyeccion[$i]->mes = $i + 1;
            $proyeccion[$i]->mes_letra = $meses[$i];
            $proyeccion[$i]->isr = 0;
            if ($i + 1 === 7) {
                $proyeccion[$i]->aguinaldo = $proyeccion[$i]->sueldo;
            }
            if ($i + 1 === 12) {
                $proyeccion[$i]->aguinaldo = $proyeccion[$i]->sueldo;
            }
        }
        array_push($sueldo, $proyeccion[$i]->sueldo);
        array_push($bonificacion, $proyeccion[$i]->bonificacion);
        array_push($igss, $proyeccion[$i]->igss);
        array_push($isr, $proyeccion[$i]->isr);
        array_push($bonos, $proyeccion[$i]->aguinaldo);
        array_push($extra, $proyeccion[$i]->sueldoextra);
    }

    $totales = new stdClass();
    $totales->bonificacion = round(array_sum($bonificacion), 2);
    $totales->igss = round(array_sum($igss), 2);
    $totales->isr = round(array_sum($isr), 2);
    $totales->bonos = round(array_sum($bonos), 2);
    $totales->extra = round(array_sum($extra), 2);

    // premios
    $ingreso = new DateTime($data[0]->ingreso);
    $ingreso = $ingreso->format('Y');
    $anios = $hoy - $ingreso;
    if ($anios == 40) {
        $totales->premios = 70000;
    } else if ($anios == 35) {
        $totales->premios = 17500;
    } else if ($anios == 30) {
        $totales->premios = 15000;
    } else if ($anios == 25) {
        $totales->premios = 12500;
    } else if ($anios == 20) {
        $totales->premios = 10000;
    } else if ($anios == 15) {
        $totales->premios = $data[0]->sueldo * 3;
    } else if ($anios == 10) {
        $totales->premios = $data[0]->sueldo * 2;
    } else if ($anios == 5) {
        $totales->premios = (float)$data[0]->sueldo;
    } else {
        $totales->premios = 0;
    }

    array_push($sueldo, $totales->premios);
    array_push($sueldo, $totales->extra);
    $totales->sueldo = round(array_sum($sueldo), 2);

    $totales->renta_bruta = round($totales->sueldo + $totales->bonos + $totales->bonificacion, 2);
    $totales->res_aguinaldo = round($totales->bonos);
    $totales->renta_neta = round($totales->renta_bruta - $totales->res_aguinaldo, 2);
    // gastos
    $totales->gastos_igss = round($totales->igss, 2);
    $totales->gastos_medicos = 0.00;
    $totales->gastos_personales = 48000;
    $totales->renta_imponible = round($totales->renta_neta - $totales->gastos_igss - $totales->gastos_personales, 2);
    // impuesto
    $totales->impuesto = round($totales->renta_imponible * 0.05, 2);
    $totales->impuesto_pendiente = round($totales->impuesto - $totales->isr, 2);
    // mensual
    $totales->mensual = round($totales->impuesto_pendiente / 12, 2);

    print json_encode([ 'encabezado' => $letra, 'meses' => $proyeccion, 'totales' => $totales ]);
});

$app->post('/carta', function () { 
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    $d = json_decode(file_get_contents('php://input'));

    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");

    $query = "SELECT 
                a.id,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                b.documento,
                d.nomempresa AS empresa,
                d.direccion,
                IFNULL(d.telefono, 'favor agregar telefono') AS telefono,
                DATE_FORMAT(c.ingreso, '%Y/%m/%d') AS ingreso,
                IFNULL(DATE_FORMAT(c.baja, '%Y/%m/%d'),
                        'hasta la fecha') AS egreso,
                e.descripcion AS puesto,
                c.sueldo + c.bonificacionley AS sueldo,
                b.sexo
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                empresa d ON c.idempresadebito = d.id
                    INNER JOIN
                puesto e ON c.idpuesto = e.id
            WHERE
                a.id = $d->idempleado";
    $datos = $db->getQuery($query)[0];

    if ($datos->sexo === 'hombre') {
        $datos->cortesia = 'el señor';
    } else {
        $datos->cortesia = 'la señora';
    }

    if (!$d->sueldo) {
        $datos->sueldo = null;
    }

    $fecha = new DateTime($datos->ingreso);
    $dia = $fecha->format('d');
    $mes = $meses[$fecha->format('n') - 1];
    $anio = $fecha->format('Y');
    $datos->ingreso = $dia . ' de ' . $mes . ' de ' . $anio;

    if ($datos->egreso !== 'hasta la fecha') {
        $fecha = new DateTime($datos->egreso);
        $dia = $fecha->format('d');
        $mes = $meses[$fecha->format('n') - 1];
        $anio = $fecha->format('Y');
        $datos->egreso = $dia . ' de ' . $mes . ' de ' . $anio;
    }

    $fecha = new DateTime();
    $dia = $fecha->format('d');
    $mes = $meses[$fecha->format('n') - 1];
    $anio = $fecha->format('Y');
    $dia_letras = trim(strtolower($n2l->to_word($dia)));
    $datos->hoy = 'a los ' . $dia_letras . ' días del mes de ' . $mes . ' de ' . $anio;
    $datos->mes = $fecha->format('n');
    $datos->anio = $fecha->format('Y');

    print json_encode([ 'empleado' => $datos ]);
});

$app->post('/indemnizacion', function () {
    date_default_timezone_set("America/Guatemala");

    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $porproyecto = $d->agrupar == 2 ? true : false;
    $letra = new stdClass();
    $totales = ['monto'];

    // fecha y hora que descargan reporte
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    // titulos
    $letra->agrupar = $porproyecto ? 'proyecto' : 'empresa';
    $letra->titulo = 'Indeminizaciones del año ' . $d->anio;

    $query = "SELECT 
                a.id,
                d.id AS idempresa,
                d.nombre AS empresa,
                d.numeropat AS numero,
                d.abreviatura,
                g.id AS idproyecto,
                g.nomproyecto AS proyecto,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                DATE_FORMAT(IFNULL(c.reingreso, c.ingreso),
                        '%d/%m/%Y') AS ingreso,
                DATE_FORMAT(c.baja, '%d/%m/%Y') AS baja,
                f.numero AS cheque,
                e.finiquito,
                e.vacaciones,
                e.aguinaldo,
                e.bono,
                e.otrosbono,
                e.prestamos,
                e.anticipos,
                e.otrosdesc,
                f.monto
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnempresa d ON c.idempresaactual = d.id
                    INNER JOIN
                plnfiniquito e ON a.id = e.idplnempleado
                    INNER JOIN
                tranban f ON e.idtranban = f.id
                    INNER JOIN
                proyecto g ON c.idproyecto = g.id
            WHERE
                YEAR(e.fecha) = $d->anio AND e.idtranban > 0 ";
    $query.= isset($d->idempresa) ? "AND d.id = $d->idempresa " : "";
    $query.= "ORDER BY "; 
    $query.= $d->agrupar == 1 ? "3 , 6" : "3 , 5 , 6";
    $data = $db->getQuery($query);

    // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
    $reporte = new GeneradorReportes($data, 'empleados', $totales, $porproyecto);
    $empleados = $reporte->getReporte();
    $montos_generales = $reporte->getTotalesGenerales();

    foreach($totales as $t) {
        $letra->$t = array_sum($montos_generales->$t);
    }

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/ivs', function () {
    date_default_timezone_set("America/Guatemala");

    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $letra = new stdClass();

    $fdel = new DateTime($d->fdelstr);
    $fal = new DateTime($d->falstr);

    // fecha y hora que descargan reporte
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->fechas = 'Del ' . $fdel->format('d/m/Y') . ' al ' . $fal->format('d/m/Y');

    $query = "SELECT 
                t.codigo,
                t.igss,
                t.empresa,
                t.nombre_empleado,
                MIN(t.fecha) AS fecha_orden,
                DATE_FORMAT(MIN(t.fecha), '01/%m/%Y') AS fecha_inicio,
                DATE_FORMAT(MAX(t.fecha), '%d/%m/%Y') AS fecha_fin,
                SUBSTRING_INDEX(GROUP_CONCAT(t.sueldoordinario ORDER BY t.fecha ASC), ',', 1) AS sueldoordinario,
                SUBSTRING_INDEX(GROUP_CONCAT(t.sueldoextra ORDER BY t.fecha ASC), ',', 1) AS sueldoextra,
                SUBSTRING_INDEX(GROUP_CONCAT(t.vacaciones ORDER BY t.fecha ASC), ',', 1) AS vacaciones,
                SUBSTRING_INDEX(GROUP_CONCAT(t.grantotal ORDER BY t.fecha ASC), ',', 1) AS grantotal
            FROM (
                SELECT 
                    c.id AS codigo,
                    b.nombre AS empresa,
                    e.igss,
                    CONCAT(
                        d.primernombre, ' ', IFNULL(d.segundonombre, ''), ' ', IFNULL(d.tercernombre, ''), 
                        IFNULL(d.primerapellido, ''), ' ', IFNULL(d.segundoapellido, ''), 
                        IFNULL(d.apellidocasada, '')
                    ) AS nombre_empleado,
                    a.fecha,
                    IFNULL(a.sueldoordinario, 0.00) AS sueldoordinario,
                    IFNULL(a.sueldoextra, 0.00) AS sueldoextra,
                    IFNULL(a.vacaciones, 0.00) AS vacaciones,
                    (IFNULL(a.sueldoordinario, 0.00) + IFNULL(a.sueldoextra, 0.00) + IFNULL(a.vacaciones, 0.00)) AS grantotal,
                    @grp := @grp + IF(
                        @prev_empresa = b.nombre
                        AND PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM a.fecha), EXTRACT(YEAR_MONTH FROM @prev_fecha)) = 1
                        AND @prev_sueldo = IFNULL(a.sueldoordinario, 0.00),
                        0, 1
                    ) AS grupo,
                    @prev_empresa := b.nombre,
                    @prev_fecha := a.fecha,
                    @prev_sueldo := IFNULL(a.sueldoordinario, 0.00)
                FROM plnnomina a
                INNER JOIN plnempresa b ON b.id = a.idempresa
                INNER JOIN plnempleado c ON a.idplnempleado = c.id
                INNER JOIN plnpersonal d ON c.idpersonal = d.id
                INNER JOIN plnlaboral e ON c.idlaboral = e.id
                CROSS JOIN (SELECT @grp := 0, @prev_empresa := '', @prev_fecha := NULL, @prev_sueldo := NULL) vars
                WHERE a.idplnempleado = $d->idempleado
                    AND DAY(a.fecha) <> 15
                    AND a.esbonocatorce = 0
                    AND a.aguinaldo = 0
                    AND a.fecha BETWEEN '$d->fdelstr' AND '$d->falstr'
                ORDER BY a.fecha
            ) t
            GROUP BY t.empresa, t.nombre_empleado, t.grupo
            ORDER BY fecha_orden";
    $data = $db->getQuery($query);

    $letra->empleado = $data[0]->nombre_empleado;
    $letra->codigo = $data[0]->codigo;
    $letra->igss = $data[0]->igss;

    for ($i = 0; $i < count($data); $i++) {
        $actual = $data[$i];
        $proximo = isset($data[$i + 1]) ? $data[$i + 1] : null;

        if ($proximo != null && $actual->empresa != $proximo->empresa) {
            $mes = new DateTime($proximo->fecha_inicio);
            $mes = $mes->format('m');
            $anio = new DateTime($proximo->fecha_inicio);
            $anio = $anio->format('Y');

            $fecha_real = "SELECT movfecha AS fecha FROM plnbitacora WHERE idplnempleado = $d->idempleado AND MONTH(movfecha) = $mes AND YEAR(movfecha) = $anio ORDER BY movfecha DESC LIMIT 1";

            // $proximo->fecha_inicio = isset($fecha_real) ? (new DateTime($fecha_real))->format('d/m/Y') : $proximo->fecha_inicio; 
        }
    }

    return print json_encode([ 'encabezado' => $letra, 'cambios' => $data ]);
});

$app->post('/embargos', function () {
    date_default_timezone_set("America/Guatemala");

    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $cargos = [];

    $query = "SELECT 
                e.id,
                CONCAT(b.primernombre,
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        b.primerapellido,
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                c.sueldo AS salariobase,
                ROUND(c.sueldo * 0.35, 2) AS cuota,
                e.monto AS total,
                ROUND(e.monto - e.monto * 0.10, 2) AS monto,
                d.nombre AS empresa,
                e.iniciopago
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnprestamo e ON e.idplnempleado = a.id
                    INNER JOIN
                plnempresa d ON c.idempresadebito = d.id
            WHERE
                e.esembargo = 1 AND a.id = $d->idempleado";
    $data = $db->getQuery($query)[0];

    $data->estampa = new DateTime();
    $data->estampa = $data->estampa->format('d-m-Y H:i');

    $monto_restante = $data->total;
    $id = 0;
    $fecha = new DateTime($data->iniciopago);
    $acumulado = 0;

    while ($monto_restante > 0) {
        $cargo = new stdClass();

        $cargo->id = $id++;

        $cargo->empresa = $data->empresa;

        // Obtener último día del mes actual
        $ultimoDia = clone $fecha;
        $ultimoDia->modify('last day of this month');
        $fecha_busqueda = $ultimoDia->format('Y-m-d');
        $cargo->fecha = $ultimoDia->format('d/m/Y');

        // Avanzar un mes
        $fecha->modify('+1 month');
        
        $comprobante = $db->getQuery("SELECT a.monto, a.idplnnomina AS comprobante FROM plnpresnom a INNER JOIN plnnomina b ON a.idplnnomina = b.id WHERE idplnprestamo = $data->id AND b.fecha = '{$fecha_busqueda}'");
        if (!is_array($comprobante) || count($comprobante) === 0) {
            $comprobante = null;
        } else {
            $comprobante = $comprobante[0];
        }
        if (isset($comprobante)) {
            $cargo->descuento = $comprobante->monto;
            $cargo->comprobante = $comprobante->comprobante;
        } else {
            $cargo->descuento = $data->cuota <= $monto_restante ? $data->cuota : $monto_restante;
            $cargo->comprobante = '';
        }

        $cargo->acumulado = $acumulado > 0 ? $acumulado + $cargo->descuento : $cargo->descuento;

        $monto_restante -= $cargo->descuento;
        $acumulado += $cargo->descuento;

        array_push($cargos, $cargo);
    }

    return print json_encode([ 'encabezado' => $data, 'cargos' => $cargos ]);
});

$app->post('/lst_embargos', function () { 
    date_default_timezone_set("America/Guatemala");

    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    $d = json_decode(file_get_contents('php://input'));
    $letra = new stdClass();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->titulo = $d->ver === 1 ? 'activos' : 'todos';

    $query = "SELECT 
                c.id AS id,
                CONCAT(d.primernombre,
                        ' ',
                        IFNULL(d.segundonombre, ''),
                        ' ',
                        IFNULL(d.tercernombre, ''),
                        d.primerapellido,
                        ' ',
                        IFNULL(d.segundoapellido, ''),
                        ' ',
                        IFNULL(d.apellidocasada, '')) AS nombre,
                COUNT(a.id) AS numero,
                a.idplnprestamo AS vale,
                DATE_FORMAT(b.iniciopago, '%d/%m/%Y') AS fecha,
                b.monto AS total_deuda,
                b.cuotamensual AS descuento_en_planilla,
                b.saldo AS acumulado,
                (b.monto - b.saldo) AS saldo_pendiente
            FROM
                plnpresnom a
                    INNER JOIN
                plnprestamo b ON a.idplnprestamo = b.id
                    INNER JOIN
                plnempleado c ON b.idplnempleado = c.id
                    INNER JOIN
                plnpersonal d ON c.idpersonal = d.id
            WHERE
                b.esembargo = 1
                    AND c.idempresadebito = $d->idempresa ";
    $query.= $d->ver === 1 ?  "AND b.finalizado = 0 " : ""; 
    $query.="GROUP BY c.id";
    $datos = $db->getQuery($query);

    print json_encode([ 'empleado' => $datos, 'encabezado' => $letra ]);
});

$app->post('/boleta_pagos', function () { 
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    $d = json_decode(file_get_contents('php://input'));

    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
                e.nombre AS empresa,
                a.monto,
                DATE_FORMAT(a.iniciopago, '%d/%m/%Y') AS fecha,
                CONCAT(c.primernombre,
                        ' ',
                        IFNULL(c.segundonombre, ''),
                        ' ',
                        IFNULL(c.tercernombre, ''),
                        c.primerapellido,
                        ' ',
                        IFNULL(c.segundoapellido, ''),
                        ' ',
                        IFNULL(c.apellidocasada, '')) AS nombre
            FROM
                plnprestamo a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    INNER JOIN
                plnpersonal c ON b.idpersonal = c.id
                    INNER JOIN
                plnlaboral d ON b.idlaboral = d.id
                    INNER JOIN
                plnempresa e ON d.idempresadebito = e.id
            WHERE
                a.id = $d->idprestamo";
    $datos = $db->getQuery($query)[0];

    print json_encode([ 'prestamo' => $datos, 'encabezado' => $letra ]);
});

$app->post('/vacaciones_empleado', function () { 
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    $d = json_decode(file_get_contents('php://input'));

    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
                a.id,
                CONCAT(IFNULL(b.primernombre, ''),
                        ' ',
                        IFNULL(b.segundonombre, ''),
                        ' ',
                        IFNULL(b.tercernombre, ''),
                        IFNULL(b.primerapellido, ''),
                        ' ',
                        IFNULL(b.segundoapellido, ''),
                        ' ',
                        IFNULL(b.apellidocasada, '')) AS nombre,
                DATE_FORMAT(IFNULL(c.reingreso, c.ingreso), '%d/%m/%Y') AS ingreso,
                DATE_FORMAT(c.baja, '%d/%m/%Y') AS baja,
                d.nombre AS empresa,
                e.nomproyecto AS proyecto,
                f.descripcion AS puesto,
                g.anio AS anio,
                NULL AS dias_correspondientes,
                IFNULL(c.reingreso, c.ingreso) AS fecha_ingreso,
                c.baja AS fecha_baja,
                DATE_FORMAT(g.fecha, '%d/%m/%Y') AS fecha_solicitud,
                DATE_FORMAT(g.fechainicio, '%d/%m/%Y') AS fecha_inicio,
                DATE_FORMAT(g.fechafin, '%d/%m/%Y') AS fecha_fin,
                g.dias,
                'Aprobado' AS estatus
            FROM
                plnempleado a
                    INNER JOIN
                plnpersonal b ON a.idpersonal = b.id
                    INNER JOIN
                plnlaboral c ON a.idlaboral = c.id
                    INNER JOIN
                plnempresa d ON c.idempresaactual = d.id
                    INNER JOIN
                proyecto e ON c.idproyecto = e.id
                    INNER JOIN
                plnpuesto f ON a.idplnpuesto = f.id
                    INNER JOIN
                plnvacaciones g ON g.idplnempleado = a.id
            WHERE
                a.id = $d->idempleado AND g.anio = $d->anio
                    AND g.anulado = 0";
    $datos = $db->getQuery($query);

    if (count($datos) > 0) {
        $letra->empleado = $datos[0]->nombre;
        $letra->empresa = $datos[0]->empresa;
        $letra->proyecto = $datos[0]->proyecto;
        $letra->puesto = $datos[0]->puesto;
        $letra->anio = $datos[0]->anio;
        $letra->ingreso = $datos[0]->ingreso;
        $letra->baja = $datos[0]->baja;

        // calcular dias desde fecha_ingreso hasta fecha_baja (si existe) o hasta el ultimo dia del año
        $inicio = $datos[0]->fecha_ingreso;
        $fin = $datos[0]->fecha_baja;
        $inicio = new DateTime($inicio);
        $fin = !empty($fin) ? new DateTime($fin) : new DateTime($letra->anio . '-12-31');

        if ($inicio->format('Y') < $letra->anio) {
            $letra->dias = 15;
        } else {
            if ($fin < $inicio) {
                $totalDias = 0;
            } else {
                $interval = $inicio->diff($fin);
                // incluir ambos extremos
                $totalDias = (float)$interval->days + 1;
            }

            // multiplicar por 15, dividir por 365 y aplicar round
            $letra->dias = round(($totalDias * 15) / 365, 2);
        }

        $letra->dias_gozados = 0;
        foreach($datos as $vac) {
            $letra->dias_gozados += (float)$vac->dias;
        }

        $letra->dias_pendientes = $letra->dias - $letra->dias_gozados;
    } else {
        $letra = 'sin datos';
        $datos = []; 
    }

    print json_encode([ 'encabezado' => $letra, 'vacaciones' => $datos ]);
});

$app->run();