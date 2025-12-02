<?php
require 'vendor/autoload.php';
require_once 'db.php';
require 'Reportes.php';
require_once 'NumberToLetterConverter.class.php';

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

    // para provisiones
    $anio_ant = $d->anio - 1;
    $tipo_provision = $d->mesdel === 7 ? 'BONIF' : ($d->mesdel === 12 ? 'AGUI' : 'N/A');

    $cntMeses = contarMeses($mesdel, $mesal);

    $query = "SELECT 
                b.idtiposervicio,
                MONTH(a.fecha) AS mes,
                UPPER(c.desctiposervventa) AS cuenta,
                c.cuentac AS codigo,
                IFNULL(d.nombrecorto, SUBSTR(a.nombre, 1, 15)) AS cliente,
                IFNULL(IF(b.idtiposervicio = 4, h.nombre, f.nombre), 'N/A') AS unidad,
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
                    LEFT JOIN
                lectUraservicio g ON g.idfactura = a.id
                    LEFT JOIN
                unidad h ON g.idunidad = h.id
            WHERE
                a.idempresa = $d->idempresa
                    AND (a.idproyecto = $d->idproyecto OR e.idproyecto = $d->idproyecto) ";
    $query.= isset($d->idunidad) ? "AND e.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND YEAR(a.fecha) = $d->anio
                    AND a.anulada = 0
                    AND b.idtiposervicio NOT IN (1 ";
    $query.= $d->idproyecto == 16 ? ")" : ", 16)"; 
    $query.="   ORDER BY 2 ASC, 1, 6";
    $data_v = $db->getQuery($query);

        $query = "SELECT 
                c.id,
                MONTH(b.fechaingreso) AS mes,
                c.codigo,
                UPPER(c.nombrecta) AS nombrecta,
                d.fecha AS fechatran,
                d.cheque AS cheque,
                SUBSTRING(d.beneficiario, 1, 30) AS beneficiario,
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
                (SELECT a.idcompra, GROUP_CONCAT(b.tipotrans, '-', b.numero) AS cheque, DATE_FORMAT(b.fecha, '%d/%m/%Y') AS fecha, b.beneficiario 
                FROM detpagocompra a INNER JOIN tranban b ON a.idtranban = b.id GROUP BY a.idcompra) d ON d.idcompra = b.id  
                -- detpagocompra d ON d.idcompra = b.id
                --     LEFT JOIN
                -- tranban e ON d.idtranban = e.id
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
                ROUND(IF(a.debe = 0, a.haber * -1, IF(b.idtipofactura = 10, a.debe * -1, a.debe)), 2) AS total,
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
                    INNER JOIN 
                reembolso i ON b.idreembolso = i.id
                    LEFT JOIN 
                subtipogasto j ON i.idsubtipogasto = j.id
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
                    AND (j.idtipogasto != 1 OR j.idtipogasto IS NULL)
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
                NULL AS orden,
                'Devengado' AS concepto,
                NULL AS fechafact,
                IFNULL(c.nombre, '') AS documento,
                ROUND(a.devengado, 2) AS total,
                a.idplnempleado AS ord
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    INNER JOIN 
                plnlaboral e ON b.idlaboral = e.id
                    LEFT JOIN
                unidad c ON e.idunidad = c.id
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
                a.idempresa = $d->idempresa AND e.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND e.idunidad = $d->idunidad " : "";
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
                NULL AS orden,
                'Cuota patronal' AS concepto,
                NULL AS fechafact,
                NULL AS documento,
                ROUND((a.sueldoordinario + a.sueldoextra) * d.patronaligss,
                        2) AS total,
                a.idplnempleado AS ord
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    INNER JOIN
                plnlaboral e ON b.idlaboral = e.id
                    LEFT JOIN
                unidad c ON e.idunidad = c.id
                    INNER JOIN
                plnempresa d ON e.idempresaactual = d.id
            WHERE
                a.idempresa = $d->idempresa AND e.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND e.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) >= 16
                    AND YEAR(a.fecha) = $d->anio
                    AND a.descigss > 0
            UNION ALL SELECT 
                9999 AS id,
                MONTH(a.fecha) AS mes,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                NULL AS fechatran,
                NULL AS cheque,
                NULL AS beneficiario,
                NULL AS orden,
                'Bono 14' AS concepto,
                NULL AS fechafact,
                NULL AS documento,
                ROUND(a.bonocatorce, 2) AS total,
                a.idplnempleado AS ord
            FROM
                plnnomina a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    INNER JOIN 
                plnlaboral d ON b.idlaboral = d.id
                    LEFT JOIN
                unidad c ON b.idunidad = c.id
            WHERE
                a.idempresa = $d->idempresa AND d.idproyecto = $d->idproyecto ";
    $query.= isset($d->idunidad) ? "AND d.idunidad = $d->idunidad " : "";
    $query.="       AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND DAY(a.fecha) = 15
                    AND YEAR(a.fecha) = $d->anio
                    AND a.esbonocatorce = 1
            UNION ALL SELECT 
                9999 AS id,
                MONTH(a.fecha) AS mes,
                5120101 AS codigo,
                'SALARIOS' AS nombrecta,
                NULL AS fechatran,
                NULL AS cheque,
                NULL AS beneficiario,
                NULL AS orden,
                'Aguinaldo' AS concepto,
                NULL AS fechafact,
                NULL AS documento,
                ROUND(a.aguinaldo, 2) AS total,
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
                    AND DAY(a.fecha) = 15
                    AND YEAR(a.fecha) = $d->anio
                    AND a.aguinaldo > 0
            UNION ALL SELECT 
                    c.id AS id,
                    MONTH(a.fecha) AS mes,
                    c.codigo, 
                    c.nombrecta AS nombrecta,
                    DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                    NULL AS cheque,
                    SUBSTRING(a.concepto, 1, 30) AS beneficiario,
                    NULL AS orden,
                    b.conceptomayor AS concepto,
                    NULL AS fechafact,
                    a.id AS documento,
                    ROUND(b.debe, 2) AS total,
                    a.fecha AS ord
                FROM
                    directa a
                        INNER JOIN
                    detallecontable b ON a.id = b.idorigen
                        INNER JOIN
                    cuentac c ON c.id = b.idcuenta
                WHERE
                    a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto 
                    AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND YEAR(a.fecha) = $d->anio
                    AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%')
                    AND b.debe > 0
            UNION ALL SELECT
                    c.id AS id,
                    MONTH(a.fecha) AS mes,
                    c.codigo, 
                    c.nombrecta AS nombrecta,
                    DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                    CONCAT(a.tipotrans, '-', a.numero) AS cheque,
                    SUBSTRING(a.beneficiario, 1, 30) AS beneficiario,
                    NULL AS orden,
                    a.concepto AS concepto,
                    NULL AS fechafact,
                    a.id AS documento,
                    ROUND(b.debe, 2) AS total,
                    a.fecha AS ord
                FROM
                    tranban a
                        INNER JOIN
                    detallecontable b ON a.id = b.idorigen AND origen = 1
                        INNER JOIN
                    cuentac c ON c.id = b.idcuenta
						INNER JOIN
					banco d ON a.idbanco = d.id
                WHERE
                    d.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                    AND MONTH(a.fecha) >= $d->mesdel
                    AND MONTH(a.fecha) <= $d->mesal
                    AND YEAR(a.fecha) = $d->anio
                    AND b.debe > 0
            -- PARA PROVISIONES UN ANIO ANTES
            UNION ALL SELECT 
                    c.id AS id,
                    7 AS mes,
                    c.codigo, 
                    c.nombrecta AS nombrecta,
                    DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                    NULL AS cheque,
                    SUBSTRING(a.concepto, 1, 30) AS beneficiario,
                    NULL AS orden,
                    b.conceptomayor AS concepto,
                    NULL AS fechafact,
                    a.id AS documento,
                    ROUND(b.debe *-1, 2) AS total,
                    a.fecha AS ord
                FROM
                    directa a
                        INNER JOIN
                    detallecontable b ON a.id = b.idorigen
                        INNER JOIN
                    cuentac c ON c.id = b.idcuenta
                WHERE
                    a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto 
                    AND YEAR(a.fecha) = $anio_ant
                    AND MONTH(a.fecha) >= 12
                    AND MONTH(a.fecha) <= 12
                    AND c.nombrecta LIKE '%$tipo_provision%'
                    AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%')
                    AND b.debe > 0
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
        $separador_mes->diferencia = round($separador_mes->total_ventas - $separador_mes->total_compras, 2);

        usort($separador_mes->ventas, "compararPorTotal");
        usort($separador_mes->compras, "compararPorTotal");

        array_push($mes, $separador_mes);
    }

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

$app->post('/control_ingresos', function () {
    date_default_timezone_set("America/Guatemala");

    $db = new dbcpm;
    $d = json_decode(file_get_contents('php://input'));
    $n2l = new NumberToLetterConverter();

    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
    $totales = ['ingreso', 'deposito', 'isr', 'iva', 'diferencia'];
    $ingreso = [];
    $deposito = [];
    $isr = [];
    $iva = [];
    $diferencia = [];

    // estampa
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    // fecha
    $fecha = new DateTime($d->fechastr);
    $letra->fecha = '';
    $letra->fecha = 'Guatemala ' . $fecha->format('d') . ' de ' . $meses[$fecha->format('n') - 1] . ' ' . $fecha->format('Y');

    // usuario
    $letra->usuario = $d->usuario;

    // tc
    $letra->tc = $db->getOneField("SELECT ROUND(tipocambio, 5) FROM tipocambio WHERE fecha = '$d->fechastr' LIMIT 1");
    $letra->tc = $letra->tc > 0 ? $letra->tc : $db->getOneField("SELECT ROUND(tipocambio, 5) FROM tipocambio ORDER BY fecha DESC LIMIT 1"); 

    // No. de caja, son es el dia que se esta obteniendo menos los fines de semana
    $letra->caja = restarDiasHabiles($fecha);
    $letra->caja = str_pad($letra->caja, 2, '0', STR_PAD_LEFT);

    // tipo 
    $letra->tipo = $d->tipo == 1 ? 'por Empresa' : 'Personal';
    $d->tipo = $d->tipo == 1 ? '1 , 4' : '2, 3';

    $query = "SELECT 
                c.idrecibocli,
                a.id,
                b.idmoneda AS idempresa,
                CONCAT(e.nommoneda, ' ', e.simbolo) AS empresa,
                NULL AS numero,
                e.simbolo AS abreviatura,
                b.idempresa AS idproyecto,
                f.abreviatura AS proyecto,
                b.siglas,
                IF(a.numban = 0 OR a.numban IS NULL,
                    a.numero,
                    a.numban) AS tranban,
                IFNULL(d.factura, 'SC') AS factura,
                ROUND(IFNULL(SUM(IF(b.idmoneda = 2, d.ingresodlr, d.ingreso)), 0), 2) AS ingreso,
                a.monto AS deposito,
                ROUND(IFNULL(SUM(IF(b.idmoneda = 2, d.isrdlr, d.isr)), 0), 2) AS isr,
                ROUND(IFNULL(SUM(IF(b.idmoneda = 2, d.ivadlr, d.iva)), 0), 2) AS iva,
                (ROUND(IFNULL(SUM(IF(b.idmoneda = 2, d.ingresodlr, d.ingreso)), 0), 2) - a.monto - IFNULL(d.cobrado_prev, 0.00) - ROUND(IFNULL(SUM(IF(b.idmoneda = 2, d.ivadlr, d.iva)), 0), 2) - ROUND(IFNULL(SUM(IF(b.idmoneda = 2, d.isrdlr, d.isr)), 0), 2)) * -1 AS diferencia,
                e.simbolo AS moneda
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    LEFT JOIN
                reclitran c ON c.idtranban = a.id
                    LEFT JOIN
                (SELECT 
                    a.idrecibocli,
                        IF(COUNT(b.id) > 3, CAST(CONCAT(COUNT(b.id), '-FC') AS CHAR), GROUP_CONCAT(b.numeroadmin)) AS factura,
                        SUM(IF(b.pagada = 1, IF(b.idmonedafact = 2, b.subtotalcnv, (a.monto + b.retisr + b.retiva) / b.tipocambio), IF(b.idmonedafact = 2, b.subtotalcnv, b.subtotal / b.tipocambio))) AS ingresodlr,
                        SUM(IF(b.idmonedafact = 2, b.retisrcnv, b.retisr / b.tipocambio)) AS isrdlr,
                        SUM(IF(b.idmonedafact = 2, b.retivacnv, b.retiva / b.tipocambio)) AS ivadlr,
                        SUM(IF(b.pagada = 1, IF(a.monto + b.retisr + b.retiva > b.subtotal, b.subtotal, (a.monto + b.retisr + b.retiva)), IF(b.idmonedafact = 2, b.subtotalcnv, b.subtotal))) AS ingreso,
                        SUM(IF(b.idmonedafact = 2, b.retisrcnv, b.retisr)) AS isr,
                        SUM(IF(b.idmonedafact = 2, b.retivacnv, b.retiva)) AS iva,
                        IF(b.idmonedafact = 2, 1, b.tipocambio) AS tc_fact,
                        -- IFNULL((SELECT SUM(dc.monto) FROM detcobroventa dc WHERE dc.idfactura = b.id AND dc.idrecibocli != a.idrecibocli),0) AS cobrado_prev
                        0 AS cobrado_prev
                FROM
                    detcobroventa a
                INNER JOIN factura b ON a.idfactura = b.id
                GROUP BY a.idrecibocli
                ORDER BY b.fecha) d ON d.idrecibocli = c.idrecibocli
                    INNER JOIN
                moneda e ON b.idmoneda = e.id
                    INNER JOIN
                empresa f ON b.idempresa = f.id
                    INNER JOIN
                tipomovtranban g ON a.tipotrans = g.abreviatura
            WHERE
                a.fecha = '$d->fechastr'
                    AND a.tipotrans IN ('R' , 'D')
                    AND b.gruposumario IN ($d->tipo)
            GROUP BY a.id
            ORDER BY b.idmoneda , b.ordensumario , g.ordenalt , a.numero";
    $data = $db->getQuery($query);

    if (count($data) > 0) {

        for ($i = 0; $i < count($data); $i++) {
            $actual = $data[$i];
            $proximo = $i+1 == count($data) ? null : $data[$i+1];

            if (isset($proximo)) {
                if ($proximo->idrecibocli == $actual->idrecibocli && $proximo->idrecibocli > 0) {
                    // $proximo->diferencia = ($actual->ingreso - ($actual->deposito + $actual->isr + $actual->iva + $proximo->deposito)) * -1;
                    $proximo->diferencia = $actual->deposito * -1;
                    $actual->iva = 0;
                    $actual->isr = 0;
                    $actual->ingreso = 0;
                    $actual->diferencia = $actual->deposito;
                }
            }
        }
        
        // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
        $reporte = new GeneradorReportes($data, 'transacciones', $totales, true);
        $transacciones = $reporte->getReporte();
        $montos_generales = $reporte->getTotalesGenerales();
        $success = true;
    } else {
        $transacciones = 'No se recibieron datos';
        $success = false;
    }

    if ($success) {
        foreach($transacciones as $t) {
            if ($t->abreviatura === 'Q') {
                array_push($ingreso, $t->ingreso);
                array_push($deposito, $t->deposito);
                array_push($isr, $t->isr);
                array_push($iva, $t->iva);
                array_push($diferencia, $t->diferencia);
            } else {
                $ingreso_q = round($t->ingreso * $letra->tc, 2);
                array_push($ingreso, $ingreso_q);
                $deposito_q = round($t->deposito * $letra->tc, 2);
                array_push($deposito, $deposito_q);
                $isr_q = round($t->isr * $letra->tc, 2);
                array_push($isr, $isr_q);
                $iva_q = round($t->iva * $letra->tc, 2);
                array_push($iva, $iva_q);
                $diferencia_q = round($t->diferencia * $letra->tc, 2);
                array_push($diferencia, $diferencia_q);
            }
        }
    
        $letra->ingreso = array_sum($ingreso);
        $letra->deposito = array_sum($deposito);
        $letra->isr = array_sum($isr);
        $letra->iva = array_sum($iva);
        $letra->diferencia = array_sum($diferencia);
    }

    return print json_encode([ 'encabezado' => $letra, 'trans' => $transacciones, 'succes' => $success ]);
});

$app->post('/control_egresos', function () {
    date_default_timezone_set("America/Guatemala");

    $db = new dbcpm;
    $d = json_decode(file_get_contents('php://input'));
    $n2l = new NumberToLetterConverter();

    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
    $totales = ['ingreso', 'deposito', 'isr', 'iva', 'diferencia'];
    $ingreso = [];
    $deposito = [];
    $isr = [];
    $iva = [];
    $diferencia = [];
    $t_proveedor = [];

    // estampa
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    // fecha
    $fecha = new DateTime($d->fechastr);
    $letra->fecha = '';
    $letra->fecha = 'Guatemala ' . $fecha->format('d') . ' de ' . $meses[$fecha->format('n') - 1] . ' ' . $fecha->format('Y');

    // usuario
    $letra->usuario = $d->usuario;

    // tc
    $letra->tc = $db->getOneField("SELECT ROUND(tipocambio, 5) FROM tipocambio WHERE fecha = '$d->fechastr' LIMIT 1");
    $letra->tc = $letra->tc > 0 ? $letra->tc : $db->getOneField("SELECT ROUND(tipocambio, 5) FROM tipocambio ORDER BY fecha DESC LIMIT 1"); 

    // No. de caja, son es el dia que se esta obteniendo me los fines de semana
    $letra->caja = restarDiasHabiles($fecha);
    $letra->caja = str_pad($letra->caja, 2, '0', STR_PAD_LEFT);

    // tipo 
    $letra->tipo = $d->tipo == 1 ? 'por Empresa' : 'Personal';
    $d->tipo = $d->tipo == 1 ? '1 , 4' : '2, 3'; 

    $query = "SELECT 
                a.id,
                c.id AS idempresa,
                CONCAT(c.nommoneda, ' ', c.simbolo) AS empresa,
                c.simbolo AS abreviatura,
                d.id AS idproyecto,
                d.abreviatura AS proyecto,
                CONCAT(IF(a.numban = 0 OR a.numban IS NULL,
                            a.numero,
                            a.numban)) AS tranban,
                b.siglas,
                b.ordensumario,
                a.numero,
                SUBSTRING(a.beneficiario, 1, 35) AS factura,
                NULL AS ingreso,
                a.monto AS deposito,
                null AS isr, 
                NULL AS iva, 
                NULL AS diferencia,
                c.simbolo AS moneda,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                IF(2 = 1, TRUE, FALSE) AS numero
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    INNER JOIN
                moneda c ON b.idmoneda = c.id
                    INNER JOIN
                empresa d ON b.idempresa = d.id
            WHERE
                a.fecha = '$d->fechastr'
                    AND b.gruposumario IN($d->tipo)
                    AND a.beneficiario != 'anulado'  
                    AND a.tipotrans = 'B'                   
            GROUP BY a.id ORDER BY 2 , 9 , 10";
    $data = $db->getQuery($query);

    if (count($data) > 0) {
        for ($i = 0; $i < count($data); $i++) {
            // traer valor actual y anterior
            $actual = $data[$i];
            $proximo = count($data) === $i+1 ? $data[0] : $data[$i+1];
            array_push($t_proveedor, $actual->deposito);
            // si no tienen el mismo proveedor
            if ($actual->factura !== $proximo->factura || $actual->idproyecto !== $proximo->idproyecto) {
                $actual->ingreso = array_sum($t_proveedor);
                // generar variable de totales
                if (count($t_proveedor) > 1) {
                    $actual->varios = true;
                }
                $t_proveedor = [];
            }
        }

        // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
        $reporte = new GeneradorReportes($data, 'transacciones', $totales, true);
        $transacciones = $reporte->getReporte();
        $montos_generales = $reporte->getTotalesGenerales();
        $success = true;
    } else {
        $transacciones = 'No se recibieron datos';
        $success = false;
    }

    if ($success) {
        foreach($transacciones as $t) {
            if ($t->abreviatura === 'Q') {
                array_push($ingreso, $t->ingreso);
                array_push($deposito, $t->deposito);
                array_push($isr, $t->isr);
                array_push($iva, $t->iva);
                array_push($diferencia, $t->diferencia);
            } else {
                $ingreso_q = round($t->ingreso * $letra->tc, 2);
                array_push($ingreso, $ingreso_q);
                $deposito_q = round($t->deposito * $letra->tc, 2);
                array_push($deposito, $deposito_q);
                $isr_q = round($t->isr * $letra->tc, 2);
                array_push($isr, $isr_q);
                $iva_q = round($t->iva * $letra->tc, 2);
                array_push($iva, $iva_q);
                $diferencia_q = round($t->diferencia * $letra->tc, 2);
                array_push($diferencia, $diferencia_q);
            }
        }
    
        $letra->ingreso = array_sum($ingreso);
        $letra->deposito = array_sum($deposito);
        $letra->isr = array_sum($isr);
        $letra->iva = array_sum($iva);
        $letra->diferencia = array_sum($diferencia);
    }

    return print json_encode([ 'encabezado' => $letra, 'trans' => $transacciones, 'succes' => $success ]);
});

$app->post('/ocupacion', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    date_default_timezone_set("America/Guatemala");

    $d->mes_del = (int)$d->mes_del + 1;
    $d->mes_al = (int)$d->mes_al + 1;

    // array de nombre de meses
    $meses_nombre = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    // Traer datos para todo el rango de años y meses solicitado
    $query = "SELECT 
                a.nomproyecto AS proyecto,
                a.metros_rentable AS mdisponibles,
                b.id AS idunidad,
                b.nombre AS unidad,
                e.descripcion AS tipo,
                ROUND(b.mcuad, 2) AS medida,
                ROUND(b.mcuad * 100 / a.metros_rentable, 2) AS porcentaje,
                IF(d.id > 0, 1, 0) AS ocupado,
                IFNULL(MONTH(d.fecha), 0) AS mes,
                IFNULL(YEAR(d.fecha), 0) AS anio,
                ROUND(SUM(DISTINCT IF(d.idmonedafact = 1,
                            d.total,
                            d.total * d.tipocambio)),
                        2) AS total,
                GROUP_CONCAT(DISTINCT f.nombrecorto) AS cliente,
                GROUP_CONCAT(h.desctiposervventa) AS tipo_servicio
            FROM
                proyecto a
                    INNER JOIN
                unidad b ON a.id = b.idproyecto
                    LEFT JOIN
                contrato c ON b.id = c.idunidad AND c.inactivo = 0
                    LEFT JOIN
                factura d ON c.id = d.idcontrato
                    AND MONTH(d.fecha) >= $d->mes_del
                    AND MONTH(d.fecha) <= $d->mes_al
                    AND YEAR(d.fecha) BETWEEN $d->anio_del AND $d->anio_al
                    INNER JOIN 
                tipolocal e ON b.idtipolocal = e.id 
                    LEFT JOIN 
                cliente f ON d.idcliente = f.id
                    LEFT JOIN 
                detfact g ON g.idfactura = d.id 
                    LEFT JOIN 
                tiposervicioventa h ON g.idtiposervicio = h.id
            WHERE
                a.id = $d->idproyecto AND b.idtipolocal NOT IN (9, 17)
            GROUP BY b.id, YEAR(d.fecha), MONTH(d.fecha)
            ORDER BY YEAR(d.fecha), MONTH(d.fecha), b.id";
    $data = $db->getQuery($query);

    // Guardar copia para encabezado (si hay datos)
    $orig = $data;

    $result = [];

    // número de meses en el rango (para promedios por año)
    $num_months_range = ($d->mes_al - $d->mes_del) + 1;
    $num_months_range = $num_months_range > 0 ? $num_months_range : 1;

    // Agrupar por año y por mes dentro de cada año
    for ($y = $d->anio_del; $y <= $d->anio_al; $y++) {
        $yearObj = new stdClass();
        $yearObj->anio = $y;
        $yearObj->meses = [];

        // acumuladores por año para calcular promedios
        $sum_porcentaje_anual = 0;
        $sum_total_anual = 0;
        $sum_metros_anual = 0;

        for ($m = $d->mes_del; $m <= $d->mes_al; $m++) {
            $data_mes = new StdClass();
            $data_mes->mes = $meses_nombre[$m - 1];
            $procentaje = [];
            $metros = [];
            $data_mes->total = 0;
            $data_mes->detalles = [];

            // recorrer datos y extraer los que correspondan a este mes y año
            for ($j = 0; $j < count($data); $j++) {
                $unidad = $data[$j];
                if ((int)$unidad->mes == $m && (int)$unidad->anio == $y) {
                    array_push($procentaje, (float)$unidad->porcentaje);
                    array_push($metros, (float)$unidad->medida);
                    $data_mes->total += (float)$unidad->total;
                
                    // Guardar detalle de la unidad
                    $detalle = new StdClass();
                    $detalle->unidad = $unidad->unidad;
                    $detalle->tipo = $unidad->tipo;     
                    $detalle->medida = (float)$unidad->medida;
                    $detalle->porcentaje = (float)$unidad->porcentaje;
                    $detalle->cliente = $unidad->cliente;
                    $detalle->servicio = $unidad->tipo_servicio;
                    $detalle->total = (float)$unidad->total;
                    array_push($data_mes->detalles, $detalle);
                
                    // eliminar la entrada ya procesada
                    array_splice($data, $j, 1);
                    $j--;
                }
            }

            $data_mes->porcentaje_ocupado = round(array_sum($procentaje), 2);
            $data_mes->metros_ocupados = round(array_sum($metros), 2);
            $data_mes->porcentaje_vacante = round(100 - $data_mes->porcentaje_ocupado, 2);
            $data_mes->total = round($data_mes->total, 2);
            $data_mes->unidades_ocupadas = count($procentaje);

            // acumular para promedios anuales
            $sum_porcentaje_anual += $data_mes->porcentaje_ocupado;
            $sum_total_anual += $data_mes->total;
            $sum_metros_anual += $data_mes->metros_ocupados;

            array_push($yearObj->meses, $data_mes);
        }

        // calcular promedios por año (promedio sobre todos los meses del rango)
        $yearObj->promedio_porcentaje_ocupado = round($sum_porcentaje_anual / $num_months_range, 2);
        $yearObj->promedio_total = round($sum_total_anual / $num_months_range, 2);
        $yearObj->promedio_metros_ocupados = round($sum_metros_anual / $num_months_range, 2);
        $yearObj->columnas = contarMeses($d->mes_del, $d->mes_al) + 2;
        $yearObj->columna_tres = floor((contarMeses($d->mes_del, $d->mes_al) + 1) / 4);

        array_push($result, $yearObj);
    }

    // Rellenar encabezado con información del proyecto si hay datos originales
    if (count($orig) > 0) {
        $letra->proyecto = $orig[0]->proyecto;
        $letra->metros = round($orig[0]->mdisponibles, 2);
    } else {
        $letra->proyecto = $db->getOneField("SELECT nomproyecto FROM proyecto WHERE id = $d->idproyecto");
        $letra->metros = 0;
    }

    $letra->columnas = contarMeses($d->mes_del, $d->mes_al) + 1;

    print json_encode([ 'encabezado' => $letra, 'anios' => $result ]);
});

$app->post('/resumen_prov', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    date_default_timezone_set("America/Guatemala");

    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
            a.id,
            c.nomempresa AS empresa,
            d.nomproyecto AS proyecto,
            a.nombre,
            COUNT(b.id) AS cuantos
        FROM
            proveedor a
                INNER JOIN
            compra b ON b.idproveedor = a.id
                INNER JOIN
            empresa c ON b.idempresa = c.id
                INNER JOIN
            proyecto d ON b.idproyecto = d.id
        WHERE
            a.hoja_control = 1
                AND (b.idreembolso = 0
                OR b.idreembolso IS NULL)
                AND (b.ordentrabajo IS NULL
                OR b.ordentrabajo = 0)
                AND YEAR(b.fechafactura) = $d->anio
        GROUP BY b.idproyecto, b.idproveedor
        ORDER BY a.nombre";
    $data = $db->getQuery($query);

    // agregar correlativo por cada id unico
    $correlativos = [];
    $nextCorrelativo = 1;

    foreach ($data as $c) {
        // asignar correlativo único por proveedor (mismo correlativo si el id ya apareció)
        if (!isset($correlativos[$c->id])) {
            $correlativos[$c->id] = $nextCorrelativo++;
        }
        $c->correlativo = $correlativos[$c->id];

        $months = max(1, (int)$d->mes); // evitar división por cero
        $cnt = (int)$c->cuantos;

        if ($cnt === 0) {
            $c->recurrencia = 'Sin movimientos';
            continue;
        }

        // frecuencia media: cuantos meses pasan entre movimientos
        $freq = $months / $cnt;

        if ($freq <= 1.25) {
            // ~1 mes o más de 1 movimiento por mes
            $c->recurrencia = 'Mensual';
        } elseif ($freq <= 2.25) {
            // ~2 meses
            $c->recurrencia = 'Bimensual';
        } elseif ($freq <= 3.25) {
            // ~3 meses
            $c->recurrencia = 'Trimestral';
        } elseif ($freq <= 6.5) {
            // ~6 meses
            $c->recurrencia = 'Semestral';
        } elseif ($cnt < $months) {
            // menos movimientos que meses del periodo y no encaja en las reglas anteriores
            $c->recurrencia = 'Ocasional';
        } else {
            // más movimientos que meses (varios por mes) o casos atípicos
            $c->recurrencia = 'Varios movimientos';
        }
    }

    print json_encode([ 'encabezado' => $letra, 'resumen' => $data ]);
});

function restarDiasHabiles($fecha) {
    $diasContados = 0;
    $dias_restantes = $fecha->format('d');

    // minetras queden dias del mes le suma uno a dias contados cuando no es fin de semana y resta uno de dias restantes
    while ($dias_restantes > 0) {
        $diaSemana = $fecha->format('N');

        // < 6 ya que 6 es sabado y 7 es domingo, agrega uno a dias
        if ($diaSemana < 6) { 
            $diasContados++;
        }

        $fecha->modify('-1 day');

        $dias_restantes--;
    }

    return $diasContados;
}

$app->run();