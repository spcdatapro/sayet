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
    $suma_montos = array();
    $primero = true;
    $cuerpo = array();
    $suma_ventas = array();
    $suma_compras = array();

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

    $letra->del = $meses[$mesdel-1].$aniodel;

    // validar si solo estan obteniendo un mes
    if ($mesal != $mesdel) {
        $letra->al = 'a '.$meses[$mesal-1].$anioal;
    } else {
        $letra->al = $anioal;
    }

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $letra->empresa = $db->getOneField("SELECT nomempresa FROM empresa WHERE id = 4");
    $letra->proyecto = $db->getOneField("SELECT nomproyecto FROM proyecto WHERE id = 3");

    $query = "SELECT 
                b.idtiposervicio,
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
                a.idempresa = 4
                    AND (a.idproyecto = 3 OR e.idproyecto = 3)
                    AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND YEAR(a.fecha) = $d->anio
                    AND b.idtiposervicio != 1
            ORDER BY 2, 4";
    $data = $db->getQuery($query);

    $ventas = array();

    $cntsVentas = count($data);

    for ($i = 1; $i < $cntsVentas; $i++) {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

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
        if ($actual->idtiposervicio != $anterior->idtiposervicio) {
            // generar variable de totales
            $totales->total = round(array_sum($suma_montos), 2);
            $separador->totales = $totales;

            // total general
            array_push($suma_ventas, $totales->total);

            // empujar a array global de recibo los recibos separados
            array_push($ventas, $separador);
            // limpiar variables 
            $totales = new StdClass;
            $suma_montos = array();
            $separador = new StdClass;
            $separador->id = $actual->idtiposervicio;
            $separador->nombre = $actual->cuenta;
            $separador->codigo = $actual->codigo;
            $separador->facturas = array();
        }

        // para empujar el ultimo dato
        if ($i+1 == $cntsVentas) {
            array_push($suma_montos, $actual->total);
            array_push($separador->facturas, $actual);
            $totales->total = round(array_sum($suma_montos), 2);
            array_push($suma_ventas, $totales->total);
            $separador->totales = $totales;
            array_push($ventas, $separador);

            // limpiar 
            $suma_montos = array();
            $separador = new StdClass;
            $totales = new StdClass;
            $primero = true;
        }
    }

    $query = "SELECT 
                c.id,
                c.codigo,
                UPPER(c.nombrecta) AS nombrecta,
                DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fechatran,
                CONCAT(e.tipotrans, ' ', e.numero) AS cheque,
                SUBSTRING(e.beneficiario, 1, 30) AS beneficiario,
                IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                        '') AS orden,
                SUBSTRING(LOWER(b.conceptomayor), 1, 50) AS concepto,
                DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                CONCAT(g.siglas, '(', b.documento, ')') AS documento,
                ROUND(IF(b.idtipofactura = 10, b.subtotal * -1, b.subtotal), 2) AS total
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
                b.idempresa = 4 AND b.idproyecto = 3
                    AND MONTH(b.fechaingreso) >= $d->mesdel
                    AND MONTH(b.fechaingreso) <= $d->mesal
                    AND YEAR(b.fechaingreso) = $d->anio
                    AND c.id 
            UNION ALL SELECT 
                c.id,
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
                SUBSTRING(LOWER(b.conceptomayor), 1, 50) AS concepto,
                DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                CONCAT(h.siglas, '(', b.documento, ')') AS documento,
                ROUND(IF(b.idtipofactura = 10, b.subtotal * -1, b.subtotal), 2) AS total
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
                b.idempresa = 4 AND b.idproyecto = 3
                AND MONTH(b.fechaingreso) >= $d->mesdel
                    AND MONTH(b.fechaingreso) <= $d->mesal
                    AND YEAR(b.fechaingreso) = $d->anio
                    AND b.idreembolso > 0
                    AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%'
                    OR TRIM(c.codigo) = '1120299')
                    AND c.id 
            UNION ALL SELECT 
                9999 AS id,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                NULL AS cheque,
                SUBSTRING(CONCAT(b.nombre, ' ', IFNULL(b.apellidos, '')),
                    1,
                    30) AS beneficiario,
                a.idplnempleado AS orden,
                'Devengado y cuota patronal' AS concepto,
                NULL AS fechafact,
                IFNULL(c.nombre, '') AS documento,
                ROUND(SUM(a.descanticipo) + SUM(a.liquido) + SUM(a.descprestamo)
                 + (SUM(a.sueldoordinario) + SUM(a.sueldoextra)) * 0.1267, 2) AS total
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    LEFT JOIN
                unidad c ON b.idunidad = c.id
            WHERE
                a.idempresa = 4 AND b.idproyecto = 3
                    AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) >= 16
                    AND YEAR(a.fecha) = $d->anio
            GROUP BY a.idplnempleado
            -- UNION ALL SELECT 
            --     9999 AS id,
            --     5120101 AS codigo,
            --     'SALARIOS' AS nombrecta,
            --     NULL AS fechatran,
            --     NULL AS cheque,
            --     NULL AS beneficiario,
            --     a.idplnempleado AS orden,
            --     'Cuota patronal' AS conceptomayor,
            --     NULL AS fechafact,
            --     NULL AS documento,
            --     ROUND((SUM(a.sueldoordinario) + SUM(a.sueldoextra)) * 0.1267,
            --             2) AS total
            -- FROM
            --     plnnomina a
            --         INNER JOIN
            --     plnempleado b ON a.idplnempleado = b.id
            --         LEFT JOIN
            --     unidad c ON b.idunidad = c.id
            -- WHERE
            --     a.idempresa = 4 AND b.idproyecto = 3
            --         AND MONTH(a.fecha) >= $d->mesdel
            --         AND MONTH(a.fecha) <= $d->mesal
            --         AND DAY(a.fecha) >= 16
            --         AND YEAR(a.fecha) = $d->anio
            -- GROUP BY a.idplnempleado
            ORDER BY 3 DESC, 11 ASC, 7 DESC, 4 DESC";
    $data = $db->getQuery($query);

    foreach($data AS $comp) {
        if($comp->id == 9999) {
            $comp->orden = null;
        }
    }

    $compras = array();

    $cntsCompras = count($data);

    for ($i = 1; $i < $cntsCompras; $i++) {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

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
        if ($actual->id != $anterior->id) {
            // generar variable de totales
            $totales->total = round(array_sum($suma_montos), 2);
            $separador->totales = $totales;

            // para graficas
            array_push($montos, $totales->total);
            array_push($nombres, substr($actual->nombrecta, 0, 6));
            array_push($suma_compras, $totales->total);

            // empujar a array global de recibo los recibos separados
            array_push($compras, $separador);
            // limpiar variables 
            $totales = new StdClass;
            $suma_montos = array();
            $separador = new StdClass;
            $separador->id = $actual->id;
            $separador->nombre = $actual->nombrecta;
            $separador->codigo = $actual->codigo;
            $separador->facturas = array();
        }

        // para empujar el ultimo dato
        if ($i+1 == $cntsCompras) {
            array_push($suma_montos, $actual->total);
            array_push($separador->facturas, $actual);
            $totales->total = round(array_sum($suma_montos), 2);
            array_push($suma_compras, $totales->total);
            $separador->totales = $totales;
            array_push($compras, $separador);

            // para graficas
            array_push($montos, $totales->total);

            // limpiar 
            $suma_montos = array();
            $separador = new StdClass;
            $totales = new StdClass;
        }
    }

    // nombres y montos de todas las cuentas
    $grafica->nombres = $nombres;
    $grafica->montos = $montos;
    $cntNombres = count($nombres);

    $grafica->colores = gradient_colors($cntNombres);

    $tot_ventas = array_sum($suma_ventas);
    $tot_compras = array_sum($suma_compras);
    $diferencia = $tot_ventas - $tot_compras;

    $letra->total_ventas = $tot_ventas;
    $letra->total_compras = $tot_compras;
    $letra->diferencia = $diferencia;

    print json_encode([ 'encabezado' => $letra, 'ventas' => $ventas, 'compras' => $compras, 'grafica' => $grafica ]);
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

$app->run();