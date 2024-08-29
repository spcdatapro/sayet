<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptempelados', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // separadores
    $separador_empresa = new StdClass;
    $separador_proyecto = new StdClass;

    // sumadores
    $sumas_empresa = new StdClass;
    $sumas_empresa->sueldo = array();
    $sumas_empresa->bono = array();
    $sumas_empresa->total = array();
    // proyecto
    $sumas_proyecto = new StdClass;
    $sumas_proyecto->sueldo = array();
    $sumas_proyecto->bono = array();
    $sumas_proyecto->total = array();
    // general
    $sumas_general = new StdClass;
    $sumas_general->sueldo = array();
    $sumas_general->bono = array();
    $sumas_general->total = array();

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

    $cntsFacturas = count($data);

    if ($cntsFacturas > 1) {
    for ($i = 1; $i < $cntsFacturas; $i++)  {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            // empresa
            $separador_empresa->nombre = $anterior->empresa;
            $separador_empresa->abreviatura = $anterior->abreviatura;
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

        // sumas
        array_push($sumas_empresa->sueldo, $anterior->sueldo);
        array_push($sumas_empresa->bono, $anterior->bonificacionley);
        array_push($sumas_empresa->total, $anterior->sueldotot);
        // general
        array_push($sumas_general->sueldo, $anterior->sueldo);
        array_push($sumas_general->bono, $anterior->bonificacionley);
        array_push($sumas_general->total, $anterior->sueldotot);

        if ($d->agrupar == 2) {
            array_push($separador_proyecto->empleados, $anterior);
            array_push($sumas_proyecto->sueldo, $anterior->sueldo);
            array_push($sumas_proyecto->bono, $anterior->bonificacionley);
            array_push($sumas_proyecto->total, $anterior->sueldotot);
        } else {
            array_push($separador_empresa->empleados, $anterior);
        }

        if ($d->agrupar == 2) {
            if ($anterior->idproyecto !== $actual->idproyecto) {
                $separador_proyecto->tsueldo = round(array_sum($sumas_proyecto->sueldo), 2);
                $separador_proyecto->tbono = round(array_sum($sumas_proyecto->bono), 2);
                $separador_proyecto->total = round(array_sum($sumas_proyecto->total), 2);

                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

                // separador
                $separador_proyecto = new StdClass;
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
                $sumas_proyecto->sueldo = array();
                $sumas_proyecto->bono = array();
                $sumas_proyecto->total = array();
            }
        }

        if ($anterior->idempresa !== $actual->idempresa) {
            $separador_empresa->tsueldo = round(array_sum($sumas_empresa->sueldo), 2);
            $separador_empresa->tbono = round(array_sum($sumas_empresa->bono), 2);
            $separador_empresa->total = round(array_sum($sumas_empresa->total), 2);

            // empujar a array padre
            array_push($empleados, $separador_empresa);

            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->abreviatura = $actual->abreviatura;
            $separador_empresa->numero = $actual->numero;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            $sumas_empresa->sueldo = array();
            $sumas_empresa->bono = array();
            $sumas_empresa->total = array();
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
                // sumas
                array_push($sumas_proyecto->sueldo, $actual->sueldo);
                array_push($sumas_proyecto->bono, $actual->bonificacionley);
                array_push($sumas_proyecto->total, $actual->sueldotot);
            } else {
                array_push($separador_empresa->empleados, $actual);
            }

            // sumas
            array_push($sumas_empresa->sueldo, $actual->sueldo);
            array_push($sumas_empresa->bono, $actual->bonificacionley);
            array_push($sumas_empresa->total, $actual->sueldotot);
            // general
            array_push($sumas_general->sueldo, $actual->sueldo);
            array_push($sumas_general->bono, $actual->bonificacionley);
            array_push($sumas_general->total, $actual->sueldotot);


            if ($d->agrupar == 2) {
                $separador_proyecto->tsueldo = round(array_sum($sumas_proyecto->sueldo), 2);
                $separador_proyecto->tbono = round(array_sum($sumas_proyecto->bono), 2);
                $separador_proyecto->total = round(array_sum($sumas_proyecto->total), 2);
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);
            }

            $separador_empresa->tsueldo = round(array_sum($sumas_empresa->sueldo), 2);
            $separador_empresa->tbono = round(array_sum($sumas_empresa->bono), 2);
            $separador_empresa->total = round(array_sum($sumas_empresa->total), 2);

            array_push($empleados, $separador_empresa);
        }
    }
    } else {
        for ($i = 0; $i < $cntsFacturas; $i++)  {
            // traer valor actual y anterior
            $actual = $data[$i];

            // si es el primero insertar nombre del separador y crear array de recibos
            if ($primero) {
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
            array_push($empleados, $separador_empresa);
        }
    } 

    $letra->tsueldo = round(array_sum($sumas_general->sueldo), 2);
    $letra->tbono = round(array_sum($sumas_general->bono), 2);
    $letra->total = round(array_sum($sumas_general->total), 2);

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

    // separadores
    $separador_empresa = new StdClass;
    $separador_proyecto = new StdClass;

    // sumadores
    $sumas_empresa = array();
    $sumas_proyecto = array();
    $sumas_general = array();

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

    // array de facturas
    $empleados = array();

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

    $cntsDatos = count($data);

    if ($cntsDatos > 1) {
    for ($i = 1; $i < $cntsDatos; $i++)  {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            // empresa
            $separador_empresa->nombre = $anterior->empresa;
            $separador_empresa->numero = $anterior->numero;
            $separador_empresa->abreviatura = $anterior->abreviatura;
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

        // sumas
        array_push($sumas_empresa, $anterior->bonocatorce);
        array_push($sumas_general, $anterior->bonocatorce);

        if ($d->agrupar == 2) {
            array_push($separador_proyecto->empleados, $anterior);
            array_push($sumas_proyecto, $anterior->bonocatorce);
        } else {
            array_push($separador_empresa->empleados, $anterior);
        }


        if ($anterior->idproyecto !== $actual->idproyecto && $d->agrupar == 2) {
            // sumar total
            $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);

            // empujar a array padre
            array_push($separador_empresa->proyectos, $separador_proyecto);

            // separador
            $separador_proyecto = new StdClass;
            $separador_proyecto->nombre = $actual->proyecto;
            $separador_proyecto->empleados = array();
            $sumas_proyecto = array();
        }

        if ($anterior->idempresa !== $actual->idempresa) {
            if ($anterior->idproyecto == $actual->idproyecto && $d->agrupar == 2) {
                // sumar total
                $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);

                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

                // separador
                $separador_proyecto = new StdClass;
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
                $sumas_proyecto = array();
            }

            // sumar total 
            $separador_empresa->total = round(array_sum($sumas_empresa), 2);

            // empujar a array padre
            array_push($empleados, $separador_empresa);

            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->numero = $actual->numero;
            $separador_empresa->abreviatura = $actual->abreviatura;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            if ($d->agrupar == 2) {
                $separador_empresa->proyectos = array();
            } else {
                $separador_empresa->empleados = array();
            }
            $sumas_empresa = array();
        }
        
        // para empujar el ultimo dato
        if ($i+1 == $cntsDatos) {
            // empujar ultimo
            if ($d->agrupar == 2) {
                array_push($separador_proyecto->empleados, $actual);
                array_push($sumas_proyecto, $actual->bonocatorce);
                $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

            } else {
                array_push($separador_empresa->empleados, $actual);
            }
            array_push($sumas_empresa, $actual->bonocatorce);
            array_push($sumas_general, $actual->bonocatorce);

            $separador_empresa->total = round(array_sum($sumas_empresa), 2);
            
            // empujar a array padre
            array_push($empleados, $separador_empresa);
        }
    }
    } else {
        for ($i = 0; $i < $cntsDatos; $i++)  {
            // traer valor actual y anterior
            $actual = $data[$i];

            // si es el primero insertar nombre del separador y crear array de recibos
            if ($primero) {
                // empresa
                $separador_empresa->nombre = $actual->empresa;
                $separador_empresa->numero = $actual->numero;
                $separador_empresa->abreviatura = $actual->abreviatura;
                $separador_empresa->proyectos = array();
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
                array_push($separador_proyecto->empleados, $actual);
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

            } else {
                array_push($separador_empresa->empleados, $actual);
            }
            array_push($empleados, $separador_empresa);
        }
    } 

    $letra->total = array_sum($sumas_general);

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/aguinaldo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // separadores
    $separador_empresa = new StdClass;
    $separador_proyecto = new StdClass;

    // sumadores
    $sumas_empresa = array();
    $sumas_proyecto = array();
    $sumas_general = array();

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

    // array de facturas
    $empleados = array();

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

    $cntsDatos = count($data);

    if ($cntsDatos > 1) {
    for ($i = 1; $i < $cntsDatos; $i++)  {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            // empresa
            $separador_empresa->nombre = $anterior->empresa;
            $separador_empresa->numero = $anterior->numero;
            $separador_empresa->abreviatura = $anterior->abreviatura;
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

        // sumas
        array_push($sumas_empresa, $anterior->aguinaldo);
        array_push($sumas_general, $anterior->aguinaldo);

        if ($d->agrupar == 2) {
            array_push($separador_proyecto->empleados, $anterior);
            array_push($sumas_proyecto, $anterior->aguinaldo);
        } else {
            array_push($separador_empresa->empleados, $anterior);
        }


        if ($anterior->idproyecto !== $actual->idproyecto && $d->agrupar == 2) {
            // sumar total
            $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);

            // empujar a array padre
            array_push($separador_empresa->proyectos, $separador_proyecto);

            // separador
            $separador_proyecto = new StdClass;
            $separador_proyecto->nombre = $actual->proyecto;
            $separador_proyecto->empleados = array();
            $sumas_proyecto = array();
        }

        if ($anterior->idempresa !== $actual->idempresa) {
            if ($anterior->idproyecto == $actual->idproyecto && $d->agrupar == 2) {
                // sumar total
                $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);

                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

                // separador
                $separador_proyecto = new StdClass;
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
                $sumas_proyecto = array();
            }

            // sumar total 
            $separador_empresa->total = round(array_sum($sumas_empresa), 2);

            // empujar a array padre
            array_push($empleados, $separador_empresa);

            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->numero = $actual->numero;
            $separador_empresa->abreviatura = $actual->abreviatura;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            if ($d->agrupar == 2) {
                $separador_empresa->proyectos = array();
            } else {
                $separador_empresa->empleados = array();
            }
            $sumas_empresa = array();
        }
        
        // para empujar el ultimo dato
        if ($i+1 == $cntsDatos) {
            // empujar ultimo
            if ($d->agrupar == 2) {
                array_push($separador_proyecto->empleados, $actual);
                array_push($sumas_proyecto, $actual->aguinaldo);
                $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

            } else {
                array_push($separador_empresa->empleados, $actual);
            }
            array_push($sumas_empresa, $actual->aguinaldo);
            array_push($sumas_general, $actual->aguinaldo);

            $separador_empresa->total = round(array_sum($sumas_empresa), 2);
            
            // empujar a array padre
            array_push($empleados, $separador_empresa);
        }
    }
    } else {
        for ($i = 0; $i < $cntsDatos; $i++)  {
            // traer valor actual y anterior
            $actual = $data[$i];

            // si es el primero insertar nombre del separador y crear array de recibos
            if ($primero) {
                // empresa
                $separador_empresa->nombre = $actual->empresa;
                $separador_empresa->numero = $actual->numero;
                $separador_empresa->abreviatura = $actual->abreviatura;
                $separador_empresa->proyectos = array();
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
                array_push($separador_proyecto->empleados, $actual);
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

            } else {
                array_push($separador_empresa->empleados, $actual);
            }
            array_push($empleados, $separador_empresa);
        }
    } 

    $letra->total = array_sum($sumas_general);

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

$app->post('/vacaciones', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    date_default_timezone_set("America/Guatemala");

    // separadores
    $separador_empresa = new StdClass;
    $separador_proyecto = new StdClass;

    // sumadores
    $sumas_empresa = array();
    // proyecto
    $sumas_proyecto = array();
    // general
    $sumas_general = array();

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

    $cntsFacturas = count($data);

    if ($cntsFacturas > 1) {
    for ($i = 1; $i < $cntsFacturas; $i++)  {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            // empresa
            $separador_empresa->nombre = $anterior->empresa;
            $separador_empresa->abreviatura = $anterior->abreviatura;
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

        // sumas
        array_push($sumas_empresa, $anterior->liquido);
        // general
        array_push($sumas_general, $anterior->liquido);

        if ($d->agrupar == 2) {
            array_push($separador_proyecto->empleados, $anterior);
            array_push($sumas_proyecto, $anterior->liquido);
        } else {
            array_push($separador_empresa->empleados, $anterior);
        }

        if ($d->agrupar == 2) {
            if ($anterior->idproyecto !== $actual->idproyecto) {
                $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);

                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);

                // separador
                $separador_proyecto = new StdClass;
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
                $sumas_proyecto = array();
            }
        }

        if ($anterior->idempresa !== $actual->idempresa) {
            $separador_empresa->total = round(array_sum($sumas_empresa), 2);

            // empujar a array padre
            array_push($empleados, $separador_empresa);

            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->abreviatura = $actual->abreviatura;
            $separador_empresa->numero = $actual->numero;
            $separador_empresa->porproyecto = $d->agrupar == 2 ? true : null;
            $sumas_empresa = array();
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
                // sumas
                array_push($sumas_proyecto, $actual->liquido);
            } else {
                array_push($separador_empresa->empleados, $actual);
            }

            // sumas
            array_push($sumas_empresa, $actual->liquido);
            // general
            array_push($sumas_general, $actual->liquido);


            if ($d->agrupar == 2) {
                $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);
                // empujar a array padre
                array_push($separador_empresa->proyectos, $separador_proyecto);
            }

            $separador_empresa->total = round(array_sum($sumas_empresa), 2);

            array_push($empleados, $separador_empresa);
        }
    }
    } else {
        for ($i = 0; $i < $cntsFacturas; $i++)  {
            // traer valor actual y anterior
            $actual = $data[$i];

            // si es el primero insertar nombre del separador y crear array de recibos
            if ($primero) {
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
                array_push($separador_empresa->proyectos, $separador_proyecto);
            } else {
                array_push($separador_empresa->empleados, $actual);
            }
            array_push($sumas_general, $actual->liquido);
            array_push($empleados, $separador_empresa);
        }
    } 

    $letra->tsueldo = round(array_sum($sumas_general), 2);

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

function minusculas ($dat) {
    $dat->nombre = ucwords(strtolower($dat->nombre), ' ');
    $dat->puesto = ucfirst(strtolower($dat->puesto));
}
$app->run();