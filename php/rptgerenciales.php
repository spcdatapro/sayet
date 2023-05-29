<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/finanzas', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $mesdel = date("m", strtotime($d->fdel));
    $mesal = date("m", strtotime($d->fal));
    $aniodel = ' '.date("Y", strtotime($d->fdel));
    $anioal = ' '.date("Y", strtotime($d->fal));

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    $nombres = array();
    $montos = array();
    $colores = array();

    if ($aniodel == $anioal) {
        $aniodel = '';
    }

    $letra = new stdClass();

    $letra->del = $meses[$mesdel-1].$aniodel;
    $letra->al = $meses[$mesal-1].$anioal;

    // traer empresas empresas con movimiento
    $query = "SELECT id, nomempresa AS nombre FROM empresa WHERE ";
    $query.= $d->idempresa > 0 ? "id = $d->idempresa" : 
    "id IN(SELECT idempresa FROM compra WHERE fechafactura >= $d->fdel AND fechafactura <= $d->fal
    UNION SELECT idempresa FROM factura WHERE fecha >= $d->fdel AND fecha <= $d->fal)";
    // validar si es resumen por empresa o detalle de empresa
    if ($d->idempresa > 0) {
        $empresas = $db->getQuery($query)[0];
    } else {
        $empresas = $db->getQuery($query);
    }

    if ($d->idempresa > 0) {
        // traer proyectos con movimiento dentro de la empresa
        $query = "SELECT id, nomproyecto AS nombre FROM proyecto WHERE ";
        $query.= $d->idproyecto > 0 ? "id = $d->idproyecto" :
        "id IN(SELECT idproyecto FROM compra WHERE idempresa = 4 AND fechafactura >= 20230101 AND fechafactura <= 20230131 
        UNION SELECT IFNULL(a.idproyecto, b.idproyecto) FROM factura a INNER JOIN contrato b ON a.idcontrato = b.id 
        WHERE a.idempresa = 4 AND fecha >= 20230101 AND fecha <= 20230131)";
        $empresas->proyecto = $db->getQuery($query)[0];

        // traer cuentas de ingresos dentro de proyecto y empresa. No mostrar arrendamientos
        $query = "SELECT 
                    c.id,
                    c.cuentac AS codigo,
                    c.desctiposervventa AS cuenta,
                    ROUND(SUM((b.preciotot * IF(a.idtipofactura <> 9, 1, -1)) / 1.12), 2) AS total
                FROM
                    factura a
                        INNER JOIN
                    detfact b ON b.idfactura = a.id
                        INNER JOIN
                    tiposervicioventa c ON b.idtiposervicio = c.id
                        LEFT JOIN
                    contrato d ON a.idcontrato = d.id AND a.idproyecto = 0
                WHERE
                    a.idempresa = $d->idempresa
                        AND (a.idproyecto = $d->idproyecto OR d.idproyecto = $d->idproyecto)
                        AND a.fecha >= $d->fdel
                        AND a.fecha <= $d->fal
                        AND c.id != 1
                GROUP BY c.id ORDER BY c.desctiposervventa";
        $empresas->proyecto->ingresos = $db->getQuery($query); 

        $cntCuentas = count($empresas->proyecto->ingresos);

        for ($i = 0; $i < $cntCuentas; $i++) {
            $cuenta = $empresas->proyecto->ingresos[$i];

            // empujar los nombres para grafica
            array_push($nombres, substr($cuenta->cuenta, 0, 5));

            // empujar los montos para grafica
            array_push($montos, $cuenta->total);

            // si es con detalle insertar las facturas que respaldan los valores de cada ingreso
            $query = "SELECT 
                        c.cuentac AS codigo,
                        c.desctiposervventa AS cuenta,
                        IFNULL(e.nombrecorto, e.nombre) AS cliente,
                        IFNULL(SUBSTRING(UnidadesPorContrato(d.id), 1, 15), 'N/A') AS unidad,
                        CONCAT(a.serie, '-', a.numero) AS factura,
                        IFNULL(ROUND(MCuadPorContrato(d.id), 2), 0.00) AS mcuad,
                        IFNULL(ROUND(ROUND((b.preciotot * IF(a.idtipofactura <> 9, 1, -1)) / 1.12, 2) / IFNULL(ROUND(MCuadPorContrato(d.id), 2), 0.00),
                                2), 0.00) AS unitario,
                        ROUND((b.preciotot * IF(a.idtipofactura <> 9, 1, -1) / 1.12), 2) AS total
                    FROM
                        factura a
                            INNER JOIN
                        detfact b ON b.idfactura = a.id
                            INNER JOIN
                        tiposervicioventa c ON b.idtiposervicio = c.id
                            LEFT JOIN
                        contrato d ON a.idcontrato = d.id
                            INNER JOIN
                        cliente e ON a.idcliente = e.id
                            LEFT JOIN
                        unidad f ON d.idunidad = f.id
                    WHERE
                        a.idempresa = $d->idempresa
                            AND (a.idproyecto = $d->idproyecto OR d.idproyecto = $d->idproyecto)
                            AND a.fecha >= $d->fdel
                            AND a.fecha <= $d->fal
                            AND c.id = $cuenta->id";
            $cuenta->ventas = $db->getQuery($query); 
        }

        // traer cuentas con movimiento de egresos
        $query = "SELECT 
                    c.id, c.codigo, SUBSTRING(REPLACE(c.nombrecta, 'Ñ', 'N'), 1, 18) AS cuenta, ROUND(SUM(b.subtotal), 2) AS total
                FROM
                    compraproyecto a
                        INNER JOIN
                    compra b ON a.idcompra = b.id
                        INNER JOIN
                    cuentac c ON a.idcuentac = c.id
                WHERE
                    b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                        AND b.fechafactura >= $d->fdel
                        AND b.fechafactura <= $d->fal
                GROUP BY c.id 
                UNION SELECT 
                    c.id, c.codigo, SUBSTRING(REPLACE(c.nombrecta, 'Ñ', 'N'), 1, 18) AS cuenta, ROUND(SUM(b.subtotal), 2) AS total
                FROM
                    detallecontable a
                        INNER JOIN
                    compra b ON a.idorigen = b.id AND a.origen = 2
                        INNER JOIN
                    cuentac c ON a.idcuenta = c.id
                WHERE
                    b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                        AND b.fechafactura >= $d->fdel
                        AND b.fechafactura <= $d->fal
                        AND b.idreembolso > 0
                        AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%'
                        OR TRIM(c.codigo) = '1120299')
                        AND c.id != 343
                GROUP BY c.id
                UNION SELECT 
                    NULL AS id, 
                    NULL AS codigo,
                    'PLANILLA' AS cuenta,
                    ROUND(SUM(a.descanticipo + a.liquido + a.descprestamo) + SUM(a.sueldoordinario + a.sueldoextra) * 0.1267,
                            2) AS total
                FROM
                    plnnomina a
                        INNER JOIN
                    plnempleado b ON a.idplnempleado = b.id
                WHERE
                    a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                        AND a.fecha >= 20230116
                        AND a.fecha <= $d->fal
                ORDER BY 3";
        $empresas->proyecto->egresos = $db->getQuery($query);

        $cntCuentas = count($empresas->proyecto->egresos);

        for ($i = 0; $i < $cntCuentas; $i++) {
            $cuenta = $empresas->proyecto->egresos[$i];

            // empujar nombre de cuentas egresos
            array_push($nombres, substr($cuenta->cuenta, 0, 5));

            // empujar montos de egresos y multiplicar para ser negativos
            array_push($montos, $cuenta->total * -1);

            // si es con detalle insertar compras que respalden los movimientos de egresos
            if ($cuenta->id > 0) {
                $query = "SELECT 
                            c.codigo,
                            c.nombrecta,
                            DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fechatran,
                            CONCAT(e.tipotrans, ' ', e.numero) AS cheque,
                            SUBSTRING(REPLACE(e.beneficiario, 'Ñ', 'N'), 1, 30) AS beneficiario,
                            IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                                    '') AS orden,
                            SUBSTRING(b.conceptomayor, 1, 50) AS concepto,
                            DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                            b.documento,
                            ROUND(b.subtotal, 2) AS total
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
                        WHERE
                            b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                                AND b.fechafactura >= $d->fdel
                                AND b.fechafactura <= $d->fal
                                AND c.id = $cuenta->id
                        UNION SELECT 
                            c.codigo,
                            c.nombrecta,
                            DATE_FORMAT(IFNULL(e.fecha, g.fecha), '%d/%m/%Y') AS fechatran,
                            IFNULL(CONCAT(e.tipotrans, ' ', e.numero), CONCAT(g.tipotrans, ' ', g.numero)) AS cheque,
                            SUBSTRING(IFNULL(SUBSTRING(REPLACE(e.beneficiario, 'Ñ', 'N'), 1, 30), SUBSTRING(REPLACE(g.beneficiario, 'Ñ', 'N'), 1, 17)), 1, 17) AS beneficiario,
                            IFNULL(CONCAT(f.idpresupuesto, '-', f.correlativo),
                                    '') AS orden,
                            SUBSTRING(b.conceptomayor, 1, 50) AS concepto,
                            DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafact,
                            b.documento,
                            ROUND(b.subtotal, 2) AS total
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
                        WHERE
                            b.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                                AND b.fechafactura >= $d->fdel
                                AND b.fechafactura <= $d->fal
                                AND b.idreembolso > 0
                                AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%'
                                OR TRIM(c.codigo) = '1120299')
                                AND c.id = $cuenta->id";
                } else {
                    $query = "SELECT 
                                NULL AS codigo,
                                'PLANILLA' AS nombrecta,
                                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                                NULL AS cheque,
                                SUBSTRING(CONCAT(b.nombre, ' ', IFNULL(b.apellidos, '')), 1, 30) AS beneficiario,
                                NULL AS orden,
                                NULL AS concepto,
                                NULL AS fechafact,
                                ROUND(a.descanticipo + a.liquido + a.descprestamo, 2) AS total
                            FROM
                                plnnomina a
                                    INNER JOIN
                                plnempleado b ON a.idplnempleado = b.id
                            WHERE
                                a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                                    AND a.fecha >= 20230116
                                    AND a.fecha <= $d->fal
                            UNION ALL SELECT 
                                NULL AS codigo,
                                'PLANILLA' AS nombrecta,
                                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechatran,
                                NULL AS cheque,
                                'CUOTA PATRONAL' AS beneficiario,
                                NULL AS orden,
                                NULL AS conceptomayor,
                                NULL AS fechafact,
                                ROUND(SUM(a.sueldoordinario + a.sueldoextra) * 0.1267,
                                        2) AS total
                            FROM
                                plnnomina a
                                    INNER JOIN
                                plnempleado b ON a.idplnempleado = b.id
                            WHERE
                                a.idempresa = $d->idempresa AND b.idproyecto = $d->idproyecto
                                    AND a.fecha >= 20230116
                                    AND a.fecha <= $d->fal";
                } 
                $cuenta->compras = $db->getQuery($query);
        }
        $grafica = new stdClass;

        $grafica->nombres = $nombres;
        $grafica->montos = $montos;

        $cntNombres = count($nombres);

        for ($i = 0; $i < $cntNombres; $i++) {
            $color = random_hex_color();
            array_push($colores, $color);
        }

        $grafica->colores = $colores;
    }

    print json_encode([ 'general' => $letra, 'empresa' => $empresas, 'grafica' => $grafica ]);
});


function random_hex_color () {
    $r = rand (0, 255);
    $g = rand (0, 255);
    $b = rand (0, 255);
    return sprintf ('#%02x%02x%02x', $r, $g, $b);
}

$app->run();