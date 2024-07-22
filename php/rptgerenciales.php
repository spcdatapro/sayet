<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/finanzas', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $separador = new StdClass;
    $totales = new StdClass;
    $grafica = new stdClass;
    $mes = array();
    $suma_montos = array();
    $primero = true;
    $cuerpo = array();

    // variables iniciales
    $idcuenta = array();
    $nombres = array();
    $montos = array();
    $colores = array();

    // meses
    $mesdel = $d->mesdel;
    $mesal = $d->mesal;

    // anios
    $aniodel = $d->anio;
    $anioal = $d->anio;

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // validar si solo estan obteniendo un anio
    if ($aniodel == $anioal) {
        $aniodel = '';
    }

    // clase para fechas
    $letra = new stdClass();

    $letra->del = $meses[$mesdel].$aniodel;

    // validar si solo estan obteniendo un mes
    if ($mesal != $mesdel) {
        $letra->al = 'a '.$meses[$mesal].' '.$anioal;
    } else {
        $letra->al = $anioal;
    }

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $letra->empresa = $db->getOneField("SELECT nomempresa FROM empresa WHERE id = $d->idempresa");
    $letra->proyecto = $db->getOneField("SELECT nomproyecto FROM proyecto WHERE id = $d->idproyecto");
    $letra->unidad = isset($d->idunidad) ? $db->getOneField("SELECT nombre FROM unidad WHERE id = $d->idunidad") : null;

    // convertir los meses
    $d->mesdel = $d->mesdel + 1;
    $d->mesal = $d->mesal + 1;

    $cntMeses = contarMeses($mesdel, $mesal);

    $query = "SELECT 
                b.idtiposervicio,
                MONTH(a.fecha) AS mes,
                UPPER(c.desctiposervventa) AS cuenta,
                c.cuentac AS codigo,
                IFNULL(d.nombrecorto, SUBSTR(a.nombre, 1, 15)) AS cliente,
                IFNULL(f.nombre, 'N/A') AS unidad,
                CONCAT(a.serie, '-', a.numero) AS factura,
                IFNULL(ROUND(f.mcuad, 2), 0.00) AS mcuad,
                IFNULL(ROUND(ROUND((b.preciotot * IF(a.idtipofactura <> 9, 1, - 1)) / 1.12,
                                        2) / IFNULL(ROUND(f.mcuad, 2), 0.00),
                                2),
                        0.00) AS unitario,
                ROUND((b.preciotot * IF(a.idtipofactura <> 9, 1, - 1) / 1.12),
                        2) AS total
            FROM
                factura a
                    INNER JOIN
                detfact b ON b.idfactura = a.id
                    INNER JOIN
                tiposervicioventa c ON b.idtiposervicio = c.id
                    LEFT JOIN
                cliente d ON a.idcliente = d.id
                    LEFT JOIN
                contrato e ON a.idcontrato = e.id
                    LEFT JOIN
                unidad f ON e.idunidad = f.id
            WHERE
                a.idempresa = $d->idempresa
                    AND (a.idproyecto = $d->idproyecto OR e.idproyecto = $d->idproyecto) ";
    $query.= isset($d->idunidad) ? "AND e.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND YEAR(a.fecha) = $d->anio
                    AND b.idtiposervicio != 1
            ORDER BY 2 ASC, 1, 6";
    $data_v = $db->getQuery($query);

        $query = "SELECT 
                c.id,
                MONTH(b.fechaingreso) AS mes,
                c.codigo,
                UPPER(c.nombrecta) AS nombrecta,
                DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fechatran,
                CONCAT(e.tipotrans, ' ', e.numero) AS cheque,
                SUBSTRING(e.beneficiario, 1, 30) AS beneficiario,
                IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                        '') AS orden,
                SUBSTRING(LOWER(b.conceptomayor), 1, 65) AS concepto,
                DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                CONCAT(g.siglas, ' (', b.documento, ')') AS documento,
                ROUND(IF(b.idtipofactura = 10, a.monto * -1, a.monto), 2) AS total,
                b.fechafactura AS ord
            FROM
                compraproyecto a
                    INNER JOIN
                compra b ON a.idcompra = b.id
                    INNER JOIN
                cuentac c ON a.idcuentac = c.id
                    LEFT JOIN
                detpagocompra d ON d.idcompra = b.id
                    LEFT JOIN
                tranban e ON d.idtranban = e.id
                    LEFT JOIN
                detpresupuesto f ON b.ordentrabajo = f.id
                    INNER JOIN
                tipofactura g ON b.idtipofactura = g.id
            WHERE
                b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(b.fechaingreso) >= $d->mesdel
                    AND MONTH(b.fechaingreso) <= $d->mesal
                    AND YEAR(b.fechaingreso) = $d->anio
                    AND c.id 
            UNION ALL SELECT 
                c.id,
                MONTH(b.fechaingreso) AS mes,
                c.codigo,
                UPPER(c.nombrecta) AS nombrecta,
                DATE_FORMAT(IFNULL(e.fecha, g.fecha), '%d/%m/%Y') AS fechatran,
                IFNULL(CONCAT(e.tipotrans, ' ', e.numero),
                        CONCAT(g.tipotrans, ' ', g.numero)) AS cheque,
                SUBSTRING(IFNULL(SUBSTRING(e.beneficiario, 1, 30),
                            SUBSTRING(g.beneficiario, 1, 30)),
                    1,
                    30) AS beneficiario,
                IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                        '') AS orden,
                SUBSTRING(LOWER(b.conceptomayor), 1, 65) AS concepto,
                DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                CONCAT(h.siglas, ' (', b.documento, ')') AS documento,
                ROUND(IF(b.idtipofactura = 10, a.debe + a.haber * -1, a.debe + a.haber), 2) AS total,
                b.fechafactura AS ord
            FROM
                detallecontable a
                    INNER JOIN
                compra b ON a.idorigen = b.id AND a.origen = 2
                    INNER JOIN
                cuentac c ON a.idcuenta = c.id
                    LEFT JOIN
                dettranreem d ON d.idreembolso = b.idreembolso
                    LEFT JOIN
                tranban e ON d.idtranban = e.id
                    LEFT JOIN
                detpresupuesto f ON b.ordentrabajo = f.id
                    LEFT JOIN
                tranban g ON g.idreembolso = b.idreembolso
                    INNER JOIN
                tipofactura h ON b.idtipofactura = h.id
            WHERE
                b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(b.fechaingreso) >= $d->mesdel
                    AND MONTH(b.fechaingreso) <= $d->mesal
                    AND YEAR(b.fechaingreso) = $d->anio
                    AND b.idreembolso > 0
                    AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%'
                    OR TRIM(c.codigo) = '1120299')
                    AND c.id 
            UNION ALL SELECT 
                9999 AS id,
                MONTH(a.fecha) AS mes,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                CONCAT(d.tipotrans, '-', d.numero) AS cheque,
                SUBSTRING(CONCAT(b.nombre, ' ', IFNULL(b.apellidos, '')),
                    1,
                    30) AS beneficiario,
                a.idplnempleado AS orden,
                'Devengado' AS concepto,
                NULL AS fechafact,
                IFNULL(c.nombre, '') AS documento,
                ROUND(a.devengado, 2) AS total,
                a.idplnempleado AS ord
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    LEFT JOIN
                unidad c ON b.idunidad = c.id
                    LEFT JOIN
                (SELECT 
                    id, 
                    tipotrans, 
                    numero, 
                    idempleado 
                FROM tranban WHERE MONTH(fecha) >= $d->mesdel 
                    AND MONTH(fecha) <= $d->mesal 
                    AND DAY(fecha) >= 16 AND YEAR(fecha) = $d->anio GROUP BY idempleado) d ON d.idempleado = a.idplnempleado
            WHERE
                a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) >= 16
                    AND YEAR(a.fecha) = $d->anio
            UNION ALL SELECT 
                9999 AS id,
                MONTH(a.fecha) AS mes,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                NULL AS fechatran,
                NULL AS cheque,
                NULL AS beneficiario,
                a.idplnempleado AS orden,
                'Cuota patronal' AS concepto,
                NULL AS fechafact,
                NULL AS documento,
                ROUND((a.sueldoordinario + a.sueldoextra) * 0.1267,
                        2) AS total,
                a.idplnempleado AS ord
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    LEFT JOIN
                unidad c ON b.idunidad = c.id
            WHERE
                a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) >= 16
                    AND YEAR(a.fecha) = $d->anio
            ORDER BY 2 ASC, 1 ASC, 13 ASC, 5 DESC, 7 ASC";
    $data_c = $db->getQuery($query);

    $cntsCompras = count($data_c);

    $cntsVentas = count($data_v);

    for ($j = 0; $j < $cntMeses; $j++) {
        $separador_mes = new StdClass;
        $separador_mes->varios = $cntMeses > 1 ? 1 : null;
        $separador_mes->nombre = $meses[$mesdel + $j];
        $separador_mes->ventas = array();
        $separador_mes->compras = array();
        $suma_ventas = array();
        $suma_compras = array();

        if ($cntsVentas > 1) {
            for ($i = 1; $i < $cntsVentas; $i++) {
                // traer valor actual y anterior
                $actual = $data_v[$i];
                $anterior = $data_v[$i-1];

                if ($d->mesdel + $j == $anterior->mes) {

                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        $separador->id = $anterior->idtiposervicio;
                        $separador->nombre = $anterior->cuenta;
                        $separador->codigo = $anterior->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    // siempre empujar el monto anterior ya que fue validado anteriormente
                    array_push($suma_montos, $anterior->total);
                    array_push($separador->facturas, $anterior);

                    // si no tienen el mismo separador
                    if ($actual->idtiposervicio != $anterior->idtiposervicio || $actual->mes != $anterior->mes) {
                        // generar variable de totales
                        $totales->total = round(array_sum($suma_montos), 2);
                        $separador->total = round(array_sum($suma_montos), 2);
                        // $separador->totales = $totales;

                        // total general
                        array_push($suma_ventas, $totales->total);

                        // empujar a array global de recibo los recibos separados
                        array_push($separador_mes->ventas, $separador);
                        // limpiar variables 
                        $totales = new StdClass;
                        $suma_montos = array();
                        $separador = new StdClass;
                        $separador->id = $actual->idtiposervicio;
                        $separador->nombre = $actual->cuenta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                    }
                }
                if ($d->mesdel + $j == $actual->mes) {
                    // para empujar el ultimo dato
                    if ($i+1 == $cntsVentas) {
                        array_push($suma_montos, $actual->total);
                        array_push($separador->facturas, $actual);
                        $totales->total = round(array_sum($suma_montos), 2);
                        array_push($suma_ventas, $totales->total);
                        $separador->total = round(array_sum($suma_montos), 2);
                        array_push($separador_mes->ventas, $separador);
                    
                        // limpiar 
                        $suma_montos = array();
                        $separador = new StdClass;
                        $totales = new StdClass;
                        $primero = true;
                    }
                }
            } 
        } else {
            for ($i = 0; $i < $cntsVentas; $i++) {
                // traer valor actual y anterior
                $actual = $data_v[$i];

                if ($d->mesdel + $j == $actual->mes) {

                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        $separador->id = $actual->idtiposervicio;
                        $separador->nombre = $actual->cuenta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    array_push($suma_montos, $actual->total);
                    array_push($separador->facturas, $actual);
                    $totales->total = round(array_sum($suma_montos), 2);
                    array_push($suma_ventas, $totales->total);
                    $separador->total = round(array_sum($suma_montos), 2);
                    array_push($separador_mes->ventas, $separador);

                    // limpiar 
                    $suma_montos = array();
                    $separador = new StdClass;
                    $totales = new StdClass;
                    $primero = true;
                }
            }
        } 

        if ($cntsCompras > 1) {
            for ($i = 1; $i < $cntsCompras; $i++) {
                // traer valor actual y anterior
                $actual = $data_c[$i];
                $anterior = $data_c[$i-1];

                if ($d->mesdel + $j == $anterior->mes) {
                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        array_push($nombres, substr($anterior->nombrecta, 0, 6));
                        $separador->id = $anterior->id;
                        $separador->nombre = $anterior->nombrecta;
                        $separador->codigo = $anterior->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    // siempre empujar el monto anterior ya que fue validado anteriormente
                    array_push($suma_montos, $anterior->total);
                    array_push($separador->facturas, $anterior);

                    // si no tienen el mismo separador
                    if ($actual->id != $anterior->id || $actual->mes != $anterior->mes) {
                        // generar variable de totales
                        $totales->total = round(array_sum($suma_montos), 2);
                        $separador->total = round(array_sum($suma_montos), 2);
                        // $separador->totales = $totales;

                        // para graficas
                        array_push($montos, $totales->total);
                        array_push($nombres, substr($actual->nombrecta, 0, 6));
                        array_push($suma_compras, $totales->total);

                        // empujar a array global de recibo los recibos separados
                        array_push($separador_mes->compras, $separador);
                        // limpiar variables 
                        $totales = new StdClass;
                        $suma_montos = array();
                        $separador = new StdClass;
                        $separador->id = $actual->id;
                        $separador->nombre = $actual->nombrecta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                    }
                }
                if ($d->mesdel + $j == $actual->mes) {
                    // para empujar el ultimo dato
                    if ($i+1 == $cntsCompras) {
                        array_push($suma_montos, $actual->total);
                        array_push($separador->facturas, $actual);
                        $totales->total = round(array_sum($suma_montos), 2);
                        array_push($suma_compras, $totales->total);
                        $separador->total = round(array_sum($suma_montos), 2);
                        // $separador->totales = $totales;
                        array_push($separador_mes->compras, $separador);
                
                        // para graficas
                        array_push($montos, $totales->total);
                
                        // limpiar 
                        $suma_montos = array();
                        $separador = new StdClass;
                        $totales = new StdClass;
                    }
                }
            }
        } else {
            for ($i = 0; $i < $cntsCompras; $i++) {
                // traer valor actual y anterior
                $actual = $data_c[$i];

                if ($d->mesdel + $j == $actual->mes) {

                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        array_push($nombres, substr($actual->nombrecta, 0, 6));
                        $separador->id = $actual->id;
                        $separador->nombre = $actual->nombrecta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    array_push($suma_montos, $actual->total);
                    array_push($separador->facturas, $actual);
                    $totales->total = round(array_sum($suma_montos), 2);
                    array_push($suma_compras, $totales->total);
                    $separador->total = round(array_sum($suma_montos), 2);
                    // $separador->totales = $totales;
                    array_push($separador_mes->compras, $separador);

                    // limpiar 
                    $suma_montos = array();
                    $separador = new StdClass;
                    $totales = new StdClass;
                }
            }
        }

        $separador_mes->total_compras = round(array_sum($suma_compras), 2);        
        $separador_mes->total_ventas = round(array_sum($suma_ventas), 2);
        $separador_mes->diferencia = round($separador_mes->total_ventas - $separador_mes->total_compras);

        usort($separador_mes->ventas, "compararPorTotal");
        usort($separador_mes->compras, "compararPorTotal");

        array_push($mes, $separador_mes);
    }

    // nombres y montos de todas las cuentas
    // $grafica->nombres = $nombres;
    // $grafica->montos = $montos;
    // $cntNombres = count($nombres);

    // $grafica->colores = gradient_colors($cntNombres);

    // $tot_ventas = array_sum($suma_ventas);
    // $tot_compras = array_sum($suma_compras);
    // $diferencia = $tot_ventas - $tot_compras;

    // $letra->total_ventas = $tot_ventas;
    // $letra->total_compras = $tot_compras;
    // $letra->deficit = $diferencia < 0 ? true : null;
    // $letra->diferencia = $diferencia;

    print json_encode([ 'encabezado' => $letra, 'meses' => $mes ]);
});

function random_hex_color () {
    $r = rand (0, 255);
    $g = rand (0, 255);
    $b = rand (0, 255);
    return sprintf ('#%02x%02x%02x', $r, $g, $b);
}

function comparar_nombres ($a, $b) {
    if ($a->codigo == $b->codigo) return 0;
    return 1;
}

function gradient_colors($num_colors) {
    $colors = [];
    for ($i = 0; $i < $num_colors; $i++) {
        // Calcula los valores RGB para el gradiente
        $r = intval(255 * (1 - $i / ($num_colors - 1)));
        $g = intval(255 * ($i / ($num_colors - 1)));
        $b = 0;
        // Convierte a formato hexadecimal
        $hex_color = sprintf("#%02x%02x%02x", $r, $g, $b);
        $colors[] = $hex_color;
    }
    return $colors;
}

function compararPorTotal($a, $b) {
    return $b->total - $a->total;
}

function contarMeses($min, $max) {
    $contador = 1;
    for ($i = $min; $i < $max; $i++) {
        $contador++;
    }
    return $contador;
}

$app->post('/resumen', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $separador = new StdClass;
    $totales = new StdClass;
    $grafica = new stdClass;
    $mes = array();
    $suma_montos = array();
    $primero = true;
    $montos = array();
    $nombres = array();

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    $mesdel = $d->mesdel;
    $mesal = $d->mesal;

    // convertir los meses
    $d->mesdel = $d->mesdel + 1;
    $d->mesal = $d->mesal + 1;

    $cntMeses = contarMeses($mesdel, $mesal);

    $query = "SELECT 
                b.idtiposervicio,
                MONTH(a.fecha) AS mes,
                UPPER(c.desctiposervventa) AS cuenta,
                c.cuentac AS codigo,
                IFNULL(d.nombrecorto, SUBSTR(a.nombre, 1, 15)) AS cliente,
                IFNULL(f.nombre, 'N/A') AS unidad,
                CONCAT(a.serie, '-', a.numero) AS factura,
                IFNULL(ROUND(f.mcuad, 2), 0.00) AS mcuad,
                IFNULL(ROUND(ROUND((b.preciotot * IF(a.idtipofactura <> 9, 1, - 1)) / 1.12,
                                        2) / IFNULL(ROUND(f.mcuad, 2), 0.00),
                                2),
                        0.00) AS unitario,
                ROUND((b.preciotot * IF(a.idtipofactura <> 9, 1, - 1) / 1.12),
                        2) AS total,
                a.id
            FROM
                factura a
                    INNER JOIN
                detfact b ON b.idfactura = a.id
                    INNER JOIN
                tiposervicioventa c ON b.idtiposervicio = c.id
                    LEFT JOIN
                cliente d ON a.idcliente = d.id
                    LEFT JOIN
                contrato e ON a.idcontrato = e.id
                    LEFT JOIN
                unidad f ON e.idunidad = f.id
            WHERE
                a.idempresa = $d->idempresa
                    AND (a.idproyecto = $d->idproyecto OR e.idproyecto = $d->idproyecto) ";
    $query.= isset($d->idunidad) ? "AND e.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND YEAR(a.fecha) = $d->anio
                    AND b.idtiposervicio != 1
            ORDER BY 2 ASC, 1, 6";
    $data_v = $db->getQuery($query);

        $query = "SELECT 
                c.id,
                MONTH(b.fechaingreso) AS mes,
                c.codigo,
                UPPER(c.nombrecta) AS nombrecta,
                DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fechatran,
                CONCAT(e.tipotrans, ' ', e.numero) AS cheque,
                SUBSTRING(e.beneficiario, 1, 20) AS beneficiario,
                IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                        '') AS orden,
                SUBSTRING(LOWER(b.conceptomayor), 1, 65) AS concepto,
                DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                CONCAT(g.siglas, ' (', b.documento, ')') AS documento,
                ROUND(IF(b.idtipofactura = 10, a.monto * -1, a.monto), 2) AS total,
                b.fechafactura AS ord,
                b.id AS idcompra
            FROM
                compraproyecto a
                    INNER JOIN
                compra b ON a.idcompra = b.id
                    INNER JOIN
                cuentac c ON a.idcuentac = c.id
                    LEFT JOIN
                detpagocompra d ON d.idcompra = b.id
                    LEFT JOIN
                tranban e ON d.idtranban = e.id
                    LEFT JOIN
                detpresupuesto f ON b.ordentrabajo = f.id
                    INNER JOIN
                tipofactura g ON b.idtipofactura = g.id
            WHERE
                b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(b.fechaingreso) >= $d->mesdel
                    AND MONTH(b.fechaingreso) <= $d->mesal
                    AND YEAR(b.fechaingreso) = $d->anio
                    AND c.id 
            UNION ALL SELECT 
                c.id,
                MONTH(b.fechaingreso) AS mes,
                c.codigo,
                UPPER(c.nombrecta) AS nombrecta,
                DATE_FORMAT(IFNULL(e.fecha, g.fecha), '%d/%m/%Y') AS fechatran,
                IFNULL(CONCAT(e.tipotrans, ' ', e.numero),
                        CONCAT(g.tipotrans, ' ', g.numero)) AS cheque,
                SUBSTRING(IFNULL(SUBSTRING(e.beneficiario, 1, 30),
                            SUBSTRING(g.beneficiario, 1, 30)),
                    1,
                    20) AS beneficiario,
                IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                        '') AS orden,
                SUBSTRING(LOWER(b.conceptomayor), 1, 65) AS concepto,
                DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                CONCAT(h.siglas, ' (', b.documento, ')') AS documento,
                ROUND(IF(b.idtipofactura = 10, a.debe + a.haber * -1, a.debe + a.haber), 2) AS total,
                b.fechafactura AS ord,
                b.id AS idcompra
            FROM
                detallecontable a
                    INNER JOIN
                compra b ON a.idorigen = b.id AND a.origen = 2
                    INNER JOIN
                cuentac c ON a.idcuenta = c.id
                    LEFT JOIN
                dettranreem d ON d.idreembolso = b.idreembolso
                    LEFT JOIN
                tranban e ON d.idtranban = e.id
                    LEFT JOIN
                detpresupuesto f ON b.ordentrabajo = f.id
                    LEFT JOIN
                tranban g ON g.idreembolso = b.idreembolso
                    INNER JOIN
                tipofactura h ON b.idtipofactura = h.id
            WHERE
                b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(b.fechaingreso) >= $d->mesdel
                    AND MONTH(b.fechaingreso) <= $d->mesal
                    AND YEAR(b.fechaingreso) = $d->anio
                    AND b.idreembolso > 0
                    AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%'
                    OR TRIM(c.codigo) = '1120299')
                    AND c.id 
            UNION ALL SELECT 
                9999 AS id,
                MONTH(a.fecha) AS mes,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                CONCAT(d.tipotrans, '-', d.numero) AS cheque,
                SUBSTRING(CONCAT(b.nombre, ' ', IFNULL(b.apellidos, '')),
                    1,
                    20) AS beneficiario,
                a.idplnempleado AS orden,
                'Devengado' AS concepto,
                NULL AS fechafact,
                IFNULL(c.nombre, '') AS documento,
                ROUND(a.descanticipo + a.liquido + a.descprestamo, 2) AS total,
                a.idplnempleado AS ord,
                NULL AS idcompra
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    LEFT JOIN
                unidad c ON b.idunidad = c.id
                    LEFT JOIN
                (SELECT 
                    id, 
                    tipotrans, 
                    numero, 
                    idempleado 
                FROM tranban WHERE MONTH(fecha) >= $d->mesdel 
                    AND MONTH(fecha) <= $d->mesal 
                    AND DAY(fecha) >= 16 AND YEAR(fecha) = $d->anio GROUP BY idempleado) d ON d.idempleado = a.idplnempleado
            WHERE
                a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) >= 16
                    AND YEAR(a.fecha) = $d->anio
            UNION ALL SELECT 
                9999 AS id,
                MONTH(a.fecha) AS mes,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                NULL AS fechatran,
                NULL AS cheque,
                NULL AS beneficiario,
                a.idplnempleado AS orden,
                'Cuota patronal' AS concepto,
                NULL AS fechafact,
                NULL AS documento,
                ROUND((a.sueldoordinario + a.sueldoextra) * 0.1267,
                        2) AS total,
                a.idplnempleado AS ord,
                NULL AS idcompra
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    LEFT JOIN
                unidad c ON b.idunidad = c.id
            WHERE
                a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND b.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) >= 16
                    AND YEAR(a.fecha) = $d->anio
            ORDER BY 2 ASC, 1 ASC, 13 ASC, 5 DESC, 7 ASC";
    $data_c = $db->getQuery($query);

    $cntsCompras = count($data_c);

    $cntsVentas = count($data_v);

    for ($j = 0; $j < $cntMeses; $j++) {
        $separador_mes = new StdClass;
        $separador_mes->varios = $cntMeses > 1 ? 1 : null;
        $separador_mes->nombre = $meses[$mesdel + $j];
        $separador_mes->ventas = array();
        $separador_mes->compras = array();
        $suma_ventas = array();
        $suma_compras = array();

        if ($cntsVentas > 1) {
            for ($i = 1; $i < $cntsVentas; $i++) {
                // traer valor actual y anterior
                $actual = $data_v[$i];
                $anterior = $data_v[$i-1];

                if ($d->mesdel + $j == $anterior->mes) {

                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        $separador->id = $anterior->idtiposervicio;
                        $separador->nombre = $anterior->cuenta;
                        $separador->codigo = $anterior->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    // siempre empujar el monto anterior ya que fue validado anteriormente
                    array_push($suma_montos, $anterior->total);
                    array_push($separador->facturas, $anterior);

                    // si no tienen el mismo separador
                    if ($actual->idtiposervicio != $anterior->idtiposervicio || $actual->mes != $anterior->mes) {
                        // generar variable de totales
                        $totales->total = round(array_sum($suma_montos), 2);
                        $separador->total = round(array_sum($suma_montos), 2);
                        // $separador->totales = $totales;

                        // total general
                        array_push($suma_ventas, $totales->total);

                        // empujar a array global de recibo los recibos separados
                        array_push($separador_mes->ventas, $separador);
                        // limpiar variables 
                        $totales = new StdClass;
                        $suma_montos = array();
                        $separador = new StdClass;
                        $separador->id = $actual->idtiposervicio;
                        $separador->nombre = $actual->cuenta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                    }
                }
                if ($d->mesdel + $j == $actual->mes) {
                    // para empujar el ultimo dato
                    if ($i+1 == $cntsVentas) {
                        array_push($suma_montos, $actual->total);
                        array_push($separador->facturas, $actual);
                        $totales->total = round(array_sum($suma_montos), 2);
                        array_push($suma_ventas, $totales->total);
                        $separador->total = round(array_sum($suma_montos), 2);
                        array_push($separador_mes->ventas, $separador);
                    
                        // limpiar 
                        $suma_montos = array();
                        $separador = new StdClass;
                        $totales = new StdClass;
                        $primero = true;
                    }
                }
            } 
        } else {
            for ($i = 0; $i < $cntsVentas; $i++) {
                // traer valor actual y anterior
                $actual = $data_v[$i];

                if ($d->mesdel + $j == $actual->mes) {

                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        $separador->id = $actual->idtiposervicio;
                        $separador->nombre = $actual->cuenta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    array_push($suma_montos, $actual->total);
                    array_push($separador->facturas, $actual);
                    $totales->total = round(array_sum($suma_montos), 2);
                    array_push($suma_ventas, $totales->total);
                    $separador->total = round(array_sum($suma_montos), 2);
                    array_push($separador_mes->ventas, $separador);

                    // limpiar 
                    $suma_montos = array();
                    $separador = new StdClass;
                    $totales = new StdClass;
                    $primero = true;
                }
            }
        } 

        if ($cntsCompras > 1) {
            for ($i = 1; $i < $cntsCompras; $i++) {
                // traer valor actual y anterior
                $actual = $data_c[$i];
                $anterior = $data_c[$i-1];

                if ($d->mesdel + $j == $anterior->mes) {
                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        array_push($nombres, substr($anterior->nombrecta, 0, 6));
                        $separador->id = $anterior->id;
                        $separador->nombre = $anterior->nombrecta;
                        $separador->codigo = $anterior->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    // siempre empujar el monto anterior ya que fue validado anteriormente
                    array_push($suma_montos, $anterior->total);
                    array_push($separador->facturas, $anterior);

                    // si no tienen el mismo separador
                    if ($actual->id != $anterior->id || $actual->mes != $anterior->mes) {
                        // generar variable de totales
                        $totales->total = round(array_sum($suma_montos), 2);
                        $separador->total = round(array_sum($suma_montos), 2);
                        // $separador->totales = $totales;

                        // para graficas
                        array_push($montos, $totales->total);
                        array_push($nombres, substr($actual->nombrecta, 0, 6));
                        array_push($suma_compras, $totales->total);

                        // empujar a array global de recibo los recibos separados
                        array_push($separador_mes->compras, $separador);
                        // limpiar variables 
                        $totales = new StdClass;
                        $suma_montos = array();
                        $separador = new StdClass;
                        $separador->id = $actual->id;
                        $separador->nombre = $actual->nombrecta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                    }
                }
                if ($d->mesdel + $j == $actual->mes) {
                    // para empujar el ultimo dato
                    if ($i+1 == $cntsCompras) {
                        array_push($suma_montos, $actual->total);
                        array_push($separador->facturas, $actual);
                        $totales->total = round(array_sum($suma_montos), 2);
                        array_push($suma_compras, $totales->total);
                        $separador->total = round(array_sum($suma_montos), 2);
                        // $separador->totales = $totales;
                        array_push($separador_mes->compras, $separador);
                
                        // para graficas
                        array_push($montos, $totales->total);
                
                        // limpiar 
                        $suma_montos = array();
                        $separador = new StdClass;
                        $totales = new StdClass;
                    }
                }
            }
        } else {
            for ($i = 0; $i < $cntsCompras; $i++) {
                // traer valor actual y anterior
                $actual = $data_c[$i];

                if ($d->mesdel + $j == $actual->mes) {

                    // si es el primero insertar nombre del separador y crear array de recibos
                    if ($primero) {
                        array_push($nombres, substr($actual->nombrecta, 0, 6));
                        $separador->id = $actual->id;
                        $separador->nombre = $actual->nombrecta;
                        $separador->codigo = $actual->codigo;
                        $separador->facturas = array();
                        $primero = false;
                    }

                    array_push($suma_montos, $actual->total);
                    array_push($separador->facturas, $actual);
                    $totales->total = round(array_sum($suma_montos), 2);
                    array_push($suma_compras, $totales->total);
                    $separador->total = round(array_sum($suma_montos), 2);
                    // $separador->totales = $totales;
                    array_push($separador_mes->compras, $separador);

                    // limpiar 
                    $suma_montos = array();
                    $separador = new StdClass;
                    $totales = new StdClass;
                }
            }
        }

        $separador_mes->total_compras = round(array_sum($suma_compras), 2);        
        $separador_mes->total_ventas = round(array_sum($suma_ventas), 2);
        $separador_mes->diferencia = round($separador_mes->total_ventas - $separador_mes->total_compras);

        usort($separador_mes->ventas, "compararPorTotal");
        usort($separador_mes->compras, "compararPorTotal");

        array_push($mes, $separador_mes);
    }

    print json_encode($mes);
});

$app->run();