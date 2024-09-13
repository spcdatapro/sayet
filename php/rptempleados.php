<?php
require 'vendor/autoload.php';
require_once 'db.php';
require 'Reportes.php';

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
                a.idproyecto,
                IFNULL(d.nomproyecto, 'NO ESPECIFICADO') AS proyecto,
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                DATE_FORMAT(ingreso, '%d/%m/%y') AS fingreso,
                IFNULL(DATE_FORMAT(reingreso, '%d/%m/%y'), '') AS freingreso,
                a.sueldo,
                a.bonificacionley,
                a.sueldo + a.bonificacionley AS sueldotot,
                b.descripcion AS puesto,
                c.abreviatura
            FROM
                plnempleado a
                    INNER JOIN
                plnpuesto b ON a.idplnpuesto = b.id
                    LEFT JOIN
                plnempresa c ON a.idempresadebito = c.id
                    LEFT JOIN
                proyecto d ON a.idproyecto = d.id ";
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

    // array de facturas
    $empleados = array();

    $query = "SELECT 
                a.id AS idempleado,
                IFNULL(b.id, '9999') AS idempresa,
                a.idproyecto,
                IF(a.baja AND $d->tipo = 3, '1', '0') AS tipo,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                c.nomproyecto AS proyecto,
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                IFNULL(d.descripcion, 'NO ESPECIFICADO') AS puesto,
                IF(a.baja AND $d->tipo = 3,
                    DATE_FORMAT(a.baja, '%d/%m/%Y'),
                    DATE_FORMAT(a.ingreso, '%d/%m/%Y')) AS fecha,
                a.sueldo,
                a.bonificacionley AS bono,
                (a.bonificacionley + a.sueldo) AS total,
                IF(a.formapago = 1,
                    'Quincenal',
                    'Mensual') AS pago,
                b.numeropat AS numero
            FROM
                plnempleado a
                    LEFT JOIN
                plnempresa b ON a.idempresadebito = b.id
                    INNER JOIN
                proyecto c ON a.idproyecto = c.id
                    LEFT JOIN
                plnpuesto d ON a.idplnpuesto = d.id
            WHERE  1 = 1 ";
    $query.= $d->tipo == 1 ? "AND a.ingreso >= '$d->fdelstr' AND a.ingreso <= '$d->falstr' " :
    ($d->tipo == 2 ? "AND a.baja >= '$d->fdelstr' AND a.baja <= '$d->falstr' " : 
    "AND (a.ingreso >= '$d->fdelstr' AND a.ingreso <= '$d->falstr' OR a.baja >= '$d->fdelstr' AND a.baja <= '$d->falstr') ");
    $query.= isset($d->idempresa) ? "AND a.idempresadebito = $d->idempresa " : "";
    $query.= isset($d->idproyecto) ? "AND a.idproyecto = $d->idproyecto " : "";
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
                IFNULL(a.idproyecto, '9999') AS idproyecto,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                IFNULL(c.nomproyecto, 'SIN PROYECTO') AS proyecto,
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                b.numeropat AS numero,
                IFNULL(d.descripcion, 'NO ESPECIFICADO') AS puesto,
                DATE_FORMAT(a.ingreso, '%d/%m/%Y') AS fecha,
                a.sueldo,
                e.bonocatorcedias,
                e.bonocatorce,
                b.abreviatura
            FROM
                plnempleado a
                    INNER JOIN
                plnnomina e ON e.idplnempleado = a.id
                    LEFT JOIN
                plnempresa b ON e.idempresa = b.id
                    LEFT JOIN
                proyecto c ON a.idproyecto = c.id
                    LEFT JOIN
                plnpuesto d ON a.idplnpuesto = d.id
            WHERE
                e.bonocatorce > 0 AND YEAR(fecha) = $d->anio ";
    $query.= isset($d->idempresa) ? "AND a.idempresadebito = $d->idempresa " : "";
    $query.=   "ORDER BY 4 ,";
    $query.= $d->agrupar == 2 ? " 5 , 6" : " 6";
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
                IFNULL(a.idproyecto, '9999') AS idproyecto,
                IFNULL(b.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                IFNULL(c.nomproyecto, 'SIN PROYECTO') AS proyecto,
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                b.numeropat AS numero,
                IFNULL(d.descripcion, 'NO ESPECIFICADO') AS puesto,
                DATE_FORMAT(a.ingreso, '%d/%m/%Y') AS fecha,
                a.sueldo,
                e.aguinaldodias,
                e.aguinaldo,
                b.abreviatura
            FROM
                plnempleado a
                    INNER JOIN
                plnnomina e ON e.idplnempleado = a.id
                    LEFT JOIN
                plnempresa b ON e.idempresa = b.id
                    LEFT JOIN
                proyecto c ON a.idproyecto = c.id
                    LEFT JOIN
                plnpuesto d ON a.idplnpuesto = d.id
            WHERE
                e.aguinaldo > 0 AND YEAR(fecha) = $d->anio ";
    $query.= isset($d->idempresa) ? "AND a.idempresadebito = $d->idempresa " : "";
    $query.=   "ORDER BY 4 ,";
    $query.= $d->agrupar == 2 ? " 5 , 6" : " 6";
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

$app->post('/vacaciones', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // array para totales si se tiene que modificar por reporte
    $totales = ['liquido'];

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

    // array de facturas
    $empleados = array();

    $query = "SELECT 
                d.id AS idempresa,
                IFNULL(d.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                d.numeropat AS numero,
                d.abreviatura,
                c.idproyecto,
                IFNULL(e.nomproyecto, 'NO ESPECIFICADO') AS proyecto,
                c.id AS idempleado,
                CONCAT(c.nombre, ' ', IFNULL(c.apellidos, '')) AS nombre,
                DATE_FORMAT(ingreso, '%d/%m/%y') AS ingreso,
                c.sueldo,
                b.vacastotal AS monto,
                b.vacasdias AS dias,
                b.vacasdescuento AS descuento,
                b.vacasusados,
                b.vacasliquido AS liquido,
                IFNULL(f.descripcion, 'NO ESPECIFICADO') AS puesto
            FROM
                plnextra a
                    INNER JOIN
                plnextradetalle b ON b.idplnextra = a.id
                    INNER JOIN
                plnempleado c ON b.idplnempleado = c.id
                    LEFT JOIN
                plnempresa d ON c.idempresadebito = d.id
                    LEFT JOIN
                proyecto e ON c.idproyecto = e.id
                    LEFT JOIN 
                plnpuesto f ON c.idplnpuesto = f.id
            WHERE
                a.anio = $d->anio ";
    $query.= isset($d->idempresa) ? "AND c.idempresadebito = $d->idempresa " : "";
    $query.= isset($d->idempleado) ? "AND b.idplnempleado = $d->idempleado " : "";
    $query.= "ORDER BY  2 , ";
    $query.= $d->agrupar == 2 ? " 6 , 8" : " 8";
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
                d.id AS idempresa,
                IFNULL(d.nombre, 'SIN EMPRESA DÉBITO') AS empresa,
                d.numeropat AS numero,
                d.abreviatura,
                c.idproyecto,
                IFNULL(e.nomproyecto, 'NO ESPECIFICADO') AS proyecto,
                c.id AS idempleado,
                CONCAT(c.nombre, ' ', IFNULL(c.apellidos, '')) AS nombre,
                IFNULL(f.descripcion, 'NO ESPECIFICADO') AS puesto,
                a.id AS idprestamo,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                a.monto AS monto,
                a.cuotamensual AS cuota,
                IF(MONTH(a.fecha) = $d->mes AND YEAR(a.fecha), 0.00, (a.saldo + IFNULL(g.descprestamo, 0) + IFNULL(b.monto, 0))) AS saldoant,
                IF(MONTH(a.fecha) = $d->mes
                        AND YEAR(a.fecha) = $d->anio,
                    a.monto,
                    0.00) AS nuevo,
                g.descprestamo AS descnomina,
                b.monto AS descuento,
                (IFNULL(b.monto, 0.00) + IFNULL(g.descprestamo, 0.00)) AS totdesc,
                a.saldo AS saldo
            FROM
                plnprestamo a
                    LEFT JOIN
                (SELECT 
                    idplnprestamo, monto
                FROM
                    plnpresabono
                WHERE
                    MONTH(fecha) = $d->mes AND YEAR(fecha) = $d->anio) b ON b.idplnprestamo = a.id
                    INNER JOIN
                plnempleado c ON a.idplnempleado = c.id
                    LEFT JOIN
                plnempresa d ON c.idempresadebito = d.id
                    LEFT JOIN
                proyecto e ON c.idproyecto = e.id
                    LEFT JOIN
                plnpuesto f ON c.idplnpuesto = f.id
                    LEFT JOIN
                (SELECT 
                    descprestamo, idplnempleado
                FROM
                    plnnomina
                WHERE
                    DAY(fecha) > 16 AND MONTH(fecha) = $d->mes
                        AND YEAR(fecha) = $d->anio) g ON g.idplnempleado = a.idplnempleado
            WHERE
                a.anulado = 0 AND (a.finalizado = 0 OR (YEAR(a.liquidacion) = $d->anio AND MONTH(a.liquidacion) = $d->mes)) ";
    $query.= isset($d->idempresa) ? "AND c.idempresadebito = $d->idempresa " : "";
    $query.= isset($d->idempleado) ? "AND b.idplnempleado = $d->idempleado " : "";
    $query.= "ORDER BY  2 , ";
    $query.= $d->agrupar == 2 ? " 6 , 8" : " 8";
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
                c.idproyecto,
                IFNULL(e.nomproyecto, 'NO ESPECIFICADO') AS proyecto,
                c.id AS idempleado,
                CONCAT(c.nombre, ' ', IFNULL(c.apellidos, '')) AS nombre,
                IFNULL(f.descripcion, 'NO ESPECIFICADO') As puesto,
                DATE_FORMAT(ingreso, '%d/%m/%Y') AS ingreso,
                DATEDIFF('2024-09-11', ingreso) AS dias,
                TIMESTAMPDIFF(YEAR,
                    ingreso,
                    '2024-09-11') AS anios,
                TIMESTAMPDIFF(MONTH,
                    ingreso,
                    '2024-09-11') AS meses
            FROM
                plnempleado c
                    LEFT JOIN
                plnempresa d ON c.idempresadebito = d.id
                    LEFT JOIN
                proyecto e ON c.idproyecto = e.id
                    LEFT JOIN
                plnpuesto f ON c.idplnpuesto = f.id ";
    $query.= isset($d->idempresa) ? "WHERE c.idempresadebito = $d->idempresa " : "";
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
    $dat->puesto = ucfirst(strtolower($dat->puesto));
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

$app->run();