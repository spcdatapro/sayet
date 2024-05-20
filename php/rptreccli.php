<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/mensual', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    // convertir array a str separado por comas si existe mas de una empresa
    $ids_str = count($d->idempresa) > 0 ? implode(',', $d->idempresa) : "''";
    // variables iniciales
    $primero = true;
    $separador = new StdClass;
    $totales = new StdClass;
    $recibos = array();
    $montos_dia_gtq = array();
    $montos_dia_dlr = array();
    $mesdel = date("m", strtotime($d->fdelstr));
    $mesal = date("m", strtotime($d->falstr));
    $aniodel = ' '.date("Y", strtotime($d->fdelstr));
    $anioal = ' '.date("Y", strtotime($d->falstr));
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // clase para fechas
    $letra = new stdClass();

    $letra->del = date("d/m/Y", strtotime($d->fdelstr));
    $letra->al = 'al '.date("d/m/Y", strtotime($d->falstr));

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $letra->empresas = $db->getOneField("SELECT GROUP_CONCAT(nomempresa SEPARATOR ', ') FROM empresa WHERE id IN($ids_str)");

    $query = "SELECT 
                a.id,
                IF(a.anulado = 0,
                    CONCAT(a.serie, '-', IFNULL(b.seriea, b.serieb)),
                    'ANULADO') AS recibo,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                IFNULL(IFNULL(c.nombre, d.nombre),
                        'Clientes Varios') AS cliente,
                IF(e.idmoneda = 1 AND a.anulado = 0,
                    ROUND(SUM(e.monto), 2),
                    NULL) AS montogtq,
                IF(e.idmoneda = 2 AND a.anulado = 0,
                    ROUND(SUM(e.monto), 2),
                    NULL) AS montodlr,
                f.simbolo AS moneda,
                a.fecha AS fecharec,
                g.idproyecto,
                IFNULL(h.nomproyecto, 'SIN PROYECTO') AS proyecto,
                MONTH(a.fecha) AS idmes,
                DAY(a.fecha) AS dia
            FROM
                recibocli a
                    INNER JOIN
                serierecli b ON b.idrecibocli = a.id
                    LEFT JOIN
                cliente c ON a.idcliente = c.id
                    LEFT JOIN
                (SELECT 
                    d.nit, d.nombre
                FROM
                    factura d
                LIMIT 1) d ON d.nit = a.nit
                    LEFT JOIN
                detpagorecli e ON e.idreccli = a.id
                    LEFT JOIN
                moneda f ON e.idmoneda = f.id
                    LEFT JOIN
                (SELECT 
                    e.idrecibocli AS idrecibo,
                        IF(g.idproyecto = 0, d.idproyecto, g.idproyecto) AS idproyecto
                FROM
                    detcobroventa e
                INNER JOIN factura g ON e.idfactura = g.id
                INNER JOIN contrato d ON g.idcontrato = d.id GROUP BY e.idrecibocli) g ON g.idrecibo = a.id
                    LEFT JOIN
                proyecto h ON g.idproyecto = h.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
    $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
    $query.= "GROUP BY a.id ";
    $query.= $d->tipo == 1 ? "ORDER BY a.fecha ASC, " : "ORDER BY h.nomproyecto ASC, ";
    $query.= "a.serie ASC, b.seriea ASC, b.serieb ASC";
    $data = $db->getQuery($query);

    foreach($data AS $rec) {
        $rec->nombre = $rec->dia.' de '.$meses[$rec->idmes-1];
    }

    $cntsRecibos = count($data);

    for ($i = 1; $i < $cntsRecibos; $i++) {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];
        // separar por
        $por_ant = $d->tipo == 1 ? $anterior->nombre : $anterior->proyecto;
        $por_act = $d->tipo == 1 ? $actual->nombre : $actual->proyecto;
        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            $separador->nombre = $por_ant;
            $separador->recibos = array();
            $primero = false;
        }
        // si tienen el mismo proyecto empujar montos anteriores
        array_push($montos_dia_gtq, $anterior->montogtq);
        array_push($montos_dia_dlr, $anterior->montodlr);
        array_push($separador->recibos, $anterior);
        // si no tienen el mismo proyecto 
        if ($por_ant != $por_act) {
            // generar variable de totales
            $totales->quetzales = round(array_sum($montos_dia_gtq), 2);
            $totales->dolares = round(array_sum($montos_dia_dlr), 2);
            $separador->totales = $totales;
            // empujar a array global de recibo los recibos separados
            array_push($recibos, $separador);
            // limpiar variables 
            $totales = new StdClass;
            $montos_dia_gtq = array();
            $montos_dia_dlr = array();
            $separador = new StdClass;
            $separador->nombre = $por_act;
            $separador->recibos = array();
        }
        // para empujar el ultimo dato
        if ($i+1 == $cntsRecibos) {
            array_push($montos_dia_gtq, $actual->montogtq);
            array_push($montos_dia_dlr, $actual->montodlr);
            array_push($separador->recibos, $actual);
            $totales->quetzales = round(array_sum($montos_dia_gtq), 2);
            $totales->dolares = round(array_sum($montos_dia_dlr), 2);
            $separador->totales = $totales;
            array_push($recibos, $separador);
        }
    }

    print json_encode(['fechas' => $letra, 'recibos' => $recibos]);

});

$app->post('/correlativo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    // variables
    $primero = true;
    $separador = new StdClass;
    $totales = new StdClass;
    $recibos = array();
    $montos_empresa_gtq = array();
    $montos_empresa_dlr = array();
    $montos_proyecto_gtq = array();
    $montos_proyecto_dlr = array();
    $montos_dia_gtq = array();
    $montos_dia_dlr = array();

    // clase para fechas
    $letra = new stdClass();

    $letra->del = date("d/m/Y", strtotime($d->fdelstr));
    $letra->al = 'al '.date("d/m/Y", strtotime($d->falstr));

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $query = "SELECT 
                a.id,
                IF(a.anulado = 0,
                    CONCAT(a.serie, '-', IFNULL(b.seriea, b.serieb)),
                    'ANULADO') AS recibo,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                IFNULL(IFNULL(c.nombre, d.nombre),
                        'Clientes Varios') AS cliente,
                IF(e.idmoneda = 1 AND a.anulado = 0,
                    ROUND(SUM(e.monto), 2),
                    NULL) AS montogtq,
                IF(e.idmoneda = 2 AND a.anulado = 0,
                    ROUND(SUM(e.monto), 2),
                    NULL) AS montodlr,
                f.simbolo AS moneda,
                a.fecha AS fecharec,
                MONTH(a.fecha) AS idmes,
                DAY(a.fecha) AS dia,
                g.idproyecto,
                IFNULL(h.nomproyecto, 'SIN PROYECTO') AS proyecto,
                a.idempresa,
                k.nomempresa AS empresa,
                l.cuantos
            FROM
                recibocli a
                    INNER JOIN
                serierecli b ON b.idrecibocli = a.id
                    LEFT JOIN
                cliente c ON a.idcliente = c.id
                    LEFT JOIN
                (SELECT 
                    d.nit, d.nombre
                FROM
                    factura d
                LIMIT 1) d ON d.nit = a.nit
                    INNER JOIN
                detpagorecli e ON e.idreccli = a.id
                    INNER JOIN
                moneda f ON e.idmoneda = f.id
                    INNER JOIN 
                (SELECT e.idrecibocli AS idrecibo, IF(g.idproyecto = 0, d.idproyecto, g.idproyecto) AS idproyecto 
                FROM 
                    detcobroventa e 
                    INNER JOIN 
                factura g ON e.idfactura = g.id 
                    INNER JOIN contrato d ON g.idcontrato = d.id 
                GROUP BY e.idrecibocli) g ON g.idrecibo = a.id 
                    INNER JOIN 
                proyecto h ON g.idproyecto = h.id
                    INNER JOIN
                empresa k ON a.idempresa = k.id
                    INNER JOIN
                (SELECT 
                    a.idrecibocli, COUNT(DISTINCT c.idproyecto) AS cuantos
                FROM
                    detcobroventa a
                INNER JOIN factura b ON a.idfactura = b.id
                INNER JOIN contrato c ON b.idcontrato = c.id
                GROUP BY a.idrecibocli) l ON l.idrecibocli = a.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr'
                    AND l.cuantos = 1 ";
    $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : "";
    $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : ""; 
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
    $query.= $d->idproyecto != 0 ? "AND h.id = $d->idproyecto ": '';   
    $query.="GROUP BY a.id 
            UNION ALL SELECT 
                a.id,
                IF(a.anulado = 0,
                    CONCAT(a.serie, '-', IFNULL(b.seriea, b.serieb)),
                    'ANULADO') AS recibo,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                IFNULL(IFNULL(c.nombre, d.nombre),
                        'Clientes Varios') AS cliente,
                IF(h.idmoneda = 1 AND a.anulado = 0,
                    ROUND(SUM(g.monto), 2),
                    NULL) AS montogtq,
                IF(h.idmoneda = 2 AND a.anulado = 0,
                    ROUND(SUM(g.monto), 2),
                    NULL) AS montodlr,
                m.simbolo AS moneda,
                a.fecha AS fecharec,
                MONTH(a.fecha) AS idmes,
                DAY(a.fecha) AS dia,
                j.id AS proyecto,
                IFNULL(j.nomproyecto, 'SIN PROYECTO') AS proyecto,
                a.idempresa,
                k.nomempresa AS empresa,
                l.cuantos
            FROM
                recibocli a
                    INNER JOIN
                serierecli b ON b.idrecibocli = a.id
                    LEFT JOIN
                cliente c ON a.idcliente = c.id
                    LEFT JOIN
                (SELECT 
                    d.nit, d.nombre
                FROM
                    factura d
                LIMIT 1) d ON d.nit = a.nit
                    INNER JOIN
                detcobroventa g ON g.idrecibocli = a.id
                    INNER JOIN
                factura h ON g.idfactura = h.id
                    INNER JOIN
                contrato i ON h.idcontrato = i.id
                    INNER JOIN
                proyecto j ON j.id = i.idproyecto
                    INNER JOIN
                empresa k ON a.idempresa = k.id
                    INNER JOIN
                (SELECT 
                    a.idrecibocli, COUNT(DISTINCT c.idproyecto) AS cuantos
                FROM
                    detcobroventa a
                INNER JOIN factura b ON a.idfactura = b.id
                INNER JOIN contrato c ON b.idcontrato = c.id
                GROUP BY a.idrecibocli) l ON l.idrecibocli = a.id
                    INNER JOIN
                moneda m ON h.idmoneda = m.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr'
                    AND l.cuantos > 1 ";
    $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : "";
    $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : ""; 
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
    $query.= $d->idproyecto != 0 ? "AND j.id = $d->idproyecto ": '';   
    $query.=   "GROUP BY a.id , j.id ORDER BY 14 ASC , 12 ASC , 2 ASC";
    $data = $db->getQuery($query);

    $cntsRecibos = count($data);

    for ($i = 1; $i < $cntsRecibos; $i++) {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];
        // separar por
        $por_ant = $d->tipo == 1 ? $anterior->nombre : $anterior->proyecto;
        $por_act = $d->tipo == 1 ? $actual->nombre : $actual->proyecto;
        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            if ($d->porempresa) {
                $separador->empresa = $anterior->empresa;
            }
            $separador->nombre = $por_ant;
            $separador->recibos = array();
            $primero = false;
        }
        // si tienen el mismo proyecto empujar montos anteriores
        array_push($montos_dia_gtq, $anterior->montogtq);
        array_push($montos_dia_dlr, $anterior->montodlr);
        if ($d->porempresa) {
            array_push($montos_empresa_gtq, $anterior->montogtq);
            array_push($montos_empresa_dlr, $anterior->montodlr);
        }
        array_push($separador->recibos, $anterior);
        // si no tienen el mismo proyecto 
        if ($por_ant != $por_act) {
            // generar variable de totales
            $totales->quetzales = round(array_sum($montos_dia_gtq), 2);
            $totales->dolares = round(array_sum($montos_dia_dlr), 2);
            if ($d->porempresa && $anterior->idempresa != $actual->idempresa){
                $totales->empresa_quetzal = round(array_sum($montos_empresa_gtq), 2);
                $totales->empresa_dolar = round(array_sum($montos_empresa_dlr), 2);
                $montos_empresa_gtq = array();
                $montos_empresa_dlr = array();
            }
            $separador->totales = $totales;
            // empujar a array global de recibo los recibos separados
            array_push($recibos, $separador);
            // limpiar variables 
            $totales = new StdClass;
            $montos_dia_gtq = array();
            $montos_dia_dlr = array();
            $separador = new StdClass;
            if ($d->porempresa && $anterior->idempresa != $actual->idempresa){
                $separador->empresa = $actual->empresa;
            }
            $separador->nombre = $por_act;
            $separador->recibos = array();
        }
        // para empujar el ultimo dato
        if ($i+1 == $cntsRecibos) {
            array_push($montos_dia_gtq, $actual->montogtq);
            array_push($montos_dia_dlr, $actual->montodlr);
            if ($d->porempresa) {
                array_push($montos_empresa_gtq, $actual->montogtq);
                array_push($montos_empresa_dlr, $actual->montodlr);
                $totales->empresa_quetzal = round(array_sum($montos_empresa_gtq), 2);
                $totales->empresa_dolar = round(array_sum($montos_empresa_dlr), 2);
            }
            array_push($separador->recibos, $actual);
            $totales->quetzales = round(array_sum($montos_dia_gtq), 2);
            $totales->dolares = round(array_sum($montos_dia_dlr), 2);
            $separador->totales = $totales;
            array_push($recibos, $separador);
        }
    }

    print json_encode(['fechas' => $letra, 'recibos' => $recibos]);
});

$app->run();