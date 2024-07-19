<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptempelados', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT a.id, a.nombre, IFNULL(CONCAT('(', a.numeropat, ')'), '') AS patronal 
    FROM plnempresa a INNER JOIN proyecto b ON b.idempresa = a.id WHERE b.id IN (SELECT idproyecto FROM plnempleado ";
    $query.= $d->inactivos == 0 ? "WHERE baja IS NULL) " : ")";
    $query.= $d->idempresa == 0 ? "" : "AND id = $d->idempresa ";
    $empresas = $db->getQuery($query);

    $cntEmpresas = count($empresas);

    for ($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $empresas[$i];

        $query = "SELECT id, nomproyecto AS proyecto FROM proyecto WHERE idempresa = $empresa->id AND 
        id IN (SELECT idproyecto FROM plnempleado ";
        $query.= $d->inactivos == 0 ? "WHERE baja IS NULL) " : ")";
        $empresa->proyectos = $db->getQuery($query);

        $cntProyectos = count($empresa->proyectos);

        if ($cntProyectos > 0) {
            $empresa->mostrar = true;
        } else {
            $empresa->mostrar = null;
        }

        for ($j = 0; $j < $cntProyectos; $j++) {
            $proyecto = $empresa->proyectos[$j];
    
            $query = "SELECT 
                        a.id,
                        CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                        DATE_FORMAT(ingreso, '%d/%m/%y') AS fingreso,
                        IFNULL(DATE_FORMAT(reingreso, '%d/%m/%y'), '') AS freingreso,
                        a.sueldo,
                        a.bonificacionley,
                        a.sueldo + a.bonificacionley AS sueldotot,
                        b.descripcion AS puesto
                    FROM
                        plnempleado a
                    INNER JOIN 
                        plnpuesto b ON a.idplnpuesto = b.id
                    WHERE
                        a.idproyecto = $proyecto->id ";
            $query.= $d->inactivos == 0 ? "AND a.baja IS NULL " : "";
            $query.= "ORDER BY a.nombre ASC ";
            $proyecto->empleados = $db->getQuery($query);
        }
    }

    print json_encode([ 'empresas' => $empresas ]);
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
    $query.=   "ORDER BY 4 , 5 , 6";
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
            $separador_empresa->proyectos = array();
            // proyecto
            $separador_proyecto->nombre = $anterior->proyecto;
            $separador_proyecto->empleados = array();
            $primero = false;
        }

        // sumas
        array_push($sumas_empresa, $anterior->bonocatorce);
        array_push($sumas_proyecto, $anterior->bonocatorce);
        array_push($sumas_general, $anterior->bonocatorce);

        array_push($separador_proyecto->empleados, $anterior);


        if ($anterior->idproyecto !== $actual->idproyecto) {
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
            if ($anterior->idproyecto == $actual->idproyecto) {
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
            $separador_empresa->proyectos = array();
            $sumas_empresa = array();
        }
        
        // para empujar el ultimo dato
        if ($i+1 == $cntsDatos) {
            // empujar ultimo
            array_push($separador_proyecto->empleados, $actual);
            array_push($sumas_empresa, $actual->bonocatorce);
            array_push($sumas_proyecto, $actual->bonocatorce);
            array_push($sumas_general, $actual->bonocatorce);

            $separador_proyecto->total = round(array_sum($sumas_proyecto), 2);

            // empujar a array padre
            array_push($separador_empresa->proyectos, $separador_proyecto);

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
                // proyecto
                $separador_proyecto->nombre = $actual->proyecto;
                $separador_proyecto->empleados = array();
                $primero = false;
            }
    
            array_push($separador_proyecto->empleados, $actual);
            array_push($separador_empresa->proyectos, $separador_proyecto);
            array_push($empleados, $separador_empresa);
        }
    } 

    $letra->total = array_sum($sumas_general);

    print json_encode([ 'encabezado' => $letra, 'empresas' => $empleados ]);
});

function minusculas ($dat) {
    $dat->nombre = ucwords(strtolower($dat->nombre), ' ');
    $dat->puesto = ucfirst(strtolower($dat->puesto));
}
$app->run();